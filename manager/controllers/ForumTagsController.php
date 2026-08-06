<?php

/**
 * ForumTags Controller is used for add/edit Forum Tags
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumTagsController extends AdminBaseController
{

    /**
     * Initialize ForumTags
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewDiscussionForum();
    }

    /**
     * Render Forum Tags Form
     */
    public function index()
    {
        $this->set('canEdit', $this->objPrivilege->canEditDiscussionForum(true));
        $this->set('frmSearch', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Forum Tags
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $keyword = trim(FatApp::getPostedData('keyword'));
        $srch = new ForumTagSearch($langId, false);
        if (!empty($keyword)) {
            $srch->addCondition('ftag_name', 'like', '%' . $keyword . '%');
        }
        if ($post['ftag_active'] != '') {
            $srch->addCondition('ftag_active', '=', $post['ftag_active']);
        }
        $post['pageno'] = $post['pageno'] ?? 1;
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $languages = Language::getAllNames();
        $this->sets([
            "arrListing" => $records,
            "languages" => $languages,
            "post" => $post,
            "recordCount" => $srch->recordCount(),
            "canEdit" => $this->objPrivilege->canEditDiscussionForum(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Forum Tag Form
     */
    public function form($id = 0)
    {
        $this->objPrivilege->canEditDiscussionForum();
        $frm = $this->getForm();
        if (0 < $id) {
            $data = ForumTag::getAttributesById($id, ['ftag_id', 'ftag_language_id', 'ftag_name', 'ftag_active']);
            if (false === $data) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->set('frm', $frm);
        $this->set('id', $id);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Setup Forum Tags
     */
    public function setup()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $frm = $this->getForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (!$post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $post['ftag_name'] = ForumTag::sanitizeName($post['ftag_name']);
        $ftagId = FatApp::getPostedData('ftag_id', FatUtility::VAR_INT, 0);
        $record = new ForumTag($ftagId);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Change status of Tag
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $fTagId = FatApp::getPostedData('ftag_id', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        $fTag = new ForumTag($fTagId);
        if (!$fTag->changeStatus($status)) {
            FatUtility::dieJsonError($fTag->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Action_performed_successfully'));
    }

    /**
     * Delete Tag
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $fTagId = FatApp::getPostedData('ftag_id', FatUtility::VAR_INT, 0);
        $fTag = new ForumTag($fTagId);
        if (!$fTag->deleteTag()) {
            FatUtility::dieJsonError($fTag->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Record_deleted_successfully'));
    }

    /**
     * Restore Deleted Tag
     */
    public function restoreTag()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $fTagId = FatApp::getPostedData('ftag_id', FatUtility::VAR_INT, 0);
        $fTag = new ForumTag($fTagId);
        if (!$fTag->restoreTag()) {
            FatUtility::dieJsonError($fTag->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Tag_restored_successfully'));
    }

    /**
     * Get Form
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmForumTags');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox(Label::getLabel('LBL_Tag_Name'), 'ftag_name');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(2, 50);
        $fld->htmlAfterField = '<small class="text--small">' . CommonHelper::replaceStringData(Label::getLabel('LBL_Do_not_include_special_symbols_except_{allowed-special-chars}'), ['{allowed-special-chars}' => ForumTag::allowedSpecialCharacters()]) . '</small>';
        $languages = Language::getAllNames();
        $frm->addSelectBox(Label::getLabel('LBL_Language'), 'ftag_language_id', $languages, '', array(), '')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        $frm->addHiddenField('', 'ftag_id')->requirements()->setIntPositive();
        return $frm;
    }

    /**
     * Get Search Form
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_keyword'), 'keyword', '');
        $frm->addSelectBox(Label::getLabel('LBL_Language'), 'lang_id', Language::getAllNames(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'ftag_active', AppConstant::getActiveArr(), '', [], Label::getLabel('LBL_SELECT'));
        $fldSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fldCancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fldSubmit->attachField($fldCancel);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        return $frm;
    }

}
