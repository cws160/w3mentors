<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $categories = DB::table('tbl_faq_categories as fc')
            ->join('tbl_faq_categories_lang as fcl', function ($join) use ($langId) {
                $join->on('fc.faqcat_id', '=', 'fcl.faqcatlang_faqcat_id')
                    ->where('fcl.faqcatlang_lang_id', '=', $langId);
            })
            ->where('fc.faqcat_active', 1)
            ->where('fc.faqcat_deleted', 0)
            ->orderBy('fc.faqcat_order')
            ->get(['fc.faqcat_id as id', 'fcl.faqcat_name as name']);

        $faqs = DB::table('tbl_faq as f')
            ->join('tbl_faq_lang as fl', function ($join) use ($langId) {
                $join->on('f.faq_id', '=', 'fl.faqlang_faq_id')
                    ->where('fl.faqlang_lang_id', '=', $langId);
            })
            ->where('f.faq_active', 1)
            ->orderBy('f.faq_id')
            ->get([
                'f.faq_id as id',
                'f.faq_category as category_id',
                'fl.faq_title as title',
                'fl.faq_description as description',
            ]);

        $grouped = [];
        foreach ($faqs as $faq) {
            $catId = (int) $faq->category_id;
            $grouped[$catId][] = [
                'id' => $faq->id,
                'title' => $faq->title,
                'description' => $faq->description,
            ];
        }

        return response()->json([
            'categories' => $categories,
            'faqs_by_category' => $grouped,
        ]);
    }
}
