<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminSitemapGenerateService;
use App\Services\Admin\AdminSitemapHtmlService;
use App\Services\Admin\AdminSitemapViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSitemapController extends Controller
{
    public function __construct(
        private AdminSitemapGenerateService $sitemapService,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function generate(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_SITE_MAPS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $this->sitemapService->generate();
        } catch (\Throwable $e) {
            Log::error('Sitemap generate failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Something went wrong'], 500);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Sitemap has been updated successfully',
            'msg' => 'Sitemap has been updated successfully',
        ]);
    }

    public function xml(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_SITE_MAPS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = app(AdminSitemapViewService::class)->xmlIndex();
        $frontUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');

        return response()->json([
            'data' => [
                'content' => $data['content'],
                'files' => $data['files'],
                'public_url' => $frontUrl.'/sitemap.xml',
            ],
        ]);
    }

    public function html(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_SITE_MAPS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = max(1, $request->integer('lang_id', $request->integer('langId', 1)));

        return response()->json([
            'data' => app(AdminSitemapHtmlService::class)->sections($langId),
        ]);
    }
}
