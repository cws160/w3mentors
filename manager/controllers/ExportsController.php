<?php

/**
 * Exports is used for CSV Exports handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ExportsController extends AdminBaseController
{

    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function download(int $id)
    {
        $row = Export::getAttributesById($id);
        if (empty($row)) {
            FatUtility::exitWithErrorCode(404);
        }
        $class = 'Export' . $row['export_type'];
        $export = new $class($this->siteLangId, $id);
        if ($export->download() === false) {
            FatUtility::exitWithErrorCode(404);
        }
    }

}
