<?php

class ExportPackageClasses extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::PACKAGE_CLASSES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'grpcls_title' => Label::getLabel('LBL_PACKAGE'),
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'grpcls_start_datetime' => Label::getLabel('LBL_START_TIME'),
            'grpcls_end_datetime' => Label::getLabel('LBL_END_TIME'),
            'grpcls_added_on' => Label::getLabel('LBL_CREATED'),
            'grpcls_offline' => Label::getLabel('LBL_SERVICE_TYPE'),
            'grpcls_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) as teacher_name',
            'grpcls.grpcls_start_datetime', 'grpcls.grpcls_end_datetime',
            'grpcls.grpcls_added_on', 'grpcls.grpcls_status', 'grpcls.grpcls_offline',
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $clsType = ($row['grpcls_offline'] == AppConstant::YES) ? Label::getLabel('LBL_OFFLINE') : Label::getLabel('LBL_ONLINE');
            fputcsv($fh, [
                'grpcls_title' => $row['grpcls_title'],
                'teacher_name' => $row['teacher_name'],
                'grpcls_start_datetime' => MyDate::formatDate($row['grpcls_start_datetime']),
                'grpcls_end_datetime' => MyDate::formatDate($row['grpcls_end_datetime']),
                'grpcls_added_on' => MyDate::formatDate($row['grpcls_added_on']),
                'grpcls_offline' => $clsType,
                'grpcls_status' => GroupClass::getStatuses($row['grpcls_status'])
            ]);
            $count++;
        }
        return $count;
    }

}
