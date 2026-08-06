<?php

class ExportReportedIssues extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::REPORTED_ISSUES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'repiss_record_type' => Label::getLabel('LBL_TYPE'),
            'repiss_record_id' => GroupClass::isEnabled() ? Label::getLabel('LBL_CLASS/LESSON_ID') : Label::getLabel('LBL_LESSON_ID'),
            'order_id' => Label::getLabel('LBL_ORDER_ID'),
            'repiss_title' => Label::getLabel('LBL_Issue'),
            'repiss_reported_by' => Label::getLabel('LBL_Reported_By'),
            'repiss_reported_on' => Label::getLabel('LBL_Reported_On'),
            'repiss_status' => Label::getLabel('LBL_Status'),
        ];
        return [
            'repiss_record_type', 'repiss_record_id', 'ordcls_order_id',
            'ordles_order_id', 'repiss_title', 'learner.user_first_name',
            'learner.user_last_name', 'repiss_reported_on', 'repiss_status'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $orderId = ($row['repiss_record_type'] == AppConstant::GCLASS) ? $row['ordcls_order_id'] : $row['ordles_order_id'];
            fputcsv($fh, [
                'repiss_record_type' => AppConstant::getClassTypes($row['repiss_record_type']),
                'repiss_record_id' => $row['repiss_record_id'],
                'order_id' => Order::formatOrderId(FatUtility::int($orderId)),
                'repiss_title' => $row['repiss_title'],
                'repiss_reported_by' => $row['user_first_name'] . ' ' . $row['user_last_name'],
                'repiss_reported_on' => MyDate::formatDate($row['repiss_reported_on']),
                'repiss_status' => Issue::getStatusArr($row['repiss_status']),
            ]);
            $count++;
        }
        return $count;
    }

}
