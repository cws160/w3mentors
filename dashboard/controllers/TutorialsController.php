<?php

/**
 * This Controller is used for handling course learning process
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class TutorialsController extends DashboardController
{

    /**
     * Initialize Tutorials
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (!Course::isEnabled()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Start Course
     *
     * @param int $ordcrsId
     */
    public function start(int $ordcrsId)
    {
        $order = new OrderCourse($ordcrsId, $this->siteUserId);
        if (!$order->getOrderCourseById()) {
            FatUtility::exitWithErrorCode(404);
        }
        /* check if already started */
        $srch = new SearchBase(CourseProgress::DB_TBL);
        $srch->addCondition('crspro_ordcrs_id', '=', $ordcrsId);
        $srch->addMultipleFields(['crspro_id', 'crspro_started', 'crspro_ordcrs_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (!$data = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::exitWithErrorCode(404);
        }
        if (empty($data['crspro_started'])) {
            $db = FatApp::getDb();
            $db->startTransaction();

            $progress = new CourseProgress($data['crspro_id']);
            $progress->assignValues(['crspro_started' => date('Y-m-d'), 'crspro_status' => CourseProgress::IN_PROGRESS]);
            if (!$progress->save()) {
                FatUtility::exitWithErrorCode(404);
            }

            $ordcrs = new OrderCourse($data['crspro_ordcrs_id']);
            $ordcrs->setFldValue('ordcrs_status', OrderCourse::IN_PROGRESS);
            if (!$ordcrs->save()) {
                $db->rollbackTransaction();
                FatUtility::exitWithErrorCode(404);
            }
            $db->commitTransaction();
        }
        FatApp::redirectUser(MyUtility::generateUrl('Tutorials', 'index', [$data['crspro_id']]));
    }

    /**
     * Render Study Page with course progress details
     *
     * @param int $progressId
     */
    public function index(int $progressId)
    {
        $progressData = CourseProgress::getAttributesById($progressId, ['crspro_ordcrs_id', 'crspro_lecture_id', 'crspro_progress', 'crspro_completed']);

        $srch = new OrderCourseSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applyPrimaryConditions();
        $srch->addCondition('ordcrs.ordcrs_id', '=', $progressData['crspro_ordcrs_id']);
        $srch->addCondition('ordcrs.ordcrs_status', '!=', OrderCourse::CANCELLED);
        $srch->addCondition('orders.order_user_id', '=', $this->siteUserId);
        $srch->addMultipleFields([
            'ordcrs.ordcrs_id',
            'ordcrs.ordcrs_course_id',
            'ordcrs.ordcrs_certificate_number',
            'orders.order_user_id',
            'course_quilin_id',
            'course.course_id',
            'course_certificate',
            'course_certificate_type',
            'ordcrs_status',
            'crspro_completed',
            'course_cate_id',
            'course_subcate_id',
            'ordcrs_reviewed',
            'crspro_progress'
        ]);
        $order = $srch->fetchAndFormat();
        $order = current($order);
        if (empty($order)) {
            FatUtility::exitWithErrorCode(404);
        }

        $courseId = $order['ordcrs_course_id'];
        /* fetch course details */
        $courseObj = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course = $courseObj->get()) {
            FatUtility::exitWithErrorCode(404);
        }
        /* fetch section and lectures list */
        $srch = new SectionSearch($this->siteLangId, $this->siteUserId, User::LEARNER);
        $srch->applyPrimaryConditions();
        $srch->addCondition('section.section_course_id', '=', $courseId);
        $srch->addSearchListingFields();
        $srch->addOrder('section.section_order', 'ASC');
        if (!$sections = $srch->fetchAndFormat()) {
            FatUtility::exitWithErrorCode(404);
        }
        /* format lectures stats */
        $progress = new CourseProgress($progressId);
        $lectureStats = $progress->getLectureStats($sections);

        $editRequest = $courseObj->getLatestEditRequest();
        $requestStatus = $requestDate = '';
        if (!empty($editRequest) && $courseObj->checkIfEditRequestValid($editRequest)) {
            $requestStatus = Course::EDIT_UPDATES_INPROGRESS;
            $requestDate = MyDate::formatDate(date('Y-m-d H:i:s', strtotime($editRequest['coedre_updated'] . ' + ' . $editRequest['coedre_duration'] . ' days')));
        } elseif (CourseUtility::isContentUpdated($course['course_status'], $course['course_updated'])) {
            $requestStatus = Course::EDIT_UPDATES_COMPLETE;
            $requestDate = MyDate::formatDate($course['course_updated']);
        }
        
        /* get quiz */
        $this->set('quiz', $courseObj->getQuiz($course['course_quilin_id'])); 
        $this->sets([
            'course' => $course,
            'canDownloadCertificate' => $order['can_download_certificate'],
            'sections' => $sections,
            'progress' => $progressData,
            'progressId' => $progressId,
            'lectureStats' => $lectureStats,
            'archivedLectures' => Lecture::getArchivedByCourseId($courseId),
            'requestDate' => $requestDate,
            'editRequestStatus' => $requestStatus
        ]);
        $this->_template->addJs(['js/jquery.barrating.min.js', 'js/common_ui_functions.js']);
        $this->_template->render();
    }

    /**
     * Find next & previous lecture
     *
     * @param int $next
     * @return json
     */
    public function getLecture(int $next = AppConstant::YES)
    {
        $progressId = FatApp::getPostedData('progress_id', FAtUtility::VAR_INT, 0);
        $data = CourseProgress::getAttributesById($progressId, [
            'crspro_lecture_id',
            'crspro_ordcrs_id',
            'crspro_progress'
        ]);
        $ordcrs = new OrderCourse($data['crspro_ordcrs_id'], $this->siteUserId);
        if (!$order = $ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_LECTURE_NOT_FOUND'));
        }
        $data['crspro_course_id'] = $order['ordcrs_course_id'];
        $progress = new CourseProgress($progressId);
        $lectureId = $progress->getLecture($data, $next);
        FatUtility::dieJsonSuccess([
            'previous_lecture_id' => $data['crspro_lecture_id'],
            'next_lecture_id' => $lectureId,
        ]);
    }

    /**
     * Set current active lecture
     * Get lecture data
     *
     * @param int $lectureId
     * @param int $progressId
     */
    public function getLectureData(int $lectureId, int $progressId)
    {
        $progress = new CourseProgress($progressId);
        if (!$progress->isLectureValid($lectureId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = CourseProgress::getAttributesById($progressId, [
            'crspro_lecture_id',
            'crspro_ordcrs_id',
            'crspro_progress'
        ]);
        $ordcrs = new OrderCourse($data['crspro_ordcrs_id'], $this->siteUserId);
        if (!$ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_LECTURE_NOT_FOUND'));
        }
        if (!$progress->setCurrentLecture($lectureId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNABLE_TO_RENDER_NEXT_LECTURE._PLEASE_TRY_AGAIN'));
        }

        /* get previous and next lectures */
        $lectureIds = $progress->getNextPrevLectures();

        /* get lecture content */
        $srch = new LectureSearch($this->siteLangId);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addCondition('lecture.lecture_id', 'IN', [$lectureId, $lectureIds['next'], $lectureIds['previous']]);
        $lectures = $srch->fetchAndFormat();
        $lecture = isset($lectures[$lectureId]) ? $lectures[$lectureId] : [];

        /* get quiz id */
        $quizLinkId = 0;
        if ($lecture && !isset($lectures[$lectureIds['next']])) {
            $quizLinkId = Course::getAttributesById($lecture['lecture_course_id'], 'course_quilin_id');
        }

        /* get lecture resources */
        $resources = [];
        if (!empty($lecture)) {
            $lectureObj = new Lecture($lecture['lecture_id']);
            $resources = $lectureObj->getResources();
        }

        /* get lecture video */
        $resource = new Lecture($lectureId);
        $video = $resource->getMedia(Lecture::TYPE_RESOURCE_EXTERNAL_URL);
        $videoUrl = '';
        if (!empty($video['lecsrc_link'])) {
            $streamer = new VideoStreamer();
            if (!$videoUrl = $streamer->getUrl($video['lecsrc_link'])) {
                FatUtility::dieJsonError($streamer->getError());
            }
        }

        /* get progress data */
        $progData = CourseProgress::getAttributesById($progressId, ['IFNULL(crspro_covered, "") as crspro_covered', 'crspro_progress']);
        $this->sets([
            'lecture' => $lecture,
            'previousLecture' => isset($lectures[$lectureIds['previous']]) ? $lectures[$lectureIds['previous']] : [],
            'nextLecture' => isset($lectures[$lectureIds['next']]) ? $lectures[$lectureIds['next']] : [],
            'resources' => $resources,
            'progressId' => $progressId,
            'progData' => $progData,
            'videoUrl' => $videoUrl,
            'quizLinkId' => $quizLinkId,
        ]);
        $this->_template->render(false, false, 'tutorials/get-lecture.php');
    }

    /**
     * Get Archived lecture data
     *
     * @param int $lectureId
     * @param int $progressId
     */
    public function getArchivedLectureData(int $lectureId)
    {

        /* get lecture content */
        $srch = new LectureSearch($this->siteLangId);
        $srch->addSearchListingFields();
        $srch->addCondition('lecture.lecture_id', 'IN', [$lectureId]);
        $lectures = $srch->fetchAndFormat();
        $lecture = isset($lectures[$lectureId]) ? $lectures[$lectureId] : [];

        /* get lecture resources */
        $resources = [];
        if (!empty($lecture)) {
            $lectureObj = new Lecture($lecture['lecture_id']);
            $resources = $lectureObj->getResources();
        }

        /* get lecture video */
        $resource = new Lecture($lectureId);
        $video = $resource->getMedia(Lecture::TYPE_RESOURCE_EXTERNAL_URL);
        $videoUrl = '';
        if(!empty($video['lecsrc_link'])) {
            $streamer = new VideoStreamer();
            if(!$videoUrl = $streamer->getUrl($video['lecsrc_link'])) {
                FatUtility::dieJsonError($streamer->getError());
            }
        }
        $this->sets([
            'lecture' => $lecture,
            'resources' => $resources,
            'videoUrl' => $videoUrl
        ]);
        $this->_template->render(false, false, 'tutorials/get-archived-lecture.php');
    }

    /**
     * Get lecture data
     *
     * @param int $lectureId
     * @param int $progressId
     */
    public function getVideo(int $lectureId, int $progressId)
    {
        $srch = new SearchBase(CourseProgress::DB_TBL);
        $srch->addCondition('crspro_id', '=', $progressId);
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs_id = crspro_ordcrs_id');
        $srch->addMultipleFields([
            'crspro_lecture_id',
            'ordcrs_course_id AS crspro_course_id',
            'crspro_progress'
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (empty(FatApp::getDb()->fetch($srch->getResultSet()))) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* get previous and next lectures */
        $progress = new CourseProgress($progressId);
        $lectureIds = $progress->getNextPrevLectures();
        /* get lecture content */
        $srch = new LectureSearch($this->siteLangId);
        $cnd = $srch->addCondition('lecture.lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $cnd->attachCondition('lecture_archived', '=', AppConstant::YES);
        $srch->addMultipleFields([
            'lecture_title',
            'lecture_id',
            'lecture_order',
            'lecture_course_id',
            'lecture_archived'
        ]);
        $srch->addCondition('lecture.lecture_id', 'IN', [$lectureId, $lectureIds['next'], $lectureIds['previous']]);
        $lectures = $srch->fetchAndFormat();
        $lecture = isset($lectures[$lectureId]) ? $lectures[$lectureId] : [];
        $isArchived = $lecture['lecture_archived'] ?? 0;

        /* get quiz id */
        $quizLinkId = 0;
        if ($lecture && !isset($lectures[$lectureIds['next']])) {
            $quizLinkId = Course::getAttributesById($lecture['lecture_course_id'], 'course_quilin_id');
            $quizTitle = QuizLinked::getAttributesById($quizLinkId, 'quilin_title');
            $this->set('quizTitle', $quizTitle);
        }

        /* get lecture video */
        $resource = new Lecture($lectureId);
        $video = $resource->getMedia(Lecture::TYPE_RESOURCE_EXTERNAL_URL);

        $videoUrl = $error = '';
        if (!empty($video)) {
            $streamer = new VideoStreamer();
            if (!$videoUrl = $streamer->getUrl($video['lecsrc_link'], true)) {
                $error = ucwords($streamer->getError() ?? '');
            }
        }
        $next = '';
        if (isset($lectures[$lectureIds['next']])) {
            $next = $lectures[$lectureIds['next']]['lecture_order'] . '. ' . $lectures[$lectureIds['next']]['lecture_title'];
        } elseif ($quizLinkId > 0) {
            $next = Label::getLabel('LBL_QUIZ:') . ' ' . $quizTitle;
        }
        
        FatUtility::dieJsonSuccess([
            'videoUrl' => $videoUrl,
            'error' => $error,
            'previousLecture' => (isset($lectures[$lectureIds['previous']]) && $isArchived == 0) ? $lectures[$lectureIds['previous']]['lecture_order'] . '. ' . $lectures[$lectureIds['previous']]['lecture_title'] : '',
            'nextLecture' => (isset($lectures[$lectureIds['next']]) && $isArchived == 0) ? $lectures[$lectureIds['next']]['lecture_order'] . '. ' . $lectures[$lectureIds['next']]['lecture_title'] : '',
            'quizLinkId' => $quizLinkId
        ]);
    }

    /**
     * Render teacher details
     */
    public function getTeacherDetail()
    {
        $courseId = FatApp::getPostedData('course_id');
        $teacherId = Course::getAttributesById($courseId, 'course_user_id');
        /* get teacher details */
        $srch = new TeacherSearch($this->siteLangId, 0, 0);
        $srch->addCondition('teacher.user_id', '=', $teacherId);
        $srch->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        $srch->addDirectCondition('teacher.user_deleted IS NULL');
        $srch->addDirectCondition('teacher.user_verified IS NOT NULL');
        $srch->addMultipleFields([
            'user_username',
            'user_id',
            'user_last_name',
            'user_first_name',
            'testat_ratings',
            'testat_reviewes',
            'user_country_id',
            'user_active',
            'testat_teachlang',
            'testat_speaklang',
            'testat_preference',
            'testat_availability',
            'testat_qualification'
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $teacher = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('teacher', $teacher);

        $langData = TeacherSearch::getTeachersLangData($this->siteLangId, [$teacherId]);
        $this->set('biography', $langData[$teacherId] ?? '');

        $teacherCourses = TeacherSearch::getCourses([$teacherId]);
        $this->set('courses', $teacherCourses[$teacherId] ?? 0);

        $this->set('isProfileComplete', User::isTeacherProfileComplete([$teacherId]));
        $this->_template->render(false, false);
    }

    /**
     * Mark lecture as Convered/Uncovered
     *
     * @return json
     */
    public function markComplete()
    {
        $lectureId = FatApp::getPostedData('lecture_id', FAtUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FAtUtility::VAR_INT, 0);
        $progressId = FatApp::getPostedData('progress_id', FAtUtility::VAR_INT, 0);
        if ($lectureId < 1 || $progressId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = CourseProgress::getAttributesById($progressId, [
            'crspro_lecture_id',
            'crspro_ordcrs_id',
            'crspro_progress'
        ]);
        $ordcrs = new OrderCourse($data['crspro_ordcrs_id'], $this->siteUserId);
        if (!$ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_LECTURE_NOT_FOUND'));
        }
        $progress = new CourseProgress($progressId);
        if (!$progress->setCompletedLectures($lectureId, $status)) {
            FatUtility::dieJsonError($progress->getError());
        }
        if (!empty($status)) {
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_LECTURE_MARKED_COVERED'));
        } else {
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_LECTURE_MARKED_UNCOVERED'));
        }
    }

    /**
     * Update Course Progress & Completed Status
     *
     * @return json
     */
    public function setProgress()
    {
        $progressId = FatApp::getPostedData('progress_id', FAtUtility::VAR_INT, 0);
        if ($progressId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $progressData = CourseProgress::getAttributesById($progressId, ['crspro_progress', 'crspro_ordcrs_id']);
        $ordcrs = new OrderCourse($progressData['crspro_ordcrs_id'], $this->siteUserId);
        if (!$order = $ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* update course progress */
        $progress = new CourseProgress($progressId);
        if (!$progress->updateProgress($order['ordcrs_course_id'])) {
            FatUtility::dieJsonError($progress->getError());
        }
        $progress = CourseProgress::getAttributesById($progressId, ['crspro_progress', 'crspro_completed']);
        $response = ['progress' => $progress['crspro_progress']];
        if (
            $progressData['crspro_progress'] != $progress['crspro_progress'] &&
            (int)$progress['crspro_progress'] == 100
        ) {
            $response['is_completed'] = ($progress['crspro_completed']) ? true : false;
        }
        FatUtility::dieJsonSuccess($response);
    }

    /**
     * Render Course Completed Page With Certificate Download Link
     *
     * @param int $progressId
     */
    public function completed(int $progressId)
    {
        if ($progressId < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        $data = CourseProgress::getAttributesById($progressId, [
            'crspro_completed',
            'crspro_ordcrs_id',
            'crspro_progress'
        ]);
        if (!$data['crspro_completed']) {
            FatUtility::exitWithErrorCode(404);
        }

        $srch = new OrderCourseSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applyPrimaryConditions();
        $srch->addCondition('ordcrs.ordcrs_id', '=', $data['crspro_ordcrs_id']);
        $srch->addCondition('ordcrs.ordcrs_status', '!=', OrderCourse::CANCELLED);
        $order = $srch->fetchAndFormat(true);
        $order = current($order);
        if (empty($order)) {
            FatUtility::exitWithErrorCode(404);
        }

        /* fetch course details */
        $courseObj = new Course($order['ordcrs_course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course = $courseObj->get()) {
            FatUtility::exitWithErrorCode(404);
        }
        $this->sets([
            'progressId' => $progressId,
            'progress' => $data,
            'course' => $course,
            'canDownloadCertificate' => $order['can_download_certificate'],
            'user' => User::getAttributesById($this->siteUserId, ['user_first_name', 'user_last_name'])
        ]);
        $this->_template->addJs('js/jquery.barrating.min.js');
        $this->_template->render();
    }

    /**
     * Download resources
     *
     * @param int $progressId
     * @param int $resourceId
     *
     */
    public function downloadResource(int $progressId, int $resourceId)
    {
        if ($progressId < 1 || $resourceId < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        $ordcrsId = CourseProgress::getAttributesById($progressId, 'crspro_ordcrs_id');
        if ($ordcrsId < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        $ordcrs = new OrderCourse($ordcrsId, $this->siteUserId);
        if (!$ordcrs->getOrderCourseById()) {
            FatUtility::exitWithErrorCode(404);
        }
        $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
        $srch->addCondition('lecsrc.lecsrc_id', '=', $resourceId);
        $srch->joinTable(Resource::DB_TBL, 'INNER JOIN', 'resrc.resrc_id = lecsrc.lecsrc_resrc_id', 'resrc');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields([
            'resrc_path',
            'resrc_name',
        ]);
        $srch->addCondition('resrc.resrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        if (!$resource = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::exitWithErrorCode(404);
        }
        if (!file_exists(CONF_UPLOADS_PATH . $resource['resrc_path'])) {
            FatUtility::exitWithErrorCode(404);
        }
        $filePath = CONF_UPLOADS_PATH . $resource['resrc_path'];
        if (!$contentType = mime_content_type($filePath)) {
            FatUtility::exitWithErrorCode(500);
        }
        ob_end_clean();
        header('Expires: 0');
        header('Pragma: public');
        header("Content-Type: " . $contentType);
        header('Content-Description: File Transfer');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . $resource['resrc_name'] . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        echo file_get_contents($filePath);
    }

    /**
     * Reset progress for course retake
     *
     * @return json
     */
    public function retake()
    {
        $progressId = FatApp::getPostedData('progress_id', FatUtility::VAR_INT, 0);
        $ordcrsId = CourseProgress::getAttributesById($progressId, 'crspro_ordcrs_id');
        if ($ordcrsId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $ordcrs = new OrderCourse($ordcrsId, $this->siteUserId);
        if (!$ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }
        $progress = new CourseProgress($progressId);
        if (!$progress->retake()) {
            FatUtility::dieJsonError($progress->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_COURSE_PROGRESS_RESET_SUCCESSFULLY'));
    }

    /**
     * Render Feedback Form
     *
     */
    public function feedbackForm()
    {
        $ordcrsId = FatApp::getPostedData('ordcrs_id', FatUtility::VAR_INT, 0);
        $ordcrs = new OrderCourse($ordcrsId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$order = $ordcrs->getCourseToFeedback()) {
            FatUtility::dieJsonError($ordcrs->getError());
        }
        $frm = CourseRatingReview::getFeedbackForm();
        $frm->fill(['ratrev_type_id' => $order['ordcrs_course_id'], 'ordcrs_id' => $ordcrsId]);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Feedback submission
     *
     * @return json
     */
    public function feedbackSetup()
    {
        $post = FatApp::getPostedData();
        $frm = CourseRatingReview::getFeedbackForm();
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        AbusiveWord::validateContent($post['ratrev_title'] . " " . $post['ratrev_detail']);

        $course = new Course($post['ratrev_type_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->feedback($post)) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REVIEW_SUBMITTED_SUCCESSFULLY'));
    }

    /**
     * Get reviews form and overall stats
     */
    public function getReviews()
    {
        $courseId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        $progressId = FatApp::getPostedData('progress_id', FatUtility::VAR_INT, 0);

        /* check order course details */
        $srch = new SearchBase(CourseProgress::DB_TBL, 'ratrev');
        $srch->addCondition('crspro_id', '=', $progressId);
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN', 'ordcrs_id = crspro_ordcrs_id', 'ordcrs');
        $srch->addMultipleFields([
            'crspro_ordcrs_id',
            'ordcrs_reviewed',
            'ordcrs_status'
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (!$orderCourse = FatApp::getDb()->fetch($srch->getResultSet())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
        }
        $ordcrs = new OrderCourse(FatUtility::int($orderCourse['crspro_ordcrs_id']), $this->siteUserId);
        if (!$ordcrs->getOrderCourseById()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* fetch course details */
        $courseObj = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course = $courseObj->get()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
        }
        /* fetch rating data */
        $revObj = new CourseRatingReview();
        $this->set('reviews', $revObj->getRatingStats($courseId));
        /* get sorting form */
        $frm = $this->getReviewForm();
        $frm->fill(['course_id' => $courseId]);
        $this->sets([
            'frm' => $frm,
            'courseId' => $courseId,
            'course' => $course,
            'ordcrsId' => $orderCourse['crspro_ordcrs_id'],
            'canRate' => OrderCourseSearch::canRate($orderCourse, $this->siteUserType),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get reviews list
     */
    public function searchReviews()
    {
        $courseId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        $post = FatApp::getPostedData();
        /* get reviews list */
        $srch = new SearchBase(RatingReview::DB_TBL, 'ratrev');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'course.course_id = ratrev.ratrev_type_id', 'course');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = ratrev.ratrev_user_id', 'learner');
        $srch->addCondition('ratrev.ratrev_status', '=', RatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_type', '=', AppConstant::COURSE);
        $srch->addCondition('ratrev.ratrev_type_id', '=', $courseId);
        $srch->addMultipleFields([
            'user_first_name',
            'user_last_name',
            'ratrev_id',
            'ratrev_user_id',
            'ratrev_title',
            'ratrev_detail',
            'ratrev_overall',
            'ratrev_created'
        ]);
        $sorting = FatApp::getPostedData('sorting', FatUtility::VAR_STRING, RatingReview::SORTBY_NEWEST);
        $srch->addOrder('ratrev.ratrev_id', $sorting);
        $pagesize = AppConstant::PAGESIZE;
        $srch->setPageSize($pagesize);
        $post['pageno'] = FatApp::getPostedData('pageno', FatUtility::VAR_INT, 1);
        $srch->setPageNumber($post['pageno']);
        $reviews = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($reviews as $key => $review) {
            $reviews[$key]['ratrev_created'] = MyDate::convert($review['ratrev_created']);
        }
        $this->sets([
            'reviews' => $reviews,
            'pageCount' => $srch->pages(),
            'pagesize' => $pagesize,
            'recordCount' => $srch->recordCount(),
            'post' => $post,
            'courseId' => $courseId,
        ]);
        $this->_template->render(false, false);
    }

    public function getQuizDetail()
    {
        $quizLinkId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($quizLinkId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $courseObj = new Course(0, $this->siteUserId, 0, 0);
        $quiz = $courseObj->getQuiz($quizLinkId);
        if (!$quiz) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUIZ_NOT_FOUND'));
        }
        $this->set('quiz', $quiz);
        $this->set('quizLinkId', $quizLinkId);

        /* get last lecture id */
        $srch = new SearchBase(Lecture::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('lecture_id');
        $srch->addCondition('lecture_course_id', '=', $quiz['quilin_record_id']);
        $srch->addOrder('lecture_order', 'DESC');
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $lecture = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('lectureId', $lecture['lecture_id']);

        $this->_template->render(false, false);
    }

    public function getQuiz()
    {
        $quizId = FatApp::getPostedData('id');
        $this->set('quizId', $quizId);
        $this->_template->render(false, false);
    }

    public function frame(int $id)
    {
        $this->set('data', Lecture::getAttributesById($id, 'lecture_details'));
        $this->_template->render(false, false, '_partial/frame.php');
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
        $fld = $frm->addHiddenField('', 'course_id');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $frm->addSelectBox('', 'sorting', RatingReview::getSortTypes(), '', [], '');
        $frm->addHiddenField('', 'pageno', 1);
        return $frm;
    }
}
