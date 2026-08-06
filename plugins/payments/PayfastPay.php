<?php

/**
 * Payfast Pay
 * 
 * Documentation Link
 * @link https://developers.payfast.co.za/docs
 * @author Fatbit Technologies
 */
class PayfastPay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;
    public $settings;
    private $requestBody = [];
    public const MIN_ORDER_AMOUNT = 10.00;

    const KEY = 'PayfastPay';

    public const PRODUCTION_HOST = 'https://www.payfast.co.za';
    public const PRODUCTION_URL = self::PRODUCTION_HOST . '/eng/process';
    public const PRODUCTION_VALIDATE_URL = self::PRODUCTION_HOST . '/eng/query/validate';

    public const SANDBOX_HOST = 'https://sandbox.payfast.co.za';
    public const SANDBOX_URL = self::SANDBOX_HOST . '/eng/process';
    public const SANDBOX_VALIDATE_URL = self::SANDBOX_HOST . '/eng/query/validate';

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
        if (empty($this->settings['passphrase']) || empty($this->settings['merchant_id']) || empty($this->settings['merchant_key'])) {
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
        /* Validate order Amount  */
        $amount = $this->formatAmount($this->order['order_net_amount']);
        if (!$this->validateMinAmount($amount)) {
            $this->error = Label::getLabel('LBL_ORDER_AMOUNT_MUST_NOT_BE_LESS_THAN_') . MyUtility::formatMoney(self::MIN_ORDER_AMOUNT);
            return false;
        }
        /* Format Request Data */
        if (!$this->formatRequestData()) {
            return false;
        }
        /*  Get & Fill Payment Form */
        $frm = $this->getPaymentForm();
        return ['frm' => $frm, 'order' => $this->order];
    }

    /**
     * Payfast Callback
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
        $post = FatApp::getPostedData();
        $refId = $post['pf_payment_id'] ?? '';
        if (empty($refId)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return $this->returnError();
        }
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return $this->returnError();
        }
        /* Validate received post Signature */
        if (!$this->validateResponseSignature($post)) {
            $this->error = Label::getLabel('LBL_INVALID_SIGNATURE');
            return $this->returnError();
        }
        /* Validate IP */
        if (!$this->validateIP()) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return $this->returnError();
        }

        $paramString = $this->generateParamString($post);
        /* Validate request */
        if (!$this->validServerConfirmation($paramString)) {
            $this->error = Label::getLabel('LBL_INVALID_SERVER_CONFIRMATION');
            return $this->returnError();
        }
        $amount = $this->order['order_net_amount'];

        /* Validate Amount  */
        if ($this->validPaymentAmount($this->formatAmount($amount), $post['amount_gross']) === false) {
            $this->error = Label::getLabel('LBL_INVALID_PAYMENT_AMOUNT');
        }

        if ($post['payment_status'] != 'COMPLETE') {
            $this->error = Label::getLabel('LBL_SOMETHING_WENT_WRONG');
            return $this->returnError();
        }

        /* Order Payment & Settlements */
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($refId, $amount, $post)) {
            $this->error = $payment->getError();
            return $this->returnError();
        }

        return $this->returnSuccess();
    }

    /**
     * Payfast Return Handler
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        if (Order::ISPAID == $this->order['order_payment_status']) {
            return $this->returnSuccess();
        }

        $this->error = Label::getLabel('LBL_SOMETHING_WENT_WRONG');
        return $this->returnError();
    }

    /**
     * Get Payment Form
     * 
     * @return \Form
     */
    private function getPaymentForm(): Form
    {
        $actionUrl = $this->settings['enable_live_payment'] > 0 ? static::PRODUCTION_URL : static::SANDBOX_URL;
        $frm = new Form('payfastPayForm', ['id' => 'payfastPayForm', 'action' => $actionUrl]);
        $frm = CommonHelper::setFormProperties($frm);
        foreach ($this->requestBody as $name => $value) {
            $frm->addHiddenField('', $name, $value);
        }
        return $frm;
    }

    /**
     * Format Request Data
     * 
     * @return array
     */
    private function formatRequestData()
    {
        $order = $this->order;
        $this->requestBody = [
            'merchant_id' => $this->settings['merchant_id'],
            'merchant_key' => $this->settings['merchant_key'],
            'return_url' => MyUtility::makeFullUrl('Payment', 'return', [$order['order_id']]),
            'cancel_url' => MyUtility::makeFullUrl('Payment', 'cancel', [$order['order_id']]),
            'notify_url' =>  MyUtility::makeFullUrl('Payment', 'callback', [$order['order_id']]),
            // Buyer details
            'name_first' => $order['user_name'],
            'email_address' =>  $order['user_email'],
            // Transaction details
            'm_payment_id' => $order['order_id'],
            'amount' =>  $this->formatAmount($order['order_net_amount']),
            'item_name' => 'Order#' . $order['order_id'],
            'passphrase' => $this->settings['passphrase']
        ];
        if (!$this->generateSignature()) {
            $this->error = Label::getLabel('LBL_ERROR_IN_REQUEST!');
            return false;
        }
        return true;
    }

    /**
     * Format Amount
     * 
     * @param float $amount
     * @return float
     */
    private function formatAmount(float $amount): float
    {
        return number_format(sprintf('%.2f', $amount), 2, '.', '');
    }

    /**
     * Validate Currency
     * @param string $code
     * @return bool
     */
    private function validateCurrency(string $code): bool
    {
        $arr = ['ZAR'];
        if (!in_array($code, $arr)) {
            $this->error = Label::getLabel("MSG_INVALID_CURRENCY_CODE");
            return false;
        }
        return true;
    }

    private function generateSignature()
    {

        $signature = md5(http_build_query($this->requestBody));
        if (empty($signature)) {
            return false;
        }
        $this->requestBody['signature'] =  $signature;
        return true;
    }


    /**
     * validateResponseSignature
     *
     * @param  array $response
     * @return bool
     */
    private function validateResponseSignature(array $response): bool
    {

        $responseSignature = $response['signature'];
        unset($response['signature']);
        $response['passphrase'] = $this->settings['passphrase'];
        $signature = md5(http_build_query($response));
        return ($responseSignature === $signature);
    }


    /**
     * validateIP
     *
     * @return boolean
     */
    private function validateIP()
    {
        $validHosts = array(
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
        );

        $validIps = [];

        foreach ($validHosts as $pfHostname) {
            $ips = gethostbynamel($pfHostname);

            if ($ips !== false) {
                $validIps = array_merge($validIps, $ips);
            }
        }

        $validIps = array_unique($validIps);
        $referrerIp = gethostbyname(parse_url($_SERVER['HTTP_REFERER'])['host']);
        if (in_array($referrerIp, $validIps, true)) {
            return true;
        }
        return false;
    }

    /**
     * validServerConfirmation
     * @param string $paramString parameters used to create signature
     * @param string $proxy proxy on or not
     * @return boolean
     */
    private  function validServerConfirmation($paramString, $proxy = null)
    {
        if (in_array('curl', get_loaded_extensions(), true)) {
            $validateUrl = $this->settings['enable_live_payment'] > 0 ? static::PRODUCTION_VALIDATE_URL : static::SANDBOX_VALIDATE_URL;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, null);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            curl_setopt($ch, CURLOPT_URL, $validateUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramString);
            if (!empty($proxy)) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
            }

            $response = curl_exec($ch);
            curl_close($ch);
            if (strtoupper($response) === 'VALID') {
                return true;
            }
        }

        return false;
    }

    /**
     * validPaymentAmount
     *
     * @param string $initialPaymentAmount actual cart amount
     * @param string $pgDebited actual deducted amount at pafast gateway
     * @return boolean
     */
    private function validPaymentAmount(float $initialPaymentAmount, float $pgDebited)
    {
        return !(abs($initialPaymentAmount - $pgDebited) > 0.01);
    }

    /**
     * generateParamString
     *
     * @param array $post returned data from Payfast
     * @return string
     */
    private function generateParamString($post)
    {
        unset($post['signature']);
        return http_build_query($post);
    }

    private function validateMinAmount($amount)
    {
        return ($amount >= self::MIN_ORDER_AMOUNT);
    }
}
