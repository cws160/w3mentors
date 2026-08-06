<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminThemesListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int|mixed>} */
    public function search(Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $query = DB::table('tbl_themes')
            ->orderByDesc('theme_created')
            ->orderByDesc('theme_id')
            ->select([
                'theme_id as id',
                'theme_title',
                'theme_primary_color',
                'theme_primary_inverse_color',
                'theme_secondary_color',
                'theme_secondary_inverse_color',
                'theme_footer_color',
                'theme_footer_inverse_color',
                'theme_is_default',
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where('theme_title', 'like', "%{$keyword}%");
        }

        $total = (clone $query)->count('theme_id');
        $activeThemeId = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ACTIVE_THEME')
            ->value('conf_val');

        $rows = $query->forPage($page, $perPage)->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'theme_title' => (string) ($row->theme_title ?? ''),
            'theme_primary_color' => (string) ($row->theme_primary_color ?? ''),
            'theme_primary_inverse_color' => (string) ($row->theme_primary_inverse_color ?? ''),
            'theme_secondary_color' => (string) ($row->theme_secondary_color ?? ''),
            'theme_secondary_inverse_color' => (string) ($row->theme_secondary_inverse_color ?? ''),
            'theme_footer_color' => (string) ($row->theme_footer_color ?? ''),
            'theme_footer_inverse_color' => (string) ($row->theme_footer_inverse_color ?? ''),
            'theme_is_default' => (int) ($row->theme_is_default ?? 0),
            'is_active' => (int) $row->id === $activeThemeId,
        ])->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'active_theme_id' => $activeThemeId,
            ],
        ];
    }
}
