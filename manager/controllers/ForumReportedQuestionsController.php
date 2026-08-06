<?php

/**
 * ForumReportedQuestions Controller is used for add/edit Forum Tags
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumReportedQuestionsController extends AdminBaseController
{

    /**
     * Initialize ForumReportedQuestions
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewDiscussionForum();
    }

    /**
     * Render Forum Reported Question Form
     */
    public function index()
    {
        $this->set('canEdit', $this->objPrivilege->canEditDiscussionForum(true));
        $this->set('frmSearch', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Forum Reported Question
     */
    public function search()
    {
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray(FatApp::getPostedData());
        $keyword = FatApp::getPostedData('keyword');
        $srch = ForumReportedQuestion::getSearchObject($this->siteLangId);
        $keyword = trim($keyword);
        if (!empty($keyword)) {
            $srch->addCondition('fque_title', 'like', '%' . $keyword . '%');
        }
        if ($post['status'] != '') {
            $srch->addCondition('fquerep_status', '=', $post['status']);
        }
        $srch->addMultipleFields([
            'IFNULL(frireason_name, frireason_identifier) as fquerep_title',
            'fque_title',
            'CONCAT(user_first_name, " ", user_last_name) AS username',
            'fquerep_frireason_id',
            'fquerep_id',
            'fquerep_added_on',
            'fquerep_status',
            'fque_id', 'fquerep_updated_on'
        ]);
        $post['pageno'] = $post['pageno'] ?? 1;
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('fquerep_id', 'DESC');
        $srch->addOrder('fquerep_status', 'ASC');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $records = $this->fetchAndFormat($records);
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
     * View Report Detail
     *
     * @param int $reportId
     */
    public function view($reportId)
    {
        $srch = ForumReportedQuestion::getSearchObject($this->siteLangId);
        $srch->addCondition('fquerep_id', '=', $reportId);
        $srch->addMultipleFields(['frireason_id', 'IFNULL(frireason_name, frireason_identifier) as fquerep_title', 'fque_title', 'CONCAT(user_first_name, " ", user_last_name) AS username', 'fquerep_id', 'fquerep_added_on', 'fquerep_status', 'fquerep_comments', 'fquerep_admin_comments', 'fquerep_updated_on']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $records = $this->fetchAndFormat($records, true);
        $this->set('records', $records);
        $this->_template->render(false, false);
    }

    /**
     * Render Action Form
     *
     * @param int $reportId
     */
    public function actionForm($reportId)
    {
        $this->objPrivilege->canEditDiscussionForum();
        $data = ForumReportedQuestion::getAttributesById($reportId, ['fquerep_id', 'fquerep_fque_id', 'fquerep_status']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($data['fquerep_status'] > ForumQuestion::QUEST_REPORTED_PENDING) {
            FatUtility::dieJsonError(Label::getLabel('LBL_REQUEST_Has_already_been_handled'));
        }
        $frm = $this->getActionForm();
        $frm->fill([
            'fquerep_id' => $reportId,
            'fquerep_fque_id' => $data['fquerep_fque_id']
        ]);
        $this->sets([
            'frm' => $frm
        ]);
        $this->_template->render(false, false);
    }

    public function setupAction()
    {
        $this->objPrivilege->canEditDiscussionForum();
        $frm = $this->getActionForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $record = new ForumReportedQuestion(0, $post['fquerep_fque_id'], $post['fquerep_id']);
        if (!$record->loadFromDB()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($record->getFldValue('fquerep_status') > ForumQuestion::QUEST_REPORTED_PENDING) {
            FatUtility::dieJsonError(Label::getLabel('LBL_REQUEST_Has_already_been_handled'));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $assignVals = [
            'fquerep_status' => $post['fquerep_status'],
            'fquerep_admin_comments' => $post['fquerep_admin_comments'],
            'fquerep_updated_on' => date('Y-m-d H:i:s')
        ];
        $record->assignValues($assignVals);
        if (!$record->save($assignVals)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($record->getError());
        }
        $que = new ForumQuestion($post['fquerep_fque_id']);
        if ($post['fquerep_status'] == ForumQuestion::QUEST_REPORTED_ACCEPTED) {
            if (!$que->markAsSpam()) {
                $db->rollbackTransaction();
                FatUtility::dieJsonError($que->getError());
            }
        }
        if (!$db->commitTransaction()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($db->getError());
        }
        $que->loadFromDb();
        $queData = $que->getFlds();
        $data = [
            'author_id' => $queData['fque_user_id'],
            'by_user_id' => $record->getFldValue('fquerep_user_id'),
            'fque_title' => $queData['fque_title'],
            'admin_comments' => $post['fquerep_admin_comments'] ?? Label::getLabel('LBL_NA'),
            'fquerep_status' => $post['fquerep_status'],
        ];
        $record->sendStatusUpdateNotifications($data);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    private function getReportedQuestionsSrchObj()
    {
        $srch = new SearchBase(ForumReportedQuestion::DB_TBL);
        $srch->joinTable(ForumQuestion::DB_TBL, 'INNER JOIN', 'fque_id = fquerep_fque_id');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = fque_id');
        return $srch;
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
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'status', ForumQuestion::getReportStatusArray(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $fldSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $fldCancel = $frm->addButton("", "btn_clear", Label::getLabel('LBL_CLEAR'));
        $fldSubmit->attachField($fldCancel);
        return $frm;
    }

    /**
     * Get Action Form
     *
     * @param bool $isGroupClass
     * @return Form
     */
    private function getActionForm(): Form
    {
        $frm = new Form('actionFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $options = ForumQuestion::getReportStatusArray();
        unset($options[ForumQuestion::QUEST_REPORTED_PENDING]);
        $frm->addSelectBox(Label::getLabel('LBL_TAKE_ACTION'), 'fquerep_status', $options, '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired();
        $comment = $frm->addTextArea(Label::getLabel('LBL_ADMIN_COMMENT'), 'fquerep_admin_comments', '');
        $comment->requirements()->setRequired();
        $comment->requirements()->setLength(10, 2000);
        $frm->addHiddenField('', 'fquerep_id', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'fquerep_fque_id', 1)->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_Save'));
        return $frm;
    }
    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['fquerep_added_on'] = MyDate::formatDate($row['fquerep_added_on']);
            $row['fquerep_updated_on'] = MyDate::formatDate($row['fquerep_updated_on']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
