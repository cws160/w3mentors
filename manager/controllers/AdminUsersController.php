<?php

/**
 * Admin Users Controller is used for Admin User's handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminUsersController extends AdminBaseController
{

    /**
     * Initialize Admin User 
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAdminUsers();
    }

    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditAdminUsers(true));
        $this->_template->render();
    }

    /**
     * Search Users
     */
    public function search()
    {
        $srch = AdminUsers::getSearchObject(false);
        $srch->addOrder('admin_active', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $this->sets([
            'arr_listing' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'canEdit' => $this->objPrivilege->canEditAdminUsers(true),
            'canViewAdminPermissions' => $this->objPrivilege->canViewAdminPermissions(true),
            'adminLoggedInId' => $this->siteAdminId,
            'activeInactiveArr' => AppConstant::getActiveArr(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Admin User Form 
     * 
     * @param int $adminId
     */
    public function form($adminId = 0)
    {
        $this->objPrivilege->canEditAdminUsers();
        $adminId = FatUtility::int($adminId);
        $frm = $this->getForm($adminId);
        if ($adminId > 0) {
            $data = AdminUsers::getAttributesById($adminId);
            if ($data === false) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('frm', $frm);
        $this->set('admin_id', $adminId);
        $this->_template->render(false, false);
    }

    /**
     * Setup Admin User
     */
    public function setup()
    {
        $this->objPrivilege->canEditAdminUsers();
        $post = FatApp::getPostedData();
        $adminId = FatUtility::int($post['admin_id']);
        $frm = $this->getForm($adminId);
        if (!$post = $frm->getFormDataFromArray($post)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $post['admin_email'] = trim($post['admin_email']);
        $post['admin_username'] = trim($post['admin_username']);
        $record = new AdminUsers($adminId);
        if ($adminId == 0) {
            $password = $post['password'];
            $encryptedPassword = UserAuth::encryptPassword($password);
            $post['admin_password'] = $encryptedPassword;
        }
        if ($adminId == $this->siteAdminId && $post['admin_active'] == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_CANNOT_INACTIVE_OWN_PROFILE'));
        }
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_Setup_Successful'));
    }

    /**
     * Render Change Password Form
     * 
     * @param type $adminId
     */
    public function changePassword($adminId = 0)
    {
        $this->objPrivilege->canEditAdminUsers();
        $adminId = FatUtility::int($adminId);
        $frm = $this->getChangePasswordForm($adminId);
        if (0 >= $adminId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = AdminUsers::getAttributesById($adminId);
        $this->set('adminProfile', $data);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Change Password
     */
    public function setupChangePassword()
    {
        $this->objPrivilege->canEditAdminUsers();
        $post = FatApp::getPostedData();
        $adminId = FatUtility::int($post['admin_id']);
        unset($post['admin_id']);
        if (0 >= $adminId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getChangePasswordForm($adminId);
        $post = $frm->getFormDataFromArray($post);
        if (false === $post) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $record = new AdminUsers($adminId);
        $password = $post['password'];
        $encryptedPassword = UserAuth::encryptPassword($password);
        $post['admin_password'] = $encryptedPassword;
        $post['admin_password_update'] = 1;
        $record->assignValues($post);
        if (!$record->save()) {
            FatUtility::dieJsonError($record->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_Password_Changed_Successfully'));
    }

    /**
     * Change User Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditAdminUsers();
        $adminId = FatApp::getPostedData('adminId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if ($adminId <= 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = AdminUsers::getAttributesById($adminId, ['admin_id', 'admin_active']);
        if ($data == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $adminObj = new AdminUsers($adminId);
        if (!$adminObj->changeStatus($status)) {
            FatUtility::dieJsonError($adminObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    /**
     * Render Admin User Permissions Form
     * 
     * @param int $adminId
     */
    public function permissions($adminId = 0)
    {
        $this->objPrivilege->canViewAdminPermissions();
        $adminId = FatUtility::int($adminId);
        if (1 > $adminId || $adminId == 1 || $adminId == $this->siteAdminId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getSearchForm();
        $allAccessfrm = $this->getAllAccessForm();
        $data = AdminUsers::getAttributesById($adminId);
        $frm->fill(['admin_id' => $adminId]);
        $this->set('frm', $frm);
        $this->set('canEdit', $this->objPrivilege->canEditAdminPermissions(true));
        $this->set('admin_id', $adminId);
        $this->set('allAccessfrm', $allAccessfrm);
        $this->set('data', $data);
        $this->_template->render();
    }

    /**
     * Render Admin User Role Form
     */
    public function roles()
    {
        $this->objPrivilege->canViewAdminPermissions();
        $frmSearch = $this->getSearchForm();
        $post = $frmSearch->getFormDataFromArray(FatApp::getPostedData());
        $adminId = FatUtility::int($post['admin_id']);
        $userData = [];
        if ($adminId > 0) {
            $userData = AdminUsers::getUserPermissions($adminId);
        }
        $permissionModules = AdminPrivilege::getPermissionModules($this->siteAdminId);
        $this->set('arr_listing', $permissionModules);
        $this->set('userData', $userData);
        $this->set('canEdit', $this->objPrivilege->canEditAdminPermissions(true));
        $this->set('canViewAdminPermissions', $this->objPrivilege->canViewAdminPermissions(true));
        $this->_template->render(false, false);
    }

    /**
     * Update User Permission
     * 
     * @param type $moduleId
     * @param type $permission
     */
    public function updatePermission($moduleId, $permission)
    {
        $this->objPrivilege->canEditAdminPermissions();
        $moduleId = FatUtility::int($moduleId);
        $permission = FatUtility::int($permission);
        $frmSearch = $this->getSearchForm();
        $post = $frmSearch->getFormDataFromArray(FatApp::getPostedData());
        $adminId = FatUtility::int($post['admin_id']);
        if ($adminId < 2) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = ['admperm_admin_id' => $adminId, 'admperm_section_id' => $moduleId, 'admperm_value' => $permission];
        $adminUser = new AdminUsers();
        if ($moduleId == 0) {
            if (!$adminUser->updatePermissions($data, true)) {
                FatUtility::dieJsonError($adminUser->getError());
            }
        } else {
            $permissionModules = AdminPrivilege::getPermissionModules($this->siteAdminId);
            $permissionArr = AdminPrivilege::getPermissions();
            if (!array_key_exists($moduleId, $permissionModules) || !array_key_exists($permission, $permissionArr)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            if (!$adminUser->updatePermissions($data)) {
                FatUtility::dieJsonError($adminUser->getError());
            }
        }
        $data = [
            'moduleId' => $moduleId,
            'msg' => Label::getLabel('MSG_Updated_Successfully')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Get Search Form
     * 
     * @return \Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmAdminSrchFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'admin_id');
        return $frm;
    }

    /**
     * GetAdmin User Form
     * 
     * @param int $adminId
     * @return \Form
     */
    private function getForm($adminId = 0): Form
    {
        $adminId = FatUtility::int($adminId);
        $frm = new Form('frmAdminUser');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'admin_id', $adminId, ['id' => 'admin_id']);
        $frm->addRequiredField(Label::getLabel('LBL_Full_Name'), 'admin_name');
        $fld = $frm->addTextBox(Label::getLabel('LBL_Username'), 'admin_username', '');
        $fld->setUnique(AdminUsers::DB_TBL, 'admin_username', 'admin_id', 'admin_id', 'admin_id');
        $fld->requirements()->setRequired();
        $fld->requirements()->setUsername();
        $fld->requirements()->setLength(6, 20);
        $emailFld = $frm->addEmailField(Label::getLabel('LBL_Email'), 'admin_email', '');
        $emailFld->setUnique(AdminUsers::DB_TBL, 'admin_email', 'admin_id', 'admin_id', 'admin_id');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_TIMEZONE'), 'admin_timezone', MyDate::timeZoneListing(), CONF_SERVER_TIMEZONE, [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        if ($adminId == 0) {
            $fld = $frm->addPasswordField(Label::getLabel('LBL_Password'), 'password');
            $fld->requirements()->setRequired();
            $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
            $fld->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
            $fld = $frm->addPasswordField(Label::getLabel('LBL_Confirm_Password'), 'confirm_password');
            $fld->requirements()->setRequired();
            $fld->requirements()->setCompareWith('password', 'eq', '');
        }
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'admin_active', AppConstant::getActiveArr(), '', [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get All Access Form
     * 
     * @return \Form
     */
    private function getAllAccessForm(): Form
    {
        $frm = new Form('frmAllAccess');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->setFormTagAttribute('class', 'form form_horizontal');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Select_permission_for_all_modules'), 'permissionForAll', AdminPrivilege::getPermissions(), '', ['class' => 'permissionForAll'], Label::getLabel('LBL_Select'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Apply_to_All'), ['onclick' => 'updatePermission(0);return false;']);
        return $frm;
    }

    /**
     * Get Change Password Form
     * 
     * @param int $adminId
     * @return \Form
     */
    private function getChangePasswordForm($adminId): Form
    {
        $frm = new Form('frmAdminUserChangePassword');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'admin_id', $adminId);
        $fld = $frm->addPasswordField(Label::getLabel('LBL_New_Password'), 'password');
        $fld->requirements()->setRequired();
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::PASSWORD_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel(AppConstant::PASSWORD_CUSTOM_ERROR_MSG));
        $fld = $frm->addPasswordField(Label::getLabel('LBL_Confirm_Password'), 'confirm_password');
        $fld->requirements()->setRequired();
        $fld->requirements()->setCompareWith('password', 'eq', '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }
}
