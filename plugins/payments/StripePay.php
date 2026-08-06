<?php

use Stripe\Stripe as Stripe;
use Stripe\Checkout\Session as Session;

/**
 * Stripe Pay
 * 
 * @author Fatbit Technologies
 */
class StripePay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;
    public $settings;

    const KEY = 'StripePay';

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
        if (empty($this->settings['secret_key']) || empty($this->settings['publishable_key'])) {
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
     * 3. Create Stripe Session
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
        /* Create Stripe Session */
        if (!$session = $this->createSession()) {
            return false;
        }
        return [
            'order' => $this->order,
            'stripe' => $this->settings,
            'sessionId' => $session->id
        ];
    }

    /**
     * Stripe Callback
     * 
     * 1. Initialize Payment Method
     * 2. Retrieve stripe session
     * 3. Validate Session OrderId
     * 4. Validate Payment Status
     * 5. Order Payment & Settlements
     * 
     * @param array $post
     * @return array
     */
    public function callbackHandler(array $post): array
    {
        $sessionId = $post['session_id'] ?? '';
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return $this->returnError();
        }
        /* Retrieve stripe session */
        if (!$session = $this->retrieveSession($sessionId)) {
            return $this->returnError();
        }
        /* Validate Session OrderId */
        if (empty($session) || $this->order['order_id'] != $session->metadata->order_id) {
            $this->error = Label::getLabel('LBL_STRIPE_INVALID_SESSION');
            return $this->returnError();
        }
        /* Validate Payment Status */
        if (strtoupper($session->payment_status) != 'PAID') {
            $this->error = Label::getLabel('LBL_STRIPE_PAYMENT_NOT_CONFIRMED');
            return $this->returnError();
        }
        /* Order Payment & Settlements */
        $code = $this->order['order_currency_code'];
        $amount = $this->reformatAmount($code, $session->amount_total);
        $payment = new OrderPayment($this->order['order_id']);
        if (!$payment->paymentSettlements($sessionId, $amount, (array) $session)) {
            return $this->returnError();
        }
        return $this->returnSuccess();
    }

    /**
     * Stripe Return Handler
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        return $this->callbackHandler($post);
    }

    /**
     * Create Stripe Session
     */
    private function createSession()
    {
        try {
            Stripe::setApiKey($this->settings['secret_key']);
            $request = $this->formatRequestData($this->order);
            $session = Session::create($request);
        } catch (exception $exc) {
            $this->error = $exc->getMessage();
            return false;
        }
        return $session;
    }

    /**
     * Retrieve Stripe Session
     */
    private function retrieveSession(string $sessionId)
    {
        try {
            Stripe::setApiKey($this->settings['secret_key']);
            $session = Session::retrieve($sessionId);
        } catch (Exception $exc) {
            $this->error = $exc->getMessage();
            return false;
        }
        return $session;
    }

    /**
     * Format Order Data
     * 
     * @return array
     */
    private function formatRequestData(): array
    {
        $order = $this->order;
        return [
            'customer_email' => $order['user_email'],
            'payment_method_types' => ['card'],
            'metadata' => ['order_id' => $order['order_id']],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $order['order_currency_code'],
                        'product_data' => ['name' => Order::getTypeArr($order['order_type'])],
                        'unit_amount' => $this->formatAmount($order['order_currency_code'], $order['order_net_amount'])
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => MyUtility::makeFullUrl('Payment', 'callback', [$order['order_id']]) . "?session_id={CHECKOUT_SESSION_ID}",
            'cancel_url' => MyUtility::makeFullUrl('Payment', 'cancel', [$order['order_id']]),
        ];
    }

    /**
     * Format Payable Amount
     * 
     * @param string $code
     * @param float $amount
     * @return float
     */
    private function formatAmount(string $code, float $amount): float
    {
        if (in_array($code, $this->zeroDecimalCurrencies())) {
            return round($amount);
        }
        return number_format($amount, 2, '.', '') * 100;
    }

    private function reformatAmount(string $code, float $amount): float
    {
        if (in_array($code, $this->zeroDecimalCurrencies())) {
            return round($amount);
        }
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * Validate Currency
     * @param string $code
     * @return bool
     */
    private function validateCurrency(string $code): bool
    {
        $arr = [
            'USD', 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT',
            'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY',
            'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP',
            'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR',
            'ISK', 'JMD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD',
            'LSL', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR',
            'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG',
            'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD',
            'STD', 'SZL', 'THB', 'TJS', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'UYU', 'UZS', 'VND',
            'VUV', 'WST', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW'
        ];
        if (!in_array($code, $arr)) {
            $this->error = Label::getLabel("MSG_INVALID_CURRENCY_CODE");
            return false;
        }
        return true;
    }

    private function zeroDecimalCurrencies()
    {
        return ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];
    }

}
