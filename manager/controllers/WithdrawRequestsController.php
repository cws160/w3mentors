<?php

/**
 * Withdraw Requests Controller is used for Withdraw Requests handling
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class WithdrawRequestsController extends AdminBaseController
{

    /**
     * Initialize Withdraw Requests
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewWithdrawRequests();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("frmSearch", $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Withdraw Requests
     */
    public function search()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $searchForm = $this->getSearchForm();
        if (!$post = $searchForm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($searchForm->getValidationErrors()));
        }
        $srch = new SearchBased(WithdrawRequest::DB_TBL, 'withdrawal');
        $srch->joinTable(User::DB_TBL, 'LEFT JOIN', 'withdrawal.withdrawal_user_id = tu.user_id', 'tu');
        $srch->joinTable(PaymentMethod::DB_TBL, 'INNER JOIN', 'pm.pmethod_id = withdrawal.withdrawal_payment_method_id', 'pm');
        $srch->addOrder('withdrawal_id', 'DESC');
        $srch->addMultipleFields([
            'withdrawal.*', 'user_first_name', 'user_last_name', 'user_is_teacher', 'user_deleted',
            'user_email', 'user_username', 'pmethod_code', 'pmethod_fees', 'withdrawal_status'
        ]);
        if (isset($post['keyword']) && trim($post['keyword']) != '') {
            $post['keyword'] = trim($post['keyword']);
            $keyword = strtoupper(mb_substr($post['keyword'], 0, 1, 'utf-8'));
            if ($keyword == '#') {
                $cond = $srch->addCondition('withdrawal_id', '=', ltrim(str_replace('#', '', $post['keyword']), '0'));
            } else {
                $cond = $srch->addCondition('mysql_func_CONCAT(user_first_name," ",user_last_name)', 'like', '%' . $post['keyword'] . '%', 'AND', true);
                $cond->attachCondition('user_email', 'like', '%' . $post['keyword'] . '%', 'OR');
                $cond->attachCondition('withdrawal_id', 'like', '%' . $post['keyword'] . '%', 'OR');
            }
        }
        if ($post['minprice'] > 0) {
            $srch->addCondition('withdrawal.withdrawal_amount', '>=', MyUtility::convertToSystemCurrency($post['minprice']));
        }
        if ($post['withdrawal_id'] > 0) {
            $srch->addCondition('withdrawal.withdrawal_id', '=', $post['withdrawal_id']);
        }
        if ($post['maxprice'] > 0) {
            $srch->addCondition('withdrawal.withdrawal_amount', '<=', MyUtility::convertToSystemCurrency($post['maxprice']));
        }
        if ($post['status'] >= 0) {
            $srch->addCondition('withdrawal.withdrawal_status', '=', $post['status']);
        }
        if ($post['date_from']) {
            $srch->addCondition('withdrawal.withdrawal_request_date', '>=', MyDate::formatToSystemTimezone($post['date_from']), 'AND', true);
        }
        if ($post['date_to']) {
            $srch->addCondition('withdrawal.withdrawal_request_date', '<=', MyDate::formatToSystemTimezone($post['date_to'] . ' 23:59:59'), 'AND', true);
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $records = $this->fetchAndFormat($records);
        $this->sets([
            'arrListing' => $records,
            'page' => $page,
            'pageSize' => $pagesize,
            'postedData' => FatApp::getPostedData(),
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'statusArr' => WithdrawRequest::getStatuses(),
            'canEdit' => $this->objPrivilege->canEditWithdrawRequests(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Update Status
     */
    public function updateStatus()
    {
        $this->objPrivilege->canEditWithdrawRequests();
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        $withdrawalId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $allowedStatusUpdateArr = [WithdrawRequest::STATUS_COMPLETED, WithdrawRequest::STATUS_DECLINED];
        if (!in_array($status, $allowedStatusUpdateArr)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new SearchBase('tbl_user_withdrawal_requests', 'tuwr');
        $srch->joinTable(PaymentMethod::DB_TBL, 'INNER JOIN', 'tuwr.withdrawal_payment_method_id = pm.pmethod_id', 'pm');
        $srch->addMultipleFields(['tuwr.*', 'pmethod_id', 'pmethod_code']);
        $srch->addCondition('withdrawal_status', '=', WithdrawRequest::STATUS_PENDING);
        $srch->addCondition('withdrawal_id', '=', $withdrawalId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($record)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($status == WithdrawRequest::STATUS_DECLINED) {
            if (!$this->declinedRequest($record, $withdrawalId)) {
                FatUtility::dieJsonSuccess(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
        }
        $methodFee = FatUtility::float($record['withdrawal_transaction_fee'] ?? 0);
        $amount = $record['withdrawal_amount'] - $methodFee;
        if (0 >= $amount) {
            FatUtility::dieJsonError(Label::getLabel('MSG_AMOUNT_IS_ZERO_AFTER_GATEWAY_FEE'));
        }
        if ($record['withdrawal_amount'] > User::getWalletBalance($record['withdrawal_user_id'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INSUFFICIENT_WALLET_FUNDS'));
        }
        $record['gatewayFee'] = $methodFee;
        $record['amount'] = round($amount, 2);
        $payout = new $record['pmethod_code']();
        if (!$payout->release($record)) {
            FatUtility::dieJsonError($payout->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
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
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword');
        $fld = $frm->addIntegerField(Label::getLabel('LBL_MIN_PRICE'), 'minprice');
        $fld->requirements()->setFloatPositive(true);
        $fld->requirements()->setRequired(false);
        $fld->requirements()->setRange(1, 9999999999);
        $fld = $frm->addIntegerField(Label::getLabel('LBL_MAX_PRICE'), 'maxprice');
        $fld->requirements()->setFloatPositive(true);
        $fld->requirements()->setRequired(false);
        $fld->requirements()->setRange(1, 9999999999);
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'status', WithdrawRequest::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'date_from', '', ['readonly' => 'readonly', 'class' => 'field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'date_to', '', ['readonly' => 'readonly', 'class' => 'field--calender']);
        $frm->addHiddenField('', 'withdrawal_id', '');
        $fld = $frm->addHiddenField('', 'page', 1);
        $fld->requirements()->setIntPositive();
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'), ['onclick' => 'clearSearch();']);
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Declined Request
     * 
     * @param array $record
     * @param int $withdrawalId
     * @return bool
     */
    private function declinedRequest(array $record, int $withdrawalId): bool
    {
        $db = FatApp::getDb();
        $db->startTransaction();
        $withdrawRequest = new WithdrawRequest($withdrawalId);
        $data = [
            'withdrawal_status' => WithdrawRequest::STATUS_DECLINED,
            'withdrawal_user_id' => $record['withdrawal_user_id'],
            'withdrawal_amount' => $record['withdrawal_amount'],
        ];
        if (!$withdrawRequest->updateStatus($data)) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }


    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['withdrawal_request_date'] = MyDate::formatDate($row['withdrawal_request_date']);
            $row['txnfee'] = json_decode($row['pmethod_fees'], 1);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
