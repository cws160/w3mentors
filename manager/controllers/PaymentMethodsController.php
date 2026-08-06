<?php

/**
 * Payment Methods Controller is used for Payment Methods handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PaymentMethodsController extends AdminBaseController
{

    /**
     * Initialize Payment Methods
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewPaymentMethods();
    }

    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditPaymentMethods(true));
        $this->_template->render();
    }

    /**
     * Search & List Payment Methods
     */
    public function search()
    {
        $this->sets([
            "arr_listing" => PaymentMethod::getAll(),
            "canEdit" => $this->objPrivilege->canEditPaymentMethods(true)
        ]);
        $this->_template->render(false, false);
    }

    public function settingForm()
    {
        $this->objPrivilege->canEditPaymentMethods();
        $pmethodId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if (empty($pmethodId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $pmethod = PaymentMethod::getAttributesById($pmethodId);
        $settingFrm = $this->getSettingForm($pmethod);
        if (MyUtility::isDemoUrl()) {
            MyUtility::maskAndDisableFormFields($settingFrm, ['pmethod_id', 'pmethod_code']);
        }
        $this->set('frm', $settingFrm);
        $this->set('pmethod', $pmethod);
        $this->_template->render(false, false);
    }

    public function settingSetup()
    {
        if (MyUtility::isDemoUrl()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_CHANGE_SETTINGS'));
        }
        $this->objPrivilege->canEditPaymentMethods();
        $pmethodId = FatApp::getPostedData('pmethod_id', FatUtility::VAR_INT, 0);
        if (empty($pmethodId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $pmethod = PaymentMethod::getAttributesById($pmethodId);
        $settingFrm = $this->getSettingForm($pmethod);
        if (!$post = $settingFrm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($settingFrm->getValidationErrors()));
        }
        if (count($post['pmethod_settings'] ?? []) < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NOTING_TO_SAVE'));
        }
        $settings = [];
        foreach ($post['pmethod_settings'] as $key => $value) {
            array_push($settings, ['key' => $key, 'value' => $value, 'type' => $post['pmethod_type'][$key]]);
        }
        $pmethodObj = new PaymentMethod($pmethodId);
        $pmethodObj->setFldValue('pmethod_settings', json_encode($settings));
        if (!$pmethodObj->save()) {
            FatUtility::dieJsonError($pmethodObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    public function txnfeeForm()
    {
        $this->objPrivilege->canEditPaymentMethods();
        $pmethodId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $pmethod = PaymentMethod::getAttributesById($pmethodId);
        if (empty($pmethod)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $fees = ['pmethod_id' => $pmethodId] + json_decode($pmethod['pmethod_fees'], 1);
        $frm = $this->getTxnfeeForm();
        $frm->fill($fees);
        $this->set('frm', $frm);
        $this->set('pmethod', $pmethod);
        $this->_template->render(false, false);
    }

    public function txnfeeSetup()
    {
        $this->objPrivilege->canEditPaymentMethods();
        $frm = $this->getTxnfeeForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $fees = ['type' => $post['type'], 'fee' => $post['fee']];
        $method = new PaymentMethod($post['pmethod_id']);
        $method->setFldValue('pmethod_fees', json_encode($fees));
        if (!$method->save()) {
            FatUtility::dieJsonError($method->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    public function updateOrder()
    {
        $this->objPrivilege->canEditPaymentMethods();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $pMethodObj = new PaymentMethod();
            if (!$pMethodObj->updateOrder($post['paymentMethod'])) {
                FatUtility::dieJsonError($pMethodObj->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_Order_Updated_Successfully'));
        }
    }

    public function changeStatus()
    {
        $this->objPrivilege->canEditPaymentMethods();
        $pmethodId = FatApp::getPostedData('pmethodId', FatUtility::VAR_INT, 0);
        if (0 >= $pmethodId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = PaymentMethod::getAttributesById($pmethodId, ['pmethod_id', 'pmethod_active', 'pmethod_code']);
        if ($data == false || $data['pmethod_code'] == WalletPay::KEY) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $status = ($data['pmethod_active'] == AppConstant::ACTIVE) ? AppConstant::INACTIVE : AppConstant::ACTIVE;
        $obj = new PaymentMethod($pmethodId);
        if (!$obj->changeStatus($status)) {
            FatUtility::dieJsonError($obj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    private function getTxnfeeForm()
    {
        $frm = new Form('frmSettingForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'pmethod_id');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_FEE_TYPE'), 'type', AppConstant::getPercentageFlatArr(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRequired();
       
        $feeFld = $frm->addTextBox(Label::getLabel('LBL_TXN_FEE'), 'fee');
        $feeFld->requirements()->setFloatPositive();
        $feeFld->requirements()->setRequired();

        $flatFeeFld = new FormFieldRequirement('fee', Label::getLabel('LBL_TXN_FEE'));
        $flatFeeFld->setRange(0, 9999999999);
        $fld->requirements()->addOnChangerequirementUpdate(AppConstant::FLAT_VALUE, 'eq', 'fee', $flatFeeFld);

        $percentFeeFld = new FormFieldRequirement('fee', Label::getLabel('LBL_TXN_FEE'));
        $percentFeeFld->setRange(0, 100);
        $fld->requirements()->addOnChangerequirementUpdate(AppConstant::PERCENTAGE, 'eq', 'fee', $percentFeeFld);
        
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    private function getSettingForm(array $pmethod)
    {
        $frm = new Form('frmSettingForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'pmethod_id', $pmethod['pmethod_id']);
        $settings = json_decode($pmethod['pmethod_settings'], 1);
        foreach ($settings as $row) {
            $frm->addHiddenField('', 'pmethod_type[' . $row['key'] . ']', $row['type']);
            if ($row['type'] == 'textarea') {
                $frm->addTextArea(Label::getLabel('PGL_' . $row['key']), 'pmethod_settings[' . $row['key'] . ']', $row['value'])->requirements()->setRequired();
            } elseif ($row['type'] == 'checkbox') {
                $frm->addCheckBox(Label::getLabel('PGL_' . $row['key']), 'pmethod_settings[' . $row['key'] . ']', 1, [], $row['value'], 0)->requirements()->setInt();
            } else {
                $frm->addRequiredField(Label::getLabel('PGL_' . $row['key']), 'pmethod_settings[' . $row['key'] . ']', $row['value']);
            }
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

}
