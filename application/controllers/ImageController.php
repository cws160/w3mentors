<?php

/**
 * Image Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ImageController extends MyAppController
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
     */
    public function show($fileType, $recordId = 0, $size = '', $langId = 0)
    {
        if (!$this->canAccess($fileType, $recordId)) {
            FatUtility::exitWithErrorCode(404);
        }
        $langId = empty($langId) ? MyUtility::getSiteLangId() : $langId;
        $file = new Afile(FatUtility::int($fileType), $langId);
        $file->showByRecordId($size, FatUtility::int($recordId));
    }

    /**
     * Validate if files can be accessed or not
     *
     * @param integer $fileType
     * @param integer $recordId
     * @return void
     */
    private function canAccess(int $fileType, int $recordId)
    {
        $restrictedTypes = [
            Afile::TYPE_TEACHER_APPROVAL_PROOF,
            Afile::TYPE_USER_QUALIFICATION_FILE,
            Afile::TYPE_LESSON_PLAN_FILE,
            Afile::TYPE_BLOG_CONTRIBUTION,
            Afile::TYPE_MESSAGE_ATTACHMENT,
            Afile::TYPE_ORDER_PAY_RECEIPT,
            Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE,
            Afile::TYPE_COURSE_LECTURE_VIDEO,
        ];
        if (!in_array($fileType, $restrictedTypes)) {
            return true;
        }
        
        $return = true;
        switch ($fileType) {
            case Afile::TYPE_TEACHER_APPROVAL_PROOF:
                $userId = $_SESSION[UserAuth::SESSION_ELEMENT]['user_id'] ?? 0;
                if (isset($_SESSION[TeacherRequest::SESSION_ELEMENT]['user_id'])) {
                    $userId = $_SESSION[TeacherRequest::SESSION_ELEMENT]['user_id'];
                }
                if ($userId < 1 || $userId != $recordId) {
                    $return = false;
                }
                break;
            case Afile::TYPE_USER_QUALIFICATION_FILE:
                $userId = $_SESSION[UserAuth::SESSION_ELEMENT]['user_id'] ?? 0;
                if (isset($_SESSION[TeacherRequest::SESSION_ELEMENT]['user_id'])) {
                    $userId = $_SESSION[TeacherRequest::SESSION_ELEMENT]['user_id'];
                }
                if ($userId < 1 || UserQualification::getAttributesById($recordId, 'uqualification_user_id') != $userId) {
                    $return = false;
                }
                break;
            case Afile::TYPE_LESSON_PLAN_FILE:
            case Afile::TYPE_BLOG_CONTRIBUTION:
            case Afile::TYPE_MESSAGE_ATTACHMENT:
            case Afile::TYPE_ORDER_PAY_RECEIPT:
            case Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE:
            case Afile::TYPE_COURSE_LECTURE_VIDEO:
                $return = false;
                break;
        }
        return $return;
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
     * Download File
     * 
     * @param int $fileType
     * @param int $recordId
     * @param int $subRecordId
     */
    public function download($fileType, $recordId)
    {
        if (!$this->canAccess($fileType, $recordId)) {
            FatUtility::exitWithErrorCode(404);
        }
        $file = new Afile(FatUtility::int($fileType), MyUtility::getSiteLangId());
        $file->downloadByRecordId(FatUtility::int($recordId));
    }

    /**
     * Download File By Id
     * 
     * @param int $fileId
     */
    public function downloadById($fileId)
    {
        $file = new Afile(0);
        $file->downloadById(FatUtility::int($fileId));
    }

    /**
     * Render Editor Image
     * 
     * @param string $fileNamewithPath
     */
    public function editorImage($fileNamewithPath)
    {
        /**
         * We have to use new method
         */
        $file = new Afile(0);
        $file->displayOriginalImage('editor/' . $fileNamewithPath);
    }

    /**
     * Show Image
     * 
     * @param int $fileType
     * @param int $recordId
     */
    public function showPdf($fileType, $recordId, $langId = 0)
    {
        $file = new Afile(FatUtility::int($fileType), $langId);
        $file->showPdf(FatUtility::int($recordId));
    }
}
