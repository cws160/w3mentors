<?php
/**
 * Authorize Pay
 * 
 * @author Fatbit Technologies
 */
class MpesaPay extends Payment implements PaymentInterface
{
    public $order;
    public $pmethod;
    public $settings;

    const KEY = 'MpesaPay';
    const LIVE_URL = 'https://api.safaricom.co.ke';
    const TEST_URL = 'https://sandbox.safaricom.co.ke';
    
    private $tokenResponse = [];

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
        if (
            empty($this->settings['shortcode']) || empty($this->settings['passkey']) || 
            empty($this->settings["consumer_key"]) || empty($this->settings["consumer_secret"]) ||
            empty($this->settings['till_number']) || empty($this->settings['transaction_type'])
        ) {
            $this->error = Label::getLabel("MSG_INCOMPLETE_PAYMENT_GATEWAY_SETUP");
            return false;
        }
        return true;
    }

     /**
     * Get Payment Data
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
        $frm = $this->getPaymentForm();
        return ['frm' => $frm, 'order' => $this->order];
    }

    /**
     * Mpese Payemnt Request For User
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        if(!$response = $this->STKPushSimulation($post)){
            return $this->returnError();
        }
        if (array_key_exists('errorMessage', $response)) {
            $this->error = $response['errorMessage'];
            return $this->returnError();
        }
        if (array_key_exists('ResponseCode', $response)) {
            if (0 < $response['ResponseCode']) {
                $this->error = $response['ResponseDescription'];
                return $this->returnError();
            }
            return $this->returnSuccess();
        }
        $this->error = Label::getLabel('ERR_SOMETHING_WENT_WRONG');
        return $this->returnError();
    }

    public function callbackHandler(array $post): array
    {
        $callbackResponse = $post['callback_response'] ?? $post;
        $stkCallback = $callbackResponse['Body']['stkCallback'] ?? [];
        $checkoutRequestID = $stkCallback['CheckoutRequestID'] ?? '';
        $error = empty($checkoutRequestID);

        if($checkoutRequestID && isset($stkCallback['ResultCode']) && !$stkCallback['ResultCode']){
            if(!$response = $this->STKPushQuery($checkoutRequestID)){
                return $this->returnError();
            }
            /**
             * 0 Success (for C2B).
             * 00000000	Success (For APIs that are not C2B).
             * 1 or any other number Rejecting the transaction.
             */
            $error = true;
            if (array_key_exists('ResponseCode', $response) && 1 != $response['ResponseCode']) {
                $error = ($response['ResultCode'] != $stkCallback['ResultCode']);
            }

            if (!$error) {
                $callbackMetadata = $stkCallback['CallbackMetadata'];
                $payment_amount = 0;
                $txnId = '';
                foreach ($callbackMetadata['Item'] as $orderTxn) {
                    if ('amount' == strtolower($orderTxn['Name'])) {
                        $payment_amount = $orderTxn['Value'];
                    }

                    if ('mpesareceiptnumber' == strtolower($orderTxn['Name'])) {
                        $txnId = $orderTxn['Value'];
                    }

                    if (!empty($payment_amount) && !empty($txnId)) {
                        break;
                    }
                }
                /* Order Payment & Settlements */
                $payment = new OrderPayment($this->order['order_id']);
                if (!$payment->paymentSettlements($txnId, $payment_amount, $post)) {
                    $this->error = $payment->getError();
                    return $this->returnError();
                }
                return $this->returnSuccess();
            }
        }       
        $this->error = $this->getResultCodeName($stkCallback['ResultCode']);
        return $this->returnError();
    }

    /**
     * Validate Currency
     * @param string $code
     * @return bool
     */
    private function validateCurrency(string $code): bool
    {
        $arr = [
            "CDF", "EGP", "GHS", "INR", "KES", "LSL", "ZAR", "MZN", "RON", "TZS", "USD"
        ];
        if (!in_array($code, $arr)) {
            $this->error = Label::getLabel("MSG_INVALID_CURRENCY_CODE");
            return false;
        }
        return true;
    }

    /**
     * generateToken - This is used to generate tokens for the sandbox/live environment
     *
     * @return bool
     */
    private function generateToken(): bool
    {
        if(!$this->initPayemtMethod()){
            return false;
        }
        $credentials = base64_encode($this->settings["consumer_key"] . ':' . $this->settings["consumer_secret"]);
        $url = $this->tokenUrl();
        $headers = ['Authorization: Basic ' . $credentials];
        $this->tokenResponse = json_decode($this->exeCurlRequest($url, $headers), true);
        return true;
    }

    /**
     * getToken
     *
     * @return string
     */
    private function getToken(): mixed
    {
        if(!$this->tokenResponse){
            return false;
        }
        return $this->tokenResponse['access_token'] ?? false;
    }



    /**
     * STKPushSimulation - Use this function to initiate an STKPush Simulation
     * 
     * @param  float $amount - This is the Amount transacted normaly a numeric value. Money that customer pays to the Shorcode. Only whole numbers are supported.
     * @param  string $customerPhone - The phone number sending money. The parameter expected is a Valid Safaricom Mobile Number that is M-Pesa registered in the format 2547XXXXXXXX. The MSISDN sending the funds
     * @param  string $transactionDesc - This is any additional information/comment that can be sent along with the request from your system. Maximum of 13 Characters.
     * @return mixed
     */

    private function STKPushSimulation($post): mixed
    {
        if(!$this->generateToken()){
            return false;
        }

        if(!$token = $this->getToken()){
            $this->error = Label::getLabel('LBL_INVALID_TOKEN');
            return false;
        }
        
        $timestamp = date('YmdHis');
        $password = base64_encode($this->settings['shortcode'] . $this->settings['passkey'] . $timestamp);
        $post['customerPhone'] = str_replace(['+', ' '], "", $post['customerPhone']);
        $postFields = array(
            'BusinessShortCode' => $this->settings['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $this->settings['transaction_type'], // CustomerBuyGoodsOnline | CustomerPayBillOnline,
            'Amount' => MyUtility::formatMoney(ceil($this->order['order_net_amount']),false),
            'PartyA' => $post['customerPhone'],
            'PartyB' => $this->settings['till_number'], // will equal to shortcode in case of CustomerPayBillOnline
            'PhoneNumber' => $post['customerPhone'],
            'CallBackURL' => MyUtility::makeFullUrl('Payment', 'callback', [$this->order['order_id']]),
            'AccountReference' => $this->settings['account_reference'] ?? Label::getLabel('LBL_SITE_NAME'),
            'TransactionDesc' => $post['transactionDetails'] ?? "NA"
        );
        $url = $this->STKPushSimulationUrl();
        $headers = [
            'Content-Type:application/json',
            'Authorization:Bearer ' . $token
        ];
        $response = json_decode($this->exeCurlRequest($url, $headers, $postFields), true);
        if(empty($response)){
            $this->error = Label::getLabel('LBL_UNABLE_TO_MAKE_REQUEST');
            return false;
        }
        return $response;
    }

        
    /**
     * STKPushQuery - Use this function to initiate an STKPush Status Query request(To cross verify the callback response).
     *
     * @param  string $checkoutRequestID | Checkout RequestID
     * @return mixed
     */
    public function STKPushQuery(string $checkoutRequestID): mixed
    {
        if (!$this->generateToken()) {
            return false;
        }
        if(!$token = $this->getToken()){
            return false;
        }
        $timestamp = date('YmdHis');
        $password = base64_encode($this->settings['shortcode'] . $this->settings['passkey'] . $timestamp);
        
        $url = $this->STKPushQueryUrl();
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];
        $postFields = array(
            'BusinessShortCode' => $this->settings['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestID
        );

        $response = json_decode($this->exeCurlRequest($url, $headers, $postFields), true);
        if(empty($response)){
            $this->error = Label::getLabel('LBL_UNABLE_TO_MAKE_REQUEST');
            return false;
        }
        return $response;
    }

    /**
     * tokenUrl
     *
     * @return string
     */
    private function tokenUrl(): string
    {
        return ($this->settings['enable_live_payment'] ? static::LIVE_URL : static::TEST_URL) . '/oauth/v1/generate?grant_type=client_credentials';
    }

    /**
     * STKPushSimulationUrl
     *
     * @return string
     */
    private function STKPushSimulationUrl(): string
    {
        return ($this->settings['enable_live_payment'] ? static::LIVE_URL : static::TEST_URL) . '/mpesa/stkpush/v1/processrequest';
    }

    /**
     * STKPushQueryUrl
     *
     * @return string
     */
    public function STKPushQueryUrl(): string
    {
        return ($this->settings['enable_live_payment'] ? static::LIVE_URL : static::TEST_URL) . '/mpesa/stkpushquery/v1/query';
    }

    /**
     * getForm
     *
     * @param  mixed $orderId
     * @return object
     */
    private function getPaymentForm(): Form
    {
        $frm = new Form('frmPaymentForm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addRequiredField(Label::getLabel('FRM_PHONE_NUMBER'), 'customerPhone');
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PHONE_NO_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PHONE_NO_VALIDATION_MSG'));
        $frm->addTextArea(Label::getLabel('FRM_TRANSACTION_NOTE'), 'TransactionDesc');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('BTN_SUBMIT'));
        return $frm;
    }


    /**
     * getResultCodeName
     *
     * @param  int $code
     * @return object
     */
    private function getResultCodeName(int $code): string
    {
        $arr = [
            '0' => Label::getLabel('MSG_SUCCESS'),
            '1' => Label::getLabel('ERR_INSUFFICIENT_FUNDS'),
            '2' => Label::getLabel('ERR_LESS_THAN_MINIMUM_TRANSACTION_VALUE'),
            '3' => Label::getLabel('ERR_MORE_THAN_MAXIMUM_TRANSACTION_VALUE'),
            '4' => Label::getLabel('ERR_WOULD_EXCEED_DAILY_TRANSFER_LIMIT'),
            '5' => Label::getLabel('ERR_WOULD_EXCEED_MINIMUM_BALANCE'),
            '6' => Label::getLabel('ERR_UNRESOLVED_PRIMARY_PARTY'),
            '7' => Label::getLabel('ERR_UNRESOLVED_RECEIVER_PARTY'),
            '8' => Label::getLabel('ERR_WOULD_EXCEED_MAXIUMUM_BALANCE'),
            '11' => Label::getLabel('ERR_DEBIT_ACCOUNT_INVALID'),
            '12' => Label::getLabel('ERR_CREDIT_ACCOUNT_INVALID'),
            '13' => Label::getLabel('ERR_UNRESOLVED_DEBIT_ACCOUNT'),
            '14' => Label::getLabel('ERR_UNRESOLVED_CREDIT_ACCOUNT'),
            '15' => Label::getLabel('ERR_DUPLICATE_DETECTED'),
            '17' => Label::getLabel('ERR_INTERNAL_FAILURE'),
            '20' => Label::getLabel('ERR_UNRESOLVED_INITIATOR'),
            '26' => Label::getLabel('ERR_TRAFFIC_BLOCKING_CONDITION_IN_PLACE'),
        ];
        return array_key_exists($code, $arr) ? $arr[$code] : '';
    }


    private function exeCurlRequest(string $url, array $headers, array $postFields = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if($postFields){
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postFields));
        }else{
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        if (!$curlResponse = curl_exec($curl)) {
            $this->error = curl_error($curl);
            if (empty($this->error)) {
				$this->error = Label::getLabel('ERR_UNABLE_TO_PROCEED_REQUEST');
			}
            return false;
        }
        curl_close($curl);

        return $curlResponse;
    }
}