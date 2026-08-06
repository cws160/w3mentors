<?php

/**
 * Notifications Controller is used for handling Notifications
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class NotificationsController extends DashboardController
{

    /**
     * Initialize Notifications
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Notifications Page
     */
    public function index()
    {
        $this->set('frm', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Notifications
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError($frm->getValidationErrors());
        }
        $post['pageno'] = empty($post['pageno']) ? 1 : $post['pageno'];
        $post['pagesize'] = empty($post['pagesize']) ? AppConstant::PAGESIZE : $post['pagesize'];
        $srch = new SearchBase(Notification::DB_TBL);
        $srch->addCondition("notifi_user_id", '=', $this->siteUserId);
        $srch->addCondition("notifi_user_type", 'IN', [0, $this->siteUserType]);
        $srch->addOrder("notifi_id", 'desc');
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $notifications = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($notifications as $key => $notification) {
            $notification['notifi_added'] = MyDate::convert($notification['notifi_added']);
            $notifications[$key] = $notification;
        }
        $this->sets([
            'notifications' => $notifications,
            'post' => $post,
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Mark Read Notification
     * 
     * @param int $notificationId
     */
    public function readNotification(int $notificationId)
    {
        $notificationId = FatUtility::int($notificationId);
        $srch = new SearchBase(Notification::DB_TBL);
        $srch->addCondition("notifi_user_id", '=', $this->siteUserId);
        $srch->addCondition("notifi_id", '=', $notificationId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $notification = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($notification)) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            FatApp::redirectUser(MyUtility::makeUrl('notifications', '', [], CONF_WEBROOT_DASHBOARD));
        }
        $link = MyUtility::makeUrl('notifications');
        if (!empty($notification['notifi_link'])) {
            $link = $notification['notifi_link'];
        }
        if ($notification['notifi_read'] === NULL && !UserAuth::getAdminLoggedIn()) {
            $notifi = new Notification($this->siteUserId);
            $notifi->markRead([$notificationId]);
        }
        if (API_CALL) {
            MyUtility::dieJsonSuccess(Label::getLabel('LBL_NOTIFICATION_READ_SUCCESSFULLY'));
        }
        FatApp::redirectUser($link);
    }

    /**
     * Delete Records
     */
    public function deleteRecords()
    {
        if (UserAuth::getAdminLoggedIn()) {
            MyUtility::dieJsonError(Label::getLabel('ERROR_UNAUTHORIZED'));
        }
        $notificationIds = FatApp::getPostedData('record_ids');
        $notificationIds = explode(',', $notificationIds);
        $notifi = new Notification($this->siteUserId);
        if (!$notifi->remove($notificationIds)) {
            MyUtility::dieJsonError(Label::getLabel('ERROR_UNBALE_TO_DELETE'));
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_NOTIFICATION_DELETED_SUCCESSFULLY'));
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        if (UserAuth::getAdminLoggedIn()) {
            MyUtility::dieJsonError(Label::getLabel('ERROR_UNAUTHORISED'));
        }
        $notificationIds = FatApp::getPostedData('record_ids');
        if (empty($notificationIds)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_UNBALE_TO_CHANGE_STATUS'));
        }
        $notificationIds = explode(',', $notificationIds);
        $markread = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        $updateFunction = 'markUnRead';
        if ($markread == AppConstant::YES) {
            $updateFunction = 'markRead';
        }
        $notifi = new Notification($this->siteUserId);
        if (!$notifi->$updateFunction($notificationIds)) {
            MyUtility::dieJsonError($notifi->getError());
        }
        $notiCount = $notifi->getUnreadCount($this->siteUserType);
        $messCount = ThreadMessage::getUnreadCount($this->siteUserId);
        MyUtility::dieJsonSuccess(['notifications' => $notiCount, 'messages' => $messCount, 'message' => Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY!')]);
    }

    /**
     * Get Unread Count
     */
    public function getUnreadCount()
    {
        $notiCount = (new Notification($this->siteUserId))->getUnreadCount($this->siteUserType);
        FatUtility::dieJsonSuccess(['notiCount' => $notiCount]);
    }

    private function getSearchForm()
    {
        $frm = new Form('frmNotificationSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        return $frm;
    }
}
