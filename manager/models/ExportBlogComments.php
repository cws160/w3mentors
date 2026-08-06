<?php

class ExportBlogComments extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::BLOG_COMMENTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'bpcomment_author_name' => Label::getLabel('LBL_Author_Name'),
            'bpcomment_author_email' => Label::getLabel('LBL_Author_Email'),
            'bpcomment_content' => Label::getLabel('LBL_Comment'),
            'bpcomment_approved' => Label::getLabel('LBL_Status'),
            'post_title' => Label::getLabel('LBL_Post_Title'),
            'bpcomment_added_on' => Label::getLabel('LBL_Posted_On'),
        ];
        return [
            'bpcomment_author_name', 'bpcomment_author_email', 'bpcomment_content',
            'post_title', 'bpcomment_approved', 'bpcomment_added_on'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'bpcomment_author_name' => $row['bpcomment_author_name'],
                'bpcomment_author_email' => $row['bpcomment_author_email'],
                'bpcomment_content' => $row['bpcomment_content'],
                'bpcomment_approved' => BlogPost::getCommentStatuses($row['bpcomment_approved']),
                'post_title' => $row['post_title'],
                'bpcomment_added_on' => MyDate::formatDate($row['bpcomment_added_on']),
            ]);
            $count++;
        }
        return $count;
    }

}
