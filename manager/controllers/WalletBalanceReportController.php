<?php

/**
 * Wallet Balance Report Controller is used for Wallet Balance Report handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class WalletBalanceReportController extends AdminBaseController
{
    /**
     * Initialize Wallet Balance Report
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewWalletBalanceReport();
    }

    /**
     * Get Render Index & Search Form
     */
    public function index()
    {
        $this->set('srchFrm', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List User(s) Wallet Balance
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $walletBalanceObj = new WalletBalanceReport();
        $srch = $walletBalanceObj->getSearchObject();

        /* Apply Posted Data Conditions [ */
        if(!empty($post['user_id'])) {
            $srch->addCondition('user.user_id', '=', $post['user_id']);
        } elseif (!empty($post['keyword'])) {
            $fullName = 'mysql_func_CONCAT(user.user_first_name, " ", user.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . trim($post['keyword']) . '%', 'AND', true);
        }
        if(!empty($post['user_type'])) {
            switch($post['user_type']) {
                case User::LEARNER:
                    $srch->addCondition('mysql_func_IFNULL(user_is_affiliate, 0)', '=', AppConstant::NO, 'AND', true);
                    break;
                case User::TEACHER:
                    $srch->addCondition('user.user_is_teacher', '=', AppConstant::YES);
                    break;
                case User::AFFILIATE:
                    $srch->addCondition('user.user_is_affiliate', '=', AppConstant::YES);
                    break;
                default:
                    break;
            }
        }
        /* ] */
        $srch->addOrder('uset.user_wallet_balance', 'DESC');
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
        $frm->addTextBox(Label::getLabel('LBL_USER'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_USER_TYPE'), 'user_type', User::getUserTypes(), '', [], Label::getLabel('LBL_SELECT_USER_TYPE'));
        $frm->addHiddenField('', 'user_id', '', ['id' => 'user_id', 'autocomplete' => 'off']);
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }
}