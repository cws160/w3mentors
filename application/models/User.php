<?php

/**
 * This class is used to handle User
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class User extends MyAppModel
{

    const ADMIN_SESSION_ELEMENT = 'w3MentorsAdmin';
    const DB_TBL = 'tbl_users';
    const DB_TBL_PREFIX = 'user_';
    const DB_TBL_STAT = 'tbl_teacher_stats';
    const DB_TBL_STAT_PREFIX = 'tstat_';
    const DB_TBL_SETTING = 'tbl_user_settings';
    const DB_TBL_USR_BANK_INFO = 'tbl_user_bank_details';
    const DB_TBL_USR_BANK_INFO_PREFIX = 'ub_';
    const DB_TBL_USR_WITHDRAWAL_REQ = 'tbl_user_withdrawal_requests';
    const DB_TBL_USR_WITHDRAWAL_REQ_PREFIX = 'withdrawal_';
    const DB_TBL_LANG = 'tbl_users_lang';
    const DB_TBL_LANG_PREFIX = 'userlang_';
    const DB_TBL_USER_TO_SPOKEN_LANGUAGES = 'tbl_user_speak_languages';
    const DB_TBL_TEACHER_FAVORITE = 'tbl_user_favourite_teachers';
    const DB_TBL_COURSE_FAVORITE = 'tbl_user_favourite_courses';
    const DB_TBL_AFFILIATE_STAT = 'tbl_affiliate_stats';

    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_NON_BINARY = 3;
    const GENDER_NOT_SAY = 4;
    const LESSION_EMAIL_BEFORE_12_HOUR = 12;
    const LESSION_EMAIL_BEFORE_24_HOUR = 24;
    const LESSION_EMAIL_BEFORE_48_HOUR = 48;
    const USER_NOTICATION_NUMBER_12 = 12;
    const USER_NOTICATION_NUMBER_24 = 24;
    const USER_NOTICATION_NUMBER_48 = 48;
    const WITHDRAWAL_METHOD_TYPE_BANK = 1;
    const WITHDRAWAL_METHOD_TYPE_PAYPAL = 2;

    /* User Types */
    const LEARNER = 1;
    const TEACHER = 2;
    const SUPPORT = 3;
    const SYSTEMS = 4;
    const AFFILIATE = 5;

    /* Device Types */
    public const DEVICE_IOS = 1;
    public const DEVICE_ANDROID = 2;

    /**
     * Initialize User
     * 
     * @param int $userId
     */
    public function __construct(int $userId = 0)
    {
        parent::__construct(static::DB_TBL, 'user_id', $userId);
        $this->objMainTableRecord->setSensitiveFields([
            'user_password', 'user_active', 'user_verified'
        ]);
    }

    /**
     * Get User Details
     * 
     * @param int $userId
     * @return null|array
     */
    public static function getDetail(int $userId)
    {
        $srch = new SearchBase(static::DB_TBL, 'user');
        $srch->joinTable(static::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->addCondition('user.user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get User Types
     * 
     * @param int $key
     * @param int $langId
     * @return string|array
     */
    public static function getUserTypes(int|null $key = null, int $langId = 0)
    {
        $arr = [];
        if($langId > 0) {
            $arr = [
                User::LEARNER => Label::getLabel('LBL_Learner', $langId),
                User::TEACHER => Label::getLabel('LBL_Teacher', $langId),
                User::AFFILIATE => Label::getLabel('LBL_Affiliate', $langId),
            ];
        } else {
            $arr = [
                User::LEARNER => Label::getLabel('LBL_Learner'),
                User::TEACHER => Label::getLabel('LBL_Teacher'),
                User::AFFILIATE => Label::getLabel('LBL_Affiliate'),
            ];
        }
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Gender
     * 
     * @return array
     */
    public static function getGenderTypes(): array
    {
        return [
            static::GENDER_MALE => Label::getLabel('LBL_MALE'),
            static::GENDER_FEMALE => Label::getLabel('LBL_FEMALE'),
            static::GENDER_NON_BINARY => Label::getLabel('LBL_NON_BINARY'),
            static::GENDER_NOT_SAY => Label::getLabel('LBL_PREFER_NOT_TO_SAY'),
        ];
    }

    /**
     * Get Teacher Profile Progress
     * 
     * @return array
     */
    public static function getProfileProgress(int $userId): array
    {
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->joinTable(TeacherStat::DB_TBL, 'LEFT JOIN', 'testat.testat_user_id = user.user_id', 'testat');
        $srch->addMultiplefields([
            'if(IFNULL(testat.testat_minprice,0) > 0 && IFNULL(testat.testat_maxprice,0) > 0,1,0) as priceCount',
            'if(IFNULL(testat.testat_teachlang,0) = 1 && IFNULL(testat.testat_speaklang,0) = 1,1,0) as languagesCount',
            'if(user.user_country_id > 0 && user.user_timezone != "" && user.user_username != "",1,0) as generalProfile',
            'IFNULL(testat.testat_preference,0) as preferenceCount',
            'IFNULL(testat.testat_qualification,0) as uqualificationCount',
            'IFNULL(testat.testat_availability,0) as generalAvailabilityCount',
        ]);
        $srch->addCondition('user.user_is_teacher', '=', AppConstant::YES);
        $srch->addCondition('user.user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $teacherRow = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($teacherRow)) {
            return [];
        }
        $teacherRowCount = count($teacherRow);
        $teacherFieldSum = array_sum($teacherRow);
        $teacherRow += [
            'totalFields' => $teacherRowCount,
            'totalFilledFields' => $teacherFieldSum,
            'percentage' => round((($teacherFieldSum * 100) / $teacherRowCount), 2),
            'isProfileCompleted' => ($teacherRowCount == $teacherFieldSum),
        ];
        return $teacherRow;
    }

    /**
     * Save User
     * 
     * @return bool
     */
    public function save()
    {
        if ($this->getMainTableRecordId() == 0) {
            $this->setFldValue('user_created', date('Y-m-d H:i:s'));
        }
        return parent::save();
    }

    /**
     * Set Device
     * 
     * @param int $userId
     */
    public static function setDevice(int $userId)
    {
        $type = $_SERVER['HTTP_DEVICE_TYPE'] ?? 0;
        $token = $_SERVER['HTTP_DEVICE_TOKEN'] ?? '';
        if (!empty($userId) && !empty($type) && !empty($token)) {
            $user = new UserSetting($userId);
            $user->saveData([
                'user_device_type' => $type,
                'user_device_token' => $token
            ]);
        }
    }

    /**
     * Set Language
     * 
     * @param int $userId
     * @param int $langId
     */
    public static function setLanguage(int $userId, int $langId)
    {
        if (!empty($userId) && !empty($langId)) {
            $user = new User($userId);
            $user->setFldValue('user_lang_id', $langId);
            $user->save();
        }
    }

    /**
     * Set Currency
     * 
     * @param int $userId
     * @param int $currId
     */
    public static function setCurrency(int $userId, int $currId)
    {
        if (!empty($userId) && !empty($currId)) {
            $user = new User($userId);
            $user->setFldValue('user_currency_id', $currId);
            $user->save();
        }
    }

    /**
     * Set Timezone
     * 
     * @param int $userId
     * @param string $timezone
     */
    public static function setTimezone(int $userId, string $timezone)
    {
        if (!empty($userId) && !empty($timezone)) {
            $user = new User($userId);
            $user->setFldValue('user_timezone', $timezone);
            $user->save();
        }
    }

    /**
     * Set Password
     * 
     * @param string $password
     * @return bool
     */
    public function setPassword(string $password): bool
    {
        $this->setFldValue('user_password', UserAuth::encryptPassword($password));
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Assign Gift Card to user
     *
     * @param string $email
     * @return bool
     */
    public function assignGiftCard(string $email): bool
    {
        if (!FatApp::getDb()->updateFromArray(Giftcard::DB_TBL,
                        ['ordgift_receiver_id' => $this->getMainTableRecordId()],
                        ['smt' => 'ordgift_receiver_email = ?', 'vals' => [$email]])) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Settings
     * 
     * @param array $data
     * @return bool
     */
    public function setSettings(array $data): bool
    {
        $userId = $this->getMainTableRecordId();
        if (empty($userId)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $userSetting = new UserSetting($userId);
        if (!$userSetting->saveData($data)) {
            $this->error = $userSetting->getError();
            return false;
        }
        return true;
    }

    /**
     * Change Email
     * 
     * @param string $email
     * @return bool
     */
    public function changeEmail(string $email): bool
    {
        $userId = $this->getMainTableRecordId();
        if (empty($userId)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $this->setFldValue('user_email', trim($email));
        return $this->save();
    }

    /**
     * Verify Account
     * 
     * @return bool
     */
    public function verifyAccount(): bool
    {
        $userId = $this->getMainTableRecordId();
        if (empty($userId)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $this->setFldValue('user_verified', date('Y-m-d H:i:s'));
        return $this->save();
    }

    /**
     * Update Status
     * 
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $status = 1): bool
    {
        if (empty($this->mainTableRecordId)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $this->setFlds(['user_active' => $status]);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * Get User Bank Info
     * 
     * @return boolean
     */
    public function getUserBankInfo()
    {
        if (($this->getMainTableRecordId() < 1)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED');
            return false;
        }
        $srch = new SearchBase(static::DB_TBL_USR_BANK_INFO, 'tub');
        $srch->addMultipleFields([
            'ub_bank_name', 'ub_account_holder_name',
            'ub_account_number', 'ub_ifsc_swift_code', 'ub_bank_address', 'ub_paypal_email_address'
        ]);
        $srch->addCondition('ub_user_id', '=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    /**
     * Get User Paypal Info
     * 
     * @return boolean
     */
    public function getUserPaypalInfo()
    {
        if (($this->getMainTableRecordId() < 1)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED');
            return false;
        }
        $srch = new SearchBase(static::DB_TBL_USR_BANK_INFO, 'tub');
        $srch->addMultipleFields(['ub_paypal_email_address']);
        $srch->addCondition('ub_user_id', '=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    /**
     * Update Bank Info
     * 
     * @param array $data
     * @return boolean
     */
    public function updateBankInfo(array $data = [])
    {
        if (($this->getMainTableRecordId() < 1)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED');
            return false;
        }
        $assignValues = [
            'ub_user_id' => $this->getMainTableRecordId(),
            'ub_bank_name' => $data['ub_bank_name'],
            'ub_account_holder_name' => $data['ub_account_holder_name'],
            'ub_account_number' => $data['ub_account_number'],
            'ub_ifsc_swift_code' => $data['ub_ifsc_swift_code'],
            'ub_bank_address' => $data['ub_bank_address'],
        ];
        $db = FatApp::getDb();
        if (!$db->insertFromArray(static::DB_TBL_USR_BANK_INFO, $assignValues, false, [], $assignValues)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public function updatePaypalInfo($data = [])
    {
        if (($this->getMainTableRecordId() < 1)) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED');
            return false;
        }
        $assignValues = [
            'ub_user_id' => $this->getMainTableRecordId(),
            'ub_paypal_email_address' => $data['ub_paypal_email_address']
        ];
        $db = FatApp::getDb();
        if (!$db->insertFromArray(static::DB_TBL_USR_BANK_INFO, $assignValues, false, [], $assignValues)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public static function getWalletBalance(int $userId): float
    {
        $setting = UserSetting::getSettings($userId, ['user_wallet_balance']);
        return FatUtility::float($setting['user_wallet_balance'] ?? 0);
    }

    /**
     * Get Last Withdraw Request
     */
    public static function getLastWithdrawal($userId)
    {
        $userId = FatUtility::int($userId);
        $srch = new SearchBase(static::DB_TBL_USR_WITHDRAWAL_REQ, 'tuwr');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('withdrawal_user_id', '=', $userId);
        $srch->addOrder('withdrawal_request_date', 'desc');
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Add Withdrawal Request
     * 
     * @param array $data
     * @return boolean
     */
    public function addWithdrawalRequest(array $data)
    {
        $userId = FatUtility::int($data['ub_user_id']);
        unset($data['ub_user_id']);
        if ($userId < 1) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST.');
            return false;
        }
        $assignFields = [
            'withdrawal_user_id' => $userId,
            'withdrawal_amount' => $data['withdrawal_amount'],
            'withdrawal_payment_method_id' => $data['withdrawal_payment_method_id'],
            'withdrawal_comments' => $data['withdrawal_comments'],
            'withdrawal_status' => WithdrawRequest::STATUS_PENDING,
            'withdrawal_transaction_fee' => $data['withdrawal_transaction_fee'],
            'withdrawal_request_date' => date('Y-m-d H:i:s'),
        ];
        switch ($data['pmethod_code']) {
            case BankPayout::KEY:
                $assignFields += [
                    'withdrawal_bank' => $data['ub_bank_name'],
                    'withdrawal_account_holder_name' => $data['ub_account_holder_name'],
                    'withdrawal_account_number' => $data['ub_account_number'],
                    'withdrawal_ifc_swift_code' => $data['ub_ifsc_swift_code'],
                    'withdrawal_bank_address' => $data['ub_bank_address'],
                ];
                break;
            case PaypalPayout::KEY:
                $assignFields += ['withdrawal_paypal_email_id' => $data['ub_paypal_email_address'],];
                break;
        }
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_USR_WITHDRAWAL_REQ, $assignFields)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return FatApp::getDb()->getInsertId();
    }

    /**
     * Setup Favorite Teacher
     * 
     * @param int $teacherId
     * @return bool
     * 
     */
    public function setupFavoriteTeacher(int $teacherId): bool
    {
        $data = ['uft_user_id' => $this->getMainTableRecordId(), 'uft_teacher_id' => $teacherId];
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_TEACHER_FAVORITE, $data, false, [], $data)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Favorites
     * 
     * @param array $filter
     * @param int $langId
     * @return array
     */
    public function getFavourites(array $filter, int $langId = 0): array
    {
        $srch = new SearchBase(User::DB_TBL_TEACHER_FAVORITE, 'uft');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'uft_teacher_id = ut.user_id', 'ut');
        $srch->joinTable(Afile::DB_TBL, 'LEFT JOIN', 'file.file_record_id = uft_teacher_id and file.file_type = ' . Afile::TYPE_USER_PROFILE_IMAGE, 'file');
        $srch->joinTable(UserTeachLanguage::DB_TBL, 'LEFT  JOIN', 'ut.user_id = utsl.utlang_user_id', 'utsl');
        $srch->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'tlang_id = utsl.utlang_tlang_id');
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang_tlang_id = utsl.utlang_tlang_id AND tlanglang_lang_id = ' . $langId, 'sl_lang');
        $srch->addMultipleFields([
            'utsl.utlang_user_id', 'GROUP_CONCAT(DISTINCT IFNULL(tlang_name, tlang_identifier)) as teacherTeachLanguageName',
            'uft_teacher_id', 'file_id', 'user_username', 'user_first_name', 'user_last_name', 'user_country_id'
        ]);
        $srch->addCondition('uft_user_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ut.user_is_teacher', '=', AppConstant::YES);
        $srch->addCondition('user_active', '=', AppConstant::ACTIVE);
        $srch->addDirectCondition('user_verified IS NOT NULL');
        if (!empty($filter['keyword'])) {
            $srch->addCondition('mysql_func_concat(`user_first_name`," ",`user_last_name`)', 'like', '%' . $filter['keyword'] . '%', 'AND', true);
        }
        $srch->addGroupBy('uft_teacher_id');
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pageSize = AppConstant::PAGESIZE;
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $dataArr['Favourites'] = FatApp::getDb()->fetchAll($srch->getResultSet());
        $dataArr['pagingArr'] = [
            'pageCount' => $srch->pages(), 'page' => $page,
            'pageSize' => $pageSize, 'recordCount' => $srch->recordCount()
        ];
        return $dataArr;
    }

    /**
     * Can Withdraw
     * 
     * @param int $userId
     * @return bool
     */
    public static function canWithdraw(int $userId): bool
    {
        return (bool) self::getAttributesById($userId, 'user_is_teacher');
    }

    /**
     * Get By Email
     * 
     * @param string $email
     * @return null|array
     */
    public static function getByEmail(string $email)
    {
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'user.user_id=uset.user_id', 'uset');
        $srch->addMultipleFields([
            'user.user_id as user_id', 'user_first_name', 'user_last_name', 'user_email',
            'user_username', 'user_password', 'user_timezone', 'user_gender', 'user_lang_id',
            'user_country_id', 'user_is_teacher', 'user_active', 'user_verified', 'user_dashboard','user_is_affiliate'
        ]);
        $srch->addCondition('user_email', '=', $email);
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get By Username
     * 
     * @param string $username
     * @param array $fields
     * @return null|array
     */
    public static function getByUsername(string $username, array|null $fields = null)
    {
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'user.user_id=uset.user_id', 'uset');
        if (is_null($fields)) {
            $fields = [
                'user.user_id as user_id', 'user_first_name', 'user_last_name', 'user_email',
                'user_username', 'user_password', 'user_timezone', 'user_gender', 'user_lang_id',
                'user_country_id', 'user_is_teacher', 'user_active', 'user_verified', 'user_dashboard'
            ];
        }
        $srch->addMultipleFields($fields);
        $srch->addCondition('user_username', '=', $username);
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Biography 
     * 
     * @param int $langId
     * @return null|array
     */
    public function getBio(int $langId = 0)
    {
        $srch = new SearchBase(User::DB_TBL_LANG);
        if ($langId > 0) {
            $srch->addCondition('userlang_lang_id', '=', $langId);
        }
        $srch->addCondition('userlang_user_id', '=', $this->getMainTableRecordId());
        $srch->addMultipleFields(['userlang_lang_id', 'IFNULL(user_biography, "") as user_biography']);
        $srch->doNotCalculateRecords();
        if ($langId > 0) {
            $srch->setPageSize(1);
            return FatApp::getDb()->fetch($srch->getResultSet());
        }
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Is User Delete
     * 
     * @param int $userId
     * @return bool
     */
    public static function isUserDelete(int $userId): bool
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('user_id', '=', $userId);
        $srch->addDirectCondition('user_deleted IS NOT NULL');
        $srch->addFld('count(*) as total');
        $srch->doNotCalculateRecords();
        $user = FatApp::getDb()->fetch($srch->getResultSet());
        return ($user['total'] > 0);
    }

    /**
     * Validate Teacher
     * 
     * @param int $langId
     * @param int $learnerId
     * @return type
     */
    public function validateTeacher(int $langId, int $learnerId)
    {
        $srch = new TeacherSearch($langId, $learnerId, User::LEARNER);
        $srch->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'us.user_id = teacher.user_id', 'us');
        $srch->addMultipleFields([
            'teacher.user_id', 'teacher.user_first_name', 'teacher.user_country_id',
            'teacher.user_last_name', 'us.user_trial_enabled', 'us.user_book_before','user_offline_sessions'
        ]);
        $srch->addCondition('teacher.user_id', '=', $this->getMainTableRecordId());
        $srch->applyPrimaryConditions();
        $srch->setPageSize(1);
        $teacher = FatApp::getDb()->fetch($srch->getResultSet());
        return empty($teacher) ? false : $teacher;
    }

    /**
     * Setup Favorite Course
     *
     * @param int $courseId
     * @return bool
     */
    public function setupFavoriteCourse(int $courseId): bool
    {
        $data = ['ufc_user_id' => $this->getMainTableRecordId(), 'ufc_course_id' => $courseId];
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL_COURSE_FAVORITE, $data)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Check if teacher profile is complete or not
     *
     * @param array $ids
     * @return array
     */
    public static function isTeacherProfileComplete(array $ids)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->joinTable(static::DB_TBL_STAT, 'INNER JOIN', 'testat.testat_user_id = user_id', 'testat');
        $srch->addMultipleFields([
            'user_username', 'user_country_id', 'user_active', 'testat_teachlang', 'testat_speaklang',
            'testat_preference', 'testat_availability', 'testat_qualification', 'user_id'
        ]);
        $srch->addCondition('user_id', 'IN', $ids);
        $srch->doNotCalculateRecords();
        $teachers = FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
        if (empty($teachers)) {
            return [];
        }
        $data = [];
        foreach ($teachers as $teacherId => $teacher) {
            $data[$teacherId] = true;
            if (
                    empty($teacher['user_username']) ||
                    $teacher['user_country_id'] <= AppConstant::NO ||
                    $teacher['user_active'] == AppConstant::INACTIVE ||
                    $teacher['testat_teachlang'] == AppConstant::INACTIVE ||
                    $teacher['testat_speaklang'] == AppConstant::INACTIVE ||
                    $teacher['testat_preference'] == AppConstant::INACTIVE ||
                    $teacher['testat_availability'] == AppConstant::INACTIVE ||
                    $teacher['testat_qualification'] == AppConstant::INACTIVE
            ) {
                $data[$teacherId] = false;
            }
        }
        return $data;
    }

    public function updateTeacherMeta(array $data, bool $updateKeywords = false)
    {
        $userId = $this->getMainTableRecordId();
        $username = static::getAttributesById($userId, 'user_username');
        $teacherMeta = MetaTag::getMetaTag(MetaTag::META_GROUP_TEACHER, $username);
        $metaTag = new MetaTag($teacherMeta['meta_id'] ?? 0);
        if (!$metaTag->addMetaTag(MetaTag::META_GROUP_TEACHER, $data['user_username'], $username)) {
            $this->error = $metaTag->getError();
            return false;
        }
        if ($updateKeywords && !$metaTag->updateTeacherMetaKeyword($userId)) {
            $this->error = $metaTag->getError();
            return false;
        }
        $languages = Language::getAllNames();
        $bioData = $this->getBio();
        $name = $data['user_first_name'] . ' ' . $data['user_last_name'];
        foreach ($languages as $langId => $value) {
            $title = Label::getlabel('LBL_LEARN_WITH_{name}', $langId);
            $title = str_replace('{name}', $name, $title);
            $langData = [
                'metalang_meta_id' => $this->getMainTableRecordId(),
                'metalang_lang_id' => $langId,
                'meta_title' => $title,
                'meta_og_title' => $title,
                'meta_og_url' => MyUtility::makeFullUrl('Teachers', 'view', [$data['user_username']], CONF_WEBROOT_FRONTEND),
                'meta_og_description' => $bioData[$langId] ?? '',
                'meta_description' => $bioData[$langId] ?? '',
            ];
            if (!$metaTag->updateLangData($langId, $langData)) {
                $this->error = $metaTag->getError();
                return false;
            }
        }
        return true;
    }

    public static function getOrderCount($userId)
    {
        $srch = new SearchBase(Order::DB_TBL);
        $srch->addCondition('order_user_id', '=', $userId);
        $srch->addDirectCondition('order_type != ' . Order::TYPE_WALLET);
        $srch->addFld('count(*) as total');
        $srch->doNotCalculateRecords();
        $order = FatApp::getDb()->fetch($srch->getResultSet());
        return FatUtility::int($order['total']);
    }

    public static function getRewardBalance(int $userId)
    {
        return UserSetting::getSettings($userId, ['user_reward_points'])['user_reward_points'];
    }

    public static function getPhoto(int $userId): string
    {
        return MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $userId, Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL) . '?t=' . time();
    }
	
    public function setupMeetUser()
    {
        $meetObject = new Meeting($this->getMainTableRecordId(), User::TEACHER);
        if (!$meetObject->initMeeting()) {
            return false;
        }

        $user = $this->getFlds();
        $user['user_id'] = $this->getMainTableRecordId();
        $user['user_type'] = User::TEACHER;

        $meetObject->handleUserAccountRequest($user);
        return true;
    }	

    public static function offlineSessionsEnabled($userId = 0): bool
    {
        $userOffline = ($userId > 0) ? User::getAttributesById($userId, 'user_offline_sessions') : 1;
        return (FatApp::getConfig('CONF_ENABLE_OFFLINE_SESSIONS') && $userOffline);
    }
     
    /**
     * Settle Affiliate Referal Signup Commission
     * 
     * @return bool
     */
    public function  settleAffiliateSignupCommission()
    {
        if(!User::isAffiliateEnabled()){
            return true;
        }
        $affCommisssion = FatApp::getConfig('CONF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION', FatUtility::VAR_FLOAT, 0);
        if ($affCommisssion  < 1) {
            return true;
        }
        
        $user = User::getDetail($this->getMainTableRecordId());
        
        if(empty($user) || $user['user_verified'] == NULL || $user['user_referred_by'] < 1 ){
            return true;
        }        
        
        $affiliate = User::getDetail($user['user_referred_by']);
        if(empty($affiliate) || $affiliate['user_verified'] == NULL || $affiliate['user_is_affiliate'] == AppConstant::NO ||
        $affiliate['user_active'] == AppConstant::INACTIVE ){
            return true;
        }
        $username = $user['user_first_name'] . ' ' . $user['user_last_name'];
        $comment = str_replace('{user}', $username, Label::getLabel('LBL_{user}_REGISTERED'));
        $txn = new Transaction($user['user_referred_by'], Transaction::TYPE_REFERRAL_SIGNUP_COMMISSION);
        if (!$txn->credit($affCommisssion, $comment)) {
            return false;
        }
        if(!$this->updateAffiliateStats($user['user_referred_by'],Transaction::TYPE_REFERRAL_SIGNUP_COMMISSION)){
            return false;
        }
        $notifi = new Notification($user['user_referred_by'], Notification::TYPE_SIGNUP_COMMISSSION_CREDIT_TO_AFFILIATE);
        $comment = str_replace('{user}', $username, Label::getLabel('LBL_REGISTRATION_BY_{user}', $affiliate['user_lang_id']));
        $vars = ['{rewards}' => MyUtility::formatMoney($affCommisssion), '{message}' => $comment];
        if (!$notifi->sendNotification($vars)) {
            return false;
        }
        
        $txn->sendEmail();

        return true;
    }
    
    /**
     * Settle Affiliate Referal Signup Commission
     * 
     * @param int $userId
     * 
     * @return int
     */

    public function getReferalCount(int $userId)
    {
        if($userId < 1){
            return 0;
        }
        $srch = new SearchBase(static::DB_TBL,'user');
        $srch->joinTable(static::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->addDirectCondition('user_verified IS NOT NULL');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addFld('COUNT(*) as total');  
        $srch->addCondition('user_referred_by', '=', $userId);
        return FatApp::getDb()->fetch($srch->getResultSet())['total'];

    }

     /**
     * Update Affiliate Signup Stat
     * 
     * @param int $userId
     * @param array $teacherIds
     * @return array
     */

    public function updateAffiliateStats(int $userId, int $type): bool
    {
        if($userId < 1){
            return false;
        }
        if($type == Transaction::TYPE_REFERRAL_SIGNUP_COMMISSION){
            $row = [
                'afstat_user_id' =>  $userId,
                'afstat_referees' => $this->getReferalCount($userId),
                'afstat_signup_revenue' => $this->getAffiliateCommission($userId , $type)
            ];
        }
        if($type == Transaction::TYPE_REFERRAL_ORDER_COMMISSION){
            $row = [
                'afstat_user_id' =>  $userId,
                'afstat_order_revenue' => $this->getAffiliateCommission($userId, $type)
            ];
        }
        $record = new TableRecord(User::DB_TBL_AFFILIATE_STAT);
        $record->assignValues($row);
        if (!$record->addNew([], $row)) {
            $this->error = $record->getError();
            return false;
        }
        if( $type == Transaction::TYPE_REFERRAL_ORDER_COMMISSION ) {
            $query = "UPDATE " . User::DB_TBL_AFFILIATE_STAT . " SET `afstat_referee_sessions` = `afstat_referee_sessions` + 
                1 WHERE `afstat_user_id` = " .  $userId;
            if (!FatApp::getDb()->query($query)) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        return true;
    }

    public function getAffiliateCommission(int $userId, int $type = 0)
    {
        if($userId < 1){
            return 0;
        }
        $srch = new SearchBase(Transaction::DB_TBL, 'ut');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if($type > 0){
            $srch->addCondition('usrtxn_type', '=', $type);       
        }
        $srch->addCondition('usrtxn_user_id', '=', $userId);
        $srch->addFld('SUM(usrtxn_amount) AS total');
        return FatApp::getDb()->fetch($srch->getResultSet())['total'];
    }

     /**
     * Return affiliate enabled/disabled status
     *
     * @return boolean
     */
    public static function isAffiliateEnabled()
    {
        $status = FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE', FatUtility::VAR_INT, 0);
        return (bool) $status;      
    }

      /**
     * Get Referrer Id
     * 
     * @param string $code
     * @return int $userId
     */
    public static function getReferrerId(string $code): int
    {
        $srch = new SearchBase(UserSetting::DB_TBL);
        $srch->addCondition('user_referral_code', '=', $code);
        $srch->doNotCalculateRecords();
        $srch->addFld('user_id');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return FatUtility::int($row['user_id'] ?? 0);
    }

    public static function isAffilate(int $userId) : bool
    {
        if($userId < 1){
            return false;
        }
        $srch = new SearchBase(static::DB_TBL,'user');
        $srch->joinTable(static::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->addCondition('user.user_id', '=', $userId);
        $srch->addCondition('user_is_affiliate', '=', AppConstant::YES);       
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->doNotCalculateRecords();
        $srch->addFld('COUNT(*) as total');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return (FatUtility::int($row['total']) > 0);
    }

    public  function getActiveSubscriptionPlan ()  {
        if($this->getMainTableRecordId() < 0){
            return false;     
        }
        $srch = new SearchBase(OrderSubscriptionPlan::DB_TBL,'ordsplan');
        $srch->joinTable(SubscriptionPlan::DB_TBL, 'INNER JOIN', 'subplan.subplan_id = ordsplan.ordsplan_plan_id', 'subplan');
        $srch->addCondition('ordsplan.ordsplan_user_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('ordsplan.ordsplan_status', '=', OrderSubscriptionPlan::EXPIRED);   
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return $row;
        
    }
}
