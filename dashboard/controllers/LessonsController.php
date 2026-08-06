<?php

/**
 * Lessons Controller is used for handling Lessons
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class LessonsController extends DashboardController
{

    /**
     * Initialize Lessons
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->setUserSubscription();
    }

    /**
     * Render Lessons Search Form
     */
    public function index()
    {
        $frm = LessonSearch::getSearchForm();
        $data = FatApp::getQueryStringData();
        $data['ordles_status'] = $data['ordles_status'] ?? -1;
        if (!empty($data['order_id']) || !empty($data['ordles_ordsplan_id'])) {
            $data['ordles_lesson_starttime'] = '';
        }
        $frm->fill($data);

        $this->sets([
            'frm' => $frm,
            'setMonthAndWeekNames' => true,
            'userType', $this->siteUserType,
        ]);

        $this->_template->addJs([
            'js/jquery.datetimepicker.js',
            'js/jquery.barrating.min.js',
            'js/app.timer.js',
            'js/moment.min.js',
            'js/fullcalendar-luxon.min.js',
            'js/fullcalendar.min.js',
            'js/fullcalendar-luxon-global.min.js',
            'js/fateventcalendar.js',
            'issues/page-js/common.js',
            'plans/page-js/common.js',
            'lessons/page-js/common.js',
            'attach-quizzes/page-js/index.js'
        ]);
        if (API_CALL) {
            $this->_template->render(false, false);
        } else {
            $this->_template->render();
        }
    }

    /**
     * Search & List Lessons
     */
    public function search()
    {
        $langId = $this->siteLangId;
        $userId = $this->siteUserId;
        $userType = $this->siteUserType;
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = LessonSearch::getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new LessonSearch($langId, $userId, $userType);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->applyOrderBy($post);
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $rows = $srch->fetchAndFormat();
        $this->sets([
            'post' => $post,
            'planType' => Plan::PLAN_TYPE_LESSONS,
            'recordCount' => $srch->recordCount(),
            'allLessons' => (API_CALL) ? $rows : $srch->groupDates($rows),
        ]);
        $this->_template->render(false, false, 'lessons/search-listing.php');
    }

    /**
     * Render Calendar View Page
     */
    public function calendarView()
    {
        $this->set('nowDate', MyDate::formatDate(date('Y-m-d H:i:s')));
        $this->_template->render(false, false);
    }

    /**
     * Calendar JSON
     */
    public function calendarJson()
    {
        $form = LessonSearch::getSearchForm(true);
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new LessonSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->addMultipleFields(['ordles_type', 'ordles_tlang_id', 'ordles_duration',
            'ordles_lesson_starttime', 'ordles_lesson_endtime', 'ordles_offline']);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->doNotCalculateRecords();
        FatUtility::dieJsonSuccess(['data' => $srch->fetchAndFormatCalendarData()]);
    }

    /**
     * View Lesson Detail
     * 
     * @param int $lessonId
     */
    public function view(int $lessonId, $play = 0)
    {
		if ($this->siteUserType == User::TEACHER) {
            Meeting::zoomVerificationCheck($this->siteUserId);
        }
        $srch = new LessonSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->addCondition('ordles_id', '=', $lessonId);
        $srch->applyPrimaryConditions();
        $srch->addSearchDetailFields();
        $lessons = $srch->fetchAndFormat(true);
        if (count($lessons) < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        $lesson = current($lessons);
        if ($lesson['ordles_offline'] == AppConstant::YES) {
            Message::addErrorMessage(Label::getLabel('LBL_MEETING_PAGE_CANNOT_BE_ACCESSED_WITH_OFFLINE_LESSONS'));
            FatApp::redirectUser(MyUtility::generateUrl('Lessons'));
        }
        $this->sets(['lesson' => $lesson, 'play' => $play]);
        $this->set('joinFromApp', MeetingTool::canJoinFromApp($lesson['ordles_metool_id']));
        if (API_CALL) {
            $appToken = (new AppToken())->getToken($this->siteUserId);
            $this->set('token', $appToken['apptkn_token'] ?? '');
            $this->_template->addJs(['lessons/page-js/common.js', 'js/app.timer.js', 'js/moment.min.js']);
        } else {
            if (FatApp::getConfig('CONF_ENABLE_FLASHCARD')) {
                $flashcardSrchFrm = Flashcard::getSearchForm($this->siteLangId);
                $flashcardSrchFrm->fill(['flashcard_type_id' => $lessonId]);
                $this->set('flashcardSrchFrm', $flashcardSrchFrm);
                $this->set('flashcardEnabled', AppConstant::YES);
                $this->_template->addJs('js/flashcards.js');
            }
            $this->_template->addJs([
                'issues/page-js/common.js',
                'lessons/page-js/common.js',
                'js/jquery.barrating.min.js',
                'js/app.timer.js',
                'js/moment.min.js',
                'js/fullcalendar-luxon.min.js',
                'js/fullcalendar.min.js',
                'js/fullcalendar-luxon-global.min.js',
                'js/fateventcalendar.js',
                'plans/page-js/common.js',
                'attach-quizzes/page-js/index.js'
            ]);
        }
        $this->_template->render();
    }

    /**
     * Join Meeting
     * 
     * 1. Get Lesson to join
     * 2. Initialize Meeting
     * 3. Join on Meeting Tool
     * 4. Update join Datetime
     */
    public function joinMeeting()
    {
        /* Get Lesson to join */
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        $lessonObj = new Lesson($lessonId, $this->siteUserId, $this->siteUserType);
        if (!$lesson = $lessonObj->getLessonToStart()) {
            FatUtility::dieJsonError($lessonObj->getError());
        }
        if ($this->siteUserType == User::LEARNER && is_null($lesson['ordles_teacher_starttime'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_LET_THE_TEACHER_START_LESSON'));
        }
        $tlangId = FatUtility::int($lesson['ordles_tlang_id']);
        $lesson['ordles_tlang_name'] = TeachLanguage::getLangById($tlangId, $this->siteLangId);
        $lesson['ordles_tlang_name_default'] = TeachLanguage::getLangById($tlangId, FatApp::getConfig('CONF_DEFAULT_LANG'));

        /* Initialize Meeting */
        $meetingObj = new Meeting($this->siteUserId, $this->siteUserType);
        if (!$meetingObj->initMeeting($lesson['ordles_metool_id'])) {
            FatUtility::dieJsonError($meetingObj->getError());
        }
        /* Join on Meeting Tool */
        if (!$meeting = $meetingObj->joinLesson($lesson)) {
            FatUtility::dieJsonError($meetingObj->getError());
        }
        /* Update join datetime */
        $lesson['ordles_metool_id'] = $meeting['meet_metool_id'];
        if (!$lessonObj->start($lesson)) {
            FatUtility::dieJsonError($lessonObj->getError());
        }
        FatUtility::dieJsonSuccess([
            'meeting' => $meeting,
            'msg' => Label::getLabel('LBL_JOINING_PLEASE_WAIT')
        ]);
    }

    /**
     * End Meeting
     * 
     * 1. Get Lesson To Complete
     * 2. Initialize Meeting Tool
     * 3. End on Meeting Tool
     * 4. Mark Meeting Complete
     */
    public function endMeeting()
    {
        /* Get Lesson To Complete */
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        $lessonObj = new Lesson($lessonId, $this->siteUserId, $this->siteUserType);
        if (!$lesson = $lessonObj->getLessonToComplete()) {
            FatUtility::dieJsonError($lessonObj->getError());
        }
        if ($lesson['ordles_offline'] == AppConstant::NO) {
            /* Initialize Meeting Tool */
            $meetingObj = new Meeting($this->siteUserId, $this->siteUserType);
            if (!$meetingObj->initMeeting($lesson['ordles_metool_id'])) {
                FatUtility::dieJsonError($meetingObj->getError());
            }

            /* End on Meeting Tool */
            if (!$meetingObj->endMeeting($lessonId, AppConstant::LESSON)) {
                FatUtility::dieJsonError($meetingObj->getError());
            }
        }
        /* Mark Meeting Complete */
        if (!$lessonObj->complete($lesson)) {
            FatUtility::dieJsonError($lessonObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_LESSON_ENDED_SUCCESSFULLY'));
    }

    /**
     * Playback Lesson
     */
    public function playbackLesson()
    {
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        $lesson = new Lesson($lessonId, $this->siteUserId, $this->siteUserType);
        if (!$lesson->canPlaybackLesson()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_RECORDING_NOT_FOUND'));
        }
        $meeting = Meeting::getPlaybacks($this->siteUserId, [$lessonId], AppConstant::LESSON);
        if (empty($meeting[$lessonId])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_RECORDING_NOT_FOUND'));
        }
        FatUtility::dieJsonSuccess([
            'playback_url' => $meeting[$lessonId],
            'msg' => Label::getLabel('LBL_REPLAYING_RECORDED_SESSION')
        ]);
    }

    /**
     * Render Schedule Form
     */
    public function scheduleForm()
    {
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        $lesson = new Lesson($lessonId, $this->siteUserId, $this->siteUserType);
        if (!$record = $lesson->getLessonToSchedule()) {
            FatUtility::dieJsonError($lesson->getError());
        }
        $subStartDate = '';
        $subEndDate = '';
        $subPlan = 0;
        $subdays = FatApp::getConfig('CONF_AVAILABILITY_UPDATE_WEEK_NO') * 7;
        if ($record['ordles_type'] == LESSON::TYPE_SUBCRIP) {
            $subscription = Subscription::getSubsByOrderId(
                $record['ordles_order_id'],
                ['ordsub_startdate', 'DATEDIFF(ordsub_enddate, ordsub_startdate) as subdays', 'ordsub_enddate']
            );
            if (!empty($subscription['ordsub_startdate'])) {
                $subStartDate = MyDate::formatDate($subscription['ordsub_startdate']);
                $subdays = $subscription['subdays'] + 1;
                $subEndDate = $subscription['ordsub_enddate'];
            }
        }
        $orderSubPlan =  OrderSubscriptionPlan::getActivePlan($record['order_user_id']);
        if (!empty($orderSubPlan) && !empty($record['ordles_ordsplan_id'])) {
            $subdays = MyDate::diff($orderSubPlan['ordsplan_start_date'], $orderSubPlan['ordsplan_end_date']);
            $subStartDate = MyDate::formatDate($orderSubPlan['ordsplan_start_date']);
            $subEndDate = $orderSubPlan['ordsplan_end_date'];
            $subdays = $subdays + 1;
            $subPlan = 1;
        }
        if (!$lesson->availableInSubscription($record, $orderSubPlan, $this->siteUserType)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NOT_AVAILABLE_FOR_SCHEDULE'));
        }
        $record['teacher_country_code'] = Country::getAttributesById($record['teacher_country_id'], 'country_code');
        $teacherSetting = UserSetting::getSettings($record['ordles_teacher_id']);
        $form = $this->getScheduleForm();
        $form->fill($record);
        $this->sets([
            'form' => $form,
            'subPlan' => $subPlan,
            'teacherId' => $record['ordles_teacher_id'],
            'subStartDate' => $subStartDate,
            'subEndDate' => MyDate::formatDate($subEndDate),
            'subdays' => $subdays,
            'minDateToShow' => MyDate::formatDate(date('Y-m-d H:i:s')),
            'subscription' => ($record['ordles_type'] == LESSON::TYPE_SUBCRIP) ? 1 : 0,
            'teacherBookingBefore' => FatUtility::int($teacherSetting['user_book_before'] ?? 0),
            'lesson' => $record
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Schedule Setup
     */
    public function scheduleSetup()
    {
        $posts = FatApp::getPostedData();
        $frm = $this->getScheduleForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $lesson = new Lesson($post['ordles_id'], $this->siteUserId, $this->siteUserType);
        if (!$lesson->schedule($post, $this->siteLangId)) {
            MyUtility::dieJsonError($lesson->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Render Reschedule Form
     */
    public function rescheduleForm()
    {
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        $lesson = new Lesson($lessonId, $this->siteUserId, $this->siteUserType);
        if (!$record = $lesson->getLessonToReschedule($this->siteUserType)) {
            FatUtility::dieJsonError($lesson->getError());
        }
        $subStartDate = '';
        $subEndDate = '';
        $subPlan = 0;
        $subdays = FatApp::getConfig('CONF_AVAILABILITY_UPDATE_WEEK_NO') * 7;
        if ($record['ordles_type'] == LESSON::TYPE_SUBCRIP) {
            $subscription = Subscription::getSubsByOrderId(
                $record['ordles_order_id'],
                ['ordsub_startdate', 'DATEDIFF(ordsub_enddate, ordsub_startdate) as subdays', 'ordsub_enddate']
            );
            if (!empty($subscription['ordsub_startdate'])) {
                $subStartDate = MyDate::formatDate($subscription['ordsub_startdate']);
                $subdays = $subscription['subdays'] + 1;
                $subEndDate = $subscription['ordsub_enddate'];
            }
        }
        $orderSubPlan =  OrderSubscriptionPlan::getActivePlan($record['learner_id']);
        if (!empty($orderSubPlan) && !empty($record['ordles_ordsplan_id'])) {
            $subdays = MyDate::diff($orderSubPlan['ordsplan_start_date'], $orderSubPlan['ordsplan_end_date']);
            $subStartDate = MyDate::formatDate($orderSubPlan['ordsplan_start_date']);
            $subEndDate = $orderSubPlan['ordsplan_end_date'];
            $subdays = $subdays + 1;
            $subPlan = 1;
        }
        if (!$lesson->availableInSubscription($record, $orderSubPlan, $this->siteUserType)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NOT_AVAILABLE_FOR_RESCHEDULE'));
        }
        $teacherSetting = UserSetting::getSettings($record['ordles_teacher_id']);
        $teacherBookingBefore = FatUtility::int($teacherSetting['user_book_before'] ?? 0);
        $record['teacher_country_code'] = Country::getAttributesById($record['teacher_country_id'], 'country_code');
        $form = $this->getRescheduleForm();
        if($this->siteUserType == User::LEARNER) {
            unset($record['ordles_lesson_starttime']);
            unset($record['ordles_lesson_endtime']);
        }
        $form->fill($record);
        $this->sets([
            'form' => $form,
            'teacherId' => $record['ordles_teacher_id'],
            'subPlan' => $subPlan,
            'subdays' => $subdays,
            'subStartDate' => $subStartDate,
            'subEndDate' => MyDate::formatDate($subEndDate),
            'minDateToShow' => MyDate::formatDate(date('Y-m-d H:i:s')),
            'subscription' => ($record['ordles_type'] == LESSON::TYPE_SUBCRIP) ? 1 : 0,
            'teacherBookingBefore' => $teacherBookingBefore,
            'lesson' => $record
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Reschedule Setup
     */
    public function rescheduleSetup()
    {
        $posts = FatApp::getPostedData();
        $frm = $this->getRescheduleForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $lesson = new Lesson($post['ordles_id'], $this->siteUserId, $this->siteUserType);
        if (!$lesson->reschedule($post, $this->siteLangId)) {
            MyUtility::dieJsonError($lesson->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Render Cancel Form
     */
    public function cancelForm()
    {
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        $lesson = new Lesson($lessonId, $this->siteUserId, $this->siteUserType);
        if (!$record = $lesson->getLessonToCancel(false)) {
            FatUtility::dieJsonError($lesson->getError());
        }
        $refundPercentage = ($this->siteUserType == User::TEACHER) ? 100 : 0;
        if ($this->siteUserType == User::LEARNER && FatUtility::float($record['order_net_amount']) > 0) {
            $refundPercentage = $lesson->getRefundPercentage($record['ordles_status'], $record['ordles_lesson_starttime']);
        }
        $frm = $this->getCancelForm($refundPercentage, $record['ordles_ordsplan_id']);
        $frm->fill($record);
        $this->set('frm', $frm);
        $this->set('lesson', $record);
        $this->set('refundPercentage', $refundPercentage);
        $this->_template->render(false, false);
    }

    /**
     * Cancel Setup
     */
    public function cancelSetup()
    {
        $posts = FatApp::getPostedData();
        $frm = $this->getCancelForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $lesson = new Lesson($post['ordles_id'], $this->siteUserId, $this->siteUserType);
        if (!$lesson->cancel($post, $this->siteLangId)) {
            MyUtility::dieJsonError($lesson->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_LESSON_CANCELLED_SUCCESSFULLY'));
    }

    /**
     * Render Feedback Form
     */
    public function feedbackForm()
    {
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        $lesson = new Lesson($lessonId, $this->siteUserId, $this->siteUserType);
        if (!$record = $lesson->getLessonToFeedback()) {
            MyUtility::dieJsonError($lesson->getError());
        }
        $frm = RatingReview::getFeedbackForm();
        $record['ratrev_type_id'] = $lessonId;
        $frm->fill($record);
        $this->set('frm', $frm);
        $this->set('lesson', $record);
        $this->_template->render(false, false);
    }

    /**
     * Feedback Setup
     */
    public function feedbackSetup()
    {
        $posts = FatApp::getPostedData();
        $frm = RatingReview::getFeedbackForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        
        AbusiveWord::validateContent($post['ratrev_title']." ".$post['ratrev_detail']);

        $lesson = new Lesson($post['ratrev_type_id'], $this->siteUserId, $this->siteUserType);
        $post['ratrev_lang_id'] = $this->siteLangId;
        if (!$lesson->feedback($post)) {
            MyUtility::dieJsonError($lesson->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Get Schedule Form
     * 
     * @return Form
     */
    private function getScheduleForm(): Form
    {
        $frm = new Form('scheduleFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'ordles_id')->requirements()->setRequired();
        $startTime = $frm->addHiddenField('', 'ordles_lesson_starttime');
        $startTime->requirements()->setRequired();
        $endTime = $frm->addHiddenField('', 'ordles_lesson_endtime');
        $endTime->requirements()->setRequired();
        $endTime->requirements()->setCompareWith('ordles_lesson_starttime', 'gt');
        $startTime->requirements()->setCompareWith('ordles_lesson_endtime', 'lt');
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_Confirm_It!'));
        return $frm;
    }

    /**
     * Get Reschedule Form
     * 
     * @return Form
     */
    private function getRescheduleForm(): Form
    {
        $frm = new Form('rescheduleFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'ordles_id')->requirements()->setRequired();
        $startTime = $frm->addHiddenField('', 'ordles_lesson_starttime');
        $endTime = $frm->addHiddenField('', 'ordles_lesson_endtime');
        if ($this->siteUserType == User::LEARNER) {
            $startTime->requirements()->setRequired();
            $endTime->requirements()->setRequired();
            $endTime->requirements()->setCompareWith('ordles_lesson_starttime', 'gt');
            $startTime->requirements()->setCompareWith('ordles_lesson_endtime', 'lt');
        }
        $frm->addTextArea(Label::getLabel('LBL_RESCHEDULE_REASON'), 'comment')->requirements()->setRequired();
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_CONFIRM_IT!'));
        return $frm;
    }

    /**
     * Get Cancel Form
     * 
     * @param float $refundPercentage
     * @return Form
     */
    private function getCancelForm(): Form
    {
        $frm = new Form('cancelFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $comment = $frm->addTextArea(Label::getLabel('LBL_COMMENTS'), 'comment');
        $comment->requirements()->setLength(10, 200);
        $comment->requirements()->setRequired();
        $frm->addHiddenField('', 'ordles_id')->requirements()->setRequired();
        $frm->addHtml('', 'note_text', '');
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SUBMIT'));
        return $frm;
    }

    /**
     * Check Lesson Status
     * 
     * @param int $lessonId
     */
    public function checkStatus($lessonId = 0)
    {
        $fields = ['ordles_teacher_starttime', 'ordles_lesson_endtime', 'ordles_status', 'ordles_student_starttime'];
        $srch = new LessonSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->addCondition('ordles_id', '=', FatUtility::int($lessonId));
        $srch->applyPrimaryConditions();
        $srch->addSearchDetailFields();
        $srch->addMultipleFields($fields);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $lesson = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($lesson)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST_PLEASE_REFRESH_PAGE'));
        }
        $status = $lesson['ordles_status'];
        if (Lesson::SCHEDULED == $status && User::TEACHER == $this->siteUserType) {
            if (empty($lesson['ordles_teacher_starttime']) && strtotime($lesson['ordles_lesson_endtime']) > time()) {
                FatUtility::dieJsonSuccess(['lessonStatus' => $status, 'msg' => Label::getLabel('LBL_PLEASE_JOIN_LESSON_AND_START_LESSON')]);
            } elseif (!empty($lesson['ordles_teacher_starttime']) && strtotime($lesson['ordles_lesson_endtime']) < time()) {
                FatUtility::dieJsonError(['lessonStatus' => $status, 'msg' => Label::getLabel('LBL_TIME_IS_OVER_PLEASE_END_THE_LESSON')]);
            }
        } elseif (Lesson::SCHEDULED == $status && User::LEARNER == $this->siteUserType) {
            if (empty($lesson['ordles_student_starttime']) && !empty($lesson['ordles_teacher_starttime']) && strtotime($lesson['ordles_lesson_endtime']) > time()) {
                FatUtility::dieJsonSuccess(['lessonStatus' => $status, 'msg' => Label::getLabel('LBL_TEACHER_HAS_JOINED_PLEASE_JOIN_LESSON')]);
            } elseif (!empty($lesson['ordles_student_starttime']) && empty($lesson['ordles_student_endtime']) && strtotime($lesson['ordles_lesson_endtime']) < time()) {
                FatUtility::dieJsonError(['lessonStatus' => $status, 'msg' => Label::getLabel('LBL_TIME_IS_OVER_LESSON_WILL_BE_ENDED_SOON')]);
            }
        } elseif (Lesson::COMPLETED == $status) {
            $msg = (User::LEARNER == $this->siteUserType) ? 'LBL_TEACHER_HAS_ENDED_THE_LESSON' : 'LBL_LEARNER_HAS_ENDED_THE_LESSON';
            FatUtility::dieJsonError(['msg' => Label::getLabel($msg), 'lessonStatus' => $status]);
        } elseif (Lesson::CANCELLED == $status) {
            $msg = (User::LEARNER == $this->siteUserType) ? 'LBL_TEACHER_HAS_CANCELLED_THE_LESSON' : 'LBL_LEARNER_HAS_CANCELLED_THE_LESSON';
            FatUtility::dieJsonError(['msg' => Label::getLabel($msg), 'lessonStatus' => $status]);
        } elseif (Lesson::UNSCHEDULED == $status) {
            $msg = (User::LEARNER == $this->siteUserType) ? 'LBL_TEACHER_HAS_UNSCHEDULED_THE_LESSON' : 'LBL_LEARNER_HAS_UNSCHEDULED_THE_LESSON';
            FatUtility::dieJsonError(['msg' => Label::getLabel($msg), 'lessonStatus' => $status]);
        }
        FatUtility::dieJsonSuccess(['msg' => '', 'lessonStatus' => $status]);
    }

    /**
     * Get Upcoming Lesson
     * 
     * @return array
     */
    public function upcoming()
    {
        $pageSize = FatApp::getPostedData('pagesize', FatUtility::VAR_INT, AppConstant::PAGESIZE);
        $viewType = FatApp::getPostedData('view', FatUtility::VAR_INT, AppConstant::VIEW_LISTING);
        $srch = new LessonSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->addCondition('ordles_lesson_starttime', '>=', date('Y-m-d H:i:s'));
        $srch->addCondition('ordles_status', '=', Lesson::SCHEDULED);
        $srch->addOrder('ordles_lesson_starttime');
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->setPageSize($pageSize);
        $allLessons = $srch->fetchAndFormat();
        $view = 'lessons/upcoming.php';
        if ($viewType == AppConstant::VIEW_SHORT) {
            $view = 'lessons/short-detail-listing.php';
            $allLessons = $srch->groupDates($allLessons);
        }
        $this->set('allLessons', $allLessons);
        $this->_template->render(false, false, $view);
    }

    public function end(int $id)
    {
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_LESSON_HAS_BEEN_ENDED'));
    }
}
