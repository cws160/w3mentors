<?php

namespace App\Services\Admin;

use App\Support\Admin\AdminDateRange;
use Illuminate\Support\Facades\DB;

class AdminGoogleAnalyticsService
{
    /** @var array<int, string> */
    private const EVENT_KEYS = [
        'first_visit',
        'book_lesson',
        'book_class',
        'book_course',
        'book_trial_lesson',
        'book_trial_course',
        'confirm_order',
        'book_subscription_plan',
    ];

    /** @return array{error: bool, message?: string, data?: array<string, int>} */
    public function eventMeasurements(int $interval, bool $coursesEnabled, bool $groupClassesEnabled): array
    {
        $range = $this->dateRange($interval);
        $data = $this->fetchMeasurements(
            $range['start'],
            $range['end'],
            ['eventName'],
            ['eventCount']
        );

        if (isset($data['error'])) {
            return $data;
        }

        $stats = [];
        foreach ($data['data'] ?? [] as $key => $value) {
            if ($key === 'page_view' || ! in_array($key, self::EVENT_KEYS, true)) {
                continue;
            }
            if (! $coursesEnabled && $key === 'book_course') {
                continue;
            }
            if (! $groupClassesEnabled && $key === 'book_class') {
                continue;
            }
            $stats[$key] = (int) $value;
        }

        return ['error' => false, 'data' => $stats];
    }

    /** @return array{error: bool, message?: string, data?: array<string, int>} */
    public function trafficAcquisitions(int $interval): array
    {
        $range = $this->dateRange($interval);
        $data = $this->fetchMeasurements(
            $range['start'],
            $range['end'],
            ['sessionDefaultChannelGroup'],
            ['totalUsers']
        );

        if (isset($data['error'])) {
            return $data;
        }

        $allowed = ['Direct', 'Referral', 'Unassigned'];
        $stats = [];
        foreach ($data['data'] ?? [] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            if (! in_array($label, $allowed, true)) {
                continue;
            }
            $stats[$key] = (int) $value;
        }

        return ['error' => false, 'data' => $stats];
    }

    /** @return array{error: bool, message?: string, data?: array<string, int|string>} */
    private function fetchMeasurements(string $startDate, string $endDate, array $dimensions, array $metrics): array
    {
        $propertyId = (string) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ANALYTICS_TABLE_ID')
            ->value('conf_val');

        if ($propertyId === '') {
            return [
                'error' => true,
                'message' => $this->configErrorMessage(),
            ];
        }

        $authConfig = json_decode(
            (string) DB::table('tbl_configurations')
                ->where('conf_name', 'CONF_GOOGLE_ANALYTICS_CLIENT_JSON')
                ->value('conf_val'),
            true
        );

        if (! is_array($authConfig) || $authConfig === []) {
            return [
                'error' => true,
                'message' => $this->configErrorMessage(),
            ];
        }

        if (! class_exists(\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient::class)) {
            return [
                'error' => true,
                'message' => 'Google Analytics SDK is not installed on the API server. Run composer require google/analytics-data in the backend project.',
            ];
        }

        try {
            $client = new \Google\Analytics\Data\V1beta\BetaAnalyticsDataClient(['credentials' => $authConfig]);
            $response = $client->runReport([
                'property' => 'properties/'.$propertyId,
                'dateRanges' => [
                    new \Google\Analytics\Data\V1beta\DateRange([
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]),
                ],
                'dimensions' => array_map(
                    fn (string $name) => new \Google\Analytics\Data\V1beta\Dimension(['name' => $name]),
                    $dimensions
                ),
                'metrics' => array_map(
                    fn (string $name) => new \Google\Analytics\Data\V1beta\Metric(['name' => $name]),
                    $metrics
                ),
            ]);

            $keys = [];
            $values = [];
            foreach ($response->getRows() as $row) {
                foreach ($row->getDimensionValues() as $dimensionValue) {
                    $keys[] = $dimensionValue->getValue();
                }
                foreach ($row->getMetricValues() as $metricValue) {
                    $values[] = $metricValue->getValue();
                }
            }

            return ['error' => false, 'data' => array_combine($keys, $values) ?: []];
        } catch (\Throwable) {
            return [
                'error' => true,
                'message' => 'Could not load Google Analytics data. Please verify API credentials.',
            ];
        }
    }

    /** @return array{start: string, end: string} */
    private function dateRange(int $interval): array
    {
        $interval = AdminDateRange::normalizeInterval($interval);
        $datetime = AdminDateRange::bounds($interval, null, false, 'Y-m-d');
        $days = $interval === AdminDateRange::TYPE_ALL ? 0 : 1;
        $endDate = date('Y-m-d', strtotime($datetime['endDate'].' -'.$days.' day'));

        return [
            'start' => date('Y-m-d', strtotime($datetime['startDate'])),
            'end' => $endDate,
        ];
    }

    private function configErrorMessage(): string
    {
        $fromPage = DB::table('tbl_pages_language_data')
            ->where('plang_key', 'home')
            ->where('plang_lang_id', 1)
            ->value('plang_warring_msg');

        if (is_string($fromPage) && $fromPage !== '') {
            return $fromPage;
        }

        return 'Configure the Google Analytics keys from the Third Party API settings section to view the website traffic and analytics data on the dashboard.';
    }
}
