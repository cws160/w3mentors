<?php

/**
 * Plans Controller is used for handling Plans
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SubscriptionsController extends DashboardController
{

    /**
     * Initialize Plans
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Search Form
     * 
     * @param int $classId
     * @param int $planType
     */
    public function index()
    {
        $frm = SubscriptionSearch::getSearchForm();
        $frm->fill(FatApp::getQueryStringData());
        $this->set('frm', $frm);
        $this->_template->render(true, true);
    }

    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = SubscriptionSearch::getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SubscriptionSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applyPrimaryConditions();
        $srch->applySearchConditions($post);
        $srch->addSearchListingFields();
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $srch->addOrder('ordsub_id', 'DESC');
        $rows = $srch->fetchAndFormat();
        $this->sets([
            'post' => $post,
            'recordCount' => $srch->recordCount(),
            'subscriptions' =>  $rows,
        ]);
        $this->_template->render(false, false);
    }

    public function cancelForm()
    {
        $ordsubId = FatApp::getPostedData('ordsubId', FatUtility::VAR_INT, 0);
        $subscription = new Subscription($ordsubId, $this->siteUserId, $this->siteUserType);
        if (!$record = $subscription->getSubscriptionToCancel(false)) {
            FatUtility::dieJsonError($subscription->getError());
        }
        $frm = $this->getCancelForm();
        $frm->fill($record);
        $this->set('frm', $frm);
        $this->set('lesson', $record);
        $this->_template->render(false, false);
    }

    /**
     * Cancel Setup
     */
    public function cancelSetup()
    {
        $posts = FatApp::getPostedData();
        $frm = $this->getCancelForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $subscription = new Subscription($post['ordsub_id'], $this->siteUserId, $this->siteUserType);
        if (!$subscription->cancel($post, $this->siteLangId)) {
            MyUtility::dieJsonError($subscription->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_RECURRING_LESSON_CANCELLED_SUCCESSFULLY'));
    }

    public function getCancelForm()
    {
        $frm = new Form('cancelFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $comment = $frm->addTextArea(Label::getLabel('LBL_COMMENTS'), 'comment');
        $comment->requirements()->setLength(10, 200);
        $comment->requirements()->setRequired();
        $frm->addHiddenField('', 'ordsub_id')->requirements()->setRequired();
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SUBMIT'));
        return $frm;
    }

}
