<?php

class ExportFaq extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::FAQS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'faq_identifier' => Label::getLabel('LBL_FAQ_IDENTIFIER'),
            'faq_title' => Label::getLabel('LBL_Faq_Title'),
            'faqcat_name' => Label::getLabel('LBL_Category'),
            'faq_active' => Label::getLabel('LBL_Status'),
        ];
        return [
            'faq_id', 'faq_active', 'faq_identifier', 'faq_title',
            'IFNULL(faqcatlang.faqcat_name, faqcat.faqcat_identifier) as faqcat_name'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'faq_identifier' => $row['faq_identifier'],
                'faq_title' => $row['faq_title'],
                'faqcat_name' => $row['faqcat_name'],
                'faq_active' => AppConstant::getActiveArr($row['faq_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
