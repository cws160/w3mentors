<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAbusiveWordsController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function show(Request $request, int $wordId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($wordId) {
            $row = DB::table('tbl_abusive_words')->where('abusive_id', $wordId)->first([
                'abusive_id',
                'abusive_keyword',
                'abusive_lang_id',
            ]);
            if (! $row) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'abusive_id' => (int) $row->abusive_id,
                'abusive_keyword' => (string) $row->abusive_keyword,
                'abusive_lang_id' => (int) ($row->abusive_lang_id ?? 0),
            ]]);
        });
    }

    public function store(Request $request, int $wordId = 0): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $wordId) {
            $keyword = trim((string) $request->input('abusive_keyword', ''));
            if ($keyword === '' || preg_match('/\s/', $keyword)) {
                return response()->json(['message' => 'Field required without spaces in word'], 422);
            }

            $duplicate = DB::table('tbl_abusive_words')
                ->where('abusive_keyword', $keyword)
                ->when($wordId > 0, fn ($query) => $query->where('abusive_id', '!=', $wordId))
                ->exists();
            if ($duplicate) {
                return response()->json(['message' => 'Keyword already exists'], 422);
            }

            if ($wordId > 0) {
                $updated = DB::table('tbl_abusive_words')->where('abusive_id', $wordId)->update([
                    'abusive_keyword' => $keyword,
                ]);
                if ($updated < 1 && ! DB::table('tbl_abusive_words')->where('abusive_id', $wordId)->exists()) {
                    return response()->json(['message' => 'Record not found'], 404);
                }
            } else {
                DB::table('tbl_abusive_words')->insert([
                    'abusive_lang_id' => 0,
                    'abusive_keyword' => $keyword,
                ]);
            }

            return response()->json(['message' => 'Abusive word setup successfully']);
        });
    }

    public function destroy(Request $request, int $wordId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($wordId) {
            if ($wordId < 1) {
                return response()->json(['message' => 'Invalid request'], 422);
            }
            DB::table('tbl_abusive_words')->where('abusive_id', $wordId)->delete();

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_ABUSIVE_WORDS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
