<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminQuestionsListingService
{
    use AdminListingSupport;

    private const TYPE_SINGLE = 1;

    private const TYPE_MULTIPLE = 2;

    private const TYPE_TEXT = 3;

    private const ACTIVE = 1;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_questions as ques')
            ->join('tbl_categories as cate', 'cate.cate_id', '=', 'ques.ques_cate_id')
            ->leftJoin('tbl_categories as subcate', 'subcate.cate_id', '=', 'ques.ques_subcate_id')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'ques.ques_user_id')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'ques.ques_cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcatelang', function ($join) use ($langId) {
                $join->on('subcatelang.catelang_cate_id', '=', 'ques.ques_subcate_id')
                    ->where('subcatelang.catelang_lang_id', '=', $langId);
            })
            ->whereNull('ques.ques_deleted')
            ->whereNull('cate.cate_deleted')
            ->where('cate.cate_status', '=', self::ACTIVE)
            ->select([
                'ques.ques_id as id',
                'ques.ques_title as title',
                'ques.ques_type as type',
                'ques.ques_status as status',
                'ques.ques_created as created_at',
                DB::raw('IFNULL(catelang.cate_name, cate.cate_identifier) as category_name'),
                DB::raw('IFNULL(subcatelang.cate_name, IFNULL(subcate.cate_identifier, "")) as subcategory_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where('ques.ques_title', 'like', '%'.$keyword.'%');
        }

        $cateId = $request->integer('ques_cate_id', 0);
        if ($cateId > 0) {
            $query->where('ques.ques_cate_id', '=', $cateId);
        }

        $subCateId = $request->integer('ques_subcate_id', 0);
        if ($subCateId > 0) {
            $query->where('ques.ques_subcate_id', '=', $subCateId);
        }

        $quesType = $request->integer('ques_type', 0);
        if ($quesType > 0) {
            $query->where('ques.ques_type', '=', $quesType);
        }

        $teacher = trim((string) $request->query('teacher', ''));
        if ($teacher !== '') {
            $query->whereRaw(
                'TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) LIKE ?',
                ['%'.$teacher.'%'],
            );
        }

        $quizId = $request->integer('quiz_id', 0);
        if ($quizId > 0) {
            $query->join('tbl_quizzes_questions as quique', 'quique.quique_ques_id', '=', 'ques.ques_id')
                ->join('tbl_quizzes as quiz', function ($join) use ($quizId) {
                    $join->on('quiz.quiz_id', '=', 'quique.quique_quiz_id')
                        ->where('quiz.quiz_id', '=', $quizId)
                        ->whereNull('quiz.quiz_deleted');
                });
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->distinct('ques.ques_id')->count('ques.ques_id');

        $rows = $query
            ->orderByDesc('ques.ques_status')
            ->orderByDesc('ques.ques_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'type' => (int) $row->type,
                'type_label' => $this->typeLabel((int) $row->type),
                'category_name' => (string) $row->category_name,
                'subcategory_name' => (string) $row->subcategory_name,
                'teacher_name' => ucwords(trim((string) $row->teacher_name)),
                'status' => (int) $row->status,
                'created_at' => (string) ($row->created_at ?? ''),
            ])
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    /** @return array<int, array<string, mixed>> */
    public function exportRows(Request $request): array
    {
        $request->merge(['export' => true, 'page' => 1]);

        return $this->search($request)['data'];
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_SINGLE => 'Single choice',
            self::TYPE_MULTIPLE => 'Multiple choice',
            self::TYPE_TEXT => 'Text',
            default => 'N/A',
        };
    }
}
