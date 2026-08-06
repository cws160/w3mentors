<?php

class ExportTestimonials extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::TESTIMONIALS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'testimonial_identifier' => Label::getLabel('LBL_Identifier'),
            'testimonial_user_name' => Label::getLabel('LBL_Name'),
            'testimonial_text' => Label::getLabel('LBL_Content'),
            'testimonial_active' => Label::getLabel('LBL_Status'),
        ];
        return ['testimonial_identifier', 'testimonial_user_name', 'testimonial_text', 'testimonial_active'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'testimonial_identifier' => $row['testimonial_identifier'],
                'testimonial_user_name' => $row['testimonial_user_name'],
                'testimonial_text' => $row['testimonial_text'],
                'testimonial_active' => AppConstant::getActiveArr($row['testimonial_active']),
            ]);
            $count++;
        }
        return $count;
    }
}
