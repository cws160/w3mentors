<?php

class ExportSettlements extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::SETTLEMENTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'slstat_date' => Label::getLabel('LBL_DATE'),
            'slstat_refund' => Label::getLabel('LBL_REFUND') . '[' . $currencySymbol . ']',
            'slstat_earnings' => Label::getLabel('LBL_EARNINGS') . '[' . $currencySymbol . ']',
            'slstat_teacher_paid' => Label::getLabel('LBL_TEACHER_PAID') . '[' . $currencySymbol . ']',
        ];
        return [
            'slstat_date',
            '(
                IFNULL(slstat_les_refund,0) + 
                IFNULL(slstat_cls_refund,0) + 
                IFNULL(slstat_crs_refund,0) + 
                IFNULL(slstat_subplan_refund,0)
            )  AS slstat_refund',
            '(IFNULL(slstat_les_earnings,0) + IFNULL(slstat_cls_earnings,0) + IFNULL(slstat_crs_earnings,0)) AS slstat_earnings',
            '(IFNULL(slstat_les_teacher_paid,0) + IFNULL(slstat_cls_teacher_paid,0) + IFNULL(slstat_crs_teacher_paid,0)) AS slstat_teacher_paid',
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'slstat_date' => MyDate::formatDate($row['slstat_date'], 'Y-m-d'),
                'slstat_refund' => MyUtility::formatMoney($row['slstat_refund'], false),
                'slstat_earnings' => MyUtility::formatMoney($row['slstat_earnings'], false),
                'slstat_teacher_paid' => MyUtility::formatMoney($row['slstat_teacher_paid'], false),
            ]);
            $count++;
        }
        return $count;
    }
}
