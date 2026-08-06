<?php

/**
 * Teacher Performance Controller is used for Teacher Performance handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeacherPerformanceController extends AdminBaseController
{

    /**
     * Initialize Teacher Performance
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewTeacherPerformance();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set('srchFrm', $this->getSearchForm());
        $this->set('isCourseRemoved', Course::isEnabled(1));
        $this->set('isGroupClassRemoved', GroupClass::isEnabled(1));
        $this->_template->render();
    }

    /**
     * Search & List Teacher Performance
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBased(User::DB_TBL, 'teacher');
        $srch->joinTable(User::DB_TBL_STAT, 'INNER JOIN', 'testat.testat_user_id = teacher.user_id', 'testat');
        $srch->addMultipleFields(['CONCAT(user_first_name, " ", user_last_name) as teacher_name',
            'testat_ratings', 'testat_reviewes', 'testat_students', 'testat_lessons', 'testat_classes', 'testat_courses']);
        if (!empty($post['user_id'])) {
            $srch->addCondition('teacher.user_id', '=', $post['user_id']);
        } elseif (!empty($post['keyword'])) {
            $fullName = 'mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . trim($post['keyword']) . '%', 'AND', true);
        }
        $srch->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        $cond = $srch->addCondition('testat_students', '>', 0);
        $cond->attachCondition('testat_lessons', '>', 0);
        if (GroupClass::isEnabled()) {
            $cond->attachCondition('testat_classes', '>', 0);
        }
        if (Course::isEnabled()) {
            $cond->attachCondition('testat_courses', '>', 0);
        }
        $srch->addOrder('testat_ratings', 'DESC');
        $srch->addOrder('testat_students', 'DESC');
        $srch->addOrder('testat_lessons', 'DESC');
        $srch->addOrder('teacher.user_id', 'ASC');
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('postedData', $post);
        $this->set("records", $records);
        $this->set('page', $post['pageno']);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
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
        $frm->addTextBox(Label::getLabel('LBL_USER'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off']);
        $frm->addHiddenField('', 'user_id', '', ['id' => 'user_id', 'autocomplete' => 'off']);
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $frm->addHiddenField('', 'user_is_teacher', AppConstant::YES);
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }

}
