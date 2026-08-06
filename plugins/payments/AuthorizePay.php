<?php

/**
 * Authorize Pay
 * 
 * @author Fatbit Technologies
 */
class AuthorizePay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;
    public $settings;

    const KEY = 'AuthorizePay';
    const LIVE_URL = 'https://api.authorize.net/xml/v1/request.api';
    const TEST_URL = 'https://apitest.authorize.net/xml/v1/request.api';

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
        if (
                empty($this->settings['login_id']) ||
                empty($this->settings['md5_hash']) ||
                empty($this->settings['transaction_key'])
        ) {
            $this->error = Label::getLabel("MSG_INCOMPLETE_PAYMENT_GATEWAY_SETUP");
            return false;
        }
        return true;
    }

    /**
     * Get Payment Data
     * 
     * 1. Initialize Payment Method
     * 2. Get & Fill Payment Form
     * 
     * @return bool|array
     */
    public function getChargeData()
    {
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return false;
        }
        /* Get & Fill Payment Form */
        $frm = $this->getPaymentForm();
        $frm->fill([
            'order_id' => $this->order['order_id'],
            'cc_owner' => $this->order['user_name']
        ]);
        return ['frm' => $frm, 'order' => $this->order];
    }

    /**
     * Callback Handler
     * 
     * 1. Initialize Payment Method
     * 2. Validate Received Post Data
     * 2. Format Curl Request Data
     * 3. Validate Transaction Response
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
        /* Validate Received Post Data */
        $frm = $this->getPaymentForm();
        if (!$post = $frm->getFormDataFromArray($post)) {
            $this->error = current($frm->getValidationErrors());
            return $this->returnError();
        }
        /* Format Curl Request Data */
        $params = $this->formatRequestData($post);
        $url = ($this->settings['enable_live_payment'] == 1) ? static::LIVE_URL : static::TEST_URL;
        if (!$res = $this->exeCurlRequest($url, $params)) {
            return $this->returnError();
        }
        /* Validate Transaction Response */
        if (($res['transactionResponse']['responseCode'] ?? '') != 1) {
            $this->error = $res['messages']['message'][0]['text'] ?? Label::getLabel('LBL_TRANSACTION_NOT_APPROVED');
            return $this->returnError();
        }
        /* Order Payment & Settlements */
        $txnId = $res['transactionResponse']['transId'] ?? '';
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($txnId, $this->order['order_net_amount'], $res)) {
            $this->error = $payment->getError();
            return $this->returnError();
        }
        return $this->returnSuccess();
    }

    /**
     * Return Handler
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
     * @return Form
     */
    private function getPaymentForm(): Form
    {
        $frm = new Form('frmPaymentForm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'order_id', '');
        $fld->requirements()->setRequired();
        $fld->requirements()->setIntPositive();
        $fld = $frm->addRequiredField(Label::getLabel('LBL_CREDIT_CARD_NUMBER'), 'cc_number');
        $fld->requirements()->setLength(12, 20);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_CARD_HOLDER_NAME'), 'cc_owner');
        $fld->requirements()->setLength(4, 40);
        $frm->addSelectBox(Label::getLabel('LBL_EXPIRY_MONTH'), 'cc_expire_date_month', AppConstant::getMonthsArr(), '', [], '');
        $years = range(date('Y'), date('Y') + 10);
        $frm->addSelectBox(Label::getLabel('LBL_EXPIRY_YEAR'), 'cc_expire_date_year', array_combine($years, $years), '', [], '');
        $fld = $frm->addPasswordField(Label::getLabel('LBL_CVV_SECURITY_CODE'), 'cc_cvv');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setLength(3, 5);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_PAY_NOW'));
        return $frm;
    }

    /**
     * Format Order Data
     * 
     * @param array $post
     * @return array
     */
    private function formatRequestData(array $post): array
    {
        $order = $this->order;
        $txnMode = $this->settings['enable_live_payment'] > 0 ? 'false' : 'true';
        $payGatewayDes = sprintf(Label::getLabel('MSG_ORDER_PAYMENT_DESCRIPTION_{orderid}'), $order['order_id']);
        return [
            "createTransactionRequest" => [
                "merchantAuthentication" => [
                    "name" => $this->settings['login_id'],
                    "transactionKey" => $this->settings['transaction_key']
                ],
                "refId" => $order['order_id'],
                "transactionRequest" => [
                    "transactionType" => 'authCaptureTransaction',
                    "amount" => $order['order_net_amount'],
                    "payment" => [
                        "creditCard" => [
                            "cardNumber" => str_replace(' ', '', $post['cc_number']),
                            "expirationDate" => $post['cc_expire_date_year'] . "-" . $post['cc_expire_date_month'],
                            "cardCode" => $post['cc_cvv'],
                        ]
                    ],
                    "order" => [
                        "invoiceNumber" => $order['order_id'],
                        "description" => FatUtility::decodeHtmlEntities($payGatewayDes, ENT_QUOTES, 'UTF-8')
                    ],
                    "lineItems" => [
                        "lineItem" => [
                            "itemId" => $order['order_id'],
                            "name" => Label::getLabel("LBL_CARD_PAYMENT"),
                            "description" => $payGatewayDes,
                            "quantity" => "1",
                            "unitPrice" => $order['order_net_amount'],
                        ]
                    ],
                    "customerIP" => $_SERVER['REMOTE_ADDR'],
                    "transactionSettings" => [
                        "setting" => [
                            "settingName" => "testRequest",
                            "settingValue" => $txnMode
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Execute Curl Request
     * 
     * @param string $url
     * @param array $params
     * @return bool|array
     */
    public function exeCurlRequest(string $url, array $params)
    {
        $params = json_encode($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($params)
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $res = curl_exec($ch);
        curl_close($ch);
        $response = json_decode(trim($res, "﻿"), true);
        if (empty($response)) {
            $this->error = Label::getLabel("MSG_INVALID_ACCESS");
            return false;
        }
        return $response;
    }

}
