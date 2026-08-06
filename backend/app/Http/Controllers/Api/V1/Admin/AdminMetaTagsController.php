<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminMetaTagManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMetaTagsController extends Controller
{
    public function __construct(
        private AdminMetaTagManageService $metaTags,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_META_TAGS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $metaId = max(0, $request->integer('meta_id', $request->integer('metaId', 0)));
        $metaType = $request->integer('meta_type', $request->integer('metaType', AdminMetaTagManageService::META_GROUP_DEFAULT));
        $recordId = (string) $request->query('record_id', $request->query('recordId', ''));

        try {
            $data = $this->metaTags->form($metaId, $metaType, $recordId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($data === null) {
            return response()->json(['message' => 'Meta tag not found'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function langForm(Request $request, int $metaId, int $langId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_META_TAGS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $this->metaTags->langForm($metaId, $langId);
        if ($data === null) {
            return response()->json(['message' => 'Meta tag not found'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function setup(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_META_TAGS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $metaId = max(0, $request->integer('meta_id', 0));

        try {
            $result = $this->metaTags->setup($metaId, $request->only([
                'meta_type',
                'meta_record_id',
                'meta_identifier',
                'meta_slug',
            ]));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Setup successful',
            'data' => $result,
        ]);
    }

    public function langSetup(Request $request, int $metaId, int $langId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_META_TAGS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $result = $this->metaTags->langSetup($metaId, $langId, $request->only([
                'meta_title',
                'meta_keywords',
                'meta_description',
                'meta_other_meta_tags',
                'meta_og_title',
                'meta_og_url',
                'meta_og_description',
                'update_langs_data',
            ]));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Setup successful',
            'data' => $result,
        ]);
    }

    public function destroy(Request $request, int $metaId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_META_TAGS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $this->metaTags->delete($metaId)) {
            return response()->json(['message' => 'Meta tag not found'], 404);
        }

        return response()->json(['message' => 'Record deleted successfully']);
    }
}
