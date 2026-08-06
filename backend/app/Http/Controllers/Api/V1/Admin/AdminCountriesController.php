<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCountriesController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function show(Request $request, int $countryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($countryId) {
            $country = DB::table('tbl_countries')->where('country_id', $countryId)->first();
            if (! $country) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'country_id' => (int) $country->country_id,
                'country_identifier' => (string) $country->country_identifier,
                'country_code' => (string) $country->country_code,
                'country_dial_code' => (string) $country->country_dial_code,
                'country_active' => (int) $country->country_active,
                'site_languages' => $this->siteLanguages(),
            ]]);
        });
    }

    public function update(Request $request, int $countryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $countryId) {
            if (! DB::table('tbl_countries')->where('country_id', $countryId)->exists()) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $identifier = trim((string) $request->input('country_identifier', ''));
            if ($identifier === '') {
                return response()->json(['message' => 'Identifier is required.'], 422);
            }

            $active = (int) $request->input('country_active', 1) === 1 ? 1 : 0;
            if ($active === 0 && ! $this->canInactive($countryId)) {
                return response()->json([
                    'message' => 'Country attached with other records can not be marked as inactive',
                ], 422);
            }

            DB::table('tbl_countries')->where('country_id', $countryId)->update([
                'country_identifier' => $identifier,
                'country_active' => $active,
            ]);

            return response()->json([
                'message' => 'Updated successfully',
                'data' => [
                    'country_id' => $countryId,
                    'next_lang_id' => $this->nextMissingLangId($countryId),
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $countryId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($countryId, $langId) {
            $country = DB::table('tbl_countries')->where('country_id', $countryId)->first(['country_id', 'country_identifier', 'country_active']);
            if (! $country) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $lang = DB::table('tbl_countries_lang')
                ->where('countrylang_country_id', $countryId)
                ->where('countrylang_lang_id', $langId)
                ->first(['country_name']);
            $languages = $this->siteLanguages();
            $defaultLang = $this->defaultLangId();

            return response()->json(['data' => [
                'country_id' => (int) $country->country_id,
                'country_identifier' => (string) $country->country_identifier,
                'country_active' => (int) $country->country_active,
                'lang_id' => $langId,
                'country_name' => (string) ($lang->country_name ?? ''),
                'site_languages' => $languages,
                'default_lang_id' => $defaultLang,
                'show_auto_translate' => count($languages) > 1 && $langId === $defaultLang,
                'layout_direction' => $this->layoutDirection($langId),
            ]]);
        });
    }

    public function storeLang(Request $request, int $countryId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $countryId, $langId) {
            if (! DB::table('tbl_countries')->where('country_id', $countryId)->exists()) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $name = trim((string) $request->input('country_name', ''));
            if ($name === '') {
                return response()->json(['message' => 'Country name is required.'], 422);
            }

            DB::table('tbl_countries_lang')->updateOrInsert(
                ['countrylang_country_id' => $countryId, 'countrylang_lang_id' => $langId],
                [
                    'countrylang_country_id' => $countryId,
                    'countrylang_lang_id' => $langId,
                    'country_name' => $name,
                ],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                $this->syncOtherLanguageRows($countryId, $langId, $name);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'country_id' => $countryId,
                    'next_lang_id' => $this->nextMissingLangId($countryId),
                ],
            ]);
        });
    }

    public function updateStatus(Request $request, int $countryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $countryId) {
            $active = $request->boolean('active') ? 1 : 0;
            if (! DB::table('tbl_countries')->where('country_id', $countryId)->exists()) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            if ($active === 0 && ! $this->canInactive($countryId)) {
                return response()->json([
                    'message' => 'Country attached with other records can not be marked as inactive',
                ], 422);
            }

            DB::table('tbl_countries')
                ->where('country_id', $countryId)
                ->update(['country_active' => $active]);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    private function canInactive(int $countryId): bool
    {
        $belongsToAddress = DB::table('tbl_user_addresses')
            ->where('usradd_country_id', $countryId)
            ->whereNull('usradd_deleted')
            ->exists();
        if ($belongsToAddress) {
            return false;
        }

        $belongsToUser = DB::table('tbl_users')
            ->where('user_country_id', $countryId)
            ->whereNull('user_deleted')
            ->exists();
        if ($belongsToUser) {
            return false;
        }

        $siteCountry = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_COUNTRY')
            ->value('conf_val');
        if ($siteCountry === $countryId) {
            return false;
        }

        return ! DB::table('tbl_states')
            ->where('state_country_id', $countryId)
            ->where('state_active', 1)
            ->exists();
    }

    /** @return list<array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function nextMissingLangId(int $countryId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_countries_lang')
                ->where('countrylang_country_id', $countryId)
                ->where('countrylang_lang_id', $language['id'])
                ->exists();
            if (! $exists) {
                return $language['id'];
            }
        }

        return 0;
    }

    private function defaultLangId(): int
    {
        $configured = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_DEFAULT_LANG')
            ->value('conf_val');

        return $configured > 0 ? $configured : 1;
    }

    private function layoutDirection(int $langId): string
    {
        $code = strtolower((string) DB::table('tbl_languages')->where('language_id', $langId)->value('language_code'));

        return in_array($code, ['ar', 'he', 'ur'], true) ? 'rtl' : 'ltr';
    }

    private function syncOtherLanguageRows(int $countryId, int $sourceLangId, string $name): void
    {
        foreach ($this->siteLanguages() as $language) {
            if ($language['id'] === $sourceLangId) {
                continue;
            }

            DB::table('tbl_countries_lang')->updateOrInsert(
                ['countrylang_country_id' => $countryId, 'countrylang_lang_id' => $language['id']],
                [
                    'countrylang_country_id' => $countryId,
                    'countrylang_lang_id' => $language['id'],
                    'country_name' => $name,
                ],
            );
        }
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_COUNTRIES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
