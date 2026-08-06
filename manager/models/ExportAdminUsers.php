<?php

class ExportAdminUsers extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::ADMIN_USERS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'admin_name' => Label::getLabel('LBL_FULL_NAME'),
            'admin_username' => Label::getLabel('LBL_USERNAME'),
            'admin_email' => Label::getLabel('LBL_EMAIL'),
            'admin_active' => Label::getLabel('LBL_STATUS'),
        ];
        return ['admin_name', 'admin_username', 'admin_email', 'admin_active'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'admin_name' => $row['admin_name'],
                'admin_username' => $row['admin_username'],
                'admin_email' => $row['admin_email'],
                'admin_active' => AppConstant::getActiveArr($row['admin_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
