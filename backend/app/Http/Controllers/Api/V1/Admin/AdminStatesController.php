<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStatesController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function searchForm(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_STATES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = max(1, (int) $request->query('lang_id', $this->defaultLangId()));

        return response()->json(['data' => [
            'countries' => $this->countryOptions($langId),
        ]]);
    }

    public function show(Request $request, int $stateId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $stateId) {
            $langId = max(1, (int) $request->query('lang_id', $this->defaultLangId()));
            $state = $stateId > 0 ? DB::table('tbl_states')->where('state_id', $stateId)->first() : null;
            if ($stateId > 0 && ! $state) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'state_id' => (int) ($state->state_id ?? 0),
                'state_identifier' => (string) ($state->state_identifier ?? ''),
                'state_code' => (string) ($state->state_code ?? ''),
                'state_country_id' => (int) ($state->state_country_id ?? 0),
                'state_active' => (int) ($state->state_active ?? 1),
                'site_languages' => $this->siteLanguages(),
                'countries' => $this->countryOptions($langId),
            ]]);
        });
    }

    public function update(Request $request, int $stateId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $stateId) {
            $identifier = trim((string) $request->input('state_identifier', ''));
            $code = trim((string) $request->input('state_code', ''));
            $countryId = (int) $request->input('state_country_id', 0);
            $active = (int) $request->input('state_active', 1) === 1 ? 1 : 0;

            if ($identifier === '' || $countryId < 1) {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            $country = DB::table('tbl_countries')->where('country_id', $countryId)->first(['country_active']);
            if (! $country) {
                return response()->json(['message' => 'Invalid request'], 422);
            }
            if ((int) $country->country_active !== 1) {
                return response()->json(['message' => 'Country is inactive'], 422);
            }

            if ($code !== '' && $this->duplicateStateCode($countryId, $code, $stateId)) {
                return response()->json(['message' => 'State code is already in use'], 422);
            }
            if ($this->duplicateStateIdentifier($countryId, $identifier, $stateId)) {
                return response()->json(['message' => 'State identifier is already in use'], 422);
            }
            if ($active === 0 && $stateId > 0 && ! $this->canInactive($stateId)) {
                return response()->json(['message' => 'State attached with the address can not be marked as inactive'], 422);
            }

            $data = [
                'state_country_id' => $countryId,
                'state_identifier' => $identifier,
                'state_code' => $code,
                'state_active' => $active,
                'state_updated' => now(),
            ];

            if ($stateId > 0) {
                DB::table('tbl_states')->where('state_id', $stateId)->update($data);
            } else {
                $data['state_created'] = now();
                $stateId = (int) DB::table('tbl_states')->insertGetId($data);
            }

            return response()->json([
                'message' => 'State setup successful',
                'data' => [
                    'state_id' => $stateId,
                    'next_lang_id' => $this->nextMissingLangId($stateId),
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $stateId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($stateId, $langId) {
            $state = DB::table('tbl_states')->where('state_id', $stateId)->first(['state_id', 'state_identifier', 'state_active']);
            if (! $state) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $lang = DB::table('tbl_states_lang')
                ->where('stlang_state_id', $stateId)
                ->where('stlang_lang_id', $langId)
                ->first(['state_name']);
            $languages = $this->siteLanguages();
            $defaultLang = $this->defaultLangId();

            return response()->json(['data' => [
                'state_id' => (int) $state->state_id,
                'state_identifier' => (string) $state->state_identifier,
                'state_active' => (int) $state->state_active,
                'lang_id' => $langId,
                'state_name' => (string) ($lang->state_name ?? ''),
                'site_languages' => $languages,
                'default_lang_id' => $defaultLang,
                'show_auto_translate' => count($languages) > 1 && $langId === $defaultLang,
                'layout_direction' => $this->layoutDirection($langId),
            ]]);
        });
    }

    public function storeLang(Request $request, int $stateId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $stateId, $langId) {
            $state = DB::table('tbl_states')->where('state_id', $stateId)->first(['state_country_id']);
            if (! $state) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $name = trim((string) $request->input('state_name', ''));
            if ($name === '') {
                return response()->json(['message' => 'State name is required.'], 422);
            }

            if ($this->duplicateStateName((int) $state->state_country_id, $langId, $name, $stateId)) {
                return response()->json(['message' => 'State name is already in use'], 422);
            }

            DB::table('tbl_states_lang')->updateOrInsert(
                ['stlang_state_id' => $stateId, 'stlang_lang_id' => $langId],
                [
                    'stlang_state_id' => $stateId,
                    'stlang_lang_id' => $langId,
                    'state_name' => $name,
                ],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                $this->syncOtherLanguageRows($stateId, $langId, $name);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'state_id' => $stateId,
                    'next_lang_id' => $this->nextMissingLangId($stateId),
                ],
            ]);
        });
    }

    public function updateStatus(Request $request, int $stateId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $stateId) {
            if (! DB::table('tbl_states')->where('state_id', $stateId)->exists()) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $active = $request->boolean('active') ? 1 : 0;
            if ($active === 0 && ! $this->canInactive($stateId)) {
                return response()->json(['message' => 'State attached with the address can not be marked as inactive'], 422);
            }

            DB::table('tbl_states')->where('state_id', $stateId)->update([
                'state_active' => $active,
                'state_updated' => now(),
            ]);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    private function duplicateStateCode(int $countryId, string $code, int $stateId): bool
    {
        return DB::table('tbl_states')
            ->where('state_country_id', $countryId)
            ->whereRaw('LOWER(state_code) = ?', [strtolower($code)])
            ->where('state_id', '!=', $stateId)
            ->exists();
    }

    private function duplicateStateIdentifier(int $countryId, string $identifier, int $stateId): bool
    {
        return DB::table('tbl_states')
            ->where('state_country_id', $countryId)
            ->whereRaw('LOWER(state_identifier) = ?', [strtolower($identifier)])
            ->where('state_id', '!=', $stateId)
            ->exists();
    }

    private function duplicateStateName(int $countryId, int $langId, string $name, int $stateId): bool
    {
        return DB::table('tbl_states as s')
            ->join('tbl_states_lang as sl', function ($join) use ($langId) {
                $join->on('sl.stlang_state_id', '=', 's.state_id')
                    ->where('sl.stlang_lang_id', '=', $langId);
            })
            ->where('s.state_country_id', $countryId)
            ->where('sl.state_name', $name)
            ->where('s.state_id', '!=', $stateId)
            ->exists();
    }

    private function canInactive(int $stateId): bool
    {
        return ! DB::table('tbl_user_addresses')
            ->where('usradd_state_id', $stateId)
            ->whereNull('usradd_deleted')
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

    /** @return list<array{id: int, name: string}> */
    private function countryOptions(int $langId): array
    {
        return DB::table('tbl_countries as c')
            ->leftJoin('tbl_countries_lang as cl', function ($join) use ($langId) {
                $join->on('cl.countrylang_country_id', '=', 'c.country_id')
                    ->where('cl.countrylang_lang_id', '=', $langId);
            })
            ->where('c.country_active', 1)
            ->orderByRaw('IFNULL(cl.country_name, c.country_identifier) ASC')
            ->get([
                'c.country_id as id',
                DB::raw('IFNULL(cl.country_name, c.country_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function nextMissingLangId(int $stateId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_states_lang')
                ->where('stlang_state_id', $stateId)
                ->where('stlang_lang_id', $language['id'])
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

    private function syncOtherLanguageRows(int $stateId, int $sourceLangId, string $name): void
    {
        foreach ($this->siteLanguages() as $language) {
            if ($language['id'] === $sourceLangId) {
                continue;
            }

            DB::table('tbl_states_lang')->updateOrInsert(
                ['stlang_state_id' => $stateId, 'stlang_lang_id' => $language['id']],
                [
                    'stlang_state_id' => $stateId,
                    'stlang_lang_id' => $language['id'],
                    'state_name' => $name,
                ],
            );
        }
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_STATES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
