<?php

class ExportTeachLanguage extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::TEACH_LANGUAGE;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'tlang_identifier' => Label::getLabel('LBL_LANGUAGE_IDENTIFIER'),
            'tlang_name' => Label::getLabel('LBL_Teach_Language_name'),
        ];
        if ($this->filters['level'] < TeachLanguage::MAX_LEVEL) {
            $this->headers['tlang_subcategories'] = Label::getLabel('LBL_SUB_LANGUAGES');
        }
        if (FatApp::getConfig('CONF_MANAGE_PRICES')) {
            $this->headers['tlang_hourly_price'] = Label::getLabel('LBL_PRICE/HOUR') . '[' . $currencySymbol . ']';
        } else {
            $this->headers['tlang_min_price'] = Label::getLabel('LBL_MIN_PRICE/HOUR') . '[' . $currencySymbol . ']';
            $this->headers['tlang_max_price'] = Label::getLabel('LBL_MAX_PRICE/HOUR') . '[' . $currencySymbol . ']';
        }
        if ($this->filters['parent_id'] < 1) {
            $this->headers['tlang_featured'] = Label::getLabel('LBL_FEATURED_TLANG');
        }
        if ($this->filters['parent_id'] < 1) {
            $this->headers['tlang_featured'] = Label::getLabel('LBL_FEATURED_TLANG');
        }
        $this->headers['tlang_active'] = Label::getLabel('LBL_STATUS');
        return ['tlang_identifier', 'tlang_name', 'IFNULL(tlang_featured, 0) tlang_featured', 'tlang_subcategories', 'tlang_min_price', 'tlang_max_price', 'tlang_hourly_price', 'tlang_active'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $yesNoArray = AppConstant::getYesNoArr();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $record = [
                'tlang_identifier' => $row['tlang_identifier'],
                'tlang_name' => $row['tlang_name'],
            ];
            if ($this->filters['level'] < TeachLanguage::MAX_LEVEL) {
                $record['tlang_subcategories'] = $row['tlang_subcategories'];
            }
            if (FatApp::getConfig('CONF_MANAGE_PRICES')) {
                $record['tlang_hourly_price'] = 0;
                if ($row['tlang_subcategories'] < 1) {
                    $record['tlang_hourly_price'] = MyUtility::formatMoney($row['tlang_hourly_price'], false);
                }
            } else {
                $minPrice = $maxPrice = 0;
                if ($row['tlang_subcategories'] < 1) {
                    $minPrice = $row['tlang_min_price'];
                    $maxPrice = $row['tlang_max_price'];
                }
                $record['tlang_min_price'] = MyUtility::formatMoney($minPrice, false);
                $record['tlang_max_price'] = MyUtility::formatMoney($maxPrice, false);
            }
            if ($this->filters['parent_id'] < 1) {
                $record['tlang_featured'] = $yesNoArray[$row['tlang_featured']];
            }
            $record['tlang_active'] = AppConstant::getActiveArr($row['tlang_active']);
            fputcsv($fh, $record);
            $count++;
        }
        return $count;
    }
}
