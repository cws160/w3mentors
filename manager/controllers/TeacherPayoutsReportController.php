<?php

/**
 * Teacher Payouts Report Controller is used for Payouts Report handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeacherPayoutsReportController extends AdminBaseController
{
    /**
     * Initialize Payouts Report
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewPayoutsReport();
    }

    /**
     * Render Index & Search Form
     */
    public function index()
    {
        $this->set('srchFrm', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Teacher Payouts
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $payoutObj = new PayoutsReport();
        $srch = $payoutObj->getSearchObject();

        /* Apply Posted Data Conditions [ */
        if(!empty($post['user_id'])) {
            $srch->addCondition('user.user_id', '=', $post['user_id']);
        } elseif (!empty($post['keyword'])) {
            $fullName = 'mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . trim($post['keyword']) . '%', 'AND', true);
        }
        if(!empty($post['fromDate'])) {
            $srch->addCondition('usrtxn.usrtxn_datetime', '>=', MyDate::formatToSystemTimezone($post['fromDate'] . ' 00:00:00'));
        }
        if(!empty($post['toDate'])) {
            $srch->addCondition('usrtxn.usrtxn_datetime', '<=', MyDate::formatToSystemTimezone($post['toDate'] . ' 23:59:59'));
        }
        /* ] */
        $srch->addGroupBy('usrtxn.usrtxn_user_id');
        $srch->addOrder('user.user_first_name', 'ASC');
        $srch->addOrder('user.user_id', 'ASC');
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
        $frm->addTextBox(Label::getLabel('LBL_TEACHER'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off']);
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'fromDate', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'toDate', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'user_id', '', ['id' => 'user_id', 'autocomplete' => 'off']);
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }
}