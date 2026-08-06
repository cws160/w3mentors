<?php

/**
 * This class is used to handle Testimonial
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Testimonial extends MyAppModel
{

    const DB_TBL = 'tbl_testimonials';
    const DB_TBL_PREFIX = 'testimonial_';
    const DB_TBL_LANG = 'tbl_testimonials_lang';

    /**
     * Initialize Testimonial
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'testimonial_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $active
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0, bool $active = true): SearchBase
    {
        $srch = new SearchBased(static::DB_TBL, 't');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 't_l.testimoniallang_testimonial_id '
                    . ' = t.testimonial_id AND testimoniallang_lang_id = ' . $langId, 't_l');
        }
        if ($active == true) {
            $srch->addCondition('t.testimonial_active', '=', AppConstant::ACTIVE);
        }
        $srch->addCondition('t.testimonial_deleted', '=', AppConstant::NO);
        return $srch;
    }

    /**
     * Can Record Mark Delete
     * 
     * @param int $testimonialId
     * @return bool
     */
    public function canDelete(int $testimonialId): bool
    {
        $srch = static::getSearchObject(0, false);
        $srch->addCondition('testimonial_id', '=', $testimonialId);
        $srch->addCondition('testimonial_deleted', '=', AppConstant::NO);
        $srch->getResultSet();
        return ($srch->recordCount() > 0);
    }

    public static function getTestimonials(int $langId): array
    {
        $srch = Testimonial::getSearchObject($langId, true);
        $srch->joinTable(Afile::DB_TBL, 'INNER  JOIN', 'file.file_record_id = t.testimonial_id and file_type =' . Afile::TYPE_TESTIMONIAL_IMAGE, 'file');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(4);
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

}
