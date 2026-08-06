<?php

/**
 * Affiliate Report Controller is used to handle affiliate revenue
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AffiliateReportController extends AdminBaseController
{

    /**
     * Initialize Affiliate Report
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if(!User::isAffiliateEnabled()){
            FatUtility::exitWithErrorCode(404);
        }
        $this->objPrivilege->canViewAffiliateReport();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set('srchFrm', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Affiliate Performance
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBased(User::DB_TBL, 'affiliate');
        $srch->joinTable(User::DB_TBL_AFFILIATE_STAT, 'LEFT JOIN', 'afstat.afstat_user_id = affiliate.user_id', 'afstat');
        $srch->addMultipleFields(['CONCAT(user_first_name, " ", user_last_name) as affiliate_name',
            'afstat_referees', 'afstat_referee_sessions', 'afstat_signup_revenue', 'afstat_order_revenue']);
        if (!empty($post['user_id'])) {
            $srch->addCondition('affiliate.user_id', '=', $post['user_id']);
        } elseif (!empty($post['keyword'])) {
            $fullName = 'mysql_func_CONCAT(affiliate.user_first_name, " ", affiliate.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . trim($post['keyword']) . '%', 'AND', true);
        }
        $srch->addCondition('affiliate.user_is_affiliate', '=', AppConstant::YES);
        $cond = $srch->addCondition('afstat_referees', '>', 0);
        $cond->attachCondition('afstat_referee_sessions', '>', 0);       
        $srch->addOrder('afstat_referees', 'DESC');
        $srch->addOrder('afstat_referee_sessions', 'DESC');
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
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }

}
