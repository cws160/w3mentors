<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPageLangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPageTextController extends Controller
{
    public function __construct(private AdminPageLangService $pageLang)
    {
    }

    public function show(Request $request, string $pageKey): JsonResponse
    {
        $langId = max(1, $request->integer('lang_id', 1));
        $data = $this->pageLang->getByKey($pageKey, $langId);

        return response()->json(['data' => $data]);
    }
}
