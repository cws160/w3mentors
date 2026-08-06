<?php

/**
 * This class is used to handle Learners|Teachers availabilities
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class BankPayout extends FatModel
{

    const KEY = 'BankPayout';

    /**
     * Initialize BankPayout
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Release bank payout 
     * 
     * @param array $recordData 
     * @return bool
     */
    public function release(array $recordData): bool
    {

        $data = [
            'withdrawal_status' => WithdrawRequest::STATUS_COMPLETED,
            'withdrawal_transaction_fee' => $recordData['gatewayFee'],
            'withdrawal_user_id' => $recordData['withdrawal_user_id'],
            'withdrawal_amount' => $recordData['withdrawal_amount'],
        ];
        $db = FatApp::getDb();
        $db->startTransaction();
        $withdrawRequest = new WithdrawRequest($recordData['withdrawal_id']);
        if (!$withdrawRequest->updateStatus($data)) {
            $db->rollbackTransaction();
            $this->error = $withdrawRequest->getError();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Get Withdraw Form
     * 
     * @param array $paymentMethod
     * @return Form
     */
    public static function getWithdrawalForm(array $paymentMethod): Form
    {
        $currency = MyUtility::getSystemCurrency();
        $frm = new Form('frmWithdrawal');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addRadioButtons(Label::getLabel('LBL_Payout_Type'), 'withdrawal_payment_method_id', $paymentMethod);
        $defaultCurLbl = Label::getLabel('LBL_ENTER_AMOUNT_TO_BE_ADDED_[{currency-code}]');
        $defaultCurLbl = str_replace('{currency-code}', $currency['currency_code'], $defaultCurLbl);
        $fld = $frm->addRequiredField($defaultCurLbl, 'withdrawal_amount');
        $fld->requirements()->setFloat(true);
        $fld->requirements()->setRange(FatApp::getConfig("CONF_MIN_WITHDRAW_LIMIT"), 9999999999);
        $frm->addRequiredField(Label::getLabel('LBL_BANK_NAME'), 'ub_bank_name');
        $frm->addRequiredField(Label::getLabel('LBL_ACCOUNT_HOLDER_NAME'), 'ub_account_holder_name');
        $frm->addRequiredField(Label::getLabel('LBL_ACCOUNT_NUMBER'), 'ub_account_number');
        $frm->addRequiredField(Label::getLabel('LBL_IFSC_SWIFT_CODE'), 'ub_ifsc_swift_code');
        $frm->addTextArea(Label::getLabel('LBL_BANK_ADDRESS'), 'ub_bank_address');
        $frm->addTextArea(Label::getLabel('LBL_Other_Info_Instructions'), 'withdrawal_comments');
        $frm->addHiddenField('', 'pmethod_code', BankPayout::KEY);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Send_Request'));
        $frm->addButton("", "btn_cancel", Label::getLabel('LBL_Cancel'));
        return $frm;
    }
}
