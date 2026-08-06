<?php

/**
 * Label Controller is used for Label handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AppLabelsController extends AdminBaseController
{

    /**
     * Initialize Label
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAppLabels();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditAppLabels(true));
        $this->set("frmSearch", $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Labels
     */
    public function search()
    {
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $srchFrm = $this->getSearchForm();
        if (!$post = $srchFrm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
        }
        $srch = new SearchBase(AppLabel::DB_TBL, 'lbl');
        $srch->joinTable(Language::DB_TBL, 'INNER JOIN', 'lbl.applbl_lang_id = lang.language_id', 'lang');
        $srch->addMultipleFields(['applbl_id', 'applbl_lang_id', 'applbl_key', 'applbl_value']);
        $srch->addCondition('lang.language_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('lbl.applbl_lang_id', '=', $this->siteLangId);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('lbl.applbl_key', 'like', '%' . $keyword . '%', 'AND');
            $cond->attachCondition('lbl.applbl_value', 'like', '%' . $keyword . '%', 'OR');
        }
        $srch->addOrder('lbl.applbl_key', 'ASC');
        $srch->addOrder('lbl.applbl_id', 'DESC');
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("canEdit", $this->objPrivilege->canEditAppLabels(true));
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Export Labels
     */
    public function export()
    {
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $db = FatApp::getDb();
        $langSrch = Language::getSearchObject();
        $langSrch->doNotCalculateRecords();
        $langSrch->addMultipleFields(['language_id', 'language_code', 'language_name']);
        $langSrch->addOrder('language_id', 'ASC');
        $langRs = $langSrch->getResultSet();
        $counter = 1;
        while ($row = $db->fetch($langRs)) {
            $langArr[$row['language_code']] = $counter;
            $counter++;
        }

        /* add headings row */
        $sheetRows = [];
        $headingRow = array_merge([Label::getLabel('LBL_Key')], array_keys($langArr));
        array_push($sheetRows, $headingRow);

        $srch = new SearchBase(AppLabel::DB_TBL, 'lbl');
        $srch->joinTable(Language::DB_TBL, 'INNER JOIN', 'lbl.applbl_lang_id = lang.language_id', 'lang');
        $srch->addMultipleFields(['applbl_id', 'applbl_lang_id', 'applbl_key', 'applbl_value', 'language_code']);
        $srch->addCondition('lang.language_active', '=', AppConstant::ACTIVE);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('lbl.applbl_key', 'like', '%' . $keyword . '%', 'AND');
            $cond->attachCondition('lbl.applbl_value', 'like', '%' . $keyword . '%', 'OR');
        }
        $srch->addOrder('lbl.applbl_key', 'ASC');
        $srch->addOrder('lbl.applbl_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();

        $lblArr = [];
        $sheetRowCounter = 1;
        while ($row = $db->fetch($rs)) {
            if (!array_key_exists($row['applbl_key'], $lblArr)) {
                $lblArr[$row['applbl_key']] = $sheetRowCounter;
                $sheetRows[$sheetRowCounter] = [$row['applbl_key']];
                $sheetRows[$sheetRowCounter] = array_pad($sheetRows[$sheetRowCounter], $counter, "");
                $sheetRowCounter++;
            }
            $sheetRowIndex = $lblArr[$row['applbl_key']];
            $sheetColIndex = $langArr[$row['language_code']];
            $sheetRows[$sheetRowIndex][$sheetColIndex] = html_entity_decode($row['applbl_value']);
        }
        CommonHelper::convertToCsv($sheetRows, 'Labels_' . date("d-M-Y") . '.csv', ',');
        exit;
    }

    /**
     * Render Import Form
     */
    public function importForm()
    {
        $this->objPrivilege->canEditAppLabels();
        $this->set('frm', $this->getImportForm());
        $this->_template->render(false, false);
    }

    /**
     * Get Import Form
     * 
     * @return Form
     */
    private function getImportForm(): Form
    {
        $frm = new Form('frmImportLabels', ['id' => 'frmImportLabels']);
        $frm = CommonHelper::setFormProperties($frm);
        $fldImg = $frm->addFileUpload(Label::getLabel('LBL_File_to_be_uploaded:'), 'import_file', ['id' => 'import_file']);
        $fldImg->requirements()->setRequired();
        $fldImg->htmlBeforeField = '<div class="filefield"><span class="filename" id="importFileName"></span>';
        $fldImg->htmlAfterField = '</div><small>' . nl2br(Label::getLabel('LBL_Import_Labels_Instructions')) . '</small>';
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_IMPORT'));
        return $frm;
    }

    /**
     * Upload Labels Imported File
     */
    public function setupImport()
    {
        set_time_limit(0);
        $this->objPrivilege->canEditAppLabels();
        if (!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Please_Select_A_CSV_File'));
        }
        if (!in_array($_FILES['import_file']['type'], CommonHelper::isCsvValidMimes())) {
            FatUtility::dieJsonError(Label::getLabel("LBL_Not_a_Valid_CSV_File"));
        }
        $db = FatApp::getDb();
        
        /* get system languages */
        $langSrch = Language::getSearchObject();
        $langSrch->doNotCalculateRecords();
        $langSrch->addMultipleFields(['language_id', 'language_code', 'language_name']);
        $langSrch->addOrder('language_id', 'ASC');
        $languages = $db->fetchAll($langSrch->getResultSet(), 'language_code');

        /* open uploaded file and get first row */
        $csvFilePointer = fopen($_FILES['import_file']['tmp_name'], 'r');
        $firstLine = fgetcsv($csvFilePointer);
        if (empty($firstLine)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NOT_A_VALID_CSV_FILE'));
        }
        array_shift($firstLine);

        /* validate lang codes */
        $langIndexLangIds = [];
        foreach ($firstLine as $key => $langCode) {
            if (!array_key_exists($langCode, $languages)) {
                $msg = Label::getLabel('LBL_INVAILD_LANGUAGE_CODE');
                $msg .= !empty($langCode) ? ' "' . $langCode . '"' : '';
                FatUtility::dieJsonError($msg);
            }
            $langIndexLangIds[$key] = $languages[$langCode]['language_id'];
        }
        if (empty($langIndexLangIds)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PLESAE_ADD_VAILD_LANGUAGE_CODE'));
        }

        /* import data */
        $count = 0;
        while (($line = fgetcsv($csvFilePointer)) !== FALSE) {
            if ($line[0] != '') {
                $labelKey = array_shift($line);
                foreach ($line as $key => $caption) {
                    if (!array_key_exists($key, $langIndexLangIds)) {
                        continue;
                    }
                    $dataToSaveArr = [
                        'applbl_key' => $labelKey,
                        'applbl_lang_id' => $langIndexLangIds[$key],
                        'applbl_value' => $caption,
                    ];
                    $label = new AppLabel(0);
                    if (!$label->addUpdateData($dataToSaveArr)) {
                        FatUtility::dieJsonError($label->getError());
                    }
                }
                $count++;
            }
        }
        if ($count < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NO_DATA_IMPORTED.'));
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_LABELS_DATA_IMPORTED_SUCCESSFULLY'));
    }

    /**
     * Render Label Form
     * 
     * @param int $labelId
     */
    public function form(int $labelId)
    {
        $this->objPrivilege->canEditAppLabels();
        $data = AppLabel::getAttributesById($labelId, ['applbl_key']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new SearchBase(AppLabel::DB_TBL, 'lbl');
        $srch->addCondition('lbl.applbl_key', '=', $data['applbl_key']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($rows)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $row = current($rows);
        $arr = ['applbl_key' => $row['applbl_key']];
        foreach ($rows as $k => $v) {
            $arr['applbl_value' . $v['applbl_lang_id']] = $v['applbl_value'];
        }
        $frm = $this->getForm($row['applbl_key']);
        $frm->fill($arr);
        $this->set('frm', $frm);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Setup Label
     */
    public function setup()
    {
        $this->objPrivilege->canEditAppLabels();
        $data = FatApp::getPostedData();
        $frm = $this->getForm($data['applbl_key']);
        if (!$post = $frm->getFormDataFromArray($data)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $labelKey = $post['applbl_key'];
        $srch = new SearchBase(AppLabel::DB_TBL, 'lbl');
        $srch->addCondition('lbl.applbl_key', '=', $labelKey);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rows = FatApp::getDb()->fetchAll($srch->getResultSet(), 'applbl_lang_id');
        if (empty($rows)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            $data = [
                'applbl_lang_id' => $langId,
                'applbl_key' => $labelKey,
                'applbl_value' => $post['applbl_value' . $langId]
            ];
            if (!FatApp::getDB()->insertFromArray(AppLabel::DB_TBL, $data, false, [], $data)) {
                FatUtility::dieJsonSuccess(FatApp::getDB()->getError());
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_LABEL_UPDATED_SUCCESSFULY'));
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmLabelsSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $f1 = $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear_Search'));
        $frm->addHiddenField('', 'page', 1);
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    /**
     * Get Label Form
     * 
     * @param string $key
     * @return Form
     */
    private function getForm($key): Form
    {
        $frm = new Form('frmLabels');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'applbl_key', $key);
        $languages = Language::getAllNames();
        $frm->addTextBox(Label::getLabel('LBL_Key'), 'applbl_key_label', $key);
        foreach ($languages as $langId => $langName) {
            $fld = $frm->addTextArea($langName, 'applbl_value' . $langId);
            $fld->requirements()->setRequired();
        }
        Translator::addTranslatorActions($frm, 0, $key, AppLabel::DB_TBL);
        return $frm;
    }

    public function regenerate()
    {
        $srch = new SearchBase(Language::DB_TBL);
        $srch->doNotCalculateRecords();
        $langs = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($langs as $lang) {
            $srch = new SearchBase(AppLabel::DB_TBL, 'lbl');
            $srch->addMultipleFields(['applbl_key', 'applbl_value']);
            $srch->addCondition('applbl_lang_id', '=', $lang['language_id']);
            $srch->doNotCalculateRecords();
            $srch->doNotLimitRecords();
            $rs = $srch->getResultSet();
            $rows = FatApp::getDb()->fetchAllAssoc($rs);
            $code = strtolower($lang['language_code']);
            $file = CONF_INSTALLATION_PATH . 'public/cache/' . $code . '.json';
            $labels = FatUtility::convertToJson($rows, JSON_UNESCAPED_UNICODE);
            file_put_contents($file, $labels);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_LABEL_UPDATED_SUCCESSFULY'));
    }

}
