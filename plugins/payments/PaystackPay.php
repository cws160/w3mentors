<?php

/**
 * Paystack Pay
 * 
 * NGN is a default currency for test enviourment
 * 
 * Documentation Link
 * @link https://paystack.com/docs/api/#transaction
 * @author Fatbit Technologies
 */
class PaystackPay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;
    public $settings;

    const KEY = 'PaystackPay';
    const INIT_URL = "https://api.paystack.co/transaction/initialize/";
    const VERIFY_URL = "https://api.paystack.co/transaction/verify/";
    /* Transaction Statuses */
    const TXN_FAILED = 0;
    const TXN_SUCCESS = 1;

    public function __construct(array $order)
    {
        $this->pmethod = [];
        $this->settings = [];
        $this->order = $order;
        parent::__construct();
    }

    /**
     * Initialize Payment Method
     * 
     * 1. Load Payment Method
     * 2. Format Payment Settings
     * 3. Validate Payment Settings
     * 
     * @return bool
     */
    public function initPayemtMethod(): bool
    {
        /* Load Payment Method */
        $this->pmethod = PaymentMethod::getByCode(static::KEY);
        if (empty($this->pmethod)) {
            $this->error = Label::getLabel("LBL_PAYEMNT_GATEWAY_NOT_FOUND");
            return false;
        }
        /* Format Payment Settings */
        $settings = json_decode($this->pmethod['pmethod_settings'], 1) ?? [];
        foreach ($settings as $row) {
            $this->settings[$row['key']] = $row['value'];
        }
        /* Validate Payment Settings */
        if (empty($this->settings['secret_key']) || empty($this->settings['public_key'])) {
            $this->error = Label::getLabel("MSG_INCOMPLETE_PAYMENT_GATEWAY_SETUP");
            return false;
        }
        return true;
    }

    /**
     * Get Payment Data
     * 
     * 1. Initialize Payment Method
     * 2. Validate order Currency
     * 3. Format Request Data
     * 4. Execute Curl Request
     * 5. Get & Fill Payment Form
     * 
     * @return bool|array
     */
    public function getChargeData()
    {
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return false;
        }
        /* Validate order Currency */
        $code = $this->order['order_currency_code'];
        if (!$this->validateCurrency($code)) {
            return false;
        }
        /* Format Request Data */
        if (!$request = $this->formatRequestData()) {
            return false;
        }
        /* Execute Curl Request */
        if (!$response = $this->exeCurlRequest(static::INIT_URL, $request)) {
            return false;
        }
        /*  Get & Fill Payment Form */
        $frm = $this->getPaymentForm();
        $frm->fill($this->order);
        return ['frm' => $frm, 'order' => $this->order, 'response' => $response];
    }

    /**
     * Paystack Callback
     * 
     * 1. Initialize Payment Method
     * 2. Validate received post
     * 3. Verify Transaction Status
     * 4. Order Payment & Settlements
     * 
     * @param array $post
     * @return array
     */
    public function callbackHandler(array $post): array
    {
        $refId = $post['trxref'] ?? '';
        if (empty($refId)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return $this->returnError();
        }
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return $this->returnError();
        }
        /* Validate received post */
        if (!$res = $this->validateResponse($refId)) {
            return $this->returnError();
        }
        /* Verify Transaction Status */
        if (static::TXN_SUCCESS != FatUtility::int($res['status'])) {
            $this->error = $res['message'];
            return $this->returnError();
        }
        /* Order Payment & Settlements */
        $amount = $this->order['order_net_amount'];
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($res['data']['id'], $amount, $res)) {
            $this->error = $payment->getError();
            return $this->returnError();
        }
        return $this->returnSuccess();
    }

    /**
     * Paystack Return Handler
     * Currently not in use
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        return $this->callbackHandler($post);
    }

    /**
     * Get Payment Form
     * 
     * @return \Form
     */
    private function getPaymentForm()
    {
        $frm = new Form('paystackPayForm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'order_id', '');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SUBMIT'));
        return $frm;
    }

    /**
     * Format Request Data
     * 
     * @return array
     */
    private function formatRequestData(): array
    {
        $order = $this->order;
        $request = [
            'key' => $this->settings['public_key'],
            'email' => $order['user_email'],
            'amount' => $this->formatAmount($order['order_net_amount']),
            'currency' => $order['order_currency_code'],
            'metadata' => ['order_id' => $order['order_id']],
            'callback_url' => MyUtility::makeFullUrl('Payment', 'return', [$order['order_id']]),
            'webhook_url' => MyUtility::makeFullUrl('Payment', 'callback', [$order['order_id']]),
        ];
        return $request;
    }

    /**
     * Execute Curl Request
     * 
     * @param string $url
     * @param array $params
     * @return bool|array
     */
    private function exeCurlRequest(string $url, array $params)
    {
        $headers = [
            "Authorization: Bearer " . $this->settings['secret_key'],
            "Cache-Control: no-cache", 'Content-type: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $curlResult = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = 'Error:' . curl_error($ch);
            return false;
        }
        curl_close($ch);
        $result = json_decode($curlResult, 1);
        if ($result['status'] != true) {
            $this->error = $result['message'];
            return false;
        }
        return $result;
    }

    /**
     * Validate Response
     * 
     * @param string $refId
     * @return bool|array
     */
    private function validateResponse(string $refId)
    {
        $headers = [
            "Authorization: Bearer " . $this->settings['secret_key'],
            "Content-Type: application/json", "cache-control: no-cache"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::VERIFY_URL . rawurlencode($refId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $curlResult = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = 'Error:' . curl_error($ch);
            return false;
        }
        curl_close($ch);
        $result = json_decode($curlResult, 1);
        if ($result['status'] != true) {
            $this->error = $result['message'];
            return false;
        }
        return $result;
    }

    /**
     * Format Amount
     * 
     * @param float $amount
     * @return float
     */
    private function formatAmount(float $amount): float
    {
        return number_format($amount, 2, '.', '') * 100;
    }

    /**
     * Validate Currency
     * @param string $code
     * @return bool
     */
    private function validateCurrency(string $code): bool
    {
        $arr = ['USD', 'NGN', 'GHS', 'ZAR'];
        if (!in_array($code, $arr)) {
            $this->error = Label::getLabel("MSG_INVALID_CURRENCY_CODE");
            return false;
        }
        return true;
    }

}
