<?php

/**
 * This class is used to handle Class Search  Listing
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class PackageSearch extends W3mentorsSearch
{

    /**
     * Initialize Package Search
     * 
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->alias = ($userType == User::TEACHER) ? 'grpcls' : 'ordpkg';
        $this->table = ($userType == User::TEACHER) ? GroupClass::DB_TBL : OrderPackage::DB_TBL;
        parent::__construct($langId, $userId, $userType);
        if ($userType == User::LEARNER || $userType == User::SUPPORT) {
            $this->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordpkg.ordpkg_order_id', 'orders');
            $this->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordpkg.ordpkg_package_id', 'grpcls');
            $this->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        } elseif ($userType == User::TEACHER) {
            $this->joinTable(OrderPackage::DB_TBL, 'LEFT JOIN', 'ordpkg.ordpkg_order_id = grpcls.grpcls_id', 'ordpkg');
        }
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
        $this->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id and gclang.gclang_lang_id = ' . $this->langId, 'gclang');
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        if ($this->userType == User::TEACHER) {
            $this->addDirectCondition('teacher.user_deleted IS NULL');
            $this->addCondition('grpcls.grpcls_teacher_id', '=', $this->userId);
            $this->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
            $this->addCondition('grpcls.grpcls_type', '=', GroupClass::TYPE_PACKAGE);
        } elseif ($this->userType == User::LEARNER) {
            $this->addCondition('orders.order_user_id', '=', $this->userId);
            $this->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $this->addCondition('grpcls.grpcls_type', '=', GroupClass::TYPE_PACKAGE);
        }
    }

    /**
     * Apply Search Conditions
     * 
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (!empty($post['keyword'])) {
            $keyword = trim($post['keyword']);
            $cond = $this->addCondition('gclang.grpcls_title', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('grpcls.grpcls_title', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('grpcls.grpcls_id', '=', $keyword);
            if ($this->userType == User::LEARNER) {
                $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
                $cond->attachCondition($fullName, 'LIKE', '%' . $keyword . '%', 'OR', true);
            }
            if ($this->userType == User::SUPPORT) {
                $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
                $cond->attachCondition($fullName, 'LIKE', '%' . $keyword . '%', 'OR', true);
                $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
                $cond->attachCondition($fullName, 'LIKE', '%' . $keyword . '%', 'OR', true);
            }
            $orderId = FatUtility::int(str_replace('O', '', $keyword));
            $cond->attachCondition('grpcls.grpcls_id', '=', $orderId);
            $cond->attachCondition('ordpkg.ordpkg_id', '=', $orderId);
            $cond->attachCondition('ordpkg.ordpkg_order_id', '=', $orderId);
        }
        if (!empty($post['grpcls_id'])) {
            $this->addCondition('grpcls.grpcls_id', '=', $post['grpcls_id']);
        }
        if (!empty($post['order_id'])) {
            $this->addCondition('orders.order_id', '=', $post['order_id']);
        }
        if (!empty($post['ordpkg_id'])) {
            $this->addCondition('ordpkg.ordpkg_id', '=', $post['ordpkg_id']);
        }
        if (!empty($post['ordcls_tlang_id'])) {
            $cond = $this->addCondition('grpcls.grpcls_tlang_id', '=', $post['ordcls_tlang_id']);

            $srch = TeachLanguage::getSearchObject($this->langId, false);
            $srch->addDirectCondition('FIND_IN_SET(' . $post['ordcls_tlang_id'] . ', tlang_parentids)');
            $srch->addFld('tlang_id');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $data = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
            if ($data) {
                $cond->attachCondition('grpcls.grpcls_tlang_id', 'IN', array_keys($data));
            }
        } elseif (!empty($post['ordcls_tlang'])) {
            $this->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = grpcls.grpcls_tlang_id AND tlanglang.tlanglang_lang_id = ' . $this->langId, 'tlanglang');
            $cond = $this->addCondition('tlanglang.tlang_name', 'LIKE', '%' . trim($post['ordcls_tlang']) . '%');
            $this->addLanguageCondition($post['ordcls_tlang'], $cond);
        }
        if (isset($post['order_payment_status']) && $post['order_payment_status'] != '') {
            $this->addCondition('orders.order_payment_status', '=', $post['order_payment_status']);
        }
        if (!empty($post['grpcls_status'])) {
            $this->addCondition('grpcls.grpcls_status', '=', $post['grpcls_status']);
        }
        if (!empty($post['ordpkg_status'])) {
            $this->addCondition('ordpkg.ordpkg_status', '=', $post['ordpkg_status']);
        }
        if (isset($post['ordpkg_offline']) && $post['ordpkg_offline'] != '') {
            $this->addCondition('ordpkg.ordpkg_offline', '=', $post['ordpkg_offline']);
        }
        if (!empty($post['grpcls_duration'])) {
            $this->addCondition('grpcls.grpcls_duration', '=', $post['grpcls_duration']);
        }
        if (!empty($post['grpcls_tlang_id'])) {
            $this->addCondition('grpcls.grpcls_tlang_id', '=', $post['grpcls_tlang_id']);
        }
        if (!empty($post['order_addedon_from'])) {
            $start = $post['order_addedon_from'] . ' 00:00:00';
            $this->addCondition('orders.order_addedon', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (isset($post['service_type']) && $post['service_type'] != "") {
            $this->addCondition('grpcls.grpcls_offline', '=', $post['service_type']);
        }
        if (!empty($post['order_addedon_till'])) {
            $end = $post['order_addedon_till'] . ' 23:59:59';
            $this->addCondition('orders.order_addedon', '<=', MyDate::formatToSystemTimezone($end));
        }
    }
    
    /**
     * Function to search languages by keyword and prepare condition
     *
     * @param string $keyword
     */
    private function addLanguageCondition(string $keyword, $cond)
    {
        $tlangIds = TeachLanguage::searchByKeyword($keyword, $this->langId);
        $tlangIds = !empty($tlangIds) ? array_keys($tlangIds) : [-1];
        $this->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'grpcls.grpcls_tlang_id = tlang_id');
        $cond->attachCondition('tlang_id' , 'IN', $tlangIds, 'OR', true);
        $qryStr = [];
        foreach ($tlangIds as $id) {
            $qryStr[] = 'FIND_IN_SET(' . $id . ', tlang_parentids)';
        }
        $cond->attachCondition('mysql_func_' . implode(' OR ', $qryStr) , '', 'mysql_func_', 'OR', true);
    }

    /**
     * Fetch & Format Packages
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($rows) == 0) {
            return [];
        }
        $cancelDuration = FatApp::getConfig('CONF_CLASS_CANCEL_DURATION', FatUtility::VAR_INT, 24);
        $currentTimeUnix = strtotime(MyDate::formatDate(date('Y-m-d H:i:s')));
        $teachLangs = TeachLanguage::getNames($this->langId, array_column($rows, 'grpcls_tlang_id'), false);
        $allTeachLangs = TeachLanguage::getAllLangs($this->langId);
        $countries = Country::getNames($this->langId, array_column($rows, 'teacher_country_id'));
        foreach ($rows as $key => $row) {
            $row['teacher_country'] = $countries[$row['teacher_country_id']] ?? '';
            $row['grpcls_currenttime_unix'] = $currentTimeUnix;
            $row['teacher_full_name'] = implode(" ", [$row['teacher_first_name'], $row['teacher_last_name']]);
            $row['grpcls_start_datetime'] = MyDate::formatDate($row['grpcls_start_datetime']);
            $row['grpcls_end_datetime'] = MyDate::formatDate($row['grpcls_end_datetime']);
            $row['grpcls_starttime_unix'] = strtotime($row['grpcls_start_datetime']);
            $row['grpcls_endtime_unix'] = strtotime($row['grpcls_end_datetime']);
            $row['grpcls_tlang_name'] = $teachLangs[$row['grpcls_tlang_id']] ?? '';
            $row['grpcls_language_name'] = $allTeachLangs[$row['grpcls_tlang_id']] ?? '';
            $row['canCancel'] = $this->canCancel($row, $cancelDuration);
            $row['canEdit'] = $this->canEdit($row);
            $row['order_addedon'] = isset($row['order_addedon']) ? MyDate::formatDate($row['order_addedon']) : '';
            if ($row['grpcls_offline']) {
                $row['grpcls_address'] = $this->getClassAddress($row);
            }
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Can Cancel Package
     * 
     * @param array $package
     * @return bool
     */
    private function canCancel(array $package, int $cancelDuration): bool
    {
        $startTime = strtotime(' -' . $cancelDuration . ' hours', $package['grpcls_starttime_unix']);
        return ((($this->userType == User::TEACHER && $package['grpcls_booked_seats'] == 0) ||
            ($this->userType == User::LEARNER && $package['ordpkg_status'] == OrderPackage::SCHEDULED)) &&
            $package['grpcls_status'] == GroupClass::SCHEDULED && $package['grpcls_currenttime_unix'] <= $startTime);
    }

    /**
     * Can Edit|Update Class
     * 
     * @param array $package
     * @return bool
     */
    private function canEdit(array $package): bool
    {
        return ($this->userType == User::TEACHER && $package['grpcls_booked_seats'] == 0 &&
            $package['grpcls_status'] == GroupClass::SCHEDULED &&
            $package['grpcls_starttime_unix'] > $package['grpcls_currenttime_unix']);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    public static function getSearchForm(): Form
    {
        $frm = new Form('frmPackageSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword');
        $frm->addSelectBox(Label::getLabel('LBL_SERVICE_TYPE'), 'service_type', AppConstant::getServiceType(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', 10)->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addHiddenField('', 'grpcls_id')->requirements()->setInt();
        $frm->addHiddenField('', 'order_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Get Detail Fields
     * 
     * @return array
     */
    public static function getDetailFields(): array
    {
        return static::getListingFields();
    }

    /**
     * Get Listing Fields
     * 
     * @return array
     */
    public static function getListingFields(): array
    {
        $flds = [
            'grpcls.grpcls_id' => 'grpcls_id',
            'ordpkg.ordpkg_id' => 'ordpkg_id',
            'grpcls.grpcls_teacher_id' => 'grpcls_teacher_id',
            'grpcls.grpcls_tlang_id' => 'grpcls_tlang_id',
            'grpcls.grpcls_duration' => 'grpcls_duration',
            'grpcls.grpcls_start_datetime' => 'grpcls_start_datetime',
            'grpcls.grpcls_end_datetime' => 'grpcls_end_datetime',
            'grpcls.grpcls_booked_seats' => 'grpcls_booked_seats',
            'grpcls.grpcls_total_seats' => 'grpcls_total_seats',
            'grpcls.grpcls_entry_fee' => 'grpcls_entry_fee',
            'grpcls.grpcls_added_on' => 'grpcls_added_on',
            'grpcls.grpcls_status' => 'grpcls_status',
            'ordpkg.ordpkg_status' => 'ordpkg_status',
            'ordpkg.ordpkg_order_id' => 'ordpkg_order_id',
            'ordpkg.ordpkg_package_id' => 'ordpkg_package_id',
            'teacher.user_country_id' => 'teacher_country_id',
            'teacher.user_username' => 'teacher_username',
            'teacher.user_first_name' => 'teacher_first_name',
            'teacher.user_last_name' => 'teacher_last_name',
            'IFNULL(gclang.grpcls_title, grpcls.grpcls_title)' => 'grpcls_title',
            'IFNULL(gclang.grpcls_description, grpcls.grpcls_description)' => 'grpcls_description',
            'grpcls.grpcls_address_id' => 'grpcls_address_id',
            'grpcls.grpcls_offline' => 'grpcls_offline'
        ];
        return $flds;
    }

    /**
     * Get Package Classes
     * 
     * @param int $packageId
     * @param int $langId
     * @return array
     */
    public static function getClasses(int $packageId, int $langId = null): array
    {
        if ($packageId < 1) {
            return [];
        }
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->addFld('grpcls.*');
        if (!is_null($langId)) {
            $srch->addFld('IFNULL(gclang.grpcls_title,grpcls.grpcls_title) as grpcls_title', 'IFNULL(gclang.grpcls_description,grpcls.grpcls_description) as grpcls_description');
            $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id = gclang.gclang_grpcls_id and gclang.gclang_lang_id = ' . $langId, 'gclang');
        }
        $srch->addCondition('grpcls.grpcls_parent', '=', $packageId);
        $srch->addOrder('grpcls.grpcls_start_datetime', 'ASC');
        $srch->doNotCalculateRecords();
        $classes = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($classes as $key => $class) {
            $class['grpcls_end_datetime'] = MyDate::formatDate($class['grpcls_end_datetime']);
            $class['grpcls_start_datetime'] = MyDate::formatDate($class['grpcls_start_datetime']);
            $classes[$key] = $class;
        }
        return $classes;
    }


    /**
     * Get package Addresses
     * 
     * @param array $class
     * @return array
     */
    public function getClassAddress(array $package): array
    {
        if ($package['grpcls_address_id'] == NULL && $package['	grpcls_offline'] == 0) {
            return [];
        }
        $address = new UserAddresses($package['grpcls_teacher_id']);
        $data = $address->getAll($this->langId, [$package['grpcls_address_id']]);
        return $data[0] ?? [];
    }


    /**
     * Get Package Names
     * 
     * @param int $langId
     * @param array $packageIds
     * @return array
     */
    public static function getPackageNames(int $langId, array $packageIds): array
    {
        if (empty($langId) || empty($packageIds)) {
            return [];
        }
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'grpcls.grpcls_id =  '
            . ' gclang.gclang_grpcls_id AND gclang.gclang_lang_id = ' . $langId, 'gclang');
        $srch->addMultipleFields(['grpcls.grpcls_id', 'IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title']);
        $srch->addCondition('grpcls.grpcls_id', 'IN', $packageIds);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }
}
