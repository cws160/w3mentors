<?php

/**
 * This Controller is used for handling courses
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CoursesController extends DashboardController
{

    /**
     * Initialize Courses
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
     * Render Search Form
     *
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $this->set('frm', $frm);
        $this->_template->addJs('js/jquery.barrating.min.js');
        $this->_template->render();
    }

    /**
     * Search & List Plans
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['course_subcateid'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        /* get courses list */
        if ($this->siteUserType == User::LEARNER) {
            $srch = new OrderCourseSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
            $srch->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
            $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
            $srch->addSearchListingFields();
            $srch->applyPrimaryConditions();
            $srch->applySearchConditions($post);
            $srch->addOrder('crspro_status', 'ASC');
            $srch->addOrder('ordcrs_id', 'DESC');
        } else {
            $srch = new CourseSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
            $srch->addOrder('course_id', 'DESC');
            $srch->applyPrimaryConditions();
            $srch->addSearchListingFields();
            $srch->applySearchConditions($post);
        }
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['page']);
        $this->sets([
            'courses' => $srch->fetchAndFormat(),
            'post' => $post,
            'recordCount' => $srch->recordCount(),
            'courseStatuses' => Course::getStatuses(),
            'courseTypes' => Course::getTypes(),
            'orderStatuses' => CourseProgress::getStatuses(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Course Manage Page
     *
     * @param mixed $courseId
     */
    public function form($courseId = 0)
    {
        if ($this->siteUserType == User::LEARNER) {
            FatUtility::exitWithErrorCode(404);
        }
        if (!empty($courseId) && FatUtility::int($courseId) < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        $courseId = FatUtility::int($courseId);
        $courseTitle = '';
        if ($courseId > 0) {
            $srch = new CourseSearch($this->siteLangId, $this->siteUserId, User::TEACHER);
            $srch->applyPrimaryConditions();
            $srch->addMultipleFields(['course.course_id', 'course_title']);
            $srch->addCondition('course.course_id', '=', $courseId);
            $srch->addCondition('course.course_active', '=', AppConstant::ACTIVE);
            $srch->setPageSize(1);
            if (!$course = FatApp::getDb()->fetch($srch->getResultSet())) {
                Message::addErrorMessage(Label::getLabel('LBL_COURSE_NOT_FOUND'));
                FatApp::redirectUser(MyUtility::generateUrl('Courses'));
            }
            $courseTitle = $course['course_title'];
            $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
            if (!$course->canEditCourse()) {
                Message::addErrorMessage(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
                FatApp::redirectUser(MyUtility::generateUrl('Courses'));
            }
        }

        $this->set('courseTitle', $courseTitle);
        $this->set('courseId', $courseId);
        $this->set('siteLangId', $this->siteLangId);
        $this->set("includeEditor", true);
        $this->set("videoSize", Afile::getAllowedUploadSize(Afile::TYPE_COURSE_PREVIEW_VIDEO));
        $this->_template->addJs(['js/jquery.tagit.js', 'js/jquery.ui.touch-punch.min.js', 'attach-quizzes/page-js/index.js']);
        $this->_template->render();
    }

    /**
     * Render Basic Details Page
     *
     * @param int $courseId
     */
    public function generalForm(int $courseId = 0)
    {
        $course = [];
        if ($courseId > 0) {
            $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
            if (!$course->canEditCourse()) {
                FatUtility::dieJsonError($course->getError());
            }
            $srch = new CourseSearch($this->siteLangId, $this->siteUserId, User::TEACHER);
            $srch->applyPrimaryConditions();
            $srch->addMultipleFields([
                'course_title',
                'course_subtitle',
                'course_cate_id',
                'course_subcate_id',
                'course_clang_id',
                'course_level',
                'course_details',
                'course.course_id'
            ]);
            $srch->addCondition('course.course_id', '=', $courseId);
            $srch->addCondition('course.course_active', '=', AppConstant::ACTIVE);
            $srch->setPageSize(1);
            if (!$course = FatApp::getDb()->fetch($srch->getResultSet())) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
            }
        }
        $frm = $this->getGeneralForm();
        $frm->fill($course);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Fetch sub categories for selected category
     *
     * @param int $catgId
     * @param int $subCatgId
     * @return html
     */
    public function getSubcategories(int $catgId, int $subCatgId = 0)
    {
        $catgId = FatUtility::int($catgId);
        $subcategories = [];
        if ($catgId > 0) {
            $subcategories = Category::getCategoriesByParentId($this->siteLangId, $catgId);
        }
        $this->set('subCatgId', $subCatgId);
        $this->set('subcategories', $subcategories);
        $this->_template->render(false, false);
    }

    /**
     * Setup basic details
     *
     * @return json
     */
    public function setup()
    {
        $frm = $this->getGeneralForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), [
            'course_cate_id',
            'course_subcate_id',
            'course_clang_id'
        ])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $course = new Course($post['course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->setupGeneralData($post)) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'courseId' => $course->getMainTableRecordId(),
            'title' => $post['course_title'],
        ]);
    }

    /**
     * Render Media Page
     *
     * @param int $courseId
     */
    public function mediaForm(int $courseId)
    {
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* validate course id */
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $course = Course::getAttributesById($courseId, ['course_id', 'course_preview_video', 'course_video_ready']);
        
        /* get form and fill */
        $frm = $this->getMediaForm();
        $frm->fill($course);

        /* get course image required dimensions */
        $file = new Afile(Afile::TYPE_COURSE_IMAGE);
        $image = $file->getFile($courseId);
        $dimensions = $file->getImageSizes(Afile::SIZE_LARGE);
        $videoUrl = '';
        $error = '';
        $video = new VideoStreamer();
        if ($course['course_preview_video'] != '') {
            if (!$videoUrl = $video->getUrl($course['course_preview_video'])) {
                $error = $video->getError();
            }
        }

        $this->sets([
            'frm' => $frm,
            'courseId' => $courseId,
            'videoUrl' => $videoUrl,
            'error' => $error,
            'videoReady' => $course['course_video_ready'],
            'extensions' => Afile::getAllowedExts(Afile::TYPE_COURSE_IMAGE),
            'videoFormats' => Afile::getAllowedExts(Afile::TYPE_COURSE_PREVIEW_VIDEO),
            'dimensions' => $dimensions,
            'filesize' => MyUtility::convertBitesToMb(Afile::getAllowedUploadSize()),
            'vdoFilesize' => MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_COURSE_PREVIEW_VIDEO)),
            'videoExtensions' => Afile::getAllowedExts(Afile::TYPE_COURSE_PREVIEW_VIDEO),
            'image' => $image
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup course media
     *
     * @return json
     */
    public function setupMedia()
    {
        $frm = $this->getMediaForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $course = new Course($post['course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieWithError($course->getError());
        }
        if (empty($_FILES['course_image']['name'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NO_MEDIA_SELECTED'));
        }
        $file = new Afile(Afile::TYPE_COURSE_IMAGE);
        if (!$file->saveFile($_FILES['course_image'], $post['course_id'], true)) {
            FatUtility::dieJsonError($file->getError());
        }
        if ($course->getEditRequestStatus()) {
            $course->setUpdatedDate();
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY'));
    }

    /**
     * Setup course preview video
     *
     * @return json
     */
    public function setupVideo()
    {
        $courseId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_ID'));
        }

        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }

        /* Initalize Video Tool */
        $video = new VideoStreamer();

        /* Upload Video */
        if (!$video->upload($_FILES['course_preview_video'], Afile::TYPE_COURSE_LECTURE_VIDEO)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_ERROR_WHILE_UPLOADING_FILE'));
        }

        /* Remove previous video if exists */
        $videoId = Course::getAttributesById($courseId, 'course_preview_video');
        if (!empty($videoId)) {
            if (!$video->remove($videoId)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ERROR_REMOVING_FILE'));
            }
        }

        /* Get Video Details */
        $videoId = $video->getVideoId();
        $videoReadyStatus = $video->getReadyStatus($videoId);

        $course->setFldValue('course_preview_video', $videoId);
        $course->setFldValue('course_video_ready', $videoReadyStatus);
        if ($course->getEditRequestStatus()) {
            $course->setFldValue('course_updated', date('Y-m-d H:i:s'));
        }
        if (!$course->save()) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_VIDEO_UPLOAD_COMPLETE'));
    }

    /**
     * Remove course media files
     *
     * @param int $courseId
     */
    public function removeMedia(int $courseId)
    {
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieWithError($course->getError());
        }
        $type = FatApp::getPostedData('type');
        if ($type == Afile::TYPE_COURSE_IMAGE) {
            $file = new Afile($type);
            if (!$file->removeFile($courseId, 0, true)) {
                FatUtility::dieJsonError($file->getError());
            }
            $msg = Label::getLabel('MSG_IMAGE_REMOVED_SUCCESSFULLY');
        } else {
            $videoId = Course::getAttributesById($courseId, 'course_preview_video');
            $video = new VideoStreamer();
            if (!$video->remove($videoId)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ERROR_REMOVING_FILE'));
            }
            $course->setFldValue('course_preview_video', '');
            $course->setFldValue('course_video_ready', 0);
            if (!$course->save()) {
                FatUtility::dieJsonError($course->getError());
            }
            $msg = Label::getLabel('MSG_VIDEO_REMOVED_SUCCESSFULLY');
        }
        /* update course date */
        if ($course->getEditRequestStatus()) {
            if (!$course->setUpdatedDate()) {
                return false;
            }
        }
        FatUtility::dieJsonSuccess($msg);
    }

    /**
     * Render Intended Learners Page
     *
     * @param int $courseId
     */
    public function intendedLearnersForm(int $courseId)
    {
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->get()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
        }
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        /* get form and fill */
        $frm = $this->getIntendedLearnersForm();
        $frm->fill(['course_id' => $courseId]);
        /* get saved responses */
        $learner = new IntendedLearner();
        $responses = $learner->get($courseId);
        $this->sets([
            'frm' => $frm,
            'courseId' => $courseId,
            'responses' => $responses,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Intended Learners Data
     *
     */
    public function setupIntendedLearners()
    {
        $frm = $this->getIntendedLearnersForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $course = new Course($post['course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $intended = new IntendedLearner();
        if (!$intended->setup($post)) {
            FatUtility::dieJsonError($intended->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * Updating Intended Learner Records sort order
     *
     * @return json
     */
    public function updateIntendedOrder()
    {
        $course = new Course(FatApp::getPostedData('course_id'), $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $ids = FatApp::getPostedData('order');
        $intended = new IntendedLearner();
        if (!$intended->updateOrder($ids)) {
            FatUtility::dieJsonError($intended->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_ORDER_SETUP_SUCCESSFUL'));
    }

    /**
     * function to delete course intended learner
     *
     * @param int $indLearnerId
     * @return json
     */
    public function deleteIntendedLearner(int $indLearnerId)
    {
        if ($indLearnerId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /*  check if record exists */
        if (!$courseId = IntendedLearner::getAttributesById($indLearnerId, 'coinle_course_id')) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $intended = new IntendedLearner($indLearnerId);
        if (!$intended->delete()) {
            FatUtility::dieJsonError($intended->getError());
        }
        /* update course date */
        if ($course->getEditRequestStatus()) {
            if (!$course->setUpdatedDate()) {
                return false;
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_SUCCESSFULLY'));
    }

    /**
     * Render Pricing Page
     *
     * @param int $courseId
     */
    public function priceForm(int $courseId)
    {
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* validate course id */
        $data = ['course_id' => $courseId];
        if (!$course = Course::getAttributesById($courseId, ['course_type', 'course_currency_id', 'course_price'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
        }
        $courseObj = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$courseObj->canEditCourse()) {
            FatUtility::dieJsonError($courseObj->getError());
        }
        $data = array_merge($data, $course);

        /* get form and fill */
        $frm = $this->getPriceForm();
        $data['course_price'] = round(CourseUtility::convertToCurrency($data['course_price'], $data['course_currency_id']), 2);
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->set('courseId', $courseId);
        $this->_template->render(false, false);
    }

    /**
     * Get Prices Data
     *
     * @return json
     */
    public function setupPrice()
    {
        $frm = $this->getPriceForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['course_currency_id'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $course = new Course($post['course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }

        $price = 0;
        if ($post['course_type'] == Course::TYPE_PAID) {
            /* validate currency */
            $currencyId = FatUtility::int($post['course_currency_id']);
            if ($currencyId > 0 && Currency::getAttributesById($currencyId, 'currency_active') == AppConstant::INACTIVE) {
                FatUtility::dieJsonError(Label::getLabel('LBL_CURRENCY_NOT_AVAILABLE'));
            }
            if ($post['course_price'] > 0) {
                $price = CourseUtility::convertToSystemCurrency($post['course_price'], $post['course_currency_id']);
            }
            if ($price < 1) {
                $label = Label::getLabel('LBL_COURSE_PRICE_CANNOT_BE_LESS_THAN_1_{currency}');
                $label = str_replace('{currency}', MyUtility::getSystemCurrency()['currency_code'], $label);
                FatUtility::dieJsonError($label);
            }
            $maxPrice = 9999999999;
            if ($price > $maxPrice) {
                $label = Label::getLabel('LBL_COURSE_PRICE_CANNOT_BE_GREATER_THAN_{max-price}_{currency}');
                $label = str_replace(
                    ['{currency}', '{max-price}'],
                    [MyUtility::getSystemCurrency()['currency_code'], $maxPrice],
                    $label
                );
                FatUtility::dieJsonError($label);
            }
        }
        $course->assignValues([
            'course_type' => $post['course_type'],
            'course_currency_id' => $post['course_currency_id'],
            'course_price' => $price,
        ]);
        if ($course->getEditRequestStatus()) {
            $course->setFldValue('course_updated', date('Y-m-d H:i:s'));
        }
        if (!$course->save()) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * Render Curriculum Page
     *
     * @param int $courseId
     */
    public function curriculumForm(int $courseId)
    {
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        /* validate course id */
        if (!$course->get()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
        }
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        /* get form and fill */
        $frm = $this->getCurriculumForm();
        $frm->fill(['course_id' => $courseId]);
        $this->set('frm', $frm);
        $this->set('courseId', $courseId);
        $this->_template->render(false, false);
    }

    /**
     * Render Settings Page
     *
     * @param int $courseId
     */
    public function settingsForm(int $courseId)
    {
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* validate course id */
        if (!$courseData = Course::getAttributesById($courseId, [
            'course_id',
            'course_certificate',
            'course_certificate_type',
            'course_quilin_id'
        ])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->canEditCourse()) {
            FatUtility::dieJsonError($course->getError());
        }
        $courseData = $course->get();
        /* create form data */
        $data = [
            'course_id' => $courseId,
            'course_certificate' => $courseData['course_certificate'],
            'course_certificate_type' => $courseData['course_certificate_type'],
            'course_quilin_id' => $courseData['course_quilin_id']
        ];
        /* get form data from lang table */
        $srch = new SearchBase(Course::DB_TBL_LANG);
        $srch->addCondition('course_id', '=', $courseId);
        $srch->addMultipleFields(['course_welcome', 'course_congrats', 'course_srchtags']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if ($course = FatApp::getDb()->fetch($srch->getResultSet())) {
            $crsTags = [];
            if (!empty($course['course_srchtags'])) {
                $crsTags = json_decode($course['course_srchtags']);
            }
            $course['course_tags'] = implode('||', $crsTags);
            $data = array_merge($data, $course);
        }

        /* check certificate available or not */
        $offerCertificate = CertificateTemplate::checkCourseCertificatesAvailable();
        $data['course_certificate'] = ($offerCertificate == true) ? $data['course_certificate'] : 0;
        /* get form and fill */
        $frm = $this->getSettingForm($offerCertificate, $courseData['course_status']);
        $frm->fill($data);
        $this->set('frm', $frm);

        /* get quiz detail */
        $data = QuizLinked::getQuizzes([$courseId], AppConstant::COURSE);
        $this->set('quiz', current($data));

        $this->set('offerCetificate', $offerCertificate);
        $this->set('courseId', $courseId);
        $this->set('courseStatus', $courseData['course_status']);
        $this->_template->render(false, false);
    }

    /**
     * Setup Course Settings Data
     *
     * @return json
     */
    public function setupSettings()
    {
        $frm = $this->getSettingForm(CertificateTemplate::checkCourseCertificatesAvailable());
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['course_certificate_type'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $course = new Course($post['course_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        $status = Course::getAttributesById($post['course_id'], 'course_status');
        
        if ($status == Course::DRAFTED && $post['course_certificate'] == AppConstant::YES) {
            if (!isset($post['course_certificate_type']) || $post['course_certificate_type'] < 1) {
                FatUtility::dieJsonError(Label::getLabel('LBL_PLEASE_SELECT_CERTIFICATE'));
            } else {
                $code = 'course_completion_certificate';
                if ($post['course_certificate_type'] == Certificate::TYPE_COURSE_EVALUATION) {
                    $code = 'course_evaluation_certificate';
                }
                $srch = CertificateTemplate::getSearchObject($this->siteLangId);
                $srch->addCondition('certpl_code', '=', $code);
                $srch->addCondition('certpl_status', '=', AppConstant::ACTIVE);
                if (!FatApp::getDb()->fetch($srch->getResultSet())) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_CERTIFICATE_NOT_AVAILABLE'));
                }
            }
            if ($post['course_certificate_type'] == Certificate::TYPE_COURSE_EVALUATION && $post['course_quilin_id'] < 1) {
                FatUtility::dieJsonError(Label::getLabel('LBL_PLEASE_ATTACH_QUIZ'));
            }
        }
        if (!$course->setupSettings($post)) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * function to delete course
     *
     * @param int $courseId
     * @return json
     */
    public function remove(int $courseId)
    {
        $courseId = FatUtility::int($courseId);
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->delete()) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_SUCCESSFULLY'));
    }

    /**
     * function to delete course
     *
     * @return json
     */
    public function removeQuiz()
    {
        $courseId = FatApp::getPostedData('courseId', FatUtility::VAR_INT, 0);
        $quizLinkId = FatApp::getPostedData('quizLinkId', FatUtility::VAR_INT, 0);
        if ($courseId < 1 || $quizLinkId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->removeQuiz($quizLinkId)) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_SUCCESSFULLY'));
    }

    /**
     * Function to get eligibility status for all course steps.
     *
     * @param int $courseId
     * @return json
     */
    public function getEligibilityStatus(int $courseId)
    {
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType);
        $criteria = $course->isEligibleForApproval();
        FatUtility::dieJsonSuccess(['criteria' => $criteria]);
    }

    /**
     * Submitting course for approval from admin
     *
     * @param int $courseId
     * @return bool
     */
    public function submitForApproval(int $courseId)
    {
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->submitApprovalRequest()) {
            FatUtility::dieJsonError($course->getError());
        }
        Message::addMessage(Label::getLabel('LBL_APPROVAL_REQUESTED_SUCCESSFULLY'));
        FatUtility::dieJsonSuccess('');
    }

    /**
     * Submitting course for final updates
     *
     * @param int $courseId
     * @return bool
     */
    public function submitUpdates(int $courseId)
    {
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->markUpdatesComplete()) {
            FatUtility::dieJsonError($course->getError());
        }
        Message::addMessage(Label::getLabel('LBL_UPDATES_SUBMITTED_SUCCESSFULLY'));
        FatUtility::dieJsonSuccess('');
    }

    /**
     * Add/Remove Course from user favorites list
     *
     * @return json
     */
    public function toggleFavorite()
    {
        $courseId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        if ($courseId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $db = FatApp::getDb();
        /* validate course id */
        $course = new Course($courseId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$data = $course->get()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
        }
        if ($data['course_user_id'] == $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_MARK_YOUR_OWN_COURSE_AS_FAVORITE'));
        }
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, AppConstant::NO);
        if ($status == AppConstant::NO) {
            /* check course already marked favorite */
            $srch = new SearchBase(User::DB_TBL_COURSE_FAVORITE);
            $srch->addCondition('ufc_user_id', '=', $this->siteUserId);
            $srch->addCondition('ufc_course_id', '=', $courseId);
            $srch->doNotCalculateRecords();
            $srch->setPageSize(1);
            if (FatApp::getDb()->fetch($srch->getResultSet())) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_IS_ALREADY_IN_YOUR_FAVORITES_LIST'));
            }
            /* add to favorites */
            $user = new User($this->siteUserId);
            if (!$user->setupFavoriteCourse($courseId)) {
                FatUtility::dieJsonError($user->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_COURSE_ADDED_TO_FAVORITES'));
        }
        /* remove from favorites */
        $where = [
            'smt' => 'ufc_user_id = ? AND ufc_course_id = ?',
            'vals' => [$this->siteUserId, $courseId]
        ];
        if (!$db->deleteRecords(User::DB_TBL_COURSE_FAVORITE, $where)) {
            FatUtility::dieJsonError($db->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_COURSE_REMOVED_FROM_FAVORITES'));
    }

    /**
     * Render cancellation popup
     */
    public function cancelForm()
    {
        $ordcrsId = FatApp::getPostedData('ordcrs_id', FatUtility::VAR_INT, 0);
        $order = new OrderCourse($ordcrsId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course = $order->getCourseToCancel()) {
            FatUtility::dieJsonError($order->getError());
        }
        $frm = $this->getCancelForm();
        $frm->fill(['ordcrs_id' => $ordcrsId]);
        $this->sets(['frm' => $frm]);
        $this->sets(['course' => $course]);
        $this->_template->render(false, false);
    }



    /**
     * Setup cancellation request
     *
     * @return bool
     */
    public function cancelSetup()
    {
        $frm = $this->getCancelForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $order = new OrderCourse($post['ordcrs_id'], $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$order->cancel($post['comment'])) {
            FatUtility::dieJsonError($order->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_CANCELLATION_REQUEST_SUBMITTED_SUCCESSFULLY'));
    }

    /**
     * Edit Course Form
     *
     * @return bool
     */
    public function editRequestForm()
    {
        $crsId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        if ($crsId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = new Course($crsId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        if (!$course->get()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_NOT_FOUND'));
        }
        $editRequest = $course->getLatestEditRequest();
        if (!Course::canSendEditRequest($editRequest)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_HAVE_ALREADY_SENT_AN_EDIT_REQUEST'));
        }
        $frm = $this->getEditRequestForm();
        $frm->fill(['course_id' => $crsId]);
        $this->sets([
            'frm' => $frm,
            'editRequest' => $editRequest
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Edit Course Form
     *
     * @return bool
     */
    public function editRequestSetup()
    {
        $crsId = FatApp::getPostedData('course_id', FatUtility::VAR_INT, 0);
        if ($crsId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getEditRequestForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $course = new Course($crsId, $this->siteUserId, $this->siteUserType, $this->siteLangId);
        $editRequest = $course->getLatestEditRequest();
        if (!Course::canSendEditRequest($editRequest)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_HAVE_ALREADY_SENT_AN_EDIT_REQUEST'));
        }
        if (!$course->setupEditRequest($post['coedre_reason'])) {
            FatUtility::dieJsonError($course->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_COURSE_EDIT_REQUEST_SUBMITTED_SUCCESSFULLY'));
    }


    /**
     * Get Cancel Form
     *
     */
    private function getCancelForm(): Form
    {
        $frm = new Form('cancelFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $comment = $frm->addTextArea(Label::getLabel('LBL_COMMENTS'), 'comment');
        $comment->requirements()->setLength(10, 300);
        $comment->requirements()->setRequired();
        $frm->addHiddenField('', 'ordcrs_id')->requirements()->setRequired();
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SUBMIT'));
        return $frm;
    }

    /**
     * Get Search Form
     *
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword');
        if ($this->siteUserType == User::TEACHER) {
            $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'course_status', Course::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        } else {
            $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'crspro_status', OrderCourse::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        }
        $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'course_type', Course::getTypes(), '', [], Label::getLabel('LBL_SELECT'));
        $categoryList = Category::getCategoriesByParentId($this->siteLangId);
        $frm->addSelectBox(Label::getLabel('LBL_CATEGORY'), 'course_cateid', $categoryList, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_SUB_CATEGORY'), 'course_subcateid', [], '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setInt();
        $frm->addHiddenField('', 'page', 1)->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_RESET'));
        return $frm;
    }

    /**
     * Basic Details Form
     *
     */
    private function getGeneralForm(): Form
    {
        $frm = new Form('frmCourses');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox(Label::getLabel('LBL_COURSE_TITLE'), 'course_title');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 80);
        $fld = $frm->addTextBox(Label::getLabel('LBL_COURSE_SUBTITLE'), 'course_subtitle');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 160);
        $categories = Category::getCategoriesByParentId($this->siteLangId);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_CATEGORY'), 'course_cate_id', $categories, '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_SUBCATEGORY'), 'course_subcate_id', [], '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setInt();
        $langsList = (new CourseLanguage())->getAllLangs($this->siteLangId, true);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_COURSE_LANGUAGE'), 'course_clang_id', $langsList, '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_LEVEL'), 'course_level', Course::getCourseLevels(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $frm->addHtmlEditor(Label::getLabel('LBL_DESCRIPTION'), 'course_details')->requirements()->setRequired();
        $frm->addHiddenField('', 'course_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_&_NEXT'));
        return $frm;
    }

    /**
     * Get Course Media Form
     *
     */
    private function getMediaForm(): Form
    {
        $frm = new Form('frmCourses');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addFileUpload(Label::getLabel('LBl_COURSE_IMAGE'), 'course_image');
        $frm->addFileUpload(Label::getLabel('LBL_COURSE_PREVIEW_VIDEO'), 'course_preview_video');
        $frm->addHiddenField('', 'course_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_&_NEXT'));
        return $frm;
    }

    /**
     * Get Intended Learners Form
     *
     */
    private function getIntendedLearnersForm(): Form
    {
        $intendedLearnertypes = IntendedLearner::getTypes();
        $frm = new Form('frmCourses');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox($intendedLearnertypes[IntendedLearner::TYPE_LEARNING], 'type_learnings[]');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 155);
        $fld = $frm->addTextBox($intendedLearnertypes[IntendedLearner::TYPE_REQUIREMENTS], 'type_requirements[]');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 155);
        $fld = $frm->addTextBox($intendedLearnertypes[IntendedLearner::TYPE_LEARNERS], 'type_learners[]');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 155);
        $frm->addHiddenField('', 'type_learnings_ids[]');
        $frm->addHiddenField('', 'type_requirements_ids[]');
        $frm->addHiddenField('', 'type_learners_ids[]');
        $frm->addHiddenField('', 'course_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_&_NEXT'));
        return $frm;
    }

    /**
     * Get Prices Form
     *
     */
    private function getPriceForm(): Form
    {
        $frm = new Form('frmCourses');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addRadioButtons(Label::getLabel('LBL_TYPE'), 'course_type', Course::getTypes());
        $fld->requirements()->setRequired();
        $frm->addSelectBox(
            Label::getLabel('LBL_CURRENCY'),
            'course_currency_id',
            Currency::getCurrencyNameWithCode($this->siteLangId),
            '',
            [],
            Label::getLabel('LBL_SELECT')
        );
        $frm->addTextBox(Label::getLabel('LBL_PRICE'), 'course_price')->requirements()->setFloat();
        $frm->addHiddenField('', 'course_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_&_NEXT'));
        return $frm;
    }

    /**
     * Get Curriculum Form
     *
     */
    private function getCurriculumForm(): Form
    {
        $frm = new Form('frmCourses');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'course_id')->requirements()->setInt();
        $frm->addButton('', 'btn_next', Label::getLabel('LBL_SAVE_&_NEXT'));
        return $frm;
    }


    /**
     * Get Edit Request Form
     *
     */
    private function getEditRequestForm(): Form
    {
        $frm = new Form('frmCourseEditRequest');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextArea(Label::getLabel('LBL_REQUEST_REASON'), 'coedre_reason');
        $fld->requirements()->setRequired();
        $frm->addHiddenField('', 'course_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEND'));
        return $frm;
    }


    /**
     * Get Setting Form
     *
     * @param bool $offerCertificate
     */
    private function getSettingForm(bool $offerCertificate = true, $courseStatus = Course::DRAFTED): Form
    {

        $frm = new Form('frmCourses');
        $frm = CommonHelper::setFormProperties($frm);

        $fld = $frm->addHiddenField(Label::getLabel('LBL_QUIZ'), 'course_quilin_id');
        $fld->requirements()->setInt();
        $fld->requirements()->setRequired(false);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PLEASE_ATTACH_A_QUIZ'));
        
        if ($offerCertificate == true) {
            $fld = $frm->addRadioButtons(Label::getLabel('LBL_OFFER_CERTIFICATE'), 'course_certificate', AppConstant::getYesNoArr(), AppConstant::NO);
            $fld->requirements()->setRequired();

            $types = Certificate::getTypes();
            unset($types[Certificate::TYPE_QUIZ_EVALUATION]);
            $typeFld = $frm->addSelectBox(Label::getLabel('LBL_CERTIFICATE'), 'course_certificate_type', $types);
            
            $reqFld = new FormFieldRequirement('course_quilin_id', '');
            $reqFld->setRequired(true);
            $notReqFld = new FormFieldRequirement('course_quilin_id', '');
            $notReqFld->setRequired(false);
            $typeFld->requirements()->addOnChangerequirementUpdate(Certificate::TYPE_COURSE_EVALUATION, 'eq', 'course_quilin_id', $reqFld);
            $typeFld->requirements()->addOnChangerequirementUpdate(Certificate::TYPE_COURSE_EVALUATION, 'ne', 'course_quilin_id', $notReqFld);
        } else {
            $frm->addHiddenField('', 'course_certificate', AppConstant::NO);
        }

        $frm->addTextBox(Label::getLabel('LBL_COURSE_TAGS'), 'course_tags')->requirements()->setRequired();
        $frm->addHiddenField('', 'course_id')->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_save', Label::getLabel('LBL_SAVE'));
        $lbl = Label::getLabel('LBL_SUBMIT_FOR_APPROVAL');
        if ($courseStatus ==  Course::PUBLISHED) {
            $lbl = Label::getLabel('LBL_SUBMIT_UPDATES');
        }
        $frm->addButton('', 'btn_approval', $lbl);
        return $frm;
    }
}
