<?php

/**
 * Teachers Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeachersController extends MyAppController
{

    /**
     * Initialize Teachers
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Teachers
     * 
     * @param string $slug
     */
    public function index()
    {
        $postedData = FatApp::getPostedData();
        $searchSession = $_SESSION[AppConstant::SEARCH_SESSION] ?? [];
        $srchFrm = TeacherSearch::getSearchForm($this->siteLangId);
        $srchFrm->fill($postedData + $searchSession);
        unset($_SESSION[AppConstant::SEARCH_SESSION]);

        $srch = new SearchBase(TeacherStat::DB_TBL, 'testat');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'testat.testat_user_id = teacher.user_id', 'teacher');
        $srch->addCondition('teacher.user_username', '!=', "");
        $srch->addDirectCondition('teacher.user_deleted IS NULL');
        $srch->addDirectCondition('teacher.user_verified IS NOT NULL');
        $srch->addCondition('teacher.user_country_id', '>', AppConstant::NO);
        $srch->addCondition('teacher.user_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        $srch->addCondition('testat.testat_teachlang', '=', AppConstant::YES);
        $srch->addCondition('testat.testat_speaklang', '=', AppConstant::YES);
        $srch->addCondition('testat.testat_preference', '=', AppConstant::YES);
        $srch->addCondition('testat.testat_availability', '=', AppConstant::YES);
        $srch->addCondition('testat.testat_qualification', '=', AppConstant::YES);
        $srch->addCondition('testat.testat_minprice', '>', 0);
        $srch->addCondition('testat.testat_maxprice', '>', 0);
        $srch->addFld('MAX(testat_maxprice) as maxPrice');
        $srch->addFld('MIN(testat_minprice) as minPrice');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $priceRange = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('priceRange', $priceRange);
        $this->set('srchFrm', $srchFrm);
        $this->set('languages', TeachLanguage::getAllLangs($this->siteLangId, true));
        $userAddress = UserAddresses::getDefault($this->siteUserId, $this->siteLangId);
        if ($userAddress) {
            $userAddress['formatted_address'] = UserAddresses::format($userAddress);
        }
        $this->set('userAddress',$userAddress);

        $this->_template->addJs([
            'js/moment.min.js',
            'js/fateventcalendar.js',
            'js/nouislider.min.js',
        ]);
        $this->_template->addCss([
            'css/nouislider.min.css',
        ]);
        $this->_template->render(true, true, 'teachers/index.php');
    }

    /**
     * Render Teachers based on Language
     * 
     * @param string $slug
     */
    public function languages(string $slug = '')
    {
        if(empty($slug)) {
            FatApp::redirectUser(MyUtility::makeUrl('Teachers'));
        }
        $postedData = FatApp::getPostedData();
        $teachLangs = TeachLanguage::getTeachLanguages($this->siteLangId, true);
        $teachlangSlugs = array_column($teachLangs, 'tlang_slug', 'tlang_id');
        $sLanguage = [];
        if (!empty($slug)) {
            /* add selected value data */
            $sLanguage = array_filter($teachLangs, function($elem) use(&$slug) {
                return $elem['tlang_slug'] == $slug;
            });
            if (!empty($sLanguage)) {
                $sLanguage = current($sLanguage);
                $postedData['teachs'] = [$sLanguage['tlang_id']];
            }
        }
        $parentIDs = [];
        if(!empty($sLanguage['tlang_parentids'])){
            $parentIDs = explode(',', $sLanguage['tlang_parentids']);
        }
        $teachLangKeys = array_keys($teachLangs);
        if((!empty($sLanguage['tlang_parentids']) && !array_intersect($parentIDs, $teachLangKeys)) || empty($sLanguage)) {
            FatUtility::exitWithErrorCode(404);
        }
        $srchFrm = TeacherSearch::getSearchForm($this->siteLangId);
        $srchFrm->fill($postedData);
        $this->set('srchFrm', $srchFrm);
        $this->set('langSlugs', $teachlangSlugs);
        $this->set('sLanguage', $sLanguage);
        $this->_template->addJs([
            'js/moment.min.js',
            'js/fateventcalendar.js',
        ]);
        $this->_template->render(true, true);
    }

    /**
     * Find Teachers
     */
    public function search(bool $langPage = false)
    {
        $langId = $this->siteLangId;
        $userId = $this->siteUserId;
        $userType = $this->siteUserType;
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = TeacherSearch::getSearchForm($langId);
        if (!$post = $frm->getFormDataFromArray($posts, ['teachs'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new TeacherSearch($langId, $userId, $userType);
        $srch->addSearchListingFields();
        $srch->applyPrimaryConditions();
        $srch->joinSettingTabel();
        $srch->applySearchConditions($post);
        $srch->applyOrderBy($post['sorting']);
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addMultipleFields(['us.user_trial_enabled', 'IFNULL(us.user_slots, "") as user_slots']);
        $teachers = $srch->fetchAndFormat();
        $recordCount = $srch->recordCount();
        $this->set('post', $post);
        $this->set('langPage', $langPage);
        $this->set('teachers', $teachers);
        $this->set('recordCount', $recordCount);
        $this->set('pageCount', ceil($recordCount / $posts['pagesize']));
        $this->_template->render(false, false);
    }

    /**
     * Render Teacher Detail Page
     * 
     * @param string $username
     */
    public function view($username)
    {
        $srch = new TeacherSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->addMultipleFields(['us.user_trial_enabled', 'IFNULL(us.user_slots, "") as user_slots']);
        $srch->addCondition('teacher.user_username', '=', $username);
        $srch->applyPrimaryConditions();
        $srch->joinSettingTabel();
        $srch->addSearchListingFields();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $teachers = $srch->fetchAndFormat(true);
        if (empty($teachers)) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $teacher = current($teachers);
        $teacher['user_slots'] = json_decode($teacher['user_slots'], 1);
        if (empty($teacher['user_slots'])) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $teacher['proficiencyArr'] = SpeakLanguageLevel::getAllLangLevels($this->siteLangId, true);
        if ($teacher['testat_reviewes'] > 0) {
            $reviewFrm = $this->getReviewForm();
            $reviewFrm->fill(['teacher_id' => $teacher['user_id']]);
            $this->set('reviewFrm', $reviewFrm);
        }
        /* get user free trial status */
        $freeTrialEnabled = ($teacher['user_trial_enabled'] && FatApp::getConfig('CONF_ENABLE_FREE_TRIAL', FatUtility::VAR_INT, 0));
        $isFreeTrailAvailed = ($freeTrialEnabled) ? Lesson::isTrailAvailed($this->siteUserId, $teacher['user_id']) : true;

        $userPreferences = Preference::getUserPreferences($teacher['user_id'], $this->siteLangId);
        $preferencesData = [];
        foreach ($userPreferences as $value) {
            $preferencesData[$value['prefer_type']][$value['uprefer_prefer_id']] = $value;
        }
        unset($userPreferences);

        $qualifications = (new UserQualification(0, $teacher['user_id']))->getUQualification();
        $userQualifications = [];
        foreach ($qualifications as $value) {
            $userQualifications[$value['uqualification_experience_type']][$value['uqualification_id']] = $value;
        }
        $class = new GroupClassSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $courseObj = new CourseSearch($this->siteLangId, $this->siteUserId, 0);
        $moreCourses = $courseObj->getMoreCourses($teacher['user_id']);
        /* checkout form */
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        $checkoutForm = $cart->getCheckoutForm([0 => Label::getLabel('LBL_NA')]);
        $checkoutForm->fill(['order_type' => Order::TYPE_COURSE]);
        $teacher['user_minimum_duration'] = min(FatUtility::int($teacher['user_slots']));

        /* Get Teacher Reviews Respective Percentage */
        $reviewsData = $this->getTeacherReviewsPercentage($teacher['user_id']) ?? [];      

        $this->sets([
            'teacher' => $teacher,
            'reviewsData' => $reviewsData,
            'isFreeTrailAvailed' => $isFreeTrailAvailed,
            'userPreferences' => $preferencesData,
            'preferencesType' => Preference::getPreferenceTypeArr(),
            'userQualifications' => $userQualifications,
            'qualificationType' => UserQualification::getExperienceTypeArr(),
            'moreCourses' => $moreCourses,
            'checkoutForm' => $checkoutForm,
            'bookingBefore' => FatApp::getConfig('CONF_CLASS_BOOKING_GAP'),
            'classes' => $class->getUpcomingClasses(['teacher_id' => $teacher['user_id']]),
            'userLangData' => UserTeachLanguage::getUserTeachLangs($this->siteLangId, $teacher['user_id']),
            'freeTrialEnabled' => $freeTrialEnabled
        ]);
        if (API_CALL) {
            $this->set('reviews', (new RatingReview($teacher['user_id']))->getReviews());
        }
        $this->_template->addJs([
            'js/moment.min.js',
            'js/fateventcalendar.js'
        ]);
        $this->_template->render();
    }

    /**
     * Render Teacher reviews
     */
    public function reviews()
    {
        $frm = $this->getReviewForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(Label::getLabel('LBL_CANNOT_LOAD_REVIEWS'));
        }
        $srch = new SearchBase(RatingReview::DB_TBL, 'ratrev');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id=ratrev.ratrev_user_id', 'learner');
        $srch->addCondition('ratrev.ratrev_status', '=', RatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_teacher_id', '=', $post['teacher_id']);
        if (!Course::isEnabled()) {
            $srch->addCondition('ratrev.ratrev_type', '!=', AppConstant::COURSE);
        }
        $srch->addMultipleFields([
            'user_first_name',
            'user_last_name',
            'ratrev_id',
            'ratrev_user_id',
            'ratrev_title',
            'ratrev_detail',
            'ratrev_overall',
            'ratrev_created',
        ]);
        $sorting = strtoupper(FatApp::getPostedData('sorting', FatUtility::VAR_STRING, RatingReview::SORTBY_NEWEST));
        $sorting = ($sorting == RatingReview::SORTBY_OLDEST) ? RatingReview::SORTBY_OLDEST : RatingReview::SORTBY_NEWEST;
        $srch->addOrder('ratrev.ratrev_created', $sorting);
        $srch->setPageSize(AppConstant::PAGESIZE);
        $srch->setPageNumber($post['pageno']);
        $reviews = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($reviews as &$review) {
            $review['ratrev_created'] = MyDate::convert($review['ratrev_created']);
        }
        $this->set('reviews', $reviews);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('postedData', $post);
        $this->_template->render(false, false);
    }

    /**
     * Get Review Form
     * 
     * @return Form
     */
    private function getReviewForm(): Form
    {
        $frm = new Form('reviewFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'teacher_id');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $frm->addHiddenField('', 'sorting', RatingReview::SORTBY_NEWEST);
        $frm->addHiddenField('', 'pageno', 1);
        return $frm;
    }

    /**
     * Render Calendar View
     */
    public function viewCalendar($detail = 0)
    {
        $teacherId = FatApp::getPostedData('teacherId', FatUtility::VAR_INT, 0);
        $user = new User($teacherId);
        if (!$teacher = $user->validateTeacher($this->siteLangId, $this->siteUserId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->sets(['teacher' => $teacher, 'detail' => $detail]);
        $this->_template->render(false, false);
    }

    /**
     * Check Slot Availability
     * 
     * @param type $teacherId
     */
    public function checkSlotAvailability($teacherId = 0)
    {
        $teacherId = FatUtility::int($teacherId);
        $form = $this->getAvailabilityForm();
        if ($teacherId < 1 || !$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $startDateTime = MyDate::formatToSystemTimezone($post['start']);
        $endDateTime = MyDate::formatToSystemTimezone($post['end']);
        if (strtotime($startDateTime) < time()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_START_TIME_MUST_BE_GREATER_THEN_CURRENT_TIME'));
        }
        /** check teacher availability */
        $availability = new Availability($teacherId);
        if (!$availability->isAvailable($startDateTime, $endDateTime)) {
            FatUtility::dieJsonError($availability->getError());
        }
        /** check teacher slot availability */
        if (!$availability->isUserAvailable($startDateTime, $endDateTime)) {
            FatUtility::dieJsonError($availability->getError());
        }
        /** check Learner slot availability */
        if ($this->siteUserId > 0) {
            $availability = new Availability($this->siteUserId);
            if (!$availability->isUserAvailable($startDateTime, $endDateTime)) {
                FatUtility::dieJsonError($availability->getError());
            }
        }
        return true;
    }

    /**
     * Get teacher scheduled sessions for dashboard calendar
     *
     * @param int $userId
     * @return json
     */
    public function getTeacherScheduledSessions(int $userId)
    {
        $userType = Fatapp::getPostedData('user_type', FatUtility::VAR_INT, 0);
        $start = Fatapp::getPostedData('start', FatUtility::VAR_STRING, '');
        $end = Fatapp::getPostedData('end', FatUtility::VAR_STRING, '');
        $sessions = $this->getScheduledSessions($userId, $userType, $start, $end);
        FatUtility::dieJsonSuccess(['data' => $sessions]);
    }

    /**
     * Get Scheduled Sessions
     *
     * @param int $userId
     * @param int $userType
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getScheduledSessions(int $userId, int $userType, string $start, string $end)
    {
        $userId = FatUtility::int($userId);
        if ($userId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (empty($start) || empty($end)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $start = MyDate::formatToSystemTimezone($start);
        $end = MyDate::formatToSystemTimezone($end);
        $userIds = [$userId];
        if ($this->siteUserId > 0 && $this->siteUserId != $userId) {
            array_push($userIds, $this->siteUserId);
        }
        $groupClasses = [];
        $classes = [];
        $lessons =  $this->getScheduledLessons($userIds, $start, $end, $userType);
        $classes = $this->getScheduledClasses($userIds, $start, $end, $userType);

        $classIds = array_column($classes, 'classId', 'classId');
        $groupClasses = $this->getClasses($userIds, $start, $end, $classIds);

        $gcEvents = $this->getGoogleCalendarEvents($userIds, $start, $end);
        return array_merge($lessons, $classes, $groupClasses, $gcEvents);
    }

    /**
     * Get Scheduled Lessons
     * 
     * @param array $userIds
     * @param string $start
     * @param string $end
     * @param int $userType
     * @return array
     */
    private function getScheduledLessons(array $userIds, string $start, string $end, int $userType = 0)
    {
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id ', 'orders');
        if ($userType == User::LEARNER) {
            $srch->addCondition('order_user_id', 'IN', $userIds);
        } elseif ($userType == User::TEACHER) {
            $srch->addCondition('ordles_teacher_id', 'IN', $userIds);
        } else {
            $cond = $srch->addCondition('ordles_teacher_id', 'IN', $userIds);
            $cond->attachCondition('order_user_id', 'IN', $userIds);
        }
        $srch->addCondition('ordles_status', '=', Lesson::SCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordles_lesson_starttime', '<', $end);
        $srch->addCondition('ordles_lesson_endtime', '>', $start);
        $srch->addMultipleFields(['ordles_lesson_starttime', 'ordles_lesson_endtime']);
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $jsonArr = [];
        while ($record = FatApp::getDb()->fetch($resultSet)) {
            array_push($jsonArr, [
                "title" => "",
                "start" => MyDate::formatDate($record['ordles_lesson_starttime']),
                "end" => MyDate::formatDate($record['ordles_lesson_endtime']),
                "className" => "sch_data booked-slot"
            ]);
        }
        return $jsonArr;
    }

    /**
     * Get Scheduled Classes
     * 
     * @param array $userIds
     * @param string $start
     * @param string $end
     * @param int $userType
     * @return array
     */
    private function getScheduledClasses(array $userIds, string $start, string $end, int $userType = 0)
    {
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(OrderClass::DB_TBL, 'INNER JOIN', 'ordcls.ordcls_order_id = orders.order_id', 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id =  ordcls.ordcls_grpcls_id', 'grpcls');
        if ($userType == User::LEARNER) {
            $srch->addCondition('order_user_id', 'IN', $userIds);
        } elseif ($userType == User::TEACHER) {
            $srch->addCondition('grpcls_teacher_id', 'IN', $userIds);
        } else {
            $cond = $srch->addCondition('grpcls_teacher_id', 'IN', $userIds);
            $cond->attachCondition('order_user_id', 'IN', $userIds);
        }
        $srch->addCondition('grpcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->addCondition('ordcls_status', '=', OrderClass::SCHEDULED);
        $srch->addCondition('grpcls_start_datetime', '<', $end);
        $srch->addCondition('grpcls_end_datetime', '>', $start);
        $srch->addMultipleFields(['grpcls_start_datetime', 'grpcls_end_datetime', 'grpcls_id']);
        $srch->addGroupBy('grpcls_id');
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $jsonArr = [];
        while ($record = FatApp::getDb()->fetch($resultSet)) {
            array_push($jsonArr, [
                "title" => "",
                'classId' => $record['grpcls_id'],
                "start" => MyDate::formatDate($record['grpcls_start_datetime']),
                "end" => MyDate::formatDate($record['grpcls_end_datetime']),
                "className" => "sch_data booked-slot"
            ]);
        }
        return $jsonArr;
    }

    /**
     * Get Classes
     * 
     * @param array $userIds
     * @param string $start
     * @param string $end
     * @param array $classIds
     * @return array
     */
    private function getClasses(array $userIds, string $start, string $end, array $classIds)
    {
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->addCondition('grpcls_teacher_id', 'IN', $userIds);
        $srch->addCondition('grpcls_status', '=', GroupClass::SCHEDULED);
        $srch->addCondition('grpcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->addCondition('grpcls_start_datetime', '< ', $end);
        $srch->addCondition('grpcls_end_datetime', ' > ', $start);
        $srch->addMultipleFields(['grpcls_start_datetime', 'grpcls_end_datetime', 'grpcls_id']);
        $resultSet = $srch->getResultSet();
        $jsonArr = [];
        while ($record = FatApp::getDb()->fetch($resultSet)) {
            if (array_key_exists($record['grpcls_id'], $classIds)) {
                continue;
            }
            array_push($jsonArr, [
                "title" => "",
                "start" => MyDate::formatDate($record['grpcls_start_datetime']),
                "end" => MyDate::formatDate($record['grpcls_end_datetime']),
                "className" => "sch_data booked-slot"
            ]);
        }
        return $jsonArr;
    }

    public function availability(int $userId)
    {
        $this->_template->render(false, false);
    }

    /**
     * Get Availability Form
     * 
     * @return Form
     */
    private function getAvailabilityForm(): Form
    {
        $frm = new Form('availabilityForm');
        $frm = CommonHelper::setFormProperties($frm);
        $startFld = $frm->addRequiredField(Label::getLabel('LBL_START_TIME'), 'start');
        $startFld->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
        $endFld = $frm->addRequiredField(Label::getLabel('LBL_END_TIME'), 'end');
        $endFld->requirements()->setRegularExpressionToValidate(AppConstant::DATE_TIME_REGEX);
        $endFld->requirements()->setCompareWith('start', 'gt', Label::getLabel('LBL_START_TIME'));
        return $frm;
    }

    /**
     * Google Events Watch Webhook
     * 
     * @param int $userId
     */
    public function googleEventWatch($userId)
    {
        $userId = FatUtility::int($userId);
        $userData = User::getAttributesById($userId, ['user_id']);
        if (empty($userData)) {
            exit();
        }
        $headers = FatApp::getApacheRequestHeaders();
        $channelId = '';
        foreach ($headers as $key => $value) {
            if (strtolower($key) == 'x-goog-channel-id') {
                $channelId = $value;
                break;
            }
        }
        $srch = new SearchBase(UserSetting::DB_TBL, 'us');
        $srch->addMultipleFields([
            'user_google_event_sync_date',
            'user_google_event_watch_id',
            'user_google_event_watch_expiration',
            'user_google_event_sync_token',
            'user_google_token'
        ]);
        $srch->addCondition('user_id', '=', $userId);
        $srch->addCondition('user_google_token', '!=', '');
        $srch->addCondition('user_google_event_sync_token', '!=', '');
        $srch->addCondition('user_google_event_watch_id', '=', $channelId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $settings = FatApp::getDb()->fetch($srch->getResultSet());

        $resourceState = (!empty($headers['X-Goog-Resource-State'])) ? $headers['X-Goog-Resource-State'] : '';
        if ($resourceState == 'sync') {
            exit();
        }
        $googleCalendar = new GoogleCalendar($userId);
        $googleCalendar->incrementalSync($settings['user_google_token'], $settings['user_google_event_sync_token']);
        return true;
    }

    public function getGoogleCalendarEvents(array $userIds, string $startTime, string $endTime)
    {
        $srch = new SearchBase(GoogleCalendarEvent::DB_TBL, 'gocaev');
        $srch->addCondition('gocaev_user_id', 'IN', $userIds);
        $srch->addCondition('gocaev_starttime', '<', $endTime);
        $srch->addCondition('gocaev_endtime', '>', $startTime);
        $srch->addCondition('gocaev_record_type', '=', GoogleCalendarEvent::GOOGLE_EVENTS);
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $jsonArr = [];
        while ($record = FatApp::getDb()->fetch($resultSet)) {
            array_push($jsonArr, [
                "title" => '',
                "start" =>  MyDate::formatDate($record['gocaev_starttime']),
                "end" => MyDate::formatDate($record['gocaev_endtime']),
                "className" => "sch_data booked-slot",
            ]);
        }
        return $jsonArr;
    }

    public function getTeacherReviewsPercentage($teacherId)
    {
        $reviewsData = [];
        $srch = new SearchBase(RatingReview::DB_TBL, 'ratrev');
        $srch->addCondition('ratrev.ratrev_status', '=', RatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_teacher_id', '=', $teacherId);
        $srch->addMultipleFields([
            'COUNT(IF(ratrev_overall = 5,1,null)) as rating_five',
            'COUNT(IF(ratrev_overall = 4,1,null)) as rating_four',
            'COUNT(IF(ratrev_overall = 3,1,null)) as rating_three',
            'COUNT(IF(ratrev_overall = 2,1,null)) as rating_two',
            'COUNT(IF(ratrev_overall = 1,1,null)) as rating_one',
        ]);
        $reviews = FatApp::getDb()->fetch($srch->getResultSet());
        if($reviews) {
            $reviewsTotal = array_sum($reviews);
            if ($reviewsTotal > 0) {
                foreach($reviews as $key => $review) {
                    switch($key) {
                        case 'rating_five':
                            $rateVal = 5;
                            break;
                        case 'rating_four':
                            $rateVal = 4;
                            break;
                        case 'rating_three':
                            $rateVal = 3;
                            break;
                        case 'rating_two':
                            $rateVal = 2;
                            break;
                        case 'rating_one':
                            $rateVal = 1;
                            break;
                        default:
                            break;
                    }
                    $reviewsData[$rateVal] = round(($review/$reviewsTotal) * 100, 2);
                }
            }
        }
        return $reviewsData;
    }

    /**
     * Display Teacher Availability
     * @param mixed $teacherId
     * 
     * @return void
     */
    public function teacherAvailability($teacherId)
    {
        if($teacherId < 1) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $teacherData = User::getAttributesById($teacherId, ['user_first_name', 'user_last_name']);
        $teacherName = $teacherData['user_first_name'] . (!empty($teacherData['user_last_name']) ? ' ' . $teacherData['user_last_name'] : '');;
        $this->set('minDateToShow', MyDate::formatDate(date('Y-m-d')));
        $this->set('teacherId', $teacherId);
        $this->set('teacherName', $teacherName);
        $this->set('userTimeZone', $this->siteTimezone);
        $this->_template->render(false, false);
    }

    /**
     * Fetch and Display Teacher Availability Slots
     * 
     * @param int $teacherId
     * @param string $selectedDate
     * @param int $slotsViewType
     * 
     * @return void
     */
    public function getAvailabilitySlots(int $teacherId, string $selectedDate, int $slotsViewType = AppConstant::AVAIL_VIEW_ONLY)
    {
        $this->setUserSubscription();
        $selectedDuration = FatApp::getPostedData('ordles_duration', FatUtility::VAR_INT, 0);
        $useSubEndDate = false;
        if (empty($teacherId) || empty($selectedDate)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $timezone = User::getAttributesById($this->siteUserId, 'user_timezone');
        $timezone = !empty($timezone) ? $timezone : MyUtility::getSiteTimezone();
        $time = MyDate::formatDate(date('Y-m-d H:i:s'), ' H:i:s');
        if (strtotime($selectedDate) > strtotime(MyDate::formatDate(date('Y-m-d H:i:s'), ' Y-m-d'))) {
            $time = ' 00:00:00';
        }
        
        $startdate = $selectedDate . $time;
        $enddate = $selectedDate . ' 23:59:59';

        $user = UserSetting::getSettings($teacherId, ['user_book_before', 'IFNULL(user_slots, "") as user_slots']);

        /* End date for Recurring */
        $calendarDays = FatApp::getConfig('CONF_AVAILABILITY_UPDATE_WEEK_NO') * 7;
        if (FatApp::getPostedData('ordles_type', FatUtility::VAR_INT, 0) == Lesson::TYPE_SUBCRIP) {
            $subEndDate = FatApp::getPostedData('ordles_subenddate', FatUtility::VAR_STRING, '');
            if (!empty($subEndDate)) {
                $enddate = $subEndDate;
                $useSubEndDate = true;
            } else {        
                $calendarDays = FatApp::getConfig('CONF_RECURRING_SUBSCRIPTION_WEEKS') * 7;
                $initialDateTime = MyDate::formatDate(date('Y-m-d H:i:s'), 'Y-m-d H:i:s', $timezone);
                if($selectedDate == date('Y-m-d', strtotime($initialDateTime . ' + ' . $calendarDays . ' days'))) {
                    $enddate = date('Y-m-d H:i:s', strtotime($initialDateTime . ' + ' . $calendarDays . ' days'));
                    $useSubEndDate = true;
                }
            }
        }
        /* End date for Subscription Plan */
        if(!empty($this->activePlan)) {
            $subPlanEndDate = MyDate::formatDate($this->activePlan['ordsplan_end_date'], 'Y-m-d H:i:s', $timezone);
            if($selectedDate == date('Y-m-d', strtotime($subPlanEndDate))) {
                $enddate = $subPlanEndDate;
                $useSubEndDate = true;
            }
        }
        $startdateUtc = MyDate::formatToSystemTimezone($startdate, 'Y-m-d H:i:s', $timezone);
        $enddateUtc = MyDate::formatToSystemTimezone($enddate, 'Y-m-d H:i:s', $timezone);
        $avail = new Availability($teacherId);

        $scheduledData = $this->getScheduledSessions($teacherId, 0, $startdate, $enddate);
        $userAvailability = $avail->getAvailability($startdateUtc, $enddateUtc);
        $selectedDuration = empty($selectedDuration) ? 15 : $selectedDuration;
        $userData = ['user_book_before' => $user['user_book_before'] ?? 0, 'duration' => $selectedDuration, 'timezone' => $timezone, 'useSubEndDate' => $useSubEndDate];
        $availability = Availability::getAvailabiltySlots($startdate, $enddate, $scheduledData, $userAvailability, $userData);
        
        $availabilityData = array_values($availability);
        $this->set('availabilityData', $availabilityData);
        $this->set('dateForHeading', MyDate::formatDate($startdateUtc, 'D, M d', $timezone));
        if($slotsViewType == AppConstant::AVAIL_VIEW_BOOKING) {
            $this->_template->render(false, false, 'cart/_partial/availabilitySlotsForBooking.php');
        } else if($slotsViewType == AppConstant::AVAIL_VIEW_SCHEDULE) {
            $this->_template->render(false, false, '_partial/availabilitySlotsForScheduling.php');
        } else {
            $this->_template->render(false, false, 'teachers/_partial/getAvailabilitySlots.php');
        }
    }

    /**
     * Fetch Teacher Meta Details
     * @param int $teacherId
     * @return mixed
     */
    public function fetchTeacherMeta(int $teacherId)
    {
        $username = User::getAttributesById($teacherId, 'user_username');
        $teacherMetaId = MetaTag::getMetaTag(MetaTag::META_GROUP_TEACHER, $username);
        $srch = new SearchBase(MetaTag::DB_LANG_TBL, 'mtlang');
        $srch->addCondition('mtlang.metalang_meta_id', '=', $teacherMetaId['meta_id']);
        $srch->addCondition('mtlang.metalang_lang_id', '=', $this->siteLangId);
        $srch->addFld('mtlang.meta_title');
        $srch->doNotCalculateRecords();
        if(!$teacherMeta = FatApp::getDb()->fetch($srch->getResultSet())) {
            MyUtility::dieJsonError([]);
        } else {
            MyUtility::dieJsonSuccess($teacherMeta);
        }
    }
}
