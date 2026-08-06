<?php

/**
 * This class is used to handle Course resources
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class Resource extends MyAppModel
{
    const DB_TBL = 'tbl_resources';
    const DB_TBL_PREFIX = 'resrc_';
    
    /* allowed extensions */
    const ALLOWED_EXTENSIONS = ['png', 'jpeg', 'jpg', 'gif', 'pdf', 'doc', 'docx', 'zip', 'txt'];

    private $userId;

    /**
     * Initialize Course
     *
     * @param int $id
     */
    public function __construct(int $id = 0, $userId = 0)
    {
        parent::__construct(static::DB_TBL, 'resrc_id', $id);
        $this->userId = $userId;
    }

    /**
     * Get Content Type
     * 
     * @param string $ext           File Extension
     * @return string               It return file content type
     */
    private static function getContentType(string $ext): string
    {
        switch ($ext) {
            case 'png':
                return 'image/png';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'gif':
                return 'image/gif';
            case 'txt':
                return 'text/plain';
            case 'pdf':
                return 'application/pdf';
            case 'doc':
                return 'application/msword';
            case 'docx':
                return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            case 'zip':
                return 'application/zip';
            default:
                return '';
        }
    }

    public static function getFileIcon($type)
    {
        switch ($type) {
            case 'png':
                return 'png-attachment';
            case 'jpg':
            case 'jpeg':
                return 'jpg-attachment';
            case 'gif':
                return 'gif-attachment';
            case 'txt':
                return 'txt-attachment';
            case 'pdf':
                return 'pdf-attachment';
            case 'doc':
            case 'docx':
                return 'doc-attachment';
            case 'zip':
                return 'zip-attachment';
            default:
                return 'attach';
        }
    }

    /**
     * function to upload resources
     *
     * @param array $resources
     * @param int   $userId
     * @return bool
     */
    public function saveFile(array $resources, int $userId)
    {
        if ($userId < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        $lbl = Label::getLabel('LBL_MB');
        foreach ($resources['name'] as $key => $file) {
            $this->mainTableRecordId = 0;
            /* validate uploaded files [ */
            $data = [
                'name' => $file,
                'type' => $resources['type'][$key],
                'tmp_name' => $resources['tmp_name'][$key],
                'error' => $resources['error'][$key],
                'size' => $resources['size'][$key]
            ];
            if (!$this->validateUploadedFile($data)) {
                $db->rollbackTransaction();
                return false;
            }
            /* ] */

            /* create path to upload [ */
            $uploadPath = CONF_UPLOADS_PATH;
            $filePath = date('Y') . '/' . date('m') . '/';
            if (!file_exists($uploadPath . $filePath)) {
                mkdir($uploadPath . $filePath, 0777, true);
            }
            $fileName = preg_replace('/[^a-zA-Z0-9.]/', '', $file);
            while (file_exists($uploadPath . $filePath . $fileName)) {
                $fileName = time() . '-' . $fileName;
            }
            $filePath = $filePath . $fileName;
            /* ] */
            
            /* save file data [ */
            $this->assignValues([
                'resrc_user_id' => $userId,
                'resrc_type' => pathinfo($file, PATHINFO_EXTENSION),
                'resrc_size' => MyUtility::convertBitesToMb($resources['size'][$key]) . ' ' . $lbl,
                'resrc_name' => $file,
                'resrc_path' => $filePath,
                'resrc_created' => date('Y-m-d H:i:s')
            ]);
            if (!$this->save()) {
                $this->error = $this->getError();
                $db->rollbackTransaction();
                return false;
            }
            /* ] */

            /* upload file to the path [ */
            if (!move_uploaded_file($resources['tmp_name'][$key], $uploadPath . $filePath)) {
                $this->error = Label::getLabel('FILE_COULD_NOT_SAVE_FILE');
                $db->rollbackTransaction();
                return false;
            }
            /* ] */
        }

        $db->commitTransaction();
        return true;
    }

    /**
     * Validate Uploaded File Content
     * 
     * @param array $file           It represent a row(array) of table Afile::DB_TBL
     * @return bool                 Return true on success and false on failure
     */
    private function validateUploadedFile(array $file): bool
    {
        if (empty($file['name'])) {
            $this->error = Label::getLabel('FILE_INVALID_FILE_NAME');
            return false;
        }
        if (empty($file['type'])) {
            $this->error = Label::getLabel('FILE_INVALID_FILE_TYPE');
            return false;
        }
        if (empty($file['tmp_name'])) {
            $this->error = Label::getLabel('FILE_FILE_NOT_UPLOADED');
            return false;
        }
        if (empty($file['size'])) {
            $this->error = Label::getLabel('FILE_EMPTY_FILE_UPLOADED');
            return false;
        }
        $allowedSize = Afile::getAllowedUploadSize();
        if ($file["size"] > $allowedSize) {
            $label = Label::getLabel('LBL_UPLOAD_FILE_SIZE_MUST_BE_EQUAL_OR_LESS_THEN_{size}_MB');
            $this->error = str_replace('{size}', MyUtility::convertBitesToMb($allowedSize), $label);
            return false;
        }
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, static::ALLOWED_EXTENSIONS)) {
            $this->error = Label::getLabel('FILE_INVALID_FILE_EXTENSION');
            return false;
        }
        if (static::getContentType($fileExt) != mime_content_type($file['tmp_name'])) {
            $this->error = Label::getLabel('FILE_INVALID_FILE_CONTENT');
            return false;
        }
        return true;
    }

    /**
     * Function to remove resources
     *
     * @return bool
     */
    public function delete()
    {
        $resourceId = $this->getMainTableRecordId();
        if ($resourceId < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }

        if (!$resource = static::getAttributesById($resourceId, ['resrc_path', 'resrc_user_id', 'resrc_deleted'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        if(!is_null($resource['resrc_deleted'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_RESOURCE_NOT_FOUND'));
        }
        
        if ($this->userId != $resource['resrc_user_id']) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACCESS');
            return false;
        }

        /* apply check for resources binded with courses */
        $srch = new SearchBase(Lecture::DB_TBL_LECTURE_RESOURCE);
        $srch->joinTable(Lecture::DB_TBL, 'INNER JOIN', 'lecsrc_lecture_id = lecture_id');
        $srch->addCondition('lecsrc_resrc_id', '=', $resourceId);
        $srch->addCondition('lecsrc_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_CANNOT_DELETE._THIS_RESOURCE_IS_BINDED_WITH_A_COURSE');
            return false;
        }
        
        $filePath = CONF_UPLOADS_PATH . $resource['resrc_path'];
        $db = FatApp::getDb();
        $db->startTransaction();

        $this->setFldValue('resrc_deleted', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $this->error = $db->getError();
            $db->rollbackTransaction();
            return false;
        }
        
        if (file_exists($filePath) && !unlink($filePath)) {
            $this->error = Label::getLabel('LBL_NOT_ABLE_TO_DELETE_FILE');
            $db->rollbackTransaction();
            return false;
        }
        
        $db->commitTransaction();
        return true;
    }
}
