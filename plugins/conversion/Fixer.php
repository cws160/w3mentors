<?php

class Fixer extends FatModel
{

    private $settings;

    const BASE_URL = 'https://data.fixer.io/api/latest';

    public function __construct()
    {
        $this->settings = [];
        parent::__construct();
    }

    /**
     * Initialize the tool
     * 1. Format Tool Settings
     * 2. Validate Tool Settings
     * 
     * @return bool
     */
    public function init(): bool
    {
        /* Format Tool Settings */
        $this->settings = static::getConfig();
        /* Validate Meeting Tool Settings */
        if (empty($this->settings['api_key'])) {
            $this->error = Label::getLabel("MSG_FIXER_NOT_CONFIGURED");
            return false;
        }
        if ($this->settings['status'] == AppConstant::NO) {
            $this->error = Label::getLabel("MSG_FIXER_NOT_TOOL_ACTIVE");
            return false;
        }
        return true;
    }

    /**
     * get configuration
     *
     * @return array
     */
    public static function getConfig(): array
    {
        $srch = new SearchBase('tbl_configurations');
        $srch->addCondition('conf_name', '=', 'CONF_FIXER');
        $srch->addFld('conf_val');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $record = FatApp::getDb()->fetch($srch->getResultSet());
        $config = (!empty($record['conf_val'])) ? json_decode($record['conf_val'], true) : [];
        return [
            'api_key' => (!empty($config['api_key'])) ? $config['api_key'] : '',
            'status' => (!empty($config['status'])) ? AppConstant::YES : AppConstant::NO,
            'info' => (!empty($config['info'])) ? $config['info'] : '',
            'last_synced' => (!empty($config['last_synced'])) ? $config['last_synced'] : '',
        ];
    }

    /**
     * get sync rates
     *
     * @param string|null $baseCurrency
     * @param array|null $currencies
     * @return array|bool
     */
    public function syncRates(string $baseCurrency = null, array $currencies = null)
    {
        if (is_null($baseCurrency)) {
            $systemCurrency = MyUtility::getSystemCurrency();
            $baseCurrency = $systemCurrency['currency_code'];
        }
        if (is_null($currencies)) {
            $currencies = Currency::getAllCodes();
        }
        $params = ['base' => $baseCurrency, 'symbols' => implode(",", $currencies)];
        $response = $this->exeCurlRequest(static::BASE_URL, $params);
        if (!empty($response['message'])) {
            $this->error = $response['message'];
            return false;
        }
        if (!empty($response['error'])) {
            $this->error = $response['error'];
            return false;
        }
        if (empty($response['rates'])) {
            $this->error = Label::getLabel('LBL_NOT_ABLE_TO_SYNC_CURRENCIES');
            return false;
        }
        $this->updateLastSync();
        return $response;
    }

    /**
     * update sync date
     *
     * @return boolean
     */
    public function updateLastSync(): bool
    {
        $this->settings['last_synced'] = date('Y-m-d H:i:s');
        $record = new TableRecord('tbl_configurations');
        $record->assignValues(['conf_val' => json_encode($this->settings)]);
        if (!$record->update(['smt' => 'conf_name = ?', 'vals' => ['CONF_FIXER']])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Execute Curl Request
     *
     * @param string $url
     * @param array $params
     * @return boolean
     */
    private function exeCurlRequest(string $url, array $params = [])
    {
        $params['access_key'] = $this->settings['api_key'];
        $curl = curl_init($url . '?' . http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curlResult = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->error = 'Error:' . curl_error($curl);
            return false;
        }
        curl_close($curl);
        $response = json_decode($curlResult, true) ?? [];
        if (empty($response)) {
            $this->error = Label::getLabel('LBL_CONTACT_WITH_ADMIN_ISSUE_WITH_API');
            return false;
        }
        return $response;
    }

}
