<?php

/**
 * Image Controller is used for Images handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ImageController extends AdminBaseController
{

    /**
     * Initialize Image 
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Show Image
     * 
     * @param int $fileType
     * @param int $recordId
     * @param string $size
     * @param int $langId
     */
    public function show($fileType, $recordId = 0, $size = '', $langId = 0)
    {
        $file = new Afile(FatUtility::int($fileType), FatUtility::int($langId));
        $file->showByRecordId($size, FatUtility::int($recordId));
    }

    /**
     * Show By Id
     *
     * @param int $fileId
     * @param string $size
     */
    public function showById($fileId, $size = '')
    {
        $file = new Afile(0);
        $file->showByFileId(FatUtility::int($fileId), $size);
    }

    /**
     * download
     *
     * @param int $fileType
     * @param int $recordId
     * @param int $subRecordId
     * @param int $langId
     */
    public function download($fileType, $recordId, $langId = 0)
    {
        $file = new Afile(FatUtility::int($fileType), FatUtility::int($langId));
        $file->downloadByRecordId(FatUtility::int($recordId));
    }

}
