<?php

class CourseUtility extends FatUtility
{
    
    /**
     * Format money
     *
     * @param float   $value
     * @param int $currencyId
     * @return string
     */
    public static function formatMoney(float $value, int $currencyId = 0, bool $addsymbol = true): string
    {
        if ($currencyId < 1) {
            $siteCurrency = MyUtility::getSiteCurrency();
        } else {
            $siteCurrency = Currency::getData($currencyId, MyUtility::getSiteLangId(), false);
        }
        if ($value > 0) {
            $value = static::convertToCurrency($value, $siteCurrency['currency_id']);
        }
        if (!$addsymbol) {
            return $value;
        }
        $format = (0 > $value) ? $siteCurrency['currency_negative_format'] : $siteCurrency['currency_positive_format'];
        $value = round(abs($value), 2);
        $value = number_format($value, 2, $siteCurrency['currency_decimal_symbol'], $siteCurrency['currency_grouping_symbol']);
        return Currency::format($format, $value, $siteCurrency['currency_symbol']);
    }

    public static function convertToSystemCurrency(float $value, int $currencyId): float
    {
        $value = static::float($value);
        $currencyValue = Currency::getAttributesById($currencyId, 'currency_value');
        return static::float($value / $currencyValue);
    }

    public static function convertToCurrency(float $value, int $currencyId): float
    {
        $value = static::float($value);
        $currencyValue = Currency::getAttributesById($currencyId, 'currency_value');
        return static::float($value * $currencyValue);
    }

    /**
     * Course content updated after published
     *
     * @param int $courseStatus
     * @param string $date
     * @return boolean
     */
    public static function isContentUpdated($courseStatus, $date)
    {
        if ($courseStatus != Course::PUBLISHED || empty($date)) {
            return false;
        }
        $updatedDate = date('Y-m-d H:i:s', strtotime($date . ' + ' . 7 . ' days'));
        return $updatedDate > date('Y-m-d H:i:s') ? true : false;
    }
}
