<?php

/**
 * Plans Controller is used for handling Plans
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PlansController extends DashboardController
{

    /**
     * Initialize Plans
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        if (!in_array($action, ['viewAssignedPlan', 'download'])) {
            MyUtility::setUserType(User::TEACHER);
        }
        parent::__construct($action);
    }

    /**
     * Render Plans Search Form
     * 
     * @param int $classId
     * @param int $planType
     */
    public function index(int $classId = 0, int $planType = 1)
    {
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getPostedData());
        $this->_template->addJs('js/jquery-confirm.min.js');
        $this->_template->addJs('js/jquery.tagsinput.min.js');
        if (FatUtility::isAjaxCall()) {
            $classId = FatUtility::int($classId);
            $planType = FatUtility::int($planType);
            if ($classId < 1) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill(['listing_type' => Plan::LISTING_TYPE,
                'recordId' => $classId, 'planType' => $planType]);
            $this->set('listing_type', Plan::LISTING_TYPE);
            $this->set('frm', $frm);
            $this->_template->render(false, false, 'plans/index-popup.php');
        } else {
            $this->set('frm', $frm);
            $this->_template->render();
        }
    }

    /**
     * Search & List Plans
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        $post = FatApp::getPostedData();
        $post['pageno'] = $post['pageno'] ?? 1;
        $post['pagesize'] = AppConstant::PAGESIZE;
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $recordId = FatUtility::int($post['recordId']);
        $planType = FatUtility::int($post['planType']);
        if (isset($recordId) && $planType == Plan::PLAN_TYPE_CLASSES) {
            $srchRelLsnToPln = new SearchBase('tbl_plan_classes');
            $srchRelLsnToPln->addMultipleFields(['plancls_plan_id']);
            $srchRelLsnToPln->addCondition('plancls_grpcls_id', '=', $recordId);
            $relRs = $srchRelLsnToPln->getResultSet();
            $relRows = FatApp::getDb()->fetch($relRs);
        }

        if (isset($recordId) && $planType == Plan::PLAN_TYPE_LESSONS) {
            $srchRelLsnToPln = new SearchBase('tbl_plan_lessons');
            $srchRelLsnToPln->addMultipleFields(['planles_plan_id']);
            $srchRelLsnToPln->addCondition('planles_ordles_id', '=', $recordId);
            $relRs = $srchRelLsnToPln->getResultSet();
            $relRows = FatApp::getDb()->fetch($relRs);
        }

        $srch = new SearchBase(Plan::DB_TBL);
        $srch->addMultipleFields(['plan_id', 'plan_title', 'plan_level', 'plan_teacher_id', 'plan_detail']);
        $srch->addCondition('plan_teacher_id', '=', $this->siteUserId);
        if (isset($relRows['plancls_plan_id'])) {
            $srch->addCondition('plan_id', '!=', $relRows['plancls_plan_id']);
        }
        if (isset($relRows['planles_plan_id'])) {
            $srch->addCondition('plan_id', '!=', $relRows['planles_plan_id']);
        }
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('plan_title', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('plan_detail', 'LIKE', '%' . $keyword . '%');
        }
        if (!empty($post['plan_level'])) {
            $srch->addCondition('plan_level', '=', $post['plan_level']);
        }
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('plan_id', 'DESC');
        $count = $srch->recordCount();
        $plans = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('recordId', $recordId);
        $this->set('planType', $planType);
        $this->set('countData', $count);
        $this->set('post', $post);
        $this->set('plans', $plans);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Render Plan Form
     */
    public function form()
    {
        $planId = FatApp::getPostedData('planId', FatUtility::VAR_INT, 0);
        if ($planId > 0) {
            $plan = Plan::getAttributesById($planId);
            if (empty($plan) || $plan['plan_teacher_id'] != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $file = new Afile(Afile::TYPE_LESSON_PLAN_FILE);
            $files = $file->getFiles($planId);
        }
        $frm = $this->getForm();
        $frm->fill($plan ?? []);
        $this->set('files', $files ?? []);
        $this->set('frm', $frm);
        $this->set('planId', $planId);
        $this->set('planFileExt', '.png, .jpg');
        $this->_template->render(false, false);
    }

    /**
     * Setup Plan
     */
    public function setup()
    {

        $frm = $this->getForm();
        $posted = FatApp::getPostedData();
        if (!$post = $frm->getFormDataFromArray($posted)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $plan = Plan::getAttributesById($post['plan_id']);
        if (!empty($plan) && $plan['plan_teacher_id'] != $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $post['plan_teacher_id'] = $this->siteUserId;
        $db = FatApp::getDb();
        $db->startTransaction();
        $plan = new Plan($post['plan_id']);
        $plan->assignValues($post);
        if (!$plan->save()) {
            FatUtility::dieJsonError($plan->getError());
        }
        $planId = $plan->getMainTableRecordId();
        for ($i = 0; $i < count($_FILES['plan_file']['name']); $i++) {
            if (!empty($_FILES['plan_file']['name'][$i])) {
                $fileData = [
                    'name' => $_FILES['plan_file']['name'][$i],
                    'type' => $_FILES['plan_file']['type'][$i],
                    'tmp_name' => $_FILES['plan_file']['tmp_name'][$i],
                    'error' => $_FILES['plan_file']['error'][$i],
                    'size' => $_FILES['plan_file']['size'][$i]
                ];
                $file = new Afile(Afile::TYPE_LESSON_PLAN_FILE);
                if (!$file->saveFile($fileData, $planId)) {
                    $db->rollbackTransaction();
                    FatUtility::dieJsonError($fileData['name'] . ' - ' . $file->getError());
                }
            }
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Remove Plan
     */
    public function remove()
    {
        $planId = FatApp::getPostedData('planId', FatUtility::VAR_INT, 0);
        $plan = Plan::getAttributesById($planId);
        if (empty($plan) || $plan['plan_teacher_id'] != $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $planObj = new Plan($planId);
        if (!$planObj->remove($this->siteUserId)) {
            FatUtility::dieJsonError($planObj->getError());
        }
        $file = new Afile(Afile::TYPE_LESSON_PLAN_FILE);
        $planFiles = $file->getFiles($planId);
        foreach ($planFiles as $key => $planfile) {
            $file->removeById($planfile['file_id'], true);
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Remove File
     */
    public function removeFile()
    {
        $fileId = FatApp::getPostedData('file_id', FatUtility::VAR_INT, 0);
        $planId = FatApp::getPostedData('plan_id', FatUtility::VAR_INT, 0);
        $plan = Plan::getAttributesById($planId);
        if (empty($plan) || $plan['plan_teacher_id'] != $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile(Afile::TYPE_LESSON_PLAN_FILE);
        $file->removeById($fileId);
        if ($file->getError()) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Get Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('planFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'plan_id', 0)->requirements()->setInt();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_LEVEL'), 'plan_level', Plan::getLevels(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addTextBox(Label::getLabel('LBL_TITLE'), 'plan_title');
        $fld->requirements()->setRequired();
        $fld = $frm->addTextArea(Label::getLabel('LBL_DETAIL'), 'plan_detail');
        $fld->requirements()->setRequired();
        $fld->requirements()->setLength(1, 500);
        $fld = $frm->addFileUpload(Label::getLabel('LBl_Plan_Files'), 'plan_file[]', ['multiple' => 'multiple', 'id' => 'plan_file']);
        $frm->addHtml('', 'plan_file_display', '', ['id' => 'plan_file_display']);
        $frm->addButton('', 'btn_cancel', Label::getLabel('LBL_Cancel'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Submit'));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('planSearchFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_KEYWORD'), 'id' => 'planKeyword']);
        $frm->addSelectBox(Label::getLabel('LBL_LEVEL'), 'plan_level', Plan::getLevels(), '', ['id' => 'planLevel'], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setInt();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setInt();
        $frm->addHiddenField('', 'listing_type');
        $frm->addHiddenField('', 'attached_plan_id');
        $frm->addHiddenField('', 'recordId');
        $frm->addHiddenField('', 'planType');
        $frm->addHiddenField('', 'mainpage', AppConstant::YES);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Assign Plan To Classes
     */
    public function assignPlanToClasses()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $planId = FatApp::getPostedData('planId', FatUtility::VAR_INT, 0);
        $planType = FatApp::getPostedData('planType', FatUtility::VAR_INT, 0);
        if ($recordId < 1 || $planId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $planDetail = Plan::getAttributesById($planId, ['plan_teacher_id']);
        if ($planDetail['plan_teacher_id'] != $this->siteUserId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Access_Denied'));
        }
        if ($planType == Plan::PLAN_TYPE_CLASSES) {
            $teacherId = GroupClass::getAttributesById($recordId, 'grpcls_teacher_id');
            if ($teacherId != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_Access_Denied'));
            }
            $data = ['plancls_grpcls_id' => $recordId, 'plancls_plan_id' => $planId];
            FatApp::getDb()->deleteRecords('tbl_plan_classes', ['smt' => 'plancls_grpcls_id = ?', 'vals' => [$recordId]]);
            if (!FatApp::getDb()->insertFromArray('tbl_plan_classes', $data, false, [], $data)) {
                FatUtility::dieJsonError(FatApp::getDb()->getError());
            }
        } else {
            $teacherId = Lesson::getAttributesById($recordId, 'ordles_teacher_id');
            if ($teacherId != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_Access_Denied'));
            }
            $data = ['planles_ordles_id' => $recordId, 'planles_plan_id' => $planId];
            FatApp::getDb()->deleteRecords('tbl_plan_lessons', ['smt' => 'planles_ordles_id = ?', 'vals' => [$recordId]]);
            if (!FatApp::getDb()->insertFromArray('tbl_plan_lessons', $data, false, [], $data)) {
                FatUtility::dieJsonError(FatApp::getDb()->getError());
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_Lesson_Plan_Assigned_Successfully!'));
    }

    /**
     * Remove Assigned Plan
     */
    public function removeAssignedPlan()
    {
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_INT, 0);
        $planType = FatApp::getPostedData('planType', FatUtility::VAR_INT, 0);
        if ($recordId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($planType == Plan::PLAN_TYPE_CLASSES) {
            $teacherId = GroupClass::getAttributesById($recordId, 'grpcls_teacher_id');
            if ($teacherId != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_Access_Denied'));
            }
            $data = ['smt' => 'plancls_grpcls_id = ?', 'vals' => [$recordId]];
            if (!FatApp::getDb()->deleteRecords('tbl_plan_classes', $data)) {
                FatUtility::dieJsonError(FatApp::getDb()->getError());
            }
        } else {
            $teacherId = Lesson::getAttributesById($recordId, 'ordles_teacher_id');
            if ($teacherId != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_Access_Denied'));
            }
            $data = ['smt' => 'planles_ordles_id = ?', 'vals' => [$recordId]];
            if (!FatApp::getDb()->deleteRecords('tbl_plan_lessons', $data)) {
                FatUtility::dieJsonError(FatApp::getDb()->getError());
            }
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_LESSON_PLAN_REMOVED_SUCCESSFULLY'));
    }

    /**
     * View Assigned Plan
     *
     * @param int $planId
     * @param int $recordId
     * @param int $type
     * @return void
     */
    public function viewAssignedPlan(int $planId, int $recordId, int $type)
    {
        $plan = new Plan($planId);
        if (!$plan->canViewPlan($this->siteUserId, $recordId, $type)) {
            FatUtility::dieJsonError($plan->getError());
        }
        $srch = new SearchBase(Plan::DB_TBL);
        $srch->addMultipleFields(['plan_id', 'plan_title', 'plan_level', 'plan_teacher_id', 'plan_detail']);
        if ($type == AppConstant::LESSON) {
            $srch->joinTable(Plan::DB_TBL_LESSON, 'INNER JOIN', 'planles.planles_plan_id = plan_id', 'planles');
            $srch->addCondition('planles_id', '=', FatUtility::int($recordId));
        } else {
            $srch->joinTable(Plan::DB_TBL_GCLASS, 'INNER JOIN', 'plancls.plancls_plan_id = plan_id', 'plancls');
            $srch->addCondition('plancls_id', '=', FatUtility::int($recordId));
        }
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $row['record_id'] = $recordId;
        $this->set('data', $row);
        $file = new Afile(Afile::TYPE_LESSON_PLAN_FILE);
        $this->set('planFiles', $file->getFiles($row['plan_id']));
        $this->set('type', $type);
        $this->_template->render(false, false);
    }

    /**
     * Download Plan File
     *
     * @param int $planId
     * @param int $recordId
     * @param int $type
     * @param int $fileId
     * @return void
     */
    public function download(int $planId, int $recordId, int $type, int $fileId)
    {
        $plan = new Plan($planId);
        if (!$plan->canDownload($recordId, $type, $this->siteUserId, $fileId)) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($plan->getError());
            }
            FatUtility::exitWithErrorCode(404);
        }
        $file = new Afile(Afile::TYPE_LESSON_PLAN_FILE);
        $file->downloadById($fileId);
    }

}
