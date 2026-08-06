<?php

use Mpdf\Tag\Em;

/**
 * Address Controller is used for handling addresses
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AddressesController extends DashboardController
{

    /**
     * Initialize 
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        if (!User::offlineSessionsEnabled()) {
            FatUtility::dieJsonError(Label::getLabel('MSG_MODULE_NOT_ENABLED'));
        }
        parent::__construct($action);
    }


    /**
     * Render address search list
     */
    public function search()
    {
        $address = new UserAddresses($this->siteUserId);
        $this->set("records",  $address->getAll($this->siteLangId, [], true));
        $this->set("userId", $this->siteUserId);
        $this->_template->render(false, false);
    }

    /**
     * Render Address Form
     */
    public function form()
    {
        $addressId = FatApp::getPostedData('address_id');
        $frm = $this->getForm();
        $data = [];
        if ($addressId > 0) {
            $address = new UserAddresses($this->siteUserId);
            $data = $address->getAll($this->siteLangId, [$addressId], true);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ADDRESS_NOT_FOUND'));
            }
            $frm->fill(current($data));
        }
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup data
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['usradd_state_id'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $state = State::getAttributesById($post['usradd_state_id'], ['state_active']);
        if ($state['state_active'] == AppConstant::NO) {
            FatUtility::dieJsonError(Label::getLabel('LBL_STATE_IS_INACTIVE', $this->siteLangId));
        }
        if (empty($post['usradd_latitude']) || empty($post['usradd_longitude'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PLEASE_SELECT_LOCATION'));
        }
        $usrAdrsId = FatUtility::int($post['usradd_id']);
        $usrAdd = new UserAddresses($this->siteUserId, $usrAdrsId);
        if ($usrAdrsId > 0) {
            if (!$usrAdd->getAddressById($this->siteLangId)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ADDRESS_NOT_FOUND'));
            }
        }
        $post['usradd_country_id'] = $this->siteUser['user_country_id'];
        $post['usradd_default'] = FatApp::getPostedData('usradd_default', FatUtility::VAR_INT, 0);
        if (!$usrAdd->saveRecord($post)) {
            FatUtility::dieJsonError($usrAdd->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_ADDRESS_SETUP_SUCCESSFUL')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    public function remove()
    {
        $addressId = FatApp::getPostedData('address_id', FatUtility::VAR_INT, 0);
        if ($addressId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        /* validate address */
        $address = new UserAddresses($this->siteUserId, $addressId);
        if (!$address->remove()) {
            FatUtility::dieJsonError($address->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ADDRESS_REMOVED_SUCCESSFULLY'));
    }

    /**
     * Get Address Form
     * @return Form
     */
    private function getForm(): Form
    {
        if (empty($this->siteUser['user_country_id'])) {
            MyUtility::dieJsonError(Label::getLabel('MSG_PLEASE_SELECT_COUNTRY_FROM_PROFILE_INFO'));
        }
        $data = State::getNames($this->siteLangId, $this->siteUser['user_country_id']);
        if (empty($data)) {
            MyUtility::dieJsonError(Label::getLabel('MSG_NO_STATES_FOUND_FOR_YOUR_COUNTRY'));
        }
        $frm = new Form('frmAddressInfo');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'usradd_id', '');
        $frm->addHiddenField('', 'usradd_country_id', 0);
        $frm->addHiddenField('', 'usradd_place_name', '');
        $frm->addHiddenField('', 'usradd_place_id', '');
        $frm->addRequiredField(Label::getLabel('LBL_Street'), 'usradd_address', '');
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Phone'), 'usradd_phone');
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PHONE_NO_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_PHONE_NO_VALIDATION_MSG'));
        $fld = $frm->addSelectBox(Label::getLabel('LBL_State'), 'usradd_state_id', $data, 0, [], '');
        $fld->requirements()->setRequired(true);
        $frm->addRequiredField(Label::getLabel('LBL_City'), 'usradd_city');
        $frm->addRequiredField(Label::getLabel('LBL_Zipcode'), 'usradd_zipcode');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Type'), 'usradd_type', UserAddresses::getAddressTypes(), 0, [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $fld = $frm->addHiddenField(Label::getLabel('LBL_LATITUDE'), 'usradd_latitude');
        $fld = $frm->addHiddenField(Label::getLabel('LBL_LONGITUDE'), 'usradd_longitude');
        $frm->addCheckBox(Label::getLabel('LBL_Default'), 'usradd_default', AppConstant::YES, [], true, AppConstant::NO);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        $frm->addButton('', 'btn_next', Label::getLabel('LBL_NEXT'));
        return $frm;
    }
}
