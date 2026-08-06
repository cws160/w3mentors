<?php

class ExportSalesReport extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::SALES_REPORT;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'slstat_date' => Label::getLabel('LBL_DATE'),
            'slstat_total_sales' => Label::getLabel('LBL_GROSS_SALES') . '[' . $currencySymbol . ']',
            'slstat_discount' => Label::getLabel('LBL_DISCOUNT') . '[' . $currencySymbol . ']',
            'slstat_credit_discount' => Label::getLabel('LBL_REWARDS') . '[' . $currencySymbol . ']',
            'slstat_net_sales' => Label::getLabel('LBL_NET_SALES') . '[' . $currencySymbol . ']',
        ];
        return [
            'slstat_date',
            '(IFNULL(slstat_les_sales,0) + IFNULL(slstat_cls_sales,0) + IFNULL(slstat_crs_sales,0) + IFNULL(slstat_les_discount,0) '
                . ' + IFNULL(slstat_cls_discount,0) + IFNULL(slstat_les_credit_discount,0) + IFNULL(slstat_cls_credit_discount,0) + IFNULL(slstat_crs_discount,0) + IFNULL(slstat_crs_credit_discount,0)) AS slstat_total_sales',
            '(IFNULL(slstat_les_discount,0) + IFNULL(slstat_cls_discount,0) + IFNULL(slstat_crs_discount,0)) AS slstat_discount',
            '(IFNULL(slstat_les_credit_discount,0) + IFNULL(slstat_cls_credit_discount,0) + IFNULL(slstat_crs_credit_discount,0)) AS slstat_credit_discount',
            '(IFNULL(slstat_les_sales,0) + IFNULL(slstat_cls_sales,0) + IFNULL(slstat_crs_sales,0)) AS slstat_net_sales',
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'slstat_date' => MyDate::formatDate($row['slstat_date'], 'Y-m-d'),
                'slstat_total_sales' => MyUtility::formatMoney($row['slstat_total_sales'], false),
                'slstat_discount' => MyUtility::formatMoney($row['slstat_discount'], false),
                'slstat_credit_discount' => MyUtility::formatMoney($row['slstat_credit_discount'], false),
                'slstat_net_sales' => MyUtility::formatMoney($row['slstat_net_sales'], false),
            ]);
            $count++;
        }
        return $count;
    }
}
