<?php

/**
 * This Controller is used for handling course resources
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ResourcesController extends DashboardController
{

    /**
     * Initialize Resources
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if ($this->siteUserType != User::TEACHER || !Course::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Search Form
     *
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Get Search Form
     *
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '');
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setInt();
        $frm->addHiddenField('', 'page', 1)->requirements()->setInt();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_CLEAR'));
        return $frm;
    }

    /**
     * Search & List Plans
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        /* get course resources list */
        $srch = new ResourceSearch(0, 0, 0);
        $srch->applySearchConditions($post + ['user_id' => $this->siteUserId]);
        $srch->applyPrimaryConditions();
        $srch->addOrder('resrc_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['page']);
        $resources = $srch->fetchAndFormat();
        $this->sets([
            'resources' => $resources,
            'post' => $post,
            'recordCount' => $srch->recordCount(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Upload File Form
     */
    public function form()
    {
        $frm = $this->getForm();
        $this->set('frm', $frm);

        $this->set('allowedExtensions', implode(', ', Resource::ALLOWED_EXTENSIONS));
        $this->set('filesize', MyUtility::convertBitesToMb(Afile::getAllowedUploadSize()));
        $this->_template->render(false, false);
    }

    /**
     * Get Bulk Upload Form
     *
     */
    private function getForm(): Form
    {
        $frm = new Form('frmResources');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addFileUpload(Label::getLabel('LBl_RESOURCE_FILES'), 'resource_files[]', [
            'multiple' => 'multiple',
            'id' => 'resource_file'
        ])->requirements()->setRequired();
        
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', AppConstant::PAGESIZE)
        ->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'page', 1)->requirements()->setInt();

        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SUBMIT'));
        $frm->addResetButton('', 'btn_cancel', Label::getLabel('LBL_CANCEL'));
        return $frm;
    }

    /**
     * function to upload files
     *
     * @return json
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$frm->getFormDataFromArray($_FILES)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        if (count($_FILES['resource_files']['name']) < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        
        $resource = new Resource();
        if (!$resource->saveFile($_FILES['resource_files'], $this->siteUserId)) {
            FatUtility::dieJsonError($resource->getError());
        }
        
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * function to delete uploaded resources
     *
     * @param int $resourceId
     * @return json
     */
    public function delete(int $resourceId)
    {
        $resourceId = FatUtility::int($resourceId);
        if ($resourceId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }

        $resource = new Resource($resourceId, $this->siteUserId);
        if (!$resource->delete()) {
            FatUtility::dieJsonError($resource->getError());
        }

        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_SUCCESSFULLY'));
    }
}
