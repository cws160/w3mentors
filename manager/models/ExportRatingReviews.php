<?php

class ExportRatingReviews extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::RATING_REVIEWS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'learner_name' => Label::getLabel('LBL_REVIEW_BY'),
            'teacher_name' => Label::getLabel('LBL_REVIEW_TO'),
            'ratrev_title' => Label::getLabel('LBL_REVIEW_TITLE'),
            'ratrev_status' => Label::getLabel('LBL_STATUS'),
            'ratrev_created' => Label::getLabel('LBL_POSTED'),
            'ratrev_detail' => Label::getLabel('LBL_REVIEW_DETAILS'),
            'ratrev_overall' => Label::getLabel('LBL_RATING'),
        ];
        return [
            'CONCAT(learner.user_first_name," ",learner.user_last_name) as learner_name',
            'CONCAT(teacher.user_first_name," ",teacher.user_last_name) as teacher_name',
            'ratrev_overall', 'ratrev_title', 'ratrev_detail', 'ratrev_status', 'ratrev_created'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'learner_name' => $row['learner_name'],
                'teacher_name' => $row['teacher_name'],
                'ratrev_title' => $row['ratrev_title'],
                'ratrev_status' => RatingReview::getStatues($row['ratrev_status']),
                'ratrev_created' => MyDate::formatDate($row['ratrev_created']),
                'ratrev_detail' => $row['ratrev_detail'],
                'ratrev_overall' => $row['ratrev_overall']
            ]);
            $count++;
        }
        return $count;
    }

}
