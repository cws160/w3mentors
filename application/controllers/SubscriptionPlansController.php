<?php
/**
 * SubscriptionPlans Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SubscriptionPlansController extends MyAppController
{

    /**
     * Initialize SubscriptionPlans
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (!SubscriptionPlan::isEnabled()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_SUBSCRIPTION_PLAN_MODULE_NOT_AVAILABLE'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $this->setUserSubscription();
    }

    /**
     * Render SubscriptionPlans
     */
    public function index()
    {
        $subscriptionPlans = SubscriptionPlan::getByIds($this->siteLangId);
        $this->sets([
            'siteLangId' => $this->siteLangId,
            'subscriptionPlans' => $subscriptionPlans,
            'activePlan'  => $this->activePlan
        ]);
        $this->_template->render();

    }

}

