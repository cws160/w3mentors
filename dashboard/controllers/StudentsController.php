<?php

/**
 * Students Controller is used for handling Students
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class StudentsController extends DashboardController
{

    /**
     * Initialize Students
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        MyUtility::setUserType(User::TEACHER);
        parent::__construct($action);
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set('frm', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Students
     */
    public function search()
    {
        $posts = FatApp::getPostedData();
        $page = $posts['pageno'] ?? 1;
        $pageSize = AppConstant::PAGESIZE;
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBase(OfferPrice::DB_TBL, 'offerPrices');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = offerPrices.offpri_learner_id', 'learner');
        $srch->addCondition('offerPrices.offpri_teacher_id', '=', $this->siteUserId);
        $srch->addMultipleFields([
            'CONCAT(learner.user_first_name, " ", learner.user_last_name) AS learner_full_name', 'learner.user_deleted as learner_deleted',
            'learner.user_id AS learner_id', 'IFNULL(offpri_class_price, "") as offpri_class_price', 'IFNULL(offpri_lesson_price, "") as offpri_lesson_price', 'offpri_learner_id',
            'offpri_lessons', 'offpri_classes', 'offpri_package_price', 'offpri_id'
        ]);
        $keyword = trim($post['keyword']);
        if (!empty($keyword)) {
            $fullName = 'mysql_func_CONCAT(learner.user_first_name, " ", learner.user_last_name)';
            $srch->addCondition($fullName, 'LIKE', '%' . $keyword . '%', 'AND', true);
        }
        $srch->setPageNumber($page);
        $srch->setPageSize($pageSize);
        $students = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('post', $post);
        $this->set('students', $students);
        $this->set('pageCount', $srch->pages());
        $this->set('page', $page);
        $this->set('pageSize', $pageSize);
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * Render Offer Form
     */
    public function offerForm()
    {
        $learnerId = FatApp::getPostedData('learnerId', FatUtility::VAR_INT, 0);
        $offers = (new OfferPrice($learnerId))->getLearnerOffers($this->siteUserId);
        if (empty($offers)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $lessonPrices = json_decode($offers['offpri_lesson_price'] ?? '', true);
        if (!empty($lessonPrices)) {
            $offers['offpri_lesson_price'] = array_column($lessonPrices, 'offer', 'duration');
        }
        $classPrices = json_decode(($offers['offpri_class_price']) ?? '', true);
        if (!empty($classPrices)) {
            $offers['offpri_class_price'] = array_column($classPrices, 'offer', 'duration');
        }
        $userSlots = UserSetting::getSettings($this->siteUserId, ['user_slots']);
        $userSlots = json_decode($userSlots['user_slots'] ?? '') ?? [];;
        $classSlots = explode(',', FatApp::getConfig('CONF_GROUP_CLASS_DURATION'));
        $frm = $this->getForm($userSlots, $classSlots);
        $frm->fill($offers);
        $this->sets(['frm' => $frm, 'offers' => $offers, 'userSlots' => $userSlots, 'classSlots' => $classSlots]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Offers
     */
    public function setupOfferPrice()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $offers = (new OfferPrice($post['offpri_learner_id']))->getLearnerOffers($this->siteUserId);
        if (empty($offers)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $offersPercentage = [
            'lessonPrice' => [],
            'classPrice' => [],
            'offpri_package_price' => $post['offpri_package_price']
        ];
        foreach ($post['offpri_lesson_price'] as $duration => $offer) {
            if (empty($offer)) {
                continue;
            }
            $offersPercentage['lessonPrice'][] = ['duration' => $duration, 'offer' => round($offer, 2)];
        }
        foreach ($post['offpri_class_price'] as $duration => $offer) {
            if (empty($offer)) {
                continue;
            }
            $offersPercentage['classPrice'][] = ['duration' => $duration, 'offer' => round($offer, 2)];
        }
        $offerPrice = new OfferPrice($post['offpri_learner_id'], $offers['offpri_id']);
        if (!$offerPrice->setupPrice($offersPercentage, $this->siteUserId)) {
            FatUtility::dieJsonError($offerPrice->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_PRICE_LOCKED_SUCCESSFULLY!'));
    }

    /**
     * Get Offer Price Form
     * 
     * @param array $userSlots
     * @return Form
     */
    private function getForm(array $userSlots = null, array $classSlots = null): Form
    {
        if ($userSlots == null) {
            $userSlots = UserSetting::getSettings($this->siteUserId, ['user_slots']);
            $userSlots = json_decode($userSlots['user_slots'] ?? '') ?? [];;
        }

        if ($classSlots == null) {
            $classSlots = explode(',', FatApp::getConfig('CONF_GROUP_CLASS_DURATION'));
        }
        $lessonLabel = Label::getLabel('LBL_LESSON_{slot}_SLOT_OFFER(%)');
        $classLabel = Label::getLabel('LBL_CLASS_{slot}_SLOT_OFFER(%)');
        $frm = new Form('frmOfferPrice');
        $frm = CommonHelper::setFormProperties($frm);
        foreach ($userSlots as $slot) {
            $lessonLabelText = str_replace('{slot}', $slot, $lessonLabel);
            $fld = $frm->addTextBox($lessonLabelText, 'offpri_lesson_price[' . $slot . ']');
            $fld->requirements()->setFloatPositive();
            $fld->requirements()->setRange(1, 100);
        }
        foreach ($classSlots as $classSlot) {
            $classLabelText = str_replace('{slot}', $classSlot, $classLabel);
            $fld = $frm->addTextBox($classLabelText, 'offpri_class_price[' . $classSlot . ']');
            $fld->requirements()->setFloatPositive();
            $fld->requirements()->setRange(1, 100);
        }
        $fld = $frm->addTextBox(Label::getLabel('LBL_CLASS_PACKAGES_OFFER(%)'), 'offpri_package_price');
        $fld->requirements()->setFloatPositive();
        $fld->requirements()->setRange(1, 100);
        $frm->addHiddenField('', 'offpri_id');

        $fld = $frm->addHiddenField('', 'offpri_learner_id');
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'view', 1)->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear'));
        return $frm;
    }
}
