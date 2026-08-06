<?php

/**
 * This class is used to handle Paypal Payouts
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class PaypalPayout extends FatModel
{

    public $pmethod;
    public $settings;

    const KEY = 'PaypalPayout';
    const TOKEN_TEST_URL = 'https://api.sandbox.paypal.com/v1/oauth2/token';
    const TOKEN_LIVE_URL = 'https://api.paypal.com/v1/oauth2/token';
    const PAYOUT_TEST_URL = 'https://api.sandbox.paypal.com/v1/payments/payouts';
    const PAYOUT_LIVE_URL = 'https://api.paypal.com/v1/payments/payouts';

    public function __construct()
    {
        $this->pmethod = [];
        $this->settings = [];
        parent::__construct();
    }

    /**
     * Release Payout Payment
     * 
     * 1. Initialize Payment Payout
     * 2. Get Payout Access Token
     * 3. Validate Currency Code
     * 4. Format Data & Send Request
     * 5. Update Withdraw Request Status
     * 
     * @param array $record
     * @return bool
     */
    public function release(array $record): bool
    {
        /* Get Payout Access Token */
        if (!$this->initPayemtMethod()) {
            return false;
        }
        /* Get Access Token */
        if (!$token = $this->getAccessToken()) {
            return false;
        }
        /* Validate Currency Code */
        $currency = MyUtility::getSystemCurrency();
        if (!$this->validateCurrency($currency['currency_code'])) {
            return false;
        }
        /* Format Data & Send Request */
        $reqData = $this->formatRequestData($record);
        if (!$response = $this->sendRequest($token, $reqData)) {
            return false;
        }
        $data = [
            'withdrawal_response' => json_encode($response),
            'withdrawal_user_id' => $record['withdrawal_user_id'],
            'withdrawal_transaction_fee' => $record['gatewayFee'],
            'withdrawal_status' => WithdrawRequest::STATUS_PAYOUT_SENT,
        ];
        /* Update Withdraw Request Status */
        $req = new WithdrawRequest($record['withdrawal_id']);
        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$req->updateStatus($data)) {
            $this->error = $req->getError();
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Initialize Payment Payout
     * 
     * 1. Load Payment Method
     * 2. Format Payment Settings
     * 3. Validate Payment Settings
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
        if (empty($this->settings['client_id']) || empty($this->settings['client_secret'])) {
            $this->error = Label::getLabel("MSG_INCOMPLETE_PAYOUT_GATEWAY_SETUP");
            return false;
        }

        return true;
    }

    /**
     * Get Access Token
     * 
     * @return string
     */
    private function getAccessToken(): string
    {
        $actionUrl = static::TOKEN_TEST_URL;
        if ($this->settings['enable_live_payment'] > 0) {
            $actionUrl = static::TOKEN_LIVE_URL;
        }
        $userpwd = $this->settings['client_id'] . ':' . $this->settings['client_secret'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $actionUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json', 'Accept-Language: en_US'
        ]);
        $result = curl_exec($ch);
        $response = json_decode($result ?? [], true);
        if (empty($response) || curl_errno($ch)) {
            $this->error = 'Error:' . curl_error($ch);
            return false;
        }
        curl_close($ch);
        if (!array_key_exists('access_token', $response)) {
            $this->error = $response['error'] . ' : ' . $response['error_description'];
            return false;
        }
        return $response['access_token'];
    }

    /**
     * Validate Currency
     * 
     * @param string $code
     * @return bool
     */
    private function validateCurrency(string $code): bool
    {
        $arr = [
            'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD',
            'HUF', 'ILS', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN',
            'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'USD'
        ];
        if (!in_array($code, $arr)) {
            $this->error = Label::getLabel("MSG_INVALID_CURRENCY_CODE");
            return false;
        }
        return true;
    }

    /**
     * Format Request Data
     * 
     * @return array
     */
    private function formatRequestData(array $params): array
    {
        return [
            'sender_batch_header' => [
                'sender_batch_id' => 'Payout_' . time() . '_' . $params['withdrawal_id'],
                'email_subject' => Label::getLabel('MSG_YOU_HAVE_A_PAYOUT!'),
                'email_message' => Label::getLabel('MSG_YOU_HAVE_A_RECEIVED_A_PAYOUT')
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => (string) round($params['amount'], 2),
                        'currency' => MyUtility::getSiteCurrency()['currency_code']
                    ],
                    'note' => Label::getLabel('MSG_TXN_Fee:') . ' ' . MyUtility::formatMoney($params['gatewayFee']),
                    'sender_item_id' => time() . '_' . $params['withdrawal_id'],
                    'receiver' => $params['withdrawal_paypal_email_id'],
                ]
            ]
        ];
    }

    /**
     * Send Request
     * 
     * @param string $token
     * @param array $params
     * @return type
     */
    private function sendRequest(string $token, array $params)
    {
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $token];
        $actionUrl = static::PAYOUT_TEST_URL;
        if ($this->settings['enable_live_payment'] > 0) {
            $actionUrl = static::PAYOUT_LIVE_URL;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $actionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error = 'Error:' . curl_error($ch);
            return false;
        }
        curl_close($ch);
        $response = json_decode($result, true);
        if (!array_key_exists('batch_header', $response)) {
            if (array_key_exists('message', $response)) {
                $this->error = $response['name'] . ' : ' . $response['message'];
            } else {
                $this->error = $response['details'][0]['issue'];
            }
            return false;
        }
        return $response;
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
        $fld = $frm->addEmailField(Label::getLabel('LBL_Paypal_Email'), 'ub_paypal_email_address');
        $fld->requirements()->setRequired(true);
        $fld = $frm->addRequiredField($defaultCurLbl, 'withdrawal_amount');
        $fld->requirements()->setFloat(true);
        $fld->requirements()->setRange(FatApp::getConfig("CONF_MIN_WITHDRAW_LIMIT"), 9999999999);
        $frm->addTextArea(Label::getLabel('LBL_Other_Info_Instructions'), 'withdrawal_comments');
        $frm->addHiddenField('', 'pmethod_code', PaypalPayout::KEY);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Send_Request'));
        $frm->addButton("", "btn_cancel", Label::getLabel('LBL_Cancel'));
        return $frm;
    }

}
