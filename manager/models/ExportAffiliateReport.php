<?php

class ExportAffiliateReport extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::AFFILIATE_REPORT;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $headers = [
            'affiliate_name' => Label::getLabel('LBL_AFFILIATE'),
            'afstat_referees' => Label::getLabel('LBL_REFEREE_COUNT'),
            'afstat_referee_sessions' => Label::getLabel('LBL_SESSIONS_COUNT'),
            'afstat_signup_revenue' => Label::getLabel('LBL_SIGN-UP_REVENUE') . '[' . $currencySymbol . ']',
            'afstat_order_revenue' => Label::getLabel('LBL_SESSION_REVENUE') . '[' . $currencySymbol . ']',
            'total_revenue' => Label::getLabel('LBL_TOTAL_REVENUE') . '[' . $currencySymbol . ']',
        ];
        $fields = [
            'CONCAT(user_first_name, " ", user_last_name) as affiliate_name',
            'afstat_referees', 'afstat_referee_sessions', 'afstat_signup_revenue', 'afstat_order_revenue', '(afstat_signup_revenue + afstat_order_revenue) as total_revenue'
        ];
        $this->headers = $headers;
        return $fields;
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'affiliate_name' => $row['affiliate_name'],
                'afstat_referees' => $row['afstat_referees'],
                'afstat_referee_sessions' => $row['afstat_referee_sessions'],
                'afstat_signup_revenue' => MyUtility::formatMoney($row['afstat_signup_revenue'], false),
                'afstat_order_revenue' => MyUtility::formatMoney($row['afstat_order_revenue'], false),
                'total_revenue' => MyUtility::formatMoney($row['total_revenue'], false),
            ]);
            $count++;
        }
        return $count;
    }
}
