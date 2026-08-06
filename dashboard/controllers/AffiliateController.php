<?php

/**
 * Affiliate Controller is used for handling Affiliate
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AffiliateController extends DashboardController
{

    /**
     * Initialize Affiliate
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {  
        parent::__construct($action);
        if ($this->siteUserType != User::AFFILIATE) {
            FatUtility::exitWithErrorCode(404);
        }

    }

    /**
     * Render Affiliate's Dashboard Homepage
     */
    public function index()
    {
        $stats = new Statistics($this->siteUserId);
        $refSignupEarning = $stats->getEarning(MyDate::TYPE_ALL, Transaction::TYPE_REFERRAL_SIGNUP_COMMISSION);
        $refOrderEarning = $stats->getEarning(MyDate::TYPE_ALL, Transaction::TYPE_REFERRAL_ORDER_COMMISSION);
        $this->sets([
            'referralsCount' => (new User())->getReferalCount($this->siteUserId),
            'totalSignupCommission' => $refSignupEarning['earning'] ?? 0,
            'totalOrderCommission' => $refOrderEarning['earning'] ?? 0,
            'walletBalance' => User::getWalletBalance($this->siteUserId),
        ]);
        $this->_template->render();
    }


}
