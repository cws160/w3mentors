<?php

/**
 * Profile Controller is used for handling Admin Profile
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ProfileController extends AdminBaseController
{

    /**
     * Initialize Profile
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $this->_template->addJs('js/jquery.form.js');
        $this->_template->addJs('js/cropper.js');
        $this->_template->addCss('css/cropper.css');
        $this->_template->render();
    }

    /**
     * Render Profile Info Form
     */
    public function profileInfoForm()
    {
        $frm = $this->getProfileInfoForm();
        $frm->fill($this->siteAdmin);
        $this->sets([
            'frm' => $frm,
            'imgFrm' => $this->getImageForm(),
            'fileExt' => Afile::getAllowedExts(Afile::TYPE_ADMIN_PROFILE_IMAGE),
            'fileSize' => Afile::getAllowedUploadSize(Afile::TYPE_ADMIN_PROFILE_IMAGE),
        ]);
        $file = new Afile(Afile::TYPE_ADMIN_PROFILE_IMAGE);
        $this->set('image', $file->getFiles($this->siteAdminId));
        $this->set('imgFrm', $this->getImageForm());
        $this->_template->render(false, false);
    }

    /**
     * Update Profile Info
     */
    public function updateProfileInfo()
    {
        if (MyUtility::isDemoUrl()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PROFILE_UPDATE_IS_NOT_ALLOWED_ON_DEMO_URL'));
        }
        $frm = $this->getProfileInfoForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $post['admin_email'] = trim($post['admin_email']);
        $post['admin_username'] = trim($post['admin_username']);
        $admin = new AdminUsers($this->siteAdminId);
        $admin->assignValues($post);
        if (!$admin->save()) {
            FatUtility::dieJsonError($admin->getError());
        }
        $_SESSION[AdminAuth::SESSION_ELEMENT]['admin_name'] = $post['admin_name'];
        MyUtility::setAdminTimezone($post['admin_timezone'], true);
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_SETUP_SUCCESSFUL'));
    }

    /**
     * Get Profile Info Form
     * 
     * @return Form
     */
    private function getProfileInfoForm(): Form
    {
        $frm = new Form('frmProfileInfo');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'admin_id', $this->siteAdminId, ['id' => 'admin_id']);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_USERNAME'), 'admin_username', '');
        $fld->setUnique('tbl_admin', 'admin_username', 'admin_id', 'admin_id', 'admin_id');
        $fld->requirements()->setUsername();
        $fld->requirements()->setLength(6, 20);
        $fld = $frm->addEmailField(Label::getLabel('LBL_EMAIL'), 'admin_email', '');
        $fld->setUnique('tbl_admin', 'admin_email', 'admin_id', 'admin_id', 'admin_id');
        $frm->addRequiredField(Label::getLabel('LBL_FULL_NAME'), 'admin_name');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_TIMEZONE'), 'admin_timezone', MyDate::timeZoneListing(), CONF_SERVER_TIMEZONE, [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Profile Image Form
     * 
     * @return Form
     */
    private function getImageForm(): Form
    {
        $frm = new Form('frmProfile', ['id' => 'frmProfile']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addFileUpload(Label::getLabel('LBL_PROFILE_PICTURE'), 'user_profile_image', ['id' => 'user_profile_image', 'onchange' => 'popupImage(this)', 'accept' => 'image/*']);
        $frm->addHiddenField('', 'update_profile_img', Label::getLabel('LBL_UPDATE_PROFILE_PICTURE'), ['id' => 'update_profile_img']);
        $frm->addHiddenField('', 'rotate_left', Label::getLabel('LBL_ROTATE_LEFT'), ['id' => 'rotate_left']);
        $frm->addHiddenField('', 'rotate_right', Label::getLabel('LBL_ROTATE_RIGHT'), ['id' => 'rotate_right']);
        $frm->addHiddenField('', 'remove_profile_img', 0, ['id' => 'remove_profile_img']);
        $frm->addHiddenField('', 'action', 'avatar', ['id' => 'avatar-action']);
        $frm->addHiddenField('', 'img_data', '', ['id' => 'img_data']);
        return $frm;
    }

    /**
     * Upload Profile Image
     */
    public function uploadProfileImage()
    {
        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED'));
        }
        if (!is_uploaded_file($_FILES['user_profile_image']['tmp_name'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_PLEASE_SELECT_A_FILE'));
        }
        $file = new Afile(Afile::TYPE_ADMIN_PROFILE_IMAGE);
        if (!$file->saveFile($_FILES['user_profile_image'], $this->siteAdminId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        $file = MyUtility::makeFullUrl('image', 'show', [Afile::TYPE_ADMIN_PROFILE_IMAGE, $this->siteAdminId]) . '?' . time();
        FatUtility::dieJsonSuccess(['msg' => Label::getLabel('MSG_FILE_UPLOADED_SUCCESSFULLY'), 'file' => $file]);
    }

    /**
     * Remove Profile Image
     */
    public function removeProfileImage()
    {
        $file = new Afile(Afile::TYPE_ADMIN_PROFILE_IMAGE);
        if (!$file->removeFile($this->siteAdminId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_FILE_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Bread Crumb Nodes
     * 
     * @param string $action
     * @return array
     */
    public function getBreadcrumbNodes(string $action): array
    {
        $nodes = [];
        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $className = ucwords(implode(' ', $arr));
        if ($action == 'index') {
            $nodes[] = ['title' => $className];
        } else {
            $nodes[] = ['title' => $action];
        }
        return $nodes;
    }

    /**
     * Render Change Password Form
     */
    public function changePassword()
    {
        $pwdFrm = $this->getPasswordFrm();
        $pwdFrm->setFormTagAttribute('id', 'getPwdFrm');
        $pwdFrm->setFormTagAttribute('class', 'form');
        $pwdFrm->setRequiredStarPosition('none');
        $pwdFrm->setValidatorJsObjectName('changeValidator');
        $pwdFrm->setFormTagAttribute("action", '');
        $pwdFrm->setFormTagAttribute('onsubmit', 'changePassword(this, changeValidator); return false;');
        $this->set('pwdFrm', $pwdFrm);
        $this->set('clss', 'chg_pass');
        $this->_template->render();
    }

    /**
     * Update Password
     */
    public function updatePassword()
    {
        if (MyUtility::isDemoUrl()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_PASSWORD_UPDATE_IS_NOT_ALLOWED_ON_DEMO_URL'));
        }
        $adminProfileObj = new AdminUsers($this->siteAdminId);
        $pwdFrm = $this->getPasswordFrm();
        if (!$post = $pwdFrm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($pwdFrm->getValidationErrors()));
        }
        if (!$curDbPassword = AdminUsers::getAttributesById($this->siteAdminId, 'admin_password')) {
            FatUtility::dieJsonError($adminProfileObj->getError());
        }
        $newPassword = UserAuth::encryptPassword(FatApp::getPostedData('new_password'));
        $currentPassword = UserAuth::encryptPassword(FatApp::getPostedData('current_password'));
        if ($curDbPassword != $currentPassword) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOUR_CURRENT_PASSWORD_MIS_MATCHED'));
        }
        $data = ['admin_password' => $newPassword];
        $adminProfileObj->assignValues($data);
        if (!$adminProfileObj->save()) {
            FatUtility::dieJsonError($adminProfileObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_PASSWORD_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Admin User Logout
     */
    public function logout()
    {
        AdminAuth::clearLoggedAdminLoginCookie();
        unset($_SESSION[AdminAuth::SESSION_ELEMENT]);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_YOU_ARE_LOGGED_OUT_SUCCESSFULLY'));
    }

    /**
     * Get Change Password Form
     * 
     * @return Form
     */
    private function getPasswordFrm(): Form
    {
        $frm = new Form('getPwdFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $curPwd = $frm->addPasswordField(Label::getLabel('LBL_CURRENT_PASSWORD'), 'current_password', '', ['id' => 'current_password']);
        $curPwd->requirements()->setRequired();
        $newPwd = $frm->addPasswordField(Label::getLabel('LBL_NEW_PASSWORD'), 'new_password', '', ['id' => 'new_password']);
        $newPwd->requirements()->setRequired();
        $newPwd->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $newPwd->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $conNewPwd = $frm->addPasswordField(Label::getLabel('LBL_CONFIRM_NEW_PASSWORD'), 'conf_new_password', '', ['id' => 'conf_new_password']);
        $conNewPwd->requirements()->setRequired();
        $conNewPwd->requirements()->setCompareWith('new_password', 'eq');
        $frm->addSubmitButton(Label::getLabel('LBL_CHANGE'), 'btn_submit', Label::getLabel('LBL_CHANGE'), ['id' => 'btn_submit']);
        return $frm;
    }
}
