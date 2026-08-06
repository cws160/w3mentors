<?php

/**
 * Url Rewriting Controller is used for UrlRewriting handling
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UrlRewritingController extends AdminBaseController
{

    /**
     * Initialize UrlRewriting
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSeoUrl();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $canEdit = $this->objPrivilege->canEditSeoUrl(true);
        $srchFrm = $this->getSearchForm($this->siteLangId);
        $this->set("srchFrm", $srchFrm);
        $this->set("canEdit", $canEdit);
        $this->_template->render();
    }

    /**
     * Search & List UrlRewritings
     */
    public function search()
    {
        $searchForm = $this->getSearchForm($this->siteLangId);
        if (!$post = $searchForm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($searchForm->getValidationErrors()));
        }
        $srch = new SearchBased(SeoUrl::DB_TBL, 'seourl');
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $condition = $srch->addCondition('seourl_original', 'like', '%' . $keyword . '%');
            $condition->attachCondition('seourl_custom', 'like', '%' . $keyword . '%', 'OR');
        }
        if ($post['seourl_lang_id'] > 0) {
            $srch->addCondition('seourl_lang_id', '=', $post['seourl_lang_id']);
        }
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $srch->addOrder('seourl_id', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $this->set('post', $post);
        $this->set('langCodes', Language::getCodes());
        $this->set("records", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set("canEdit", $this->objPrivilege->canEditSeoUrl(true));
        $this->set('recordCount', $srch->recordCount());
        $this->set('pageCount', $srch->pages());
        $this->_template->render(false, false);
    }

    /**
     * Render UrlRewriting Form 
     */
    public function form()
    {
        $this->objPrivilege->canEditSeoUrl();
        $frm = $this->getForm();
        $seourlId = FatApp::getPostedData('seourlId', FatUtility::VAR_INT, 0);
        if ($seourlId > 0) {
            $original = SeoUrl::getAttributesById($seourlId);
            if (empty($original)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $srch = new SearchBase(SeoUrl::DB_TBL);
            $srch->addMultipleFields(['seourl_lang_id', 'seourl_custom']);
            $srch->addCondition('seourl_original', '=', $original['seourl_original']);
            $rows = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
            $original['seourl_custom'] = $rows;
            $frm->fill($original);
        }
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup UrlRewriting
     */
    public function setup()
    {
        $this->objPrivilege->canEditSeoUrl();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $seoUrlId = FatUtility::int($post['seourl_id']);
        if ($seoUrlId > 0) {
            $seoUrl = SeoUrl::getAttributesById($seoUrlId, ['seourl_original', 'seourl_id']);
            if (empty($seoUrl)) {
                FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
            }
            if ($seoUrl['seourl_original'] != $post['seourl_original']) {
                if (!FatApp::getDb()->deleteRecords(SeoUrl::DB_TBL, ['smt' => 'seourl_original = ?', 'vals' => [$seoUrl['seourl_original']]])) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError(FatApp::getDb()->getError());
                }
            }
        }
        foreach ($post['seourl_custom'] as $langId => $url) {
            $data = [
                'seourl_lang_id' => $langId,
                'seourl_original' => $post['seourl_original'],
                'seourl_httpcode' => $post['seourl_httpcode'],
                'seourl_custom' => CommonHelper::seoUrl($url, "/[\s,<>\"&$%+?$@=]/")
            ];
            $record = new SeoUrl(0);
            $record->assignValues($data);
            if (!$record->addNew([], $data)) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($record->getError());
            }
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditSeoUrl();
        $seourlId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $seoUrl = SeoUrl::getAttributesById($seourlId, ['seourl_original', 'seourl_id']);
        if (empty($seoUrl)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
        }
        if (!FatApp::getDb()->deleteRecords(SeoUrl::DB_TBL, ['smt' => 'seourl_original = ?', 'vals' => [$seoUrl['seourl_original']]])) {
            FatUtility::dieJsonError(FatApp::getDb()->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword');
        $defaultLangId = FatApp::getConfig('CONF_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        $frm->addSelectBox(Label::getLabel('LBL_Language'), 'seourl_lang_id', Language::getAllNames(), $defaultLangId, [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear'));
        return $frm;
    }

    /**
     * Get Form
     *
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmSeoUrl');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'seourl_id', 0);
        $frm->addRequiredField(Label::getLabel('LBL_ORIGINAL_URL'), 'seourl_original');
        $langs = Language::getAllNames();
        foreach ($langs as $langId => $langName) {
            $fld = $frm->addRequiredField(Label::getLabel('LBL_CUSTOM_URL') . ' [' . $langName . ']', 'seourl_custom[' . $langId . ']');
            $fld->requirements()->setCompareWith('seourl_original', 'ne', Label::getLabel('LBL_ORIGINAL_URL'));
        }
        $frm->addSelectBox(Label::getLabel('LBL_HTTP_CODE'), 'seourl_httpcode', SeoUrl::getHttpCodes(), '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired();
        $frm->addHTML('', '', '<small>' . Label::getLabel('LBL_Example_Custom_URL_Example') . '</small>');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

}
