<?php

/**
 * This class is used to handle Group Class Search
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class GroupClassSearch extends W3mentorsSearch
{

    public const THIS_WEEK = 1;
    public const NEXT_WEEK = 2;
    public const THIS_MONTH = 3;
    public const NEXT_MONTH = 4;
    public const CUSTOM_DATE = 5;
    /* Sort Orders */
    public const SORTBY_TITLE = 1;
    public const SORTBY_TUTOR = 2;
    public const SORTBY_PRICE = 3;
    public const SORTBY_LATEST = 4;

    /**
     * Initialize roup Class Search
     * 
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = GroupClass::DB_TBL;
        $this->alias = 'grpcls';
        parent::__construct($langId, $userId, $userType);
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = grpcls.grpcls_teacher_id', 'teacher');
        $this->joinTable(User::DB_TBL_STAT, 'INNER JOIN', 'testat.testat_user_id = teacher.user_id', 'testat');
        $this->joinTable(GroupClass::DB_TBL_LANG, 'LEFT JOIN', 'gclang.gclang_grpcls_id = grpcls.grpcls_id AND gclang.gclang_lang_id = ' . $this->langId, 'gclang');
        $this->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'grpcls.grpcls_tlang_id = tlang.tlang_id', 'tlang');
    }

    /**
     * Apply Primary Conditions
     * 
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('grpcls.grpcls_parent', '=', 0);
        $this->addCondition('teacher.user_username', '!=', "");
        $this->addDirectCondition('teacher.user_deleted IS NULL');
        $this->addDirectCondition('teacher.user_verified IS NOT NULL');
        $this->addCondition('tlang.tlang_active', '=', AppConstant::ACTIVE);
        $this->addCondition('grpcls.grpcls_status', '=', GroupClass::SCHEDULED);
        $this->addCondition('teacher.user_active', '=', AppConstant::ACTIVE);
        $this->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        $this->addCondition('teacher.user_country_id', '>', AppConstant::NO);
        $this->addCondition('testat.testat_teachlang', '=', AppConstant::YES);
    }

    /**
     * Apply Search Conditions
     * 
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        /* Keyword Search */
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $this->addCondition('gclang.grpcls_title', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('grpcls.grpcls_title', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('gclang.grpcls_description', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('grpcls.grpcls_description', 'LIKE', '%' . $keyword . '%');
            $fullname = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $cond->attachCondition($fullname, 'LIKE', '%' . $keyword . '%', 'OR', true);
        }
        /* Language */
        if (!empty($post['teachs']) && is_array($post['teachs'])) {
            $srch = TeachLanguage::getSearchObject();
            $qryStr = ['tlang_id IN (' . implode(",", $post['teachs']) . ')'];
            foreach ($post['teachs'] as $id) {
                $qryStr[] = 'FIND_IN_SET(' . $id . ', tlang_parentids)';
            }
            $srch->addDirectCondition('(' . implode(' OR ', $qryStr) . ')');
            $srch->addFld('tlang_id');
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $tlangIds = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
            $this->addCondition('grpcls_tlang_id', 'IN', array_keys($tlangIds));
        }
        /* Class Type */
        if (!empty($post['classtype'])) {
            $this->addCondition('grpcls_type', 'IN', $post['classtype']);
        }
        /* Duration */
        if (!empty($post['duration'])) {
            $this->addCondition('grpcls_duration', 'IN', $post['duration']);
        }
        /* Teacher Id */
        if (!empty($post['teacher_id'])) {
            $this->addCondition('grpcls_teacher_id', '=', $post['teacher_id']);
        }
        /* Schedules */
        if (!empty($post['grpcls_schedules']) && !$dates = $this->getScheduleDates($post['grpcls_schedules'])) {
            $this->addCondition('grpcls.grpcls_start_datetime', '>', $dates['start']);
            $this->addCondition('grpcls.grpcls_end_datetime', '<', $dates['end']);
        } else {
            $startDateTime = date('Y-m-d H:i:s');
            if (!empty($post['grpcls_start_datetime']) && $post['grpcls_start_datetime'] > $startDateTime) {
                $startDateTime = $post['grpcls_start_datetime'];
            }
            $this->addCondition('grpcls.grpcls_start_datetime', '>=', $startDateTime);
            if (!empty($post['grpcls_end_datetime'])) {
                $this->addCondition('grpcls.grpcls_end_datetime', '<=', $post['grpcls_end_datetime']);
            }
        }
        /** Check Zoom */
        if (!empty($post['verify_zoom']) && $post['verify_zoom'] == 1) {
            $this->joinSettingTable();
            $cond = $this->addCondition('us.user_zoom_status', '=', ZoomMeeting::ACC_SYNCED_AND_VERIFIED)
            ->attachCondition('us.user_zoom_status', '=', AppConstant::INACTIVE);
        }
        if (!empty($post['grpcls_offline'])) {
            $this->addCondition('grpcls.grpcls_offline','=',AppConstant::YES);
        }
        if (!empty($post['user_lat']) && !empty($post['user_lng'])) {
            $this->addDistanceQuery($post['user_lat'], $post['user_lng']);
        }
    }

    private function addDistanceQuery($latitude, $longitude)
    {
        $radius = FatApp::getConfig('CONF_DEFAULT_RADIUS_FOR_SEARCH', FatUtility::VAR_INT, 50);
        $this->joinTable(UserAddresses::DB_TBL, 'INNER JOIN', 'usradd.usradd_id = grpcls.grpcls_address_id', 'usradd');
        $sqlDistance = "(((acos(sin((" . $latitude . "*pi()/180)) * sin((`usradd`.`usradd_latitude`*pi()/180))+cos((" . $latitude . "*pi()/180)) * cos((`usradd`.`usradd_latitude`*pi()/180)) * cos(((" . $longitude . "-`usradd`.`usradd_longitude`)*pi()/180))))*180/pi())*60*1.1515*1.609344)";
        $this->addFld($sqlDistance . ' AS distance');
        $this->addHaving('distance','<',$radius );
    }

	/**
     * Join setting Table
     *
     * @return void
     */
    public function joinSettingTable(): void
    {
        $this->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'us.user_id = teacher.user_id', 'us');
    }

    /**
     * Fetch and Format Data
     * 
     * @return array
     */
    public function fetchAndFormat(): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($rows) < 1) {
            return [];
        }

        $classIds = array_column($rows, 'grpcls_id');
        $countryIds = array_column($rows, 'user_country_id');
        $countries = Country::getNameAndCode($this->langId, $countryIds);
        $teachLangIds = array_column($rows, 'grpcls_tlang_id');
        $teachLangs = $this->getTeachLangs($teachLangIds, $this->langId);
        $bookedClasses = OrderClass::userBooked($this->userId, $classIds);
        $bookedPackages = OrderPackage::userBooked($this->userId, $classIds);
        $bookedItems = $bookedClasses + $bookedPackages;
        $teacherIds = array_column($rows, 'grpcls_teacher_id');
        $offers = OfferPrice::getOffers($this->userId, $teacherIds);
        $banners = static::getClassBanners($classIds);
        $photos = static::getProfilePhotos($teacherIds);
        $classCounts = static::getSubClassesCounts($classIds);
        $unpaidSeatsCount = OrderClass::getUnpaidSeats($classIds);
        $currentTimeUnix = strtotime(MyDate::formatDate(date('Y-m-d H:i:s')));
        $profileStatus = User::isTeacherProfileComplete($teacherIds);
        foreach ($rows as $key => $row) {
            $row['grpcls_banner'] = $banners[$row['grpcls_id']] ?? '';
            $row['user_photo'] = $photos[$row['grpcls_teacher_id']] ?? '';
            $row['user_country_name'] = $countries[$row['user_country_id']]['name'] ?? '';
            $row['user_country_code'] = $countries[$row['user_country_id']]['code'] ?? '';
            $row['grpcls_tlang_name'] = $teachLangs[$row['grpcls_tlang_id']] ?? '';
            $row['user_full_name'] = $row['user_first_name'] . ' ' . $row['user_last_name'];
            $row['grpcls_start_datetime'] = MyDate::formatDate($row['grpcls_start_datetime']);
            $row['grpcls_end_datetime'] = MyDate::formatDate($row['grpcls_end_datetime']);
            $row['grpcls_starttime_unix'] = strtotime($row['grpcls_start_datetime']);
            $row['grpcls_endtime_unix'] = strtotime($row['grpcls_end_datetime']);
            $row['grpcls_currenttime_unix'] = $currentTimeUnix;
            $row['grpcls_remaining_unix'] = strtotime($row['grpcls_start_datetime']) - $currentTimeUnix;
            $row['grpcls_already_booked'] = empty($bookedItems[$row['grpcls_id']]) ? 0 : 1;
            $row['grpcls_unpaid_seats'] = $unpaidSeatsCount[$row['grpcls_id']] ?? 0;
            $row['grpcls_sub_classes'] = $classCounts[$row['grpcls_id']] ?? 0;
            $row['package_offer'] = $offers[$row['grpcls_teacher_id']]['package'] ?? 0;
            $row['class_offer'] = $offers[$row['grpcls_teacher_id']]['class'][$row['grpcls_duration']] ?? 0;
            $row['canBookTill'] = date('Y-m-d H:i:s', strtotime('+' . FatApp::getConfig('CONF_CLASS_BOOKING_GAP') . ' minutes', $currentTimeUnix));
            $row['canBookClass'] = $this->canBookClass($row);
            $row['is_profile_complete'] = $profileStatus[$row['grpcls_teacher_id']] ?? 0;
            if($row['grpcls_offline']){
                $row['grpcls_address'] = $this->getClassAddress($row);
            }
            $rows[$key] = $row;
        }
        return $rows;
    }

    private function getTeachLangs(array $teachLangIds, int $langId)
    {
        if (empty($teachLangIds)) {
            return [];
        }
        $srch = TeachLanguage::getSearchObject($langId);
        $srch->addCondition('tlang_id', 'IN', $teachLangIds);
        $srch->addMultipleFields(['tlang_id', 'IFNULL(tlang_parentids, 0)']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $langs = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        $langs = implode(',', array_merge(array_keys($langs), array_values($langs)));
        $langs = array_filter(explode(',', $langs));
        $teachLanguages = TeachLanguage::getTeachLangsRecursively($langId, 0, $langs);
        return TeachLanguage::format($teachLanguages);
    }

    private function canBookClass(array $class)
    {
        if (
                ($class['grpcls_teacher_id'] == $this->userId) || ($class['grpcls_already_booked'] == 1) ||
                (($class['grpcls_booked_seats'] + $class['grpcls_unpaid_seats']) >= $class['grpcls_total_seats'])
        ) {
            return 0;
        }
        return 1;
    }

    /**
     * Apply Order By
     * 
     * @param string $sortOrder
     * @return void
     */
    public function applyOrderBy(string $sortOrder): void
    {
        switch ($sortOrder) {
            case static::SORTBY_TITLE:
                $this->addOrder('gclang.grpcls_title', 'ASC');
                break;
            case static::SORTBY_TUTOR:
                $this->addOrder('teacher.user_id', 'ASC');
                $this->addOrder('teacher.user_first_name', 'ASC');
                break;
            case static::SORTBY_PRICE:
                $this->addOrder('grpcls.grpcls_entry_fee', 'ASC');
                break;
            case static::SORTBY_LATEST:
                $this->addOrder('grpcls.grpcls_start_datetime', 'ASC');
                break;
        }
    }

    /**
     * Get Sort Options
     * 
     * @return array
     */
    public static function getSortOptions(): array
    {
        return [
            static::SORTBY_TITLE => Label::getLabel('SORT_BY_TITLE'),
            static::SORTBY_TUTOR => Label::getLabel('SORT_BY_TUTOR'),
            static::SORTBY_PRICE => Label::getLabel('SORT_BY_PRICE'),
            static::SORTBY_LATEST => Label::getLabel('SORT_BY_LATEST'),
        ];
    }

    /**
     * Get Schedule Options
     * 
     * @return array
     */
    public static function getScheduleOptions(): array
    {
        return [
            static::THIS_WEEK => Label::getLabel('LBL_THIS_WEEK'),
            static::NEXT_WEEK => Label::getLabel('LBL_NEXT_WEEK'),
            static::THIS_MONTH => Label::getLabel('LBL_THIS_MONTH'),
            static::NEXT_MONTH => Label::getLabel('LBL_NEXT_MONTH'),
            static::CUSTOM_DATE => Label::getLabel('LBL_CUSTOM_DATE'),
        ];
    }

    /**
     * Get Class Banners
     * 
     * @param array $classIds
     * @return array
     */
    public static function getClassBanners(array $classIds): array
    {
        if (count($classIds) == 0) {
            return [];
        }
        $srch = new SearchBase(Afile::DB_TBL);
        $srch->addMultipleFields(['file_record_id', 'file_name']);
        $srch->addCondition('file_type', '=', Afile::TYPE_GROUP_CLASS_BANNER);
        $srch->addCondition('file_record_id', 'IN', $classIds);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }


    /**
     * Get Class Addresses
     * 
     * @param array $class
     * @return array
     */
    public function getClassAddress(array $class): array
    {
        if ($class['grpcls_address_id'] == NULL && $class['grpcls_offline'] == 0) {
            return [];
        }
        $address = new UserAddresses($class['grpcls_teacher_id']);
        $addresses = $address->getAll($this->langId, [$class['grpcls_address_id']]);
        if (empty($addresses)) {
            return [];
        }
        return $addresses[0];
    }

    /**
     * Get Profile Photos
     * 
     * @param array $teacherIds
     * @return array
     */
    public static function getProfilePhotos(array $teacherIds): array
    {
        if (count($teacherIds) == 0) {
            return [];
        }
        $srch = new SearchBase(Afile::DB_TBL);
        $srch->addMultipleFields(['file_record_id', 'file_name']);
        $srch->addCondition('file_type', '=', Afile::TYPE_USER_PROFILE_IMAGE);
        $srch->addCondition('file_record_id', 'IN', $teacherIds);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get Schedule Dates
     * 
     * @param int $key
     * @return bool|array
     */
    private function getScheduleDates(int $key)
    {
        $datetime = new DateTime(date('Y-m-d H:i:s'));
        $weekStart = $datetime->modify('last saturday')->modify('+1 day')->format('Y-m-d H:i:s');
        $weekEnd = $datetime->modify('next saturday')->format('Y-m-d H:i:s');
        $monthStart = $datetime->modify('first day of this month')->format('Y-m-d H:i:s');
        $monthEnd = $datetime->modify('last day of this month')->format('Y-m-d H:i:s');
        $nextWeekStart = date('Y-m-d H:i:s', strtotime($weekStart, '+1 week'));
        $nextWeekEnd = date('Y-m-d H:i:s', strtotime($weekEnd, '+1 week'));
        $nextMonthStart = date('Y-m-d H:i:s', strtotime($monthStart, '+1 month'));
        $nextMonthEnd = date('Y-m-d H:i:s', strtotime($monthEnd, '+1 month'));
        $arr = [
            static::THIS_WEEK => ['start' => $weekStart, 'end' => $weekEnd],
            static::NEXT_WEEK => ['start' => $nextWeekStart, 'end' => $nextWeekEnd],
            static::THIS_MONTH => ['start' => $monthStart, 'end' => $monthEnd],
            static::NEXT_MONTH => ['start' => $nextMonthStart, 'end' => $nextMonthEnd],
        ];
        return $arr[$key] ?? false;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    public static function getSearchForm(int $langId): Form
    {
        $frm = new Form('frmSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox('', 'keyword', '', ['placeholder' => Label::getLabel('LBL_BY_KEYWORD')]);
        $frm->addCheckBoxes(Label::getLabel('LBL_TEACH_LANGUAGES'), 'teachs', TeachLanguage::getTeachLangsRecursively($langId));
        $frm->addCheckBoxes(Label::getLabel('LBL_CLASS_TYPE'), 'classtype', GroupClass::getClassTypes());
        $frm->addCheckBoxes(Label::getLabel('LBL_DURATION'), 'duration', AppConstant::fromatClassSlots(AppConstant::getGroupClassSlots()));
        $frm->addHiddenField(Label::getLabel('LBL_OFFLINE_SESSIONS'), 'grpcls_offline', '0');
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField(Label::getLabel('LBL_LAT'), 'user_lat', '0');
        $frm->addHiddenField(Label::getLabel('LBL_LNG'), 'user_lng', '0');
        $frm->addHiddenField(Label::getLabel('LBL_FORMATTED_ADDRESS'), 'formatted_address', '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear'));
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
        return [
            'testat.testat_ratings' => 'testat_ratings',
            'testat.testat_reviewes' => 'testat_reviewes',
            'testat.testat_lessons' => 'testat_lessons',
            'testat.testat_classes' => 'testat_classes',
            'testat.testat_students' => 'testat_students',
            'teacher.user_username' => 'user_username',
            'teacher.user_first_name' => 'user_first_name',
            'teacher.user_last_name' => 'user_last_name',
            'teacher.user_country_id' => 'user_country_id',
            'grpcls.grpcls_id' => 'grpcls_id',
            'grpcls.grpcls_type' => 'grpcls_type',
            'grpcls.grpcls_slug' => 'grpcls_slug',
            'grpcls.grpcls_entry_fee' => 'grpcls_entry_fee',
            'grpcls.grpcls_teacher_id' => 'grpcls_teacher_id',
            'grpcls.grpcls_total_seats' => 'grpcls_total_seats',
            'grpcls.grpcls_booked_seats' => 'grpcls_booked_seats',
            'grpcls.grpcls_tlang_id' => 'grpcls_tlang_id',
            'grpcls.grpcls_end_datetime' => 'grpcls_end_datetime',
            'grpcls.grpcls_start_datetime' => 'grpcls_start_datetime',
            'grpcls.grpcls_status' => 'grpcls_status',
            'grpcls.grpcls_duration' => 'grpcls_duration',
            'IFNULL(gclang.grpcls_title, grpcls.grpcls_title)' => 'grpcls_title',
            'IFNULL(gclang.grpcls_description, grpcls.grpcls_description)' => 'grpcls_description',
            'grpcls.grpcls_address_id' => 'grpcls_address_id',
            'grpcls.grpcls_offline' => 'grpcls_offline',
        ];
    }

    /**
     * Get Upcoming Classes
     * 
     * @param array $conds
     * @return array
     */
    public function getUpcomingClasses(array $conds = []): array
    {
        $this->addCondition('grpcls_start_datetime', '>', date('Y-m-d H:i:s'));
        $this->addOrder('grpcls_start_datetime', 'ASC');
        $this->applySearchConditions($conds);
        $this->applyPrimaryConditions();
        $this->addSearchListingFields();
        $this->setPageNumber(1);
        $this->setPageSize(5);
        return $this->fetchAndFormat();
    }

    /**
     * More Classes from Teacher
     * 
     * @param int $teacherId
     * @param int $classId
     * @return array
     */
    public function getMoreClasses(int $teacherId, int $classId = 0): array
    {
        $this->addCondition('grpcls_start_datetime', '>', date('Y-m-d H:i:s'));
        $this->addCondition('grpcls_teacher_id', '=', $teacherId);
        $this->addCondition('grpcls_id', '!=', $classId);
        $this->addOrder('grpcls_start_datetime', 'ASC');
        $this->applyPrimaryConditions();
        $this->addSearchListingFields();
        $this->setPageNumber(1);
        $this->setPageSize(5);
        return $this->fetchAndFormat();
    }

    /**
     * Get Sub Classes Counts
     * 
     * @param array $classIds
     * @return array
     */
    public static function getSubClassesCounts(array $classIds): array
    {
        if (empty($classIds)) {
            return [];
        }
        $classIds = implode(",", FatUtility::int($classIds));
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->joinTable(GroupClass::DB_TBL, 'LEFT JOIN', 'grppkg.grpcls_parent = grpcls.grpcls_id', 'grppkg');
        $srch->addDirectCondition('grpcls.grpcls_id IN (' . $classIds . ')');
        $srch->addMultipleFields(['grpcls.grpcls_id', 'COUNT(*) as subclasses']);
        $srch->addGroupBy('grpcls.grpcls_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

}
