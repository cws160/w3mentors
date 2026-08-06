<?php

class ExportUsers extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::USERS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'user_id' => Label::getLabel('LBL_USER_ID'),
            'user_full_name' => Label::getLabel('LBL_NAME'),
            'user_email' => Label::getLabel('LBL_EMAIL_ID'),
            'user_phone_number' => Label::getLabel('LBL_PHONE'),
            'user_is_teacher' => Label::getLabel('LBL_TYPE'),
            'user_created' => Label::getLabel('LBL_REGISTERED'),
            'user_featured' => Label::getLabel('LBL_FEATURED'),
            'user_verified' => Label::getLabel('LBL_VERIFIED'),
            'user_active' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'user.user_id', 'CONCAT(user_first_name, " ", user_last_name) AS user_full_name',
            'user_email', 'user_phone_code', 'user_phone_number', 'user_is_teacher','user_is_affiliate',
            'user_registered_as', 'user_created', 'user_featured', 'user_verified', 'user_active'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $ccode = Country::getDialCodes();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            if (FatUtility::int($row['user_is_affiliate'])) {
                $userType = Label::getLabel('LBL_AFFILIATE');
            } else {
                $userType =  FatUtility::int($row['user_is_teacher']) ?  Label::getLabel('LBL_LEARNER') .' | '. Label::getLabel('LBL_TEACHER') : Label::getLabel('LBL_LEARNER');
            }
            fputcsv($fh, [
                'user_id' => $row['user_id'],
                'user_full_name' => $row['user_full_name'],
                'user_email' => $row['user_email'],
                'user_phone_number' => ($ccode[$row['user_phone_code']] ?? '') . ' ' . $row['user_phone_number'],
                'user_is_teacher' => $userType,
                'user_created' => MyDate::formatDate($row['user_created']),
                'user_featured' => AppConstant::getYesNoArr(FatUtility::int($row['user_featured'])),
                'user_verified' => $row['user_verified'] ? Label::getLabel('LBL_YES') : Label::getLabel('LBL_NO'),
                'user_active' => AppConstant::getActiveArr(FatUtility::int($row['user_active'])),
            ]);
            $count++;
        }
        return $count;
    }

}
