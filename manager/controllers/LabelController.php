<?php

/**
 * Label Controller is used for Label handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class LabelController extends AdminBaseController
{

    /**
     * Initialize Label
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewLanguageLabel();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditLanguageLabel(true));
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
        $searchForm = $this->getSearchForm();
        if (!$post = $searchForm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($searchForm->getValidationErrors()));
        }
        $srch = Label::getSearchObject();
        $srch->joinTable('tbl_languages', 'inner join', 'label_lang_id = language_id and language_active = ' . AppConstant::ACTIVE);
        $srch->addCondition('label_lang_id', '=', $this->siteLangId);
        $srch->addOrder('lbl.label_key', 'ASC');
        $srch->addOrder('lbl.label_id', 'DESC');
        $srch->addGroupBy('lbl.label_key');
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('lbl.label_key', 'like', '%' . $keyword . '%', 'AND');
            $cond->attachCondition('lbl.label_caption', 'like', '%' . $keyword . '%', 'OR');
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set("canEdit", $this->objPrivilege->canEditLanguageLabel(true));
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Render Label Form
     * 
     * @param int $labelId
     */
    public function form($labelId)
    {
        $this->objPrivilege->canEditLanguageLabel();
        $labelId = FatUtility::int($labelId);
        if ($labelId == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = Label::getAttributesById($labelId, ['label_key']);
        if ($data == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $labelKey = $data['label_key'];
        $frm = $this->getForm($labelKey);
        $srch = Label::getSearchObject();
        $srch->addCondition('lbl.label_key', '=', $labelKey);
        $srch->addOrder('lbl.label_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $record = FatApp::getDb()->fetchAll($srch->getResultSet(), 'label_lang_id');
        if ($record == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $arr = [];
        foreach ($record as $k => $v) {
            $arr['label_key'] = $v['label_key'];
            $arr['label_caption' . $k] = $v['label_caption'];
        }
        $frm->fill($arr);
        $this->set('labelKey', $labelKey);
        $this->set('frm', $frm);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Setup Label
     */
    public function setup()
    {
        $this->objPrivilege->canEditLanguageLabel();
        $data = FatApp::getPostedData();
        $frm = $this->getForm($data['label_key']);
        if (!$post = $frm->getFormDataFromArray($data)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $labelKey = $post['label_key'];
        $srch = Label::getSearchObject();
        $srch->addCondition('lbl.label_key', '=', $labelKey);
        $srch->addOrder('lbl.label_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetchAll($rs, 'label_lang_id');
        if ($record == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            $data = [
                'label_lang_id' => $langId,
                'label_key' => $labelKey,
                'label_caption' => $post['label_caption' . $langId]
            ];
            $obj = new Label();
            if (!$obj->addUpdateData($data)) {
                FatUtility::dieJsonError($obj->getError());
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
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
        $srch = new SearchBase(Label::DB_TBL, 'lbl');
        $srch->joinTable(Language::DB_TBL, 'INNER JOIN', 'label_lang_id = language_id AND language_active = ' . AppConstant::ACTIVE);
        $srch->addMultipleFields(['label_id', 'label_key', 'label_lang_id', 'label_caption', 'language_code']);
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('lbl.label_key', 'like', '%' . $keyword . '%', 'AND');
            $cond->attachCondition('lbl.label_caption', 'like', '%' . $keyword . '%', 'OR');
        }
        $srch->addOrder('lbl.label_key', 'ASC');
        $srch->addOrder('lbl.label_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $rs = $srch->getResultSet();
        $sheetRows = [];
        $headingRow = array_merge([Label::getLabel('LBL_Key')], array_keys($langArr));
        array_push($sheetRows, $headingRow);
        $lblArr = [];
        $sheetRowCounter = 1;
        while ($row = $db->fetch($rs)) {
            if (!array_key_exists($row['label_key'], $lblArr)) {
                $lblArr[$row['label_key']] = $sheetRowCounter;
                $sheetRows[$sheetRowCounter] = [$row['label_key']];
                $sheetRows[$sheetRowCounter] = array_pad($sheetRows[$sheetRowCounter], $counter, "");
                $sheetRowCounter++;
            }
            $sheetRowIndex = $lblArr[$row['label_key']];
            $sheetColIndex = $langArr[$row['language_code']];
            $sheetRows[$sheetRowIndex][$sheetColIndex] = html_entity_decode($row['label_caption']);
        }
        CommonHelper::convertToCsv($sheetRows, 'Labels_' . date("d-M-Y") . '.csv', ',');
        exit;
    }

    /**
     * Render Import Labels Form
     */
    public function importLabelsForm()
    {
        $this->objPrivilege->canEditLanguageLabel();
        $this->set('frm', $this->getImportLabelsForm());
        $this->_template->render(false, false);
    }

    /**
     * Upload Labels Imported File
     */
    public function uploadLabelsImportedFile()
    {
        set_time_limit(0);
        $this->objPrivilege->canEditLanguageLabel();
        if (!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Please_Select_A_CSV_File'));
        }
        if (!in_array($_FILES['import_file']['type'], CommonHelper::isCsvValidMimes())) {
            FatUtility::dieJsonError(Label::getLabel("LBL_Not_a_Valid_CSV_File"));
        }
        set_time_limit(0);
        $db = FatApp::getDb();
        $langSrch = Language::getSearchObject();
        $langSrch->doNotCalculateRecords();
        $langSrch->addMultipleFields(['language_id', 'language_code', 'language_name']);
        $langSrch->addOrder('language_id', 'ASC');
        $langRs = $langSrch->getResultSet();
        $languages = $db->fetchAll($langRs, 'language_code');
        $csvFilePointer = fopen($_FILES['import_file']['tmp_name'], 'r');
        $firstLine = fgetcsv($csvFilePointer);
        if (empty($firstLine)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NOT_A_VALID_CSV_FILE'));
        }
        array_shift($firstLine);
        $firstLineLangArr = array_filter($firstLine);
        $langIndexLangIds = [];
        foreach ($firstLineLangArr as $key => $langCode) {
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
        while (($line = fgetcsv($csvFilePointer)) !== FALSE) {
            if ($line[0] != '') {
                $labelKey = array_shift($line);
                foreach ($line as $key => $caption) {
                    if (!array_key_exists($key, $langIndexLangIds)) {
                        continue;
                    }
                    $dataToSaveArr = [
                        'label_key' => $labelKey,
                        'label_lang_id' => $langIndexLangIds[$key],
                        'label_caption' => $caption,
                    ];
                    $label = new Label(0);
                    if (!$label->addUpdateData($dataToSaveArr)) {
                        FatUtility::dieJsonError($label->getError());
                    }
                }
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_LABELS_DATA_IMPORTED_SUCCESSFULLY'));
    }

    /**
     * Get Import Labels Form
     * 
     * @return Form
     */
    private function getImportLabelsForm(): Form
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
     * @param string $label_key
     * @return Form
     */
    private function getForm($label_key): Form
    {
        $frm = new Form('frmLabels');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'label_key', $label_key);
        $languages = Language::getAllNames();
        $frm->addTextBox(Label::getLabel('LBL_Key'), 'key', $label_key);
        foreach ($languages as $langId => $langName) {
            $fld = null;
            $fld = $frm->addTextArea($langName, 'label_caption' . $langId);
            $fld->requirements()->setRequired();
        }
        Translator::addTranslatorActions($frm, 0, $label_key, Label::DB_TBL);
        // $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

}
