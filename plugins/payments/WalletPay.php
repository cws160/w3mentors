<?php

/**
 * Wallet Pay
 * 
 * @author Fatbit Technologies
 */
class WalletPay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;

    const KEY = 'WalletPay';

    public function __construct(array $order)
    {
        $this->pmethod = [];
        $this->order = $order;
        parent::__construct();
    }

    /**
     * Initialize Payment Method
     * 
     * 1. Load Payment Method
     */
    public function initPayemtMethod(): bool
    {
        $this->pmethod = PaymentMethod::getByCode(static::KEY);
        if (empty($this->pmethod)) {
            $this->error = Label::getLabel("LBL_PAYEMNT_GATEWAY_NOT_FOUND");
            return false;
        }
        return true;
    }

    /**
     * Get Payment Data
     * 
     * 1. Initialize Payment Method
     * 2. Check user wallet balance
     * 3. Debit User Wallet Balance
     * 4. Order Payment & Settlement
     * 5. Send Transaction Notification
     * 
     * @return bool|array
     */
    public function getChargeData()
    {
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return false;
        }
        /* Check user wallet balance */
        $order = $this->order;
        $amount = FatUtility::float($order['order_net_amount']);
        if ($amount > User::getWalletBalance($order['order_user_id'])) {
            $this->error = Label::getLabel('MSG_INSUFFICIENT_WALLET_BALANCE');
            return false;
        }
        /* Debit User Wallet Balance */
        $vars = [Transaction::getTypes($order['order_type']), Order::formatOrderId($order['order_id'])];
        $comment = str_replace(['{ordertype}', '{orderid}'], $vars, Label::getLabel('LBL_{ordertype}:_ID_{orderid}'));
        $txn = new Transaction($order['order_user_id'], $order['order_type']);
        if (!$txn->debit($amount, $comment)) {
            $this->error = Label::getLabel('MSG_SOMETHING_WENT_WRONG_TRY_AGAIN');
            return false;
        }
        $txnId = $txn->getMainTableRecordId();
        $rootUrl = (API_CALL) ? CONF_WEBROOT_FRONTEND . 'api/' : CONF_WEBROOT_FRONTEND;

        $returnUrl = MyUtility::makeUrl('Payment', 'return', [$order['order_id']], $rootUrl) . '?txnId=' . $txnId;
        $data = ['order' => $order, 'txnId' => $txnId, 'returnUrl' => $returnUrl];
        if (API_CALL) {
            $token = (new AppToken())->getToken($this->order['order_user_id'])['apptkn_token'] ?? '';
            $data['token'] = $token;
            $data['returnUrl'] = $returnUrl . '&token=' . $token;
        }
        return $data;
    }

    /**
     * Wallet Callback
     * 
     * 1. Initialize Payment Method
     * 2. Order Payment & Settlements
     * 
     * @param array $post
     * @return array
     */
    public function callbackHandler(array $post): array
    {
        $txnId = $post['txnId'] ?? '';
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return $this->returnError();
        }
        $txn = Transaction::getAttributesById($txnId);
        $txn['usrtxn_datetime'] = MyDate::showDate($txn['usrtxn_datetime'], true) . ' UTC';
        if (empty($txn)) {
            $this->error = Label::getLabel('LBL_INVALID_ACCESS');
            return $this->returnError();
        }
        /* Order Payment & Settlements */
        $amount = FatUtility::float($txn['usrtxn_amount']);
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($txnId, abs($amount), $txn)) {
            $this->error = $payment->getError();
            return $this->returnError();
        }
        $userLangId = User::getAttributesById($this->order['order_user_id'], 'user_lang_id');
        $notifiVar = [
            '{amount}' => MyUtility::formatMoney(abs($amount)), 
            '{reason}' => Label::getLabel('LBL_WALLET_ORDER_PAYMENT', $userLangId)
        ];

        $notifi = new Notification($this->order['order_user_id'], Notification::TYPE_WALLET_DEBIT);
        $notifi->sendNotification($notifiVar);
        return $this->returnSuccess();
    }

    /**
     * Wallet Return
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        return $this->callbackHandler($post);
    }

}
