<?php

/**
 * Dashboard Controller is used for handling Dashboard on Teacher
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class DashboardController extends MyAppController
{

    /**
     * Initialize Dashboard 
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if(isset($_SESSION[AppConstant::SEARCH_SESSION])) {
            unset($_SESSION[AppConstant::SEARCH_SESSION]);
        }
        if (empty($this->siteUserId)) {
            if (FatUtility::isAjaxCall() || API_CALL) {
                http_response_code(401);
                MyUtility::dieUnauthorised(Label::getLabel('LBL_SESSION_EXPIRED'));
            }
            if ($action != 'logout') {
                FatApp::redirectUser(MyUtility::makeUrl('GuestUser', 'loginForm', [], CONF_WEBROOT_FRONT_URL));
            }
        }
        if ($this->siteUserType == User::TEACHER && $this->siteUser['user_is_teacher'] == AppConstant::NO) {
            MyUtility::setUserType(User::LEARNER);
            FatApp::redirectUser(MyUtility::makeUrl('TeacherRequest', 'form', [], CONF_WEBROOT_FRONTEND));
        }
        if ($this->siteUserType == User::AFFILIATE) {
            MyUtility::setUserType(User::AFFILIATE);
            if(!User::isAffiliateEnabled()){
                FatUtility::exitWithErrorCode(404);
            }
            $controllers = [
                'AffiliateController', 'AccountController', 'WalletController', 'NotificationsController', 'DashboardController', 'ReferController'
            ];
            if (!in_array($this->_controllerName, $controllers)) {
                if (FatUtility::isAjaxCall()) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
                }
                Message::addErrorMessage(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
                FatApp::redirectUser(MyUtility::makeUrl('Affiliate'));
            }
        }
        
    }

    public function switchProfile()
    {
        if ($this->siteUserType == User::AFFILIATE) {
            FatUtility::dieJsonError(Label::getLabel('LBL_UNAUTHORIZED_ACCESS'));
        }
        $userType = FatApp::getPostedData('user_type', FatUtility::VAR_STRING, User::LEARNER);
        MyUtility::setUserType($userType);
        $controller = ($userType == User::LEARNER) ? 'Learner' : 'Teacher';
        FatUtility::dieJsonSuccess(['msg' => '', 'url' => MyUtility::makeUrl($controller)]);
    }

    /**
     * Get Badge Counts
     */
    public function getBadgeCounts()
    {
        $notiCount = (new Notification($this->siteUserId))->getUnreadCount($this->siteUserType);
        $messCount = ThreadMessage::getUnreadCount($this->siteUserId);
        FatUtility::dieJsonSuccess(['notifications' => $notiCount, 'messages' => $messCount]);
    }

    /**
     * Report Search Form
     * 
     * @param int $forGraph
     * @return Form
     */
    protected function reportSearchForm(): Form
    {
        $frm = new Form('reportSearchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $field = $frm->addSelectBox(
                Label::getLabel('LBL_DURATION_TYPE'),
                'duration_type',
                MyDate::getDurationTypesArr(),
                MyDate::TYPE_TODAY,
                [],
                Label::getLabel('LBL_SELECT')
        );
        $field->requirements()->setInt();
        $field->requirements()->setRequired(true);
        return $frm;
    }

    public function translateAndAutoFill()
    {
        $tabelName = FatApp::getPostedData('tableName', FatUtility::VAR_STRING, '');
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_STRING, 0);
        $toLangId = FatApp::getPostedData('toLangId', FatUtility::VAR_INT, 0);
        if (empty($tabelName) || empty($recordId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $toLangId = empty($toLangId) ? null : [$toLangId];
        $translator = new Translator($this->siteLangId, $toLangId);
        if (!$translator->translateAndAutoFill($tabelName, $recordId, FatApp::getPostedData())) {
            FatUtility::dieJsonError($translator->getError());
        }

        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_LANGUAGE_CONTENT_UPDATE'),
            'fields' => $translator->getTranslatedFields()
        ]);
    }
	

    public function zoomVerificationCheck()
    {
        if ($this->siteUserType == User::LEARNER) {
            $this->set('zoomVerificationRequired', false);
            return true;
        }
        if (AppConstant::ACTIVE == FatApp::getConfig('CONF_ZOOM_ISV_ENABLED', 0)) {
            $this->set('zoomVerificationRequired', false);
            return true;
        }

        $meetTool = MeetingTool::getDetail(0);
        if ($meetTool['metool_code'] != ZoomMeeting::KEY) {
            $this->set('zoomVerificationRequired', false);
            return true;
        }

        $zoomVerificationStatus = ZoomMeeting::ACC_NOT_SYNCED;
        $zoomVerificationRequired = true;

        $settingData = UserSetting::getSettings($this->siteUserId, ['user_zoom_status']);

        if (is_array($settingData) && count($settingData) > 0) {
            if (ZoomMeeting::ACC_SYNCED_NOT_VERIFIED == $settingData['user_zoom_status']) {
                $zoomVerificationStatus = ZoomMeeting::ACC_SYNCED_NOT_VERIFIED;
            } elseif (ZoomMeeting::ACC_SYNCED_AND_VERIFIED == $settingData['user_zoom_status']) {
                $zoomVerificationStatus = ZoomMeeting::ACC_SYNCED_AND_VERIFIED;
            }
        }

        if (API_CALL) {
            // need to handle API calls if required
        }
        $this->sets([
            'zoomVerificationRequired' => $zoomVerificationRequired,
            'zoomVerificationStatus' => $zoomVerificationStatus
        ]);
    }

    public function createZoomAccount($actionType = '')
    {
        $meetObject = new Meeting($this->siteUserId, $this->siteUserType);

        if (!$meetObject->initMeeting()) {
            FatUtility::dieJsonError($meetObject->getError());
        }

        $user['user_email'] = $this->siteUser['user_email'];
        $user['user_id'] = $this->siteUserId;
        $user['user_type'] = $this->siteUserType;
        $user['user_first_name'] = $this->siteUser['user_first_name'];
        $user['user_last_name'] = $this->siteUser['user_last_name'];

        if (!$meetObject->handleUserAccountRequest($user, $actionType)) {
            FatUtility::dieJsonError($meetObject->getError());
        }
        if ('verify' == $actionType) {
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ZOOM_ACCOUNT_VERIFIED'));
        }

        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ZOOM_USER_CREATED'));
	}	

}
