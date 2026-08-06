<?php

class ExportGdprRequests extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::GDPR_REQUESTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'gdpreq_id' => Label::getLabel('LBL_REQ_ID'),
            'user_name' => Label::getLabel('LBL_USER_NAME'),
            'user_email' => Label::getLabel('LBL_USER_EMAIL'),
            'gdpreq_reason' => Label::getLabel('LBL_REASON'),
            'gdpreq_added_on' => Label::getLabel('LBL_REQUESTED_ON'),
            'gdpreq_updated_on' => Label::getLabel('LBL_UPDATED_ON'),
            'gdpreq_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'CONCAT(user_first_name, " ", user_last_name) as user_name',
            'gdpreq_id', 'user_email', 'gdpreq_reason', 'gdpreq_status',
            'gdpreq_added_on', 'gdpreq_updated_on',
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'gdpreq_id' => $row['gdpreq_id'],
                'user_name' => $row['user_name'],
                'user_email' => $row['user_email'],
                'gdpreq_reason' => $row['gdpreq_reason'],
                'gdpreq_added_on' => MyDate::formatDate($row['gdpreq_added_on']),
                'gdpreq_updated_on' => ($row['gdpreq_status'] == GdprRequest::STATUS_PENDING) ? Label::getLabel('LBL_NA') : MyDate::formatDate($row['gdpreq_updated_on']),
                'gdpreq_status' => GdprRequest::getStatusArr($row['gdpreq_status']),
            ]);
            $count++;
        }
        return $count;
    }

}
