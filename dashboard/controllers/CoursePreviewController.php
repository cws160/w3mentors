<?php

/**
 * This Controller is used to preview course learning page
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CoursePreviewController extends DashboardController
{

    /**
     * Initialize Tutorials
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType == User::LEARNER || !Course::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Study Page
     *
     * @param int $courseId
     */
    public function index(int $courseId)
    {
        /* fetch course details */
        $courseObj = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course = $courseObj->get()) {
            FatUtility::exitWithErrorCode(404);
        }
        if ($this->siteUserId != $course['course_user_id']) {
            FatUtility::exitWithErrorCode(404);
        }
        /* fetch section and lectures list */
        $srch = new SectionSearch($this->siteLangId, $this->siteUserId, User::LEARNER);
        $srch->applyPrimaryConditions();
        $srch->addCondition('section.section_course_id', '=', $courseId);
        $srch->addOrder('section.section_order', 'ASC');
        $srch->addSearchListingFields();
        $srch->doNotCalculateRecords();
        if (!$sections = $srch->fetchAndFormat()) {
            FatUtility::exitWithErrorCode(404);
        }
        if ($course['course_lectures'] < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        $this->sets([
            'course' => $course,
            'sections' => $sections,
            'quiz' => QuizLinked::getByCourseId($courseId),
            'archivedLectures' => Lecture::getArchivedByCourseId($courseId)
        ]);
        $this->_template->addJs('js/common_ui_functions.js');
        $this->_template->render();
    }

    /**
     * Get lecture to be displayed
     *
     * @param int $next
     * @return json
     */
    public function getLecture(int $next)
    {
        $courseId = FatApp::getPostedData('course_id', FAtUtility::VAR_INT, 0);
        $lectureId = FatApp::getPostedData('lecture_id', FAtUtility::VAR_INT, 0);

        /* get next lecture */
        $lectureId = $this->getNextPrevLecture($courseId, $lectureId, $next);
        FatUtility::dieJsonSuccess(['lecture_id' => $lectureId]);
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
        $this->_template->render(false, false);
    }

    /**
     * Find next/previous lecture id
     *
     * @param int $courseId
     * @param int $lectureId
     * @param int $getNext
     * @return int
     */
    private function getNextPrevLecture(int $courseId, int $lectureId, int $getNext = AppConstant::YES)
    {
        /* get lecture order */
        $lectureOrder = Lecture::getAttributesById($lectureId, 'lecture_order');
        $lectureOrder = empty($lectureOrder) ? 0 : $lectureOrder;
        /* get lecture */
        $srch = new SearchBase(Lecture::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('lecture_id');
        $srch->addCondition('lecture_course_id', '=', $courseId);
        if ($getNext == AppConstant::YES) {
            $srch->addCondition('lecture_order', '>', $lectureOrder);
            $srch->addOrder('lecture_order', 'ASC');
        } else {
            $srch->addCondition('lecture_order', '<', $lectureOrder);
            $srch->addOrder('lecture_order', 'DESC');
        }
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $lecture = FatApp::getDb()->fetch($srch->getResultSet());
        $lectureId = 0;
        if (!empty($lecture)) {
            $lectureId = $lecture['lecture_id'];
        }
        return $lectureId;
    }

    /**
     * Get data for next, previous & current lecture
     *
     * @param int $courseId
     * @param int $lectureId
     */
    public function getLectureData(int $courseId, int $lectureId)
    {
        $lectureIds = [$lectureId];
        $lectureIds[] = $nextLecture = $this->getNextPrevLecture($courseId, $lectureId, AppConstant::YES);
        $lectureIds[] = $prevLecture = $this->getNextPrevLecture($courseId, $lectureId, AppConstant::NO);
        $srch = new LectureSearch($this->siteLangId);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addCondition('lecture.lecture_id', 'IN', $lectureIds);
        $lectures = $srch->fetchAndFormat();
        $resources = $lecture = [];
        if (isset($lectures[$lectureId])) {
            $lecture = isset($lectures[$lectureId]) ? $lectures[$lectureId] : [];
            $lectureObj = new Lecture($lecture['lecture_id']);
            $resources = $lectureObj->getResources();
        }
        /* get lecture video */
        $resource = new Lecture($lectureId);
        $video = $resource->getMedia(Lecture::TYPE_RESOURCE_EXTERNAL_URL);

        $this->sets([
            'lecture' => $lecture,
            'previousLecture' => isset($lectures[$prevLecture]) ? $lectures[$prevLecture] : [],
            'nextLecture' => isset($lectures[$nextLecture]) ? $lectures[$nextLecture] : [],
            'resources' => $resources,
            'videoUrl' => !empty($video['lecsrc_link']) ? (new VideoStreamer())->getUrl($video['lecsrc_link']) : '',
            'quizLinkId' => ($lecture && !isset($lectures[$nextLecture])) ? Course::getAttributesById($lecture['lecture_course_id'], 'course_quilin_id') : 0
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get lecture data
     *
     * @param int $courseId
     * @param int $lectureId
     */
    public function getVideo(int $courseId, int $lectureId)
    {
        $lectureIds = [$lectureId];
        $lectureIds[] = $nextLecture = $this->getNextPrevLecture($courseId, $lectureId, AppConstant::YES);
        $lectureIds[] = $prevLecture = $this->getNextPrevLecture($courseId, $lectureId, AppConstant::NO);
        $srch = new LectureSearch($this->siteLangId);
        $cnd = $srch->addCondition('lecture.lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $cnd->attachCondition('lecture_archived', '=', AppConstant::YES);
        $srch->addSearchListingFields();
        $srch->addFld('lecture_archived');
        $srch->addCondition('lecture.lecture_id', 'IN', $lectureIds);
        $lectures = $srch->fetchAndFormat();
        $lecture = isset($lectures[$lectureId]) ? $lectures[$lectureId] : [];
        $isArchived = $lectures[$lectureId]['lecture_archived'] ?? 0;

        /* get lecture video */
        $resource = new Lecture($lectureId);
        $video = $resource->getMedia(Lecture::TYPE_RESOURCE_EXTERNAL_URL);

        $videoUrl = $error = '';
        if (!empty($video)) {
            $streamer = new VideoStreamer();
            if (!$videoUrl = $streamer->getUrl($video['lecsrc_link'])) {
                $error = $streamer->getError();
            }
        }

        /* get quiz id */
        $quizLinkId = 0;
        if ($lecture && !isset($lectures[$nextLecture])) {
            $quizLinkId = Course::getAttributesById($lecture['lecture_course_id'], 'course_quilin_id');
            $quizTitle = QuizLinked::getAttributesById($quizLinkId, 'quilin_title');
            $this->set('quizTitle', $quizTitle);
        }

        $next = '';
        if (isset($lectures[$nextLecture])) {
            $next = $lectures[$nextLecture]['lecture_order'] . '. ' . $lectures[$nextLecture]['lecture_title'];
        } elseif ($quizLinkId > 0) {
            $next = Label::getLabel('LBL_QUIZ:') . ' ' . $quizTitle;
        }
        
        FatUtility::dieJsonSuccess([
            'videoUrl' => $videoUrl,
            'error' => $error,
            'previousLecture' => (isset($lectures[$prevLecture]) && $isArchived == 0) ? $lectures[$prevLecture]['lecture_order'] . '. ' . $lectures[$prevLecture]['lecture_title'] : '',
            'nextLecture' => (isset($lectures[$nextLecture]) && $isArchived == 0) ? $lectures[$nextLecture]['lecture_order'] . '. ' . $lectures[$nextLecture]['lecture_title'] : '',
        ]);
    }

    /**
     * Download resources
     *
     * @param int $resourceId
     *
     */
    public function downloadResource(int $resourceId)
    {
        $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE, 'lecsrc');
        $srch->addCondition('lecsrc.lecsrc_id', '=', $resourceId);
        $srch->joinTable(Resource::DB_TBL, 'INNER JOIN', 'resrc.resrc_id = lecsrc.lecsrc_resrc_id', 'resrc');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields([
            'resrc_path',
            'resrc_name',
            'course_user_id'

        ]);
        $srch->addCondition('resrc.resrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('lecsrc.lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->joinTable(
            Course::DB_TBL,
            'INNER JOIN',
            'course.course_id = lecsrc.lecsrc_course_id',
            'course'
        );
        $resource = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($resource)) {
            FatUtility::exitWithErrorCode(404);
        }
        if ($resource['course_user_id'] != $this->siteUserId) {
            FatUtility::dieWithError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
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
            'testat_reviewes'
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
     * Get reviews form and overall stats
     */
    public function getReviews()
    {
        $courseId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
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
            'course' => $course
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

    public function frame(int $id, string $type = 'lecture')
    {
        if ($type == 'lecture') {
            $this->set('data', Lecture::getAttributesById($id, 'lecture_details'));
        } elseif ($type == 'quiz') {
            $this->set('data', QuizLinked::getAttributesById($id, 'quilin_detail'));
        }
        $this->_template->render(false, false, '_partial/frame.php');
    }

    public function getQuizDetail()
    {
        $quizId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $courseId = FatApp::getPostedData('courseId', FatUtility::VAR_INT, 0);
        if ($quizId < 1 && $courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        
        if (!$quiz = QuizLinked::getByCourseId($courseId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_QUIZ_NOT_FOUND'));
        }
        $this->set('quiz', $quiz);

        /* get last lecture id */
        $srch = new SearchBase(Lecture::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->addFld('lecture_id');
        $srch->addCondition('lecture_course_id', '=', $courseId);
        $srch->addOrder('lecture_order', 'DESC');
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->setPageSize(1);
        $lecture = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('lectureId', $lecture['lecture_id']);

        $this->_template->render(false, false);
    }

    public function getQuiz()
    {
        $this->set('courseId', FatApp::getPostedData('courseId'));
        $this->_template->render(false, false);
    }

    public function userQuiz(int $courseId)
    {
        $this->set('data', QuizLinked::getByCourseId($courseId));
        $this->_template->addCss('css/quiz.css');
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
        $fld = $frm->addHiddenField('', 'course_id');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $frm->addSelectBox('', 'sorting', RatingReview::getSortTypes(), '', [], '');
        $frm->addHiddenField('', 'pageno', 1);
        return $frm;
    }
}
