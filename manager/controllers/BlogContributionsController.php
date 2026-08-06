<?php

/**
 * Blog Contributions Controller is used for Blog Contributions Comments handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class BlogContributionsController extends AdminBaseController
{

    /**
     * Initialize Blog Contributions
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewBlogContributions();
    }

    /**
     * Render Blog Contributions Search Form
     */
    public function index()
    {
        $search = $this->getSearchForm();
        $search->fill(FatApp::getPostedData());
        $this->set("search", $search);
        $this->_template->render();
    }

    /**
     * Search & List Blog Contributions
     */
    public function search()
    {
        $srchFrm = $this->getSearchForm();
        if (!$post = $srchFrm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($srchFrm->getValidationErrors()));
        }
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $srch = BlogContribution::getSearchObject();
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $keywordCond = $srch->addCondition('bcontributions_author_first_name', 'like', '%' . $keyword . '%');
            $keywordCond->attachCondition('bcontributions_author_last_name', 'like', '%' . $keyword . '%');
            $keywordCond->attachCondition('mysql_func_CONCAT(bcontributions_author_first_name," ",bcontributions_author_last_name)', 'like', '%' . $keyword . '%', 'OR', true);
            $keywordCond->attachCondition('bcontributions_author_email', 'like', '%' . $keyword . '%');
            $keywordCond->attachCondition('bcontributions_author_phone', 'like', '%' . $keyword . '%');
        }
        if (isset($post['bcontributions_status']) && $post['bcontributions_status'] != '') {
            $srch->addCondition('bcontributions_status', '=', $post['bcontributions_status']);
        }
        if (isset($post['bcontributions_id']) && $post['bcontributions_id'] != '') {
            $srch->addCondition('bcontributions_id', '=', $post['bcontributions_id']);
        }
        $srch->addMultipleFields(['*', 'concat(bcontributions_author_first_name," ",bcontributions_author_last_name) author_name']);
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $srch->addOrder('bcontributions_id', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $records = $this->fetchAndFormat($records);
        $this->set("arr_listing", $records);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set('canEdit', $this->objPrivilege->canEditBlogContributions(true));
        $this->_template->render(false, false);
    }

    /**
     * View Blog Contribution
     * 
     * @param type $bcontributions_id
     */
    public function view($bcontributions_id)
    {
        $this->objPrivilege->canEditBlogContributions();
        $bcontributions_id = FatUtility::int($bcontributions_id);
        if ($bcontributions_id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm($bcontributions_id);
        $data = BlogContribution::getAttributesById($bcontributions_id);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
        }
        $data['bcontributions_added_on'] = MyDate::formatDate($data['bcontributions_added_on']);
        $frm->fill($data);
        $file = new Afile(Afile::TYPE_BLOG_CONTRIBUTION);
        $fileData = $file->getFile($bcontributions_id);
        $this->set('fileData', $fileData);
        $this->set('data', $data);
        $this->set('frm', $frm);
        $this->set('bcontributions_id', $bcontributions_id);
        $this->set('statusArr', BlogPost::getContriStatuses());
        $this->_template->render(false, false);
    }

    /**
     * Update Status
     */
    public function updateStatus()
    {
        $this->objPrivilege->canEditBlogContributions();
        $bcontributions_id = FatApp::getPostedData('bcontributions_id', FatUtility::VAR_INT, 0);
        if ($bcontributions_id < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getForm($bcontributions_id);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $bcontributions_id = FatUtility::int($post['bcontributions_id']);
        unset($post['bcontributions_id']);
        $oldData = BlogContribution::getAttributesById($bcontributions_id);
        $record = new BlogContribution($bcontributions_id);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        $newData = BlogContribution::getAttributesById($bcontributions_id);
        if ($oldData['bcontributions_status'] != $newData['bcontributions_status']) {
            $this->sendEmail($newData);
        }
        FatUtility::dieJsonSuccess([
            'bcontributionsId' => $bcontributions_id,
            'msg' => Label::getLabel('MSG_CONTRIBUTIONS_STATUS_UPDATED_SUCCESSFUL')
        ]);
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditBlogContributions();
        $bcontributions_id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $blogContribution = new BlogContribution($bcontributions_id);
        if (!$blogContribution->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$blogContribution->deleteRecord()) {
            FatUtility::dieJsonError($blogContribution->getError());
        }
        $file = new Afile(Afile::TYPE_BLOG_CONTRIBUTION);
        $file->removeFile($bcontributions_id, true);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Send Email
     * 
     * @param array $data
     * @return boolean
     */
    private function sendEmail($data)
    {
        if (empty($data)) {
            return false;
        }
        $userLangId = User::getAttributesById($data['bcontributions_user_id'], 'user_lang_id');
        $userLangId = !empty($userLangId) ? $userLangId : $this->siteLangId;
        $vars = [
            '{user_full_name}' => implode(" ", [$data['bcontributions_author_first_name'], $data['bcontributions_author_last_name']]),
            '{new_status}' => BlogPost::getContriStatuses($data['bcontributions_status'], $userLangId),
            '{posted_on_datetime}' => MyDate::showDate($data['bcontributions_added_on'], false, $userLangId),
        ];
        $mail = new FatMailer($userLangId, 'blog_contribution_status_changed');
        $mail->setVariables($vars);
        return $mail->sendMail([$data['bcontributions_author_email']]);
    }


    /**
     * Get Form
     * 
     * @param type $bcontributions_id
     * @return Form
     */
    private function getForm($bcontributions_id = 0): Form
    {
        $frm = new Form('frmBlogContribution', ['id' => 'frmBlogContribution']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'bcontributions_id', FatUtility::int($bcontributions_id));
        $frm->addSelectBox(Label::getLabel('LBL_Contribution_Status'), 'bcontributions_status', BlogPost::getContriStatuses(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save'));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm', ['id' => 'srchForm']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '', ['class' => 'search-input']);
        $frm->addSelectBox(Label::getLabel('LBL_Contribution_Status'), 'bcontributions_status', BlogPost::getContriStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'bcontributions_id');
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $fld_cancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_Clear'));
        $fld_submit->attachField($fld_cancel);
        return $frm;
    }

    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['bcontributions_added_on'] = MyDate::formatDate($row['bcontributions_added_on']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
