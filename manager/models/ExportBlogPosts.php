<?php

class ExportBlogPosts extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::BLOG_POSTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'post_identifier' => Label::getLabel('LBL_POST_IDENTIFIER'),
            'post_title' => Label::getLabel('LBL_Post_Title'),
            'categories' => Label::getLabel('LBL_Category'),
            'post_added_on' => Label::getLabel('LBL_Added_Date'),
            'post_published_on' => Label::getLabel('LBL_Published_Date'),
            'post_published' => Label::getLabel('LBL_Post_Status'),
        ];
        return [
            'post_identifier', 'post_title',
            'GROUP_CONCAT(IFNULL(bpcategory_name , bpcategory_identifier)) categories', 'post_added_on', 'post_published_on', 'post_published'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'post_identifier' => $row['post_identifier'],
                'post_title' => $row['post_title'],
                'categories' => $row['categories'],
                'post_added_on' => MyDate::formatDate($row['post_added_on']),
                'post_published_on' => MyDate::formatDate($row['post_published_on']),
                'post_published' => BlogPost::getStatuses($row['post_published']),
            ]);
            $count++;
        }
        return $count;
    }

}
