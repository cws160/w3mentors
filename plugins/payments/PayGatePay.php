<?php

/**
 * Pay Gate Pay
 * 
 * PayWeb is a secure payment system hosted by PayGate. A single integration to 
 * PayWeb gives you access to multiple payment methods. PayGate is a PCI compliant 
 * payment service provider. PayGate is continually adding to the list of available payment methods.
 * 
 * Documentation Link
 * @link https://docs.paygate.co.za/#payweb-3
 * @author Fatbit Technologies
 */
class PayGatePay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;
    public $settings;

    const KEY = 'PayGatePay';
    const INIT_URL = 'https://secure.paygate.co.za/payweb3/initiate.trans';
    const PROCESS_URL = 'https://secure.paygate.co.za/payweb3/process.trans';
    const QUERY_URL = 'https://secure.paygate.co.za/payweb3/query.trans';

    /* Transaction Statuses */
    const TXN_NOT_DONE = 0;
    const TXN_APPROVED = 1;
    const TXN_DECLINED = 2;
    const TXN_CANCELLED = 3;
    const TXN_USER_CANCELLED = 4;
    const TXN_RECEIVED = 5;
    const TXN_SETTLEMENT = 7;

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
        if (empty($this->settings['paygateId']) || empty($this->settings['encryptionKey'])) {
            $this->error = Label::getLabel("MSG_INCOMPLETE_PAYMENT_GATEWAY_SETUP");
            return false;
        }
        return true;
    }

    /**
     * Get Payment Data
     * 
     * 1. Initialize Payment Method
     * 2. Format Request Data
     * 3. Generate Request Checksum
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
        /* Format Request Data */
        $request = $this->formatRequestData();
        /* Generate Request Checksum */
        $request['CHECKSUM'] = $this->generateChecksum($request);
        /* Execute Curl Request */
        if (!$response = $this->exeCurlRequest(static::INIT_URL, $request)) {
            return false;
        }
        /* Get & Fill Payment Form */
        $frm = $this->getPaymentForm();
        $frm->fill($response);
        return ['frm' => $frm, 'order' => $this->order];
    }

    /**
     * PayGate Callback
     * 
     * 1. Initialize Payment Method
     * 2. Validate received Checksum
     * 3. Verify Transaction Status
     * 4. Order Payment & Settlements
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
        /* Validate received post */
        if (
                empty($post) || empty($post['TRANSACTION_ID']) ||
                $post['REFERENCE'] != $this->order['order_id'] ||
                $post['PAYGATE_ID'] != $this->settings['paygateId']
        ) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return $this->returnError();
        }
        $checksum = $post['CHECKSUM'];
        unset($post['CHECKSUM']);
        if (!$this->validateChecksum($checksum, $post)) {
            return $this->returnError();
        }
        /* Verify Transaction Status */
        if (static::TXN_APPROVED != ($post['TRANSACTION_STATUS'])) {
            $this->error = Label::getLabel('LBL_TRANSACTION_NOT_APPROVED');
            return $this->returnError();
        }
        /* Order Payment & Settlements */
        $amount = $this->order['order_net_amount'];
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($post['TRANSACTION_ID'], $amount, $post)) {
            $this->error = $payment->getError();
            return $this->returnError();
        }
        return $this->returnSuccess();
    }

    /**
     * PayGate Return Handler
     * 
     * 1. Initialize Payment Method
     * 2. Validate Request Checksum
     * 3. Verify Transaction Status
     * 4. Order Payment & Settlements
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return $this->returnError();
        }
        /* Validate Request Checksum */
        $checksum = $post['CHECKSUM'] ?? '';
        $requestId = $post['PAY_REQUEST_ID'] ?? '';
        $txnStatus = $post['TRANSACTION_STATUS'] ?? '';
        $param = [
            'PAYGATE_ID' => $this->settings['paygateId'],
            'PAY_REQUEST_ID' => $requestId,
            'TRANSACTION_STATUS' => $txnStatus,
            'REFERENCE' => $this->order['order_id']
        ];
        if (!$this->validateChecksum($checksum, $param)) {
            return $this->returnError();
        }
        /* Verify Transaction Status */
        if (!in_array($txnStatus, [static::TXN_APPROVED, static::TXN_RECEIVED])) {
            $this->error = static::getStatus($txnStatus);
            return $this->returnError();
        }
        /* Generate Request Checksum */
        $query = [
            'PAYGATE_ID' => $this->settings['paygateId'],
            'PAY_REQUEST_ID' => $requestId,
            'REFERENCE' => $this->order['order_id']
        ];
        $query['CHECKSUM'] = $this->generateChecksum($query);
        /* Execute Curl Request */
        $res = $this->exeCurlRequest(static::QUERY_URL, $query);
        if (
                empty($res) || empty($res['TRANSACTION_ID']) ||
                $res['REFERENCE'] != $this->order['order_id'] ||
                $res['PAYGATE_ID'] != $this->settings['paygateId']
        ) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return $this->returnError();
        }
        /* Order Payment & Settlements */
        $amount = $this->order['order_net_amount'];
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($res['TRANSACTION_ID'], $amount, $res)) {
            $this->error = $payment->getError();
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
        $frm = new Form('payGatePayForm', ['action' => static::PROCESS_URL]);
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'PAY_REQUEST_ID', '');
        $fld->requirements()->setRequired(true);
        $fld = $frm->addHiddenField('', 'CHECKSUM', '');
        $fld->requirements()->setRequired(true);
        return $frm;
    }

    /**
     * Format Request Data
     * 
     * Do not change parameters sort order of $request
     * It is restricted by Payment gateway PayGatePay
     * 
     * @return array
     */
    private function formatRequestData(): array
    {
        $order = $this->order;
        $amount = FatUtility::float($order['order_net_amount']);
        $request = [
            'PAYGATE_ID' => $this->settings['paygateId'],
            'REFERENCE' => $order['order_id'],
            'AMOUNT' => number_format($amount, 2, '.', '') * 100,
            'CURRENCY' => $order["order_currency_code"],
            'RETURN_URL' => MyUtility::makeFullUrl('Payment', 'return', [$order['order_id']]),
            'TRANSACTION_DATE' => (new DateTime())->format('Y-m-d H:i:s'),
            'LOCALE' => strtolower(MyUtility::getSiteLanguage()['language_code']),
            'COUNTRY' => 'ZAF',
            'EMAIL' => $order['user_email'],
            'NOTIFY_URL' => MyUtility::makeFullUrl('Payment', 'callback', [$order['order_id']])
        ];
        return $request;
    }

    /**
     * Generate Checksum
     * 
     * @param array $params
     * @return string
     */
    private function generateChecksum(array $params): string
    {
        $checksum = '';
        foreach ($params as $key => $value) {
            if ($value != '') {
                $checksum .= $value;
            }
        }
        return md5($checksum . $this->settings['encryptionKey']);
    }

    /**
     * Validate Checksum
     * 
     * @param string $checksum
     * @param array $params
     * @return bool
     */
    private function validateChecksum(string $checksum, array $params): bool
    {
        if ($checksum != $this->generateChecksum($params)) {
            $this->error = Label::getLabel('MSG_CHECKSUM_NOT_VALID');
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
        $posted = http_build_query($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $posted);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
        $curlResult = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = 'Error:' . curl_error($ch);
            return false;
        }
        curl_close($ch);
        parse_str($curlResult, $result);
        if (array_key_exists('ERROR', $result)) {
            $this->error = $result['ERROR'];
            return false;
        }
        return $result;
    }

    private static function getStatus(int $key)
    {
        $arr = [
            static::TXN_NOT_DONE => Label::getLabel('TXN_NOT_DONE'),
            static::TXN_APPROVED => Label::getLabel('TXN_APPROVED'),
            static::TXN_DECLINED => Label::getLabel('TXN_DECLINED'),
            static::TXN_CANCELLED => Label::getLabel('TXN_CANCELLED'),
            static::TXN_USER_CANCELLED => Label::getLabel('TXN_USER_CANCELLED'),
            static::TXN_RECEIVED => Label::getLabel('TXN_RECEIVED'),
            static::TXN_SETTLEMENT => Label::getLabel('TXN_SETTLEMENT')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

}
