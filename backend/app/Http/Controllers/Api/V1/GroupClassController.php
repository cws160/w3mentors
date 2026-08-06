<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);
        $now = now()->format('Y-m-d H:i:s');

        $query = DB::table('tbl_group_classes as gc')
            ->leftJoin('tbl_group_classes_lang as gcl', function ($join) use ($langId) {
                $join->on('gc.grpcls_id', '=', 'gcl.gclang_grpcls_id')
                    ->where('gcl.gclang_lang_id', '=', $langId);
            })
            ->join('tbl_users as u', 'gc.grpcls_teacher_id', '=', 'u.user_id')
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'u.user_id')
            ->where('gc.grpcls_status', 1)
            ->where('gc.grpcls_start_datetime', '>=', $now)
            ->where('gc.grpcls_parent', 0)
            ->select([
                'gc.grpcls_id as id',
                'gc.grpcls_slug as slug',
                DB::raw('COALESCE(gcl.grpcls_title, gc.grpcls_title) as title'),
                'gc.grpcls_start_datetime as start_at',
                'gc.grpcls_duration as duration',
                'gc.grpcls_total_seats as total_seats',
                'gc.grpcls_booked_seats as booked_seats',
                'gc.grpcls_entry_fee as entry_fee',
                'gc.grpcls_offline as offline',
                'gc.grpcls_type as type',
                'gc.grpcls_teacher_id as teacher_id',
                'u.user_username as teacher_username',
                DB::raw("CONCAT(u.user_first_name, ' ', u.user_last_name) as teacher_name"),
                'ts.testat_ratings as teacher_ratings',
                'ts.testat_reviewes as teacher_reviews',
            ])
            ->orderBy('gc.grpcls_start_datetime');

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('gc.grpcls_title', 'like', "%{$search}%")
                    ->orWhere('gcl.grpcls_title', 'like', "%{$search}%");
            });
        }

        $classes = $query->paginate($request->integer('per_page', 12));

        return response()->json([
            'data' => $classes->items(),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $row = DB::table('tbl_group_classes as gc')
            ->leftJoin('tbl_group_classes_lang as gcl', function ($join) use ($langId) {
                $join->on('gc.grpcls_id', '=', 'gcl.gclang_grpcls_id')
                    ->where('gcl.gclang_lang_id', '=', $langId);
            })
            ->join('tbl_users as u', 'gc.grpcls_teacher_id', '=', 'u.user_id')
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'u.user_id')
            ->where('gc.grpcls_slug', $slug)
            ->where('gc.grpcls_status', 1)
            ->first([
                'gc.grpcls_id as id',
                'gc.grpcls_slug as slug',
                DB::raw('COALESCE(gcl.grpcls_title, gc.grpcls_title) as title'),
                DB::raw('COALESCE(gcl.grpcls_description, gc.grpcls_description) as description'),
                'gc.grpcls_start_datetime as start_at',
                'gc.grpcls_duration as duration',
                'gc.grpcls_total_seats as total_seats',
                'gc.grpcls_booked_seats as booked_seats',
                'gc.grpcls_entry_fee as entry_fee',
                'gc.grpcls_teacher_id as teacher_id',
                'u.user_username as teacher_username',
                DB::raw("CONCAT(u.user_first_name, ' ', u.user_last_name) as teacher_name"),
                'ts.testat_ratings as teacher_ratings',
                'ts.testat_reviewes as teacher_reviews',
            ]);

        if (!$row) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        return response()->json(['data' => $row]);
    }
}
