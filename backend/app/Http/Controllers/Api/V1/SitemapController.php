<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminSitemapHtmlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function __construct(private AdminSitemapHtmlService $sitemap)
    {
    }

    public function html(Request $request): JsonResponse
    {
        $langId = max(1, $request->integer('lang_id', $request->integer('langId', 1)));

        return response()->json([
            'data' => $this->sitemap->sections($langId),
        ]);
    }
}
