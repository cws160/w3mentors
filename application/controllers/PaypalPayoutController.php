<?php

/**
 * Paypal Payout Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PaypalPayoutController extends MyAppController
{

    /**
     * Initialize PayPal Payout
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Payout callback handling
     */
    public function callback()
    {
        $webhookData = file_get_contents('php://input');
        $webhookData = json_decode($webhookData, true);
        switch ($webhookData['event_type']) {
            case "PAYMENT.PAYOUTS-ITEM.SUCCEEDED":
                $status = WithdrawRequest::STATUS_COMPLETED;
                break;
            case "PAYMENT.PAYOUTS-ITEM.CANCELED":
            case "PAYMENT.PAYOUTS-ITEM.DENIED":
            case "PAYMENT.PAYOUTS-ITEM.FAILED":
            default:
                $status = WithdrawRequest::STATUS_PAYOUT_FAILED;
                break;
        }
        if (!$this->updateWithdrawRequest($webhookData, $status)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_ERROR'));
        }
    }

    /**
     * Update Withdraw Request
     * 
     * @param array $requestData
     * @param type $status
     * @return boolean
     */
    public function updateWithdrawRequest($requestData, $status)
    {
        if (empty($requestData)) {
            return false;
        }
        $senderBatchId = $requestData['resource']['sender_batch_id'];
        $arryId = explode('_', $senderBatchId);
        $withdrawalId = FatUtility::int(end($arryId));
        $srch = new SearchBase('tbl_user_withdrawal_requests', 'tuwr');
        $srch->addCondition('withdrawal_status', '=', WithdrawRequest::STATUS_PAYOUT_SENT);
        $srch->addCondition('withdrawal_id', '=', $withdrawalId);
        $srch->addMultipleFields(['withdrawal_user_id', 'withdrawal_id', 'withdrawal_amount', 'withdrawal_status', 'withdrawal_transaction_fee']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($record)) {
            return false;
        }
        $data = [
            'withdrawal_status' => $status,
            'withdrawal_transaction_fee' => $record['withdrawal_transaction_fee'],
            'withdrawal_user_id' => $record['withdrawal_user_id'],
            'withdrawal_amount' => $record['withdrawal_amount'],
        ];
        $db = FatApp::getDb();
        $db->startTransaction();
        $withdrawRequest = new WithdrawRequest($record['withdrawal_id']);
        if (!$withdrawRequest->updateStatus($data)) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
    }

}
