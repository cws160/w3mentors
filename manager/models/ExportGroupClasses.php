<?php

class ExportGroupClasses extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::GROUP_CLASSES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'grpcls_title' => Label::getLabel('LBL_Class_Title'),
            'grpcls_type' => Label::getLabel('LBL_Type'),
            'grpcls_offline' => Label::getLabel('LBL_SERVICE_TYPE'),
            'teacher_name' => Label::getLabel('LBL_Teacher'),
            'grpcls_entry_fee' => Label::getLabel('LBL_Entry_Fee') . '[' . $currencySymbol . ']',
            'grpcls_start_datetime' => Label::getLabel('LBL_START_TIME'),
            'grpcls_end_datetime' => Label::getLabel('LBL_END_TIME'),
            'grpcls_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) as teacher_name',
            'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title',
            'grpcls_type', 'grpcls_total_seats', 'grpcls_entry_fee', 'grpcls_parent',
            'grpcls_start_datetime', 'grpcls_end_datetime', 'grpcls_added_on', 'grpcls_status', 'grpcls.grpcls_offline',
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $types = GroupClass::getClassTypes();
            $type = $types[$row['grpcls_type']];
            if ($row['grpcls_parent'] > 0) {
                $type = $types[GroupClass::TYPE_PACKAGE];
            }
            $clsType = ($row['grpcls_offline'] == AppConstant::YES) ? Label::getLabel('LBL_OFFLINE') : Label::getLabel('LBL_ONLINE');
            fputcsv($fh, [
                'grpcls_title' => $row['grpcls_title'],
                'grpcls_type' => $type,
                'grpcls_offline' => $clsType,
                'teacher_name' => $row['teacher_name'],
                'grpcls_entry_fee' => MyUtility::formatMoney($row['grpcls_entry_fee'], false),
                'grpcls_start_datetime' => MyDate::formatDate($row['grpcls_start_datetime']),
                'grpcls_end_datetime' => MyDate::formatDate($row['grpcls_end_datetime']),
                'grpcls_status' => GroupClass::getStatuses($row['grpcls_status']),
            ]);
            $count++;
        }
        return $count;
    }
}
