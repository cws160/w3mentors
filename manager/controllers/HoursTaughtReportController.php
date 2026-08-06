<?php

/**
 * Hours Taught Report Controller is used for Total Hours Taught Report handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class HoursTaughtReportController extends AdminBaseController
{
    /**
     * Initialize Hours Taught Report
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewHoursTaughtReport();
    }

    /**
     * Render Index & Search Form
     */
    public function index()
    {
        $date = FatApp::getConfig('CONF_SALES_REPORT_GENERATED_DATE', FatUtility::VAR_STRING, date('Y-m-d H:i:s'));
        $timezone = Admin::getAttributesById($this->siteAdminId, ['admin_timezone'])['admin_timezone'];
        $date = MyDate::convert($date, $timezone);
        $regenDateTime = str_replace('{datetime}', MyDate::showDate($date, true), Label::getLabel('LBL_REPORT_GENERATED_ON_{datetime}'));
        $this->set('regenDateTime', $regenDateTime);
        $this->set('srchFrm', $this->getSearchForm());
        $this->set('canEditReportStatsRegenerate', $this->objPrivilege->canEditReportStatsRegenerate(true));
        $this->_template->render();
    }

    /**
     * Search & List Teacher Hours Taught
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $hoursStat = new HoursTaughtStat();
        $srch = $hoursStat->getSearchObject();
        
        /* Apply Posted Data Conditions [ */
        if(!empty($post['user_id'])) {
            $srch->addCondition('user.user_id', '=', $post['user_id']);
        } elseif (!empty($post['keyword'])) {
            $fullName = 'mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . trim($post['keyword']) . '%', 'AND', true);
        }
        if(!empty($post['fromDate'])) {
            $srch->addCondition('hts.hts_date', '>=', MyDate::formatToSystemTimezone($post['fromDate'] . ' 00:00:00'));
        }
        if(!empty($post['toDate'])) {
            $srch->addCondition('hts.hts_date', '<=', MyDate::formatToSystemTimezone($post['toDate'] . ' 23:59:59'));
        }
        /* ] */
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