<?php

/**
 * Paypal Standard Pay
 * 
 * @author Fatbit Technologies
 */
class PaypalStandardPay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;
    public $settings;

    const KEY = 'PaypalStandardPay';
    const LIVE_URL = 'https://www.paypal.com/cgi-bin/webscr';
    const TEST_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

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
        $arr = json_decode($this->pmethod['pmethod_settings'], 1) ?? [];
        foreach ($arr as $row) {
            $this->settings[$row['key']] = $row['value'];
        }
        /* Validate Payment Settings */
        if (empty($this->settings['merchant_email'])) {
            $this->error = Label::getLabel("MSG_INCOMPLETE_PAYMENT_GATEWAY_SETUP");
            return false;
        }
        return true;
    }

    /**
     * Get Payment Data
     * 
     * 1. Initialize Payment Method
     * 2. Validate Order Currency
     * 3. Get & Fill Payment Form
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
        /* Get & Fill Payment Form */
        $frm = $this->getPaymentForm();
        $frm->fill($this->formatRequestData());
        return ['frm' => $frm, 'order' => $this->order];
    }

    /**
     * Paypal Standard Callback Handler
     * 
     * 1. Initialize Payment Method
     * 2. Validate Received Post Data
     * 3. Validate received Checksum
     * 4. Verify Transaction Status
     * 5. Order Payment & Settlements
     * 
     * @param array $post
     * @return array
     */
    public function callbackHandler(array $post): array
    {
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return $this->returnError();
        }
        /* Validate Received Post Data */
        $orderId = FatUtility::int($post['custom'] ?? 0);
        if ($this->order['order_id'] != $orderId) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return $this->returnError();
        }
        $actionUrl = static::TEST_URL;
        if ($this->settings['enable_live_payment'] > 0) {
            $actionUrl = static::LIVE_URL;
        }
        unset($post['url']);
        /* Validate received post */
        if (!$res = $this->exeCurlRequest($actionUrl, $post)) {
            return $this->returnError();
        }
        /* Verify Transaction Status */
        if (!isset($res['VERIFIED']) || (strtoupper($post['payment_status']) != 'COMPLETED')) {
            $this->error = Label::getLabel('LBL_TRANSACTION_NOT_APPROVED');
            return $this->returnError();
        }
        /* Order Payment & Settlements */
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($post['txn_id'], $post['mc_gross'], $post)) {
            $this->error = $payment->getError();
            return $this->returnError();
        }
        return $this->returnSuccess();
    }

    /**
     * Paypal Standard Return Handler
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        unset($post['url']);
        $orderId = FatUtility::int($post['custom'] ?? 0);
        if ($this->order['order_id'] != $orderId) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return $this->returnError();
        }
        if (empty($post) || empty($post['payment_status']) || strtoupper($post['payment_status']) != 'COMPLETED') {
            $this->error = Label::getLabel('LBL_TRANSACTION_NOT_APPROVED');
            return $this->returnError();
        }
        return $this->returnSuccess();
    }

    /**
     * Get Payment Form
     * 
     * @return Form
     */
    private function getPaymentForm(): Form
    {
        $actionUrl = static::TEST_URL;
        if ($this->settings['enable_live_payment'] > 0) {
            $actionUrl = static::LIVE_URL;
        }
        $frm = new Form('frmPaypalStandard', ['id' => 'frmPayPalStandard', 'action' => $actionUrl]);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'business', '');
        $frm->addHiddenField('', 'cmd', "_cart");
        $frm->addHiddenField('', 'upload', "1");
        $frm->addHiddenField('', 'item_name_1', '');
        $frm->addHiddenField('', 'item_number_1', '');
        $frm->addHiddenField('', 'amount_1', '');
        $frm->addHiddenField('', 'quantity_1', '');
        $frm->addHiddenField('', 'currency_code', '');
        $frm->addHiddenField('', 'first_name', '');
        $frm->addHiddenField('', 'address1', '');
        $frm->addHiddenField('', 'address2', '');
        $frm->addHiddenField('', 'city', '');
        $frm->addHiddenField('', 'zip', '');
        $frm->addHiddenField('', 'country', '');
        $frm->addHiddenField('', 'address_override', '');
        $frm->addHiddenField('', 'email', '');
        $frm->addHiddenField('', 'invoice', '');
        $frm->addHiddenField('', 'lc', '');
        $frm->addHiddenField('', 'rm', '');
        $frm->addHiddenField('', 'no_note', '');
        $frm->addHiddenField('', 'no_shipping', '');
        $frm->addHiddenField('', 'charset', '');
        $frm->addHiddenField('', 'return', '');
        $frm->addHiddenField('', 'notify_url', '');
        $frm->addHiddenField('', 'cancel_return', '');
        $frm->addHiddenField('', 'paymentaction', '');
        $frm->addHiddenField('', 'custom', '');
        $frm->addHiddenField('', 'bn', 'paypal_bn');
        return $frm;
    }

    /**
     * Get Request Data
     * 
     * @return array
     */
    private function formatRequestData(): array
    {
        $orderId = FatUtility::int($this->order['order_id']);
        return [
            'business' => $this->settings['merchant_email'],
            'item_name_1' => str_replace('{orderid}', $orderId, Label::getLabel('MSG_ORDER_PAYMENT_DESCRIPTION_{orderid}')),
            'item_number_1' => $orderId,
            'quantity_1' => 1,
            'amount_1' => $this->order['order_net_amount'],
            'currency_code' => $this->order['order_currency_code'],
            'first_name' => $this->order['user_first_name'],
            'email' => $this->order['user_email'],
            'address_override' => 0,
            'invoice' => $orderId,
            'lc' => Language::getAttributesById($this->order['user_lang_id'], 'language_code'),
            'rm' => 2,
            'no_note' => 1,
            'no_shipping' => 1,
            'charset' => "utf-8",
            'return' => MyUtility::makeFullUrl('Payment', 'return', [$orderId]),
            'notify_url' => MyUtility::makeFullUrl('Payment', 'callback', [$orderId]),
            'cancel_return' => MyUtility::makeFullUrl('Payment', 'cancel', [$orderId]),
            'paymentaction' => 'sale',
            'custom' => $orderId,
            'bn' => 'paypal_bn',
        ];
    }

    /**
     * Validate Currency
     * @param string $code
     * @return bool
     */
    private function validateCurrency(string $code): bool
    {
        $arr = [
            'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'MYR', 'MXN',
            'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'USD'
        ];
        if (!in_array($code, $arr)) {
            $this->error = Label::getLabel("MSG_INVALID_CURRENCY_CODE");
            return false;
        }
        return true;
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
        $request = 'cmd=_notify-validate';
        foreach ($params as $key => $value) {
            $request .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Connection: Close', 'User-Agent:paypalPayment']);
        $curlResult = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->error = 'Error:' . curl_error($curl);
            return false;
        }
        curl_close($curl);
        parse_str($curlResult, $result);
        if (array_key_exists('ERROR', $result)) {
            $this->error = $result['ERROR'];
            return false;
        }
        return $result;
    }

}
