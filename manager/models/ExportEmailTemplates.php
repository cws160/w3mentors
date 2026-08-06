<?php

class ExportEmailTemplates extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::EMAIL_TEMPLATES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'etpl_name' => Label::getLabel('LBL_name'),
            'etpl_subject' => Label::getLabel('LBL_subject'),
            'etpl_status' => Label::getLabel('LBL_Status'),
        ];
        return ['etpl_name', 'etpl_subject', 'etpl_status'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'etpl_name' => $row['etpl_name'],
                'etpl_subject' => $row['etpl_subject'],
                'etpl_status' => AppConstant::getActiveArr($row['etpl_status'])
            ]);
            $count++;
        }
        return $count;
    }

}
