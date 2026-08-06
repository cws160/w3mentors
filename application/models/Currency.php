<?php

/**
 * This class is used to handle Currency
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Currency extends MyAppModel
{

    const DB_TBL = 'tbl_currencies';
    const DB_TBL_PREFIX = 'currency_';
    const DB_TBL_LANG = 'tbl_currencies_lang';
    const DB_TBL_LANG_PREFIX = 'currencylang_';

    /**
     * Initialize Currency
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'currency_id', $id);
        $this->objMainTableRecord->setSensitiveFields(['currency_is_default']);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $isActive
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0, bool $isActive = true): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'curr');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'curr_l.currencylang_currency_id '
                    . ' = curr.currency_id and curr_l.currencylang_lang_id = ' . $langId, 'curr_l');
        }
        if ($isActive) {
            $srch->addCondition('curr.currency_active', '=', 1);
        }
        return $srch;
    }

    /**
     * Get Currency Name With Code
     * 
     * @param int $langId
     * @return bool|array
     */
    public static function getCurrencyNameWithCode(int $langId)
    {
        $srch = self::getSearchObject($langId);
        $srch->addMultipleFields(['currency_id', 'CONCAT(IFNULL(curr_l.currency_name,curr.currency_code)," (",currency_code ,")") as currency_name_code']);
        $srch->addOrder('currency_order');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $row = FatApp::getDb()->fetchAllAssoc($srch->getResultSet(), 'currency_id');
        if (!is_array($row)) {
            return false;
        }
        return $row;
    }

    /**
     * Get Data
     * 
     * @param int  $currencyId
     * @param int  $langId
     * @param bool $active
     * @return null|array
     */
    public static function getData(int $currencyId, int $langId, $active = true)
    {
        $srch = new SearchBase(static::DB_TBL, 'currency');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'curlang.currencylang_currency_id = '
                . 'currency.currency_id AND curlang.currencylang_lang_id = ' . $langId, 'curlang');
        if ($active == true) {
            $srch->addCondition('currency.currency_active', '=', AppConstant::YES);
        }
        $srch->addCondition('currency.currency_id', '=', $currencyId);
        $srch->addMultipleFields([
            'currency.currency_id AS currency_id',
            'curlang.currency_name AS currency_name',
            'currency.currency_code AS currency_code',
            'currency.currency_value AS currency_value',
            'currency.currency_symbol AS currency_symbol',
            'currency.currency_positive_format AS currency_positive_format',
            'currency.currency_negative_format AS currency_negative_format',
            'currency.currency_decimal_symbol AS currency_decimal_symbol',
            'currency.currency_grouping_symbol AS currency_grouping_symbol',
        ]);
        $srch->addOrder('currency_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /*
     * get all saved currencies code array 
     *
     * @param boolean $onlyActive
     * @return array
     */

    public static function getAllCodes(bool $onlyActive = true): array
    {
        $srch = new SearchBase(static::DB_TBL, 'currency');
        if ($onlyActive) {
            $srch->addCondition('currency.currency_active', '=', AppConstant::YES);
        }
        $srch->addMultipleFields(['currency_id', 'currency_code']);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * update currency rates
     *
     * @param array $currencies      array format ['code' => 'rate', 'code' => 'rate']
     * @return boolean
     */
    public static function updateRates(array $currencies): bool
    {
        foreach ($currencies as $code => $rate) {
            $record = new TableRecord(static::DB_TBL);
            $record->assignValues(['currency_value' => $rate]);
            if (!$record->update(['smt' => 'currency_code = ?', 'vals' => [$code]])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sync the latest converted rates
     *
     * @return boolean
     */
    public function syncRates(): bool
    {
        $fixer = new Fixer();
        if (!$fixer->init()) {
            $this->error = $fixer->getError();
            return false;
        }
        if (!$response = $fixer->syncRates()) {
            $this->error = $fixer->getError();
            return false;
        }
        if (!static::updateRates($response['rates'])) {
            $this->error = Label::getLabel('LBL_ERROR_TO_UPDATE_DATA');
            return false;
        }
        return true;
    }

    /**
     * get all currencies code list
     *
     * @return array
     */
    public static function getCodeArray(): array
    {
        return [
            "AED" => "AED", "AFN" => "AFN", "ALL" => "ALL", "AMD" => "AMD", "ANG" => "ANG", "AOA" => "AOA",
            "ARS" => "ARS", "AUD" => "AUD", "AWG" => "AWG", "AZN" => "AZN", "BAM" => "BAM", "BBD" => "BBD",
            "BDT" => "BDT", "BGN" => "BGN", "BHD" => "BHD", "BIF" => "BIF", "BMD" => "BMD", "BND" => "BND",
            "BOB" => "BOB", "BRL" => "BRL", "BSD" => "BSD", "BTC" => "BTC", "BTN" => "BTN", "BWP" => "BWP",
            "BYN" => "BYN", "BYR" => "BYR", "BZD" => "BZD", "CAD" => "CAD", "CDF" => "CDF", "CHF" => "CHF",
            "CLF" => "CLF", "CLP" => "CLP", "CNY" => "CNY", "COP" => "COP", "CRC" => "CRC", "CUC" => "CUC",
            "CUP" => "CUP", "CVE" => "CVE", "CZK" => "CZK", "DJF" => "DJF", "DKK" => "DKK", "DOP" => "DOP",
            "DZD" => "DZD", "EGP" => "EGP", "ERN" => "ERN", "ETB" => "ETB", "EUR" => "EUR", "FJD" => "FJD",
            "FKP" => "FKP", "GBP" => "GBP", "GEL" => "GEL", "GGP" => "GGP", "GHS" => "GHS", "GIP" => "GIP",
            "GMD" => "GMD", "GNF" => "GNF", "GTQ" => "GTQ", "GYD" => "GYD", "HKD" => "HKD", "HNL" => "HNL",
            "HRK" => "HRK", "HTG" => "HTG", "HUF" => "HUF", "IDR" => "IDR", "ILS" => "ILS", "IMP" => "IMP",
            "INR" => "INR", "IQD" => "IQD", "IRR" => "IRR", "ISK" => "ISK", "JEP" => "JEP", "JMD" => "JMD",
            "JOD" => "JOD", "JPY" => "JPY", "KES" => "KES", "KGS" => "KGS", "KHR" => "KHR", "KMF" => "KMF",
            "KPW" => "KPW", "KRW" => "KRW", "KWD" => "KWD", "KYD" => "KYD", "KZT" => "KZT", "LAK" => "LAK",
            "LBP" => "LBP", "LKR" => "LKR", "LRD" => "LRD", "LSL" => "LSL", "LTL" => "LTL", "LVL" => "LVL",
            "LYD" => "LYD", "MAD" => "MAD", "MDL" => "MDL", "MGA" => "MGA", "MKD" => "MKD", "MMK" => "MMK",
            "MNT" => "MNT", "MOP" => "MOP", "MRO" => "MRO", "MUR" => "MUR", "MVR" => "MVR", "MWK" => "MWK",
            "MXN" => "MXN", "MYR" => "MYR", "MZN" => "MZN", "NAD" => "NAD", "NGN" => "NGN", "NIO" => "NIO",
            "NOK" => "NOK", "NPR" => "NPR", "NZD" => "NZD", "OMR" => "OMR", "PAB" => "PAB", "PEN" => "PEN",
            "PGK" => "PGK", "PHP" => "PHP", "PKR" => "PKR", "PLN" => "PLN", "PYG" => "PYG", "QAR" => "QAR",
            "RON" => "RON", "RSD" => "RSD", "RUB" => "RUB", "RWF" => "RWF", "SAR" => "SAR", "SBD" => "SBD",
            "SCR" => "SCR", "SDG" => "SDG", "SEK" => "SEK", "SGD" => "SGD", "SHP" => "SHP", "SLL" => "SLL",
            "SOS" => "SOS", "SRD" => "SRD", "STD" => "STD", "SVC" => "SVC", "SYP" => "SYP", "SZL" => "SZL",
            "THB" => "THB", "TJS" => "TJS", "TMT" => "TMT", "TND" => "TND", "TOP" => "TOP", "TRY" => "TRY",
            "TTD" => "TTD", "TWD" => "TWD", "TZS" => "TZS", "UAH" => "UAH", "UGX" => "UGX", "USD" => "USD",
            "UYU" => "UYU", "UZS" => "UZS", "VEF" => "VEF", "VND" => "VND", "VUV" => "VUV", "WST" => "WST",
            "XAF" => "XAF", "XAG" => "XAG", "XAU" => "XAU", "XCD" => "XCD", "XDR" => "XDR", "XOF" => "XOF",
            "XPF" => "XPF", "YER" => "YER", "ZAR" => "ZAR", "ZMK" => "ZMK", "ZMW" => "ZMW", "ZWL" => "ZWL"
        ];
    }

    /**
     * Get System

     * @param int $langId
     * @return null|array
     */
    public static function getSystemCurrency(int $langId)
    {
        $srch = new SearchBase(static::DB_TBL, 'currency');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'curlang.currencylang_currency_id = '
                . 'currency.currency_id AND curlang.currencylang_lang_id = ' . $langId, 'curlang');
        $srch->addCondition('currency.currency_active', '=', AppConstant::YES);
        $srch->addCondition('currency.currency_is_default', '=', AppConstant::YES);
        $srch->addCondition('currency.currency_value', '=', 1);
        $srch->addMultipleFields([
            'currency.currency_id AS currency_id',
            'curlang.currency_name AS currency_name',
            'currency.currency_code AS currency_code',
            'currency.currency_value AS currency_value',
            'currency.currency_symbol AS currency_symbol',
            'currency.currency_positive_format AS currency_positive_format',
            'currency.currency_negative_format AS currency_negative_format',
            'currency.currency_decimal_symbol AS currency_decimal_symbol',
            'currency.currency_grouping_symbol AS currency_grouping_symbol',
        ]);
        $srch->doNotCalculateRecords();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * get the negative format array
     *
     * @param boolean $format
     * @param string $symbol
     * @param float $number
     * @return array
     */
    public static function getNegativeFormat(bool $format = false, string $symbol = '$', float $number = 6.66): array
    {
        $formatArray = [
            '({currency_symbol}{currency_number})' => '({currency_symbol}{currency_number})',
            '-{currency_symbol}{currency_number}' => '-{currency_symbol}{currency_number}',
            '{currency_symbol}-{currency_number}' => '{currency_symbol}-{currency_number}',
            '{currency_symbol}{currency_number}-' => '{currency_symbol}{currency_number}-',
            '({currency_number}{currency_symbol})' => '({currency_number}{currency_symbol})',
            '-{currency_number}{currency_symbol}' => '-{currency_number}{currency_symbol}',
            '{currency_number}-{currency_symbol}' => '{currency_number}-{currency_symbol}',
            '{currency_number}{currency_symbol}-' => '{currency_number}{currency_symbol}-',
            '-{currency_number} {currency_symbol}' => '-{currency_number} {currency_symbol}',
            '-{currency_symbol} {currency_number}' => '-{currency_symbol} {currency_number}',
            '{currency_number} {currency_symbol}-' => '{currency_number} {currency_symbol}-',
            '{currency_symbol} {currency_number}-' => '{currency_symbol} {currency_number}-',
            '{currency_symbol} -{currency_number}' => '{currency_symbol} -{currency_number}',
            '{currency_number}- {currency_symbol}' => '{currency_number}- {currency_symbol}',
            '({currency_symbol} {currency_number})' => '({currency_symbol} {currency_number})',
            '({currency_number} {currency_symbol})' => '({currency_number} {currency_symbol})'
        ];
        if (!$format) {
            return $formatArray;
        }
        foreach ($formatArray as $key => $value) {
            $formatArray[$key] = static::format($value, $number, $symbol);
        }
        return $formatArray;
    }

    /**
     * get positive format array 
     *
     * @param boolean $format
     * @param string $symbol
     * @param float $number
     * @return array
     */
    public static function getPositiveFormat(bool $format = false, string $symbol = '$', float $number = 6.66): array
    {
        $formatArray = [
            '{currency_symbol}{currency_number}' => '{currency_symbol}{currency_number}',
            '{currency_number}{currency_symbol}' => '{currency_number}{currency_symbol}',
            '{currency_symbol} {currency_number}' => '{currency_symbol} {currency_number}',
            '{currency_number} {currency_symbol}' => '{currency_number} {currency_symbol}'
        ];
        if (!$format) {
            return $formatArray;
        }
        foreach ($formatArray as $key => $value) {
            $formatArray[$key] = static::format($value, $number, $symbol);
        }
        return $formatArray;
    }

    /**
     * format the amount 
     *
     * @param string $format
     * @param [type] $number
     * @param string $symbol
     * @return string
     */
    public static function format(string $format, $number, string $symbol): string
    {
        return str_replace(['{currency_symbol}', '{currency_number}'], [$symbol, $number], $format);
    }

    /**
     * get decimal separator
     *
     * @return array
     */
    public static function getDecimalSeparator(): array
    {
        return ['.' => '.', ',' => ','];
    }

    /**
     * get grouping separator
     *
     * @return array
     */
    public static function getGroupingSeparator(): array
    {
        return ['.' => '.', ',' => ','];
    }

}
