<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StudentOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardStudentsController extends Controller
{
    public function __construct(private StudentOfferService $offers)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        $page = max(1, $request->integer('page', 1));
        $perPage = min(50, max(1, $request->integer('per_page', 20)));

        $query = DB::table('tbl_offer_prices as offer')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'offer.offpri_learner_id')
            ->where('offer.offpri_teacher_id', $user->user_id)
            ->whereNull('learner.user_deleted')
            ->select([
                'learner.user_id as id',
                'learner.user_first_name as first_name',
                'learner.user_last_name as last_name',
                'learner.user_deleted as learner_deleted',
                DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, "")) as full_name'),
                'offer.offpri_id',
                'offer.offpri_lessons as lessons_offered',
                'offer.offpri_classes as classes_offered',
                'offer.offpri_lesson_price as lesson_price_json',
                'offer.offpri_class_price as class_price_json',
                'offer.offpri_package_price as package_price',
            ]);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('learner.user_first_name', 'like', "%{$keyword}%")
                    ->orWhere('learner.user_last_name', 'like', "%{$keyword}%")
                    ->orWhere('learner.user_email', 'like', "%{$keyword}%");
            });
        }

        $total = (clone $query)->count();
        $students = $query
            ->orderBy('learner.user_first_name')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'offpri_id' => (int) $row->offpri_id,
                'first_name' => (string) $row->first_name,
                'last_name' => (string) ($row->last_name ?? ''),
                'full_name' => (string) $row->full_name,
                'lessons_offered' => (int) ($row->lessons_offered ?? 0),
                'classes_offered' => (int) ($row->classes_offered ?? 0),
                'lesson_price_json' => (string) ($row->lesson_price_json ?? ''),
                'class_price_json' => (string) ($row->class_price_json ?? ''),
                'package_price' => $row->package_price !== null ? (float) $row->package_price : null,
                'learner_deleted' => (bool) $row->learner_deleted,
            ]);

        return response()->json([
            'data' => $students,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ]);
    }

    public function offerForm(Request $request, int $learnerId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $form = $this->offers->getOfferForm((int) $user->user_id, $learnerId);
        if (! $form) {
            return response()->json(['message' => 'Invalid request'], 404);
        }

        return response()->json(['data' => $form]);
    }

    public function updateOffer(Request $request, int $learnerId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'lesson_prices' => ['nullable', 'array'],
            'class_prices' => ['nullable', 'array'],
            'package_price' => ['nullable', 'numeric'],
        ]);

        if (! $this->offers->saveOffer((int) $user->user_id, $learnerId, $request->all())) {
            return response()->json(['message' => 'Invalid request'], 404);
        }

        return response()->json(['message' => 'Price locked successfully']);
    }
}
