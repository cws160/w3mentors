<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoContentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);
        $perPage = max(1, min(50, $request->integer('per_page', 12)));

        $paginated = DB::table('tbl_bible_content as v')
            ->leftJoin('tbl_bible_content_lang as vl', function ($join) use ($langId) {
                $join->on('v.biblecontent_id', '=', 'vl.biblecontentlang_biblecontent_id')
                    ->where('vl.biblecontentlang_lang_id', '=', $langId);
            })
            ->where('v.biblecontent_active', 1)
            ->orderBy('v.biblecontent_order')
            ->select([
                'v.biblecontent_id as id',
                'v.biblecontent_url as url',
                DB::raw(
                    'COALESCE(NULLIF(TRIM(vl.biblecontentlang_biblecontent_title), ""), v.biblecontent_title) as title'
                ),
            ])
            ->paginate($perPage);

        $data = collect($paginated->items())->map(function ($row) {
            $youtubeId = $this->youtubeVideoId((string) $row->url);

            return [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'url' => (string) $row->url,
                'youtube_id' => $youtubeId,
                'embed_url' => $youtubeId ? "https://www.youtube.com/embed/{$youtubeId}" : null,
            ];
        })->values();

        $total = $paginated->total();
        $currentPage = $paginated->currentPage();
        $perPageActual = $paginated->perPage();
        $startRecord = $total === 0 ? 0 : ($currentPage - 1) * $perPageActual + 1;
        $endRecord = min($currentPage * $perPageActual, $total);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $paginated->lastPage(),
                'per_page' => $perPageActual,
                'total' => $total,
                'start_record' => $startRecord,
                'end_record' => $endRecord,
            ],
        ]);
    }

    private function youtubeVideoId(string $url): ?string
    {
        if (! str_contains($url, 'youtube')) {
            return null;
        }

        $pattern = '%^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/(?:embed/|v/)|.*v=))([\w-]{10,12})%';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
