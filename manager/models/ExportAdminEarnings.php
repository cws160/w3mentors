<?php

class ExportAdminEarnings extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::ADMIN_EARNINGS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'admtxn_amount' => Label::getLabel('LBL_EARNING').'['.$currencySymbol.']',
            'admtxn_record_type' => Label::getLabel('LBL_EARNING_TYPE'),
            'admtxn_datetime' => Label::getLabel('LBL_DATETIME'),
            'admtxn_comment' => Label::getLabel('LBL_DESCRIPTION'),
        ];
        return ['admtxn_amount', 'admtxn_record_type', 'admtxn_comment', 'admtxn_datetime'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'admtxn_amount' => MyUtility::formatMoney($row['admtxn_amount'],false),
                'admtxn_record_type' => AdminTransaction::getTypes($row['admtxn_record_type']),
                'admtxn_datetime' => MyDate::formatDate($row['admtxn_datetime']),
                'admtxn_comment' => $row['admtxn_comment'],
            ]);
            $count++;
        }
        return $count;
    }

}
