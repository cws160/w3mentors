<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMeetingToolsListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $query = DB::table('tbl_meeting_tools')
            ->orderByDesc('metool_status')
            ->orderBy('metool_code')
            ->select([
                'metool_id as id',
                'metool_code as code',
                'metool_info as info',
                'metool_status as status',
            ]);

        $keyword = trim((string) ($request->query('keyword', $request->query('metool_code', ''))));
        if ($keyword !== '') {
            $query->where('metool_code', 'like', "%{$keyword}%");
        }

        $status = $request->query('metool_status', $request->query('status', ''));
        if ($status !== '' && $status !== null) {
            $query->where('metool_status', '=', (int) $status);
        }

        $total = (clone $query)->count('metool_id');

        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) {
                $status = (int) ($row->status ?? 0);

                return [
                    'id' => (int) $row->id,
                    'code' => (string) ($row->code ?? ''),
                    'info' => (string) ($row->info ?? ''),
                    'status' => $status,
                    'active' => $status,
                    'can_toggle_status' => $status !== 1,
                ];
            })
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }
}
