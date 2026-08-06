<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardTeachersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $learnerId = (int) $request->user()->user_id;
        $keyword = trim((string) $request->query('keyword', ''));
        $page = max(1, $request->integer('page', 1));
        $perPage = min(50, max(1, $request->integer('per_page', 20)));

        $query = DB::table('tbl_order_lessons as ordles')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->where('orders.order_user_id', $learnerId)
            ->whereNull('teacher.user_deleted')
            ->where('teacher.user_is_teacher', 1)
            ->select([
                'teacher.user_id as id',
                'teacher.user_first_name as first_name',
                'teacher.user_last_name as last_name',
                'teacher.user_username as username',
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as full_name'),
                DB::raw('COUNT(DISTINCT ordles.ordles_id) as lessons_count'),
            ])
            ->groupBy(
                'teacher.user_id',
                'teacher.user_first_name',
                'teacher.user_last_name',
                'teacher.user_username'
            );

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('teacher.user_first_name', 'like', "%{$keyword}%")
                    ->orWhere('teacher.user_last_name', 'like', "%{$keyword}%")
                    ->orWhere('teacher.user_username', 'like', "%{$keyword}%");
            });
        }

        $total = (int) DB::table('tbl_order_lessons as ordles')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->where('orders.order_user_id', $learnerId)
            ->whereNull('teacher.user_deleted')
            ->where('teacher.user_is_teacher', 1)
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($inner) use ($keyword) {
                    $inner->where('teacher.user_first_name', 'like', "%{$keyword}%")
                        ->orWhere('teacher.user_last_name', 'like', "%{$keyword}%")
                        ->orWhere('teacher.user_username', 'like', "%{$keyword}%");
                });
            })
            ->distinct('ordles.ordles_teacher_id')
            ->count('ordles.ordles_teacher_id');

        $teachers = $query
            ->orderBy('teacher.user_first_name')
            ->forPage($page, $perPage)
            ->get();

        return response()->json([
            'data' => $teachers,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ]);
    }
}
