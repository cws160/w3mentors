<?php

class ExportTeacherPayoutsReport extends Export
{
    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::TEACHER_PAYOUTS_REPORT;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $headers = [
            'user_id' => Label::getLabel('LBL_USER_ID'),
            'user_name' => Label::getLabel('LBL_TEACHER_NAME'),
            'total_amount' => Label::getLabel('LBL_AMOUNT_PAID') . '[' . $currencySymbol . ']',
        ];
        $fields = [
            'user_id', 'CONCAT(user.user_first_name, " ", user.user_last_name) as user_name', 
            'SUM(usrtxn.usrtxn_amount) as total_amount',
        ];
        $this->headers = $headers;
        return $fields;
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $row['total_amount'] = MyUtility::formatMoney($row['total_amount'], false);
            fputcsv($fh, $row);
            $count++;
        }
        return $count;
    }
}