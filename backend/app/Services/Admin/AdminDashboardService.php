<?php

namespace App\Services\Admin;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function __construct(private AdminDashboardStatsCalculator $calculator)
    {
    }

    /** @return array<string, float|int> */
    public function stats(): array
    {
        $features = $this->featureFlags();
        $raw = DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ADMIN_DASHBOARD_STATS')
            ->value('conf_val');

        $stats = json_decode((string) $raw, true);
        if (! is_array($stats) || $stats === []) {
            return $this->calculator->calculate(
                $features['courses_enabled'],
                $features['group_classes_enabled'],
                $features['affiliate_enabled']
            );
        }

        return $stats;
    }

    public function refreshCachedStats(): bool
    {
        $features = $this->featureFlags();
        $stats = $this->calculator->calculate(
            $features['courses_enabled'],
            $features['group_classes_enabled'],
            $features['affiliate_enabled'],
        );

        DB::table('tbl_configurations')->updateOrInsert(
            ['conf_name' => 'CONF_ADMIN_DASHBOARD_STATS'],
            ['conf_val' => json_encode($stats)],
        );

        return true;
    }

    public function featureFlags(): array
    {
        $configs = DB::table('tbl_configurations')
            ->whereIn('conf_name', [
                'CONF_ENABLE_COURSES',
                'CONF_GROUP_CLASSES_DISABLED',
                'CONF_ENABLE_SUBSCRIPTION_PLAN',
                'CONF_ENABLE_AFFILIATE_MODULE',
            ])
            ->pluck('conf_val', 'conf_name');

        return [
            'courses_enabled' => (int) ($configs['CONF_ENABLE_COURSES'] ?? 1) === 1,
            'group_classes_enabled' => (int) ($configs['CONF_GROUP_CLASSES_DISABLED'] ?? 0) === 1,
            'subscription_plan_enabled' => (int) ($configs['CONF_ENABLE_SUBSCRIPTION_PLAN'] ?? 0) === 1,
            'affiliate_enabled' => (int) ($configs['CONF_ENABLE_AFFILIATE_MODULE'] ?? 0) === 1,
        ];
    }

    /** @return array<string, array<string, float|int>> */
    public function chartData(): array
    {
        $features = $this->featureFlags();

        return $this->calculator->chartData(
            $features['courses_enabled'],
            $features['group_classes_enabled']
        );
    }

    /** @return array<int, array{language: string, totalsold: int}> */
    public function topLessonLanguages(int $langId, int $interval): array
    {
        return $this->calculator->topLessonLanguages($langId, $interval);
    }

    /** @return array<int, array{language: string, totalsold: int}> */
    public function topClassLanguages(int $langId, int $interval): array
    {
        return $this->calculator->topClassLanguages($langId, $interval);
    }

    /** @return array<int, array{category: string, totalsold: int}> */
    public function topCourseCategories(int $langId, int $interval): array
    {
        return $this->calculator->topCourseCategories($langId, $interval);
    }

    public function reportGeneratedLabel(int $adminId): ?string
    {
        $date = DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_SALES_REPORT_GENERATED_DATE')
            ->value('conf_val');

        if (! $date) {
            return null;
        }

        $timezone = DB::table('tbl_admin')
            ->where('admin_id', $adminId)
            ->value('admin_timezone');

        $formatted = Carbon::parse((string) $date)->format('M d, Y h:i A');

        return $timezone ? "{$formatted} ({$timezone})" : $formatted;
    }

    /** @return array<string, mixed> */
    public function pageText(int $langId = 1): array
    {
        $row = DB::table('tbl_pages_language_data')
            ->where('plang_key', 'home')
            ->where('plang_lang_id', $langId)
            ->first();

        if (! $row) {
            return [];
        }

        return [
            'plang_id' => (int) $row->plang_id,
            'title' => (string) ($row->plang_title ?? ''),
            'summary' => (string) ($row->plang_summary ?? ''),
            'warning' => (string) ($row->plang_warring_msg ?? ''),
            'recommendations' => (string) ($row->plang_recommendations ?? ''),
        ];
    }
}
