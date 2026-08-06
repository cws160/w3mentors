<?php

/**
 * Refer Controller is used for handling user referrals
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ReferController extends DashboardController
{

    /**
     * Initialize 
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Refer And Earn Section
     */
    public function index()
    {
        if (!$this->validateReferRequest()) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_REFERRAL_REWARDS_MODULE_DISBALE_BY_ADMIN'));
            }
            FatUtility::exitWithErrorCode(404);
        }

        $frm = $this->getSearchForm();
        $creditBalance = User::getRewardBalance($this->siteUserId);

        if ($this->siteUserType == USER::AFFILIATE) {
            $creditBalance = 0;
        }

        $referCode = RewardPoint::getReferCode($this->siteUserId);
        $user = User::getAttributesById($this->siteUserId, ['user_first_name', 'user_last_name']);
        $mailFrm = $this->getMailForm();
        $this->set('referCode', $referCode);
        $this->set('creditBalance', $creditBalance);
        $this->set('frm', $frm);
        $this->set('mailFrm', $mailFrm);
        $this->set('fullName', $user['user_first_name'] . ' ' . $user['user_last_name']);
        $this->_template->addJs('js/jquery.tagit.js');
        $this->_template->render();
    }

    /**
     * Render Refer And Earn Section
     */
    public function sendMails()
    {
        if (!$this->validateReferRequest()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_REFERRAL_REWARDS_NOT_ENABLE'));
        }
        $frm = $this->getMailForm(true);
        if (!$frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $emails = explode('||', FatApp::getPostedData('emails'));
        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                MyUtility::dieJsonError(Label::getLabel('LBL_Invalid_Email_Format'));
            }
        }
        $referCode = RewardPoint::getReferCode($this->siteUserId);
        $user = User::getAttributesById($this->siteUserId, ['user_first_name', 'user_last_name']);
        $vars = [
            '{user_first_name}' => $user['user_first_name'],
            '{user_last_name}' => $user['user_last_name'],
            '{user_full_name}' => $user['user_first_name'] . ' ' . $user['user_last_name'],
            '{referral_url}' => MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL) . '?referral=' . $referCode,
        ];

        foreach ($emails as $email) {
            $mail = new FatMailer($this->siteLangId, 'referral_invitation_mail');
            $mail->setVariables($vars);
            if (!$mail->sendMail([$email])) {
                return false;
            }
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_INVITE_SEND_SUCCESSFULLY'));
    }

    /**
     * Render Referral earned list
     */
    public function search()
    {
        if (!$this->validateReferRequest()) {
            MyUtility::dieJsonError(Label::getLabel('LBL_REFERRAL_REWARDS_NOT_ENABLE'));
        }
        $post = FatApp::getPostedData();
        $post['pageno'] = $post['pageno'] ?? 1;
        $post['pagesize'] = AppConstant::PAGESIZE;
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($post)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if ($this->siteUserType == USER::AFFILIATE) {
            $html =  'refer/search-referees.php';

            $srch = new SearchBase(User::DB_TBL, 'user');
            $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
            $srch->addDirectCondition('user_verified IS NOT NULL');
            $srch->addCondition('user_referred_by', '=', $this->siteUserId);
            $keyword = trim($post['keyword'] ?? '');
            if (!empty($keyword)) {
                $cnd = $srch->addCondition('mysql_func_CONCAT(user_first_name," ", user_last_name)', 'like', '%' . $keyword . '%', 'AND', true);
                $cnd->attachCondition('user_first_name', 'LIKE', '%' . $keyword . '%');
                $cnd->attachCondition('user_last_name', 'LIKE', '%' . $keyword . '%', 'OR');
            }
            if (!empty($post['date_from'])) {
                $srch->addCondition('user_created', '>=', MyDate::formatToSystemTimezone($post['date_from'] . ' 00:00:00'));
            }
            if (!empty($post['date_to'])) {
                $srch->addCondition('user_created', '<=', MyDate::formatToSystemTimezone($post['date_to'] . ' 23:59:59'));
            }
            $srch->addMultipleFields([
                'user.user_id', 'user_first_name', 'user_last_name', 'user_deleted',
                'user_registered_as', 'user_created'
            ]);
            $srch->addOrder('user_created', 'DESC');
        } else {
            $html =  'refer/search.php';
            $srch = new RewardPointSearch($this->siteUserId);
            $srch->applyPrimaryConditions();
            $srch->applySearchConditions($post);
            $srch->addSearchListingFields();
            $srch->addOrder('repnt_datetime', 'DESC');
        }

        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize(AppConstant::PAGESIZE);
        $this->sets([
            'frm' => $frm,
            'records' => ($this->siteUserType == USER::AFFILIATE) ? $this->formatAffiliateRecords(FatApp::getDb()->fetchAll($srch->getResultSet()))  : $srch->fetchAndFormat(),
            'recordCount' => $srch->recordCount(),
            'post' => $post
        ]);
        $this->_template->render(false, false, $html);
    }

    public function redeemPoints()
    {
        if (!$this->validateReferRequest()) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_REFERRAL_REWARDS_MODULE_DISBALE_BY_ADMIN'));
            }
            FatUtility::exitWithErrorCode(404);
        }

        if ($this->siteUserType == USER::AFFILIATE) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $rewardPoints = new RewardPoint($this->siteUserId);
        if (!$rewardPoints->redeemPointsToWallet()) {
            MyUtility::dieJsonError($rewardPoints->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_REWARD_POINTS_REEDEMED'));
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm()
    {
        $frm = new Form('frmRewardPointSearch');
        $frm = CommonHelper::setFormProperties($frm);
        if ($this->siteUserType == USER::AFFILIATE) {
            $frm->addTextBox(Label::getLabel('LBL_USER'), 'keyword', '');
            $frm->addDateField(Label::getLabel('LBL_Date_From'), 'date_from', '');
            $frm->addDateField(Label::getLabel('LBL_Date_To'), 'date_to', '');
        } else {
            $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'repnt_comment', '');
            $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'repnt_type', RewardPoint::getTypes(), '', [], Label::getLabel('LBL_SELECT_TYPE'));
        }
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getMailForm($required = false)
    {
        $frm = new Form('frmRewardMail');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addTextBox(Label::getLabel('LBL_EMAILS'), 'emails');
        $fld->requirements()->setRequired($required);
        $fld->requirements()->setLangFile(CONF_INSTALLATION_PATH . 'public/validation/en.php');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Send'));
        return $frm;
    }

    private function validateReferRequest()
    {
        switch ($this->siteUserType) {
            case USER::AFFILIATE:
                return FatApp::getConfig('CONF_ENABLE_AFFILIATE_MODULE', FatUtility::VAR_INT, 0);
                break;
            default:
                return FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS', FatUtility::VAR_INT, 0);
                break;
        }
    }

    /**
     * Format Affliate records
     * 
     * @return array 
     */
    private function formatAffiliateRecords(array $records) : array
    {
        foreach ($records as $key => $row) {
            $row['user_created'] = MyDate::convert($row['user_created']);
            $records[$key] = $row;
        }
        return $records;
    }
}
