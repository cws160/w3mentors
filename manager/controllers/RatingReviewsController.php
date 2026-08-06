<?php

/**
 * Rating Reviews Controller is used for Rating Reviews handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class RatingReviewsController extends AdminBaseController
{

    /**
     * Rating Reviews
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Search Form
     */
    public function index($type = 0)
    {
        if ($type == AppConstant::COURSE) {
            if (!Course::isEnabled()) {
                FatUtility::exitWithErrorCode(404);
            }
            $this->objPrivilege->canViewCourseReviews();
        } else {
            $this->objPrivilege->canViewTeacherReviews();
        }
        $frm = $this->getSearchForm($this->siteLangId);
        $frm->fill(['ratrev_type' => $type]);
        $this->set("search", $frm);
        $this->set('type', $type);
        $this->_template->render();
    }

    /**
     * Search & List Reviews
     */
    public function search()
    {
        if (FatApp::getPostedData('ratrev_type') == AppConstant::COURSE) {
            if (!Course::isEnabled()) {
                FatUtility::dieWithError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            $this->objPrivilege->canViewCourseReviews();
            $canEdit = $this->objPrivilege->canEditCourseReviews(true);
        } else {
            $this->objPrivilege->canViewTeacherReviews();
            $canEdit = $this->objPrivilege->canEditTeacherReviews(true);
        }
        $srchFrm = $this->getSearchForm();
        if (!$post = $srchFrm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
        }
        $srch = new SearchBased(RatingReview::DB_TBL, 'ratrev');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = ratrev.ratrev_user_id', 'learner');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ratrev.ratrev_teacher_id', 'teacher');
        $srch->addMultipleFields([
            'CONCAT(learner.user_first_name," ",learner.user_last_name) as learner_name',
            'CONCAT(teacher.user_first_name," ",teacher.user_last_name) as teacher_name',
            'ratrev_id', 'ratrev_type', 'ratrev_user_id', 'ratrev_teacher_id', 'ratrev_overall',
            'ratrev_title', 'ratrev_detail', 'ratrev_status', 'ratrev_created'
        ]);
        if (!empty($post['ratrev_user_id'])) {
            $srch->addCondition('ratrev_user_id', '=', $post['ratrev_user_id']);
        } elseif (!empty($post['ratrev_user'])) {
            $srch->addDirectCondition('CONCAT(learner.user_first_name, " ", learner.user_last_name) LIKE "%' . trim($post['ratrev_user']) . '%"');
        }
        if (!empty($post['ratrev_teacher_id'])) {
            $srch->addCondition('ratrev_teacher_id', '=', $post['ratrev_teacher_id']);
        } elseif (!empty($post['ratrev_teacher'])) {
            $srch->addDirectCondition('CONCAT(teacher.user_first_name," ",teacher.user_last_name) LIKE "%' . trim($post['ratrev_teacher']) . '%"');
        }
        if (!empty($post['date_from'])) {
            $srch->addCondition('ratrev_created', '>=', MyDate::formatToSystemTimezone($post['date_from']));
        }
        if (!empty($post['date_to'])) {
            $srch->addCondition('ratrev_created', '<=', MyDate::formatToSystemTimezone($post['date_to'] . ' 23:59:59'), 'AND', true);
        }
        if (isset($post['ratrev_status']) && $post['ratrev_status'] != '') {
            $srch->addCondition('ratrev_status', '=', $post['ratrev_status']);
        }
        if (isset($post['ratrev_type']) && $post['ratrev_type'] > 0) {
            $srch->addCondition('ratrev_type', '=', $post['ratrev_type']);
        } else {
            $srch->addCondition('ratrev_type', '!=', AppConstant::COURSE);
        }
        $srch->addOrder('ratrev_status', 'ASC');
        $srch->addOrder('ratrev_id', 'DESC');
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize(FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $reviews = FatApp::getDb()->fetchAll($srch->getResultSet());
        $reviews = $this->fetchAndFormat($reviews);
        $this->sets([
            'reviews' => $reviews,
            'canEdit' => $canEdit,
            'recordCount' => $srch->recordCount(),
            'pageCount' => $srch->pages(),
            'postedData' => $post
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Rating Reviews Form
     */
    public function form()
    {
        $ratrevId = FatApp::getPostedData('ratrevId', FatUtility::VAR_INT, 0);
        $ratingReview = new RatingReview(0, 0, $ratrevId);
        $data = $ratingReview->getDetail();
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($data['ratrev_type'] == AppConstant::COURSE) {
            if (!Course::isEnabled()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            $this->objPrivilege->canEditCourseReviews();
            $course = new Course(FatUtility::int($data['ratrev_type_id']));
            $course = $course->get();
            $data['course_name'] = $course['course_title'] ?? '';
        } else {
            $this->objPrivilege->canEditTeacherReviews();
        }
        $frm = $this->getForm();
        $frm->fill($data);
        $this->sets(['frm' => $frm, 'data' => $data]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Rating Review
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $ratingReview = new RatingReview(0, 0, $post['ratrev_id']);
        $data = $ratingReview->getDetail();
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($data['ratrev_type'] == AppConstant::COURSE) {
            if (!Course::isEnabled()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            $this->objPrivilege->canEditCourseReviews();
        } else {
            $this->objPrivilege->canEditTeacherReviews();
        }

        $ratingReview->assignValues(['ratrev_status' => $post['ratrev_status']]);
        if (!$ratingReview->save()) {
            FatUtility::dieJsonError($ratingReview->getError());
        }
        $teacherId = FatUtility::int($data['ratrev_teacher_id']);
        (new TeacherStat($teacherId))->setRatingReviewCount();
        if ($data['ratrev_type'] == AppConstant::COURSE) {
            (new Course($data['ratrev_type_id']))->setRatingReviewCount();
        }
        if ($data['ratrev_teacher_notify'] == AppConstant::NO && $post['ratrev_status'] == RatingReview::STATUS_APPROVED) {
            $ratingReview->sendMailToTeacher($data);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField(Label::getLabel('LBL_REVIEW_BY'), 'ratrev_user_id');
        $frm->addHiddenField(Label::getLabel('LBL_REVIEW_TO'), 'ratrev_teacher_id');
        $frm->addTextBox(Label::getLabel('LBL_REVIEW_BY'), 'ratrev_user');
        $frm->addTextBox(Label::getLabel('LBL_REVIEW_TO'), 'ratrev_teacher');
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'date_from', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'date_to', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'ratrev_status', RatingReview::getStatues(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'pageno', 1);
        $frm->addHiddenField('', 'ratrev_type');
        $submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $submit->attachField($frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'), ['onclick' => 'clearSearch();']));
        return $frm;
    }

    /**
     * Get Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmCountry');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'ratrev_id');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'ratrev_status', RatingReview::getStatues(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['ratrev_created'] = MyDate::formatDate($row['ratrev_created']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = [];
        $parameters = FatApp::getParameters();
        if (isset($parameters[0]) && $parameters[0] == AppConstant::COURSE) {
            $nodes = [['title' => Label::getLabel('LBL_COURSE_RATING_REVIEWS')]];
        } else {
            $nodes = [['title' => Label::getLabel('LBL_TEACHER_RATING_REVIEWS')]];
        }
        return $nodes;
    }
}
