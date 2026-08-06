<?php

/**
 * Social Platform Controller is used for Social Platform handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SocialPlatformController extends AdminBaseController
{

    /**
     * Initialize Social Platform
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSocialPlatforms();
    }

    public function index()
    {
        $this->_template->render();
    }

    /**
     * Search & List Social Platform
     */
    public function search()
    {
        $srch = new SearchBase(SocialPlatform::DB_TBL);
        $srch->addOrder('splatform_active', 'DESC');
        $srch->addOrder('splatform_order');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $this->set("records", FatApp::getDb()->fetchAll($srch->getResultSet()));
        $this->set("canEdit", $this->objPrivilege->canEditSocialPlatforms(true));
        $this->_template->render(false, false);
    }

    /**
     * Render Social Platform Form
     * 
     * @param int $spId
     */
    public function form($spId = 0)
    {
        $this->objPrivilege->canEditSocialPlatforms();
        $spId = FatUtility::int($spId);
        $frm = $this->getForm();
        if (0 < $spId) {
            $data = SocialPlatform::getAttributesById($spId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Social Platforms
     */
    public function setup()
    {
        $this->objPrivilege->canEditSocialPlatforms();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $record = new SocialPlatform($post['splatform_id']);
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }


    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditSocialPlatforms();
        $splatformId = FatApp::getPostedData('splatformId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (0 >= $splatformId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = SocialPlatform::getAttributesById($splatformId, ['splatform_id', 'splatform_active', 'splatform_url']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if($status == AppConstant::YES && empty($data['splatform_url'])){
            FatUtility::dieJsonError(Label::getLabel('LBL_PLEASE_ADD_LINK_FIRST'));
        }
        $record = new SocialPlatform($splatformId);
        if (!$record->changeStatus($status)) {
            FatUtility::dieJsonError($record->getError());
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
        $frm = new Form('frmSocialPlatform');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'splatform_id', 0);
        $frm->addTextBox(Label::getLabel('LBL_Identifier'), 'splatform_identifier', '', ['disabled' => 'disabled', 'readonly' => 'readonly']);
        $frm->addTextBox(Label::getLabel('LBL_Link'), 'splatform_url')->requirements()->setRequired();
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'splatform_active', AppConstant::getActiveArr(), '', [], '')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

}
