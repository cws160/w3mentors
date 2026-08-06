<?php

class ExportMetaTags extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::META_TAGS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'type_title' => $this->getTitleLabel($this->filters['metaType']),
            'meta_identifier' => Label::getLabel('LBL_META_IDENTIFIER'),
            'meta_title' => Label::getLabel('LBL_META_TITLE'),
            'meta_keywords' => Label::getLabel('LBL_META_KEYWORDS'),
            'meta_description' => Label::getLabel('LBL_META_DESCRIPTION'),
            'meta_other_meta_tags' => Label::getLabel('LBL_META_OTHER_TAGS'),
            'meta_og_title' => Label::getLabel('LBL_OG_TITLE'),
            'meta_og_url' => Label::getLabel('LBL_OG_LINK'),
            'meta_og_description' => Label::getLabel('LBL_OG_DESCRIPTION'),
        ];
        $fields = [
            'meta_identifier', 'meta_title', 'meta_keywords', 'meta_description',
            'meta_other_meta_tags', 'meta_og_title', 'meta_og_url', 'meta_og_description'
        ];
        if ($this->filters['metaType'] == MetaTag::META_GROUP_DEFAULT) {
            unset($this->headers['type_title']);
        } else {
            $titleField = $this->getTitleField($this->filters['metaType']);
            array_unshift($fields, $titleField);
        }
        return $fields;
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, $row);
            $count++;
        }
        return $count;
    }

    private function getTitleLabel(int $type)
    {
        $arr = [
            MetaTag::META_GROUP_DEFAULT => Label::getLabel('LBL_TITLE'),
            MetaTag::META_GROUP_OTHER => Label::getLabel('LBL_SLUG'),
            MetaTag::META_GROUP_TEACHER => Label::getLabel('LBL_Teacher_Name'),
            MetaTag::META_GROUP_GRP_CLASS => Label::getLabel('LBL_Group_Class'),
            MetaTag::META_GROUP_CMS_PAGE => Label::getLabel('LBL_CMS_Page'),
            MetaTag::META_GROUP_BLOG_CATEGORY => Label::getLabel('LBL_Blog_Categories'),
            MetaTag::META_GROUP_BLOG_POST => Label::getLabel('LBL_Post_Title'),
            MetaTag::META_GROUP_COURSE => Label::getLabel('LBL_Course_Title'),
            MetaTag::META_GROUP_TEACH_LANGUAGE => Label::getLabel('METALBL_LANGUAGE_TITLE'),
        ];
        return $arr[$type];
    }

    private function getTitleField(int $type)
    {
        $arr = [
            MetaTag::META_GROUP_DEFAULT => 'IFNULL(meta_title, meta_identifier) as title_type',
            MetaTag::META_GROUP_OTHER => 'CONCAT(meta_controller, "/", meta_action) as slug',
            MetaTag::META_GROUP_TEACHER => 'CONCAT(u.user_first_name, " ", u.user_last_name) as title_type',
            MetaTag::META_GROUP_GRP_CLASS => 'gcls.grpcls_title as title_type',
            MetaTag::META_GROUP_CMS_PAGE => 'IFNULL(cpage_title, cpage_identifier) as title_type',
            MetaTag::META_GROUP_BLOG_CATEGORY => 'IFNULL(bpcategory_name, bpcategory_identifier) as title_type',
            MetaTag::META_GROUP_BLOG_POST => 'IFNULL(post_title, post_identifier) as title_type',
            MetaTag::META_GROUP_COURSE => 'course_title as title_type',
            MetaTag::META_GROUP_TEACH_LANGUAGE => 'IFNULL(tlang_name, tlang_identifier) as title_type',
        ];
        return $arr[$type];
    }

}
