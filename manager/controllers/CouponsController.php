<?php

/**
 * Coupons Controller is used for Coupons handling
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CouponsController extends AdminBaseController
{
    /**
     * Initialize Coupon
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewDiscountCoupons();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $this->set("frmSearch", $this->getSearchForm());
        $this->set("canEdit", $this->objPrivilege->canEditDiscountCoupons(true));
        $this->_template->addJs(['js/moment.min.js', 'js/jquery.datetimepicker.js']);
        $this->_template->addCss('css/jquery.datetimepicker.css');
        $this->_template->render();
    }

    /**
     * Search & List Coupons
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBase(Coupon::DB_TBL, 'coupon');
        $srch->joinTable(Coupon::DB_TBL_LANG, 'LEFT JOIN', 'couponlang.couponlang_coupon_id = coupon.coupon_id AND couponlang.couponlang_lang_id = ' . $this->siteLangId, 'couponlang');
        $srch->addMultipleFields(['coupon_id', 'coupon_code', 'coupon_active', 'coupon_discount_type', 'coupon_discount_value', 'coupon_start_date', 'coupon_end_date', 'IFNULL(couponlang.coupon_title, coupon_identifier) as coupon_title']);
        $post['keyword'] = trim($post['keyword'] ?? '');
        if (!empty($post['keyword'])) {
            $cnd = $srch->addCondition('coupon.coupon_code', 'like', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('couponlang.coupon_title', 'like', '%' . $post['keyword'] . '%');
            $cnd->attachCondition('coupon.coupon_identifier', 'like', '%' . $post['keyword'] . '%');
        }
        if ($post['coupon_active'] != '') {
            $srch->addCondition('coupon.coupon_active', '=', $post['coupon_active']);
        }
        if ($post['coupon_expire'] == AppConstant::YES) {
            $srch->addCondition('coupon.coupon_end_date', '<', date('Y-m-d H:i:s'));
        } elseif ($post['coupon_expire'] != '' && $post['coupon_expire'] == AppConstant::NO) {
            $srch->addCondition('coupon.coupon_end_date', '>', date('Y-m-d H:i:s'));
        }

        $srch->addOrder('coupon_active', 'DESC');
        $srch->addOrder('coupon_id', 'DESC');
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $records = $this->fetchAndFormat($records);
        $this->set('postedData', $post);
        $this->set("arr_listing", $records);
        $this->set('page', $post['pageno']);
        $this->set('pageSize', $post['pagesize']);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set("canEdit", $this->objPrivilege->canEditDiscountCoupons(true));
        $this->_template->render(false, false);
    }

    /**
     * Render Coupon Form
     *
     * @param int $couponId
     */
    public function form($couponId)
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $couponId = FatUtility::int($couponId);
        $data = ['coupon_id' => $couponId];
        $frm = $this->getForm();
        if (0 < $couponId) {
            $data = Coupon::getAttributesById($couponId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $timeFormat = MyDate::getFormatTime();
            $data['coupon_start_date'] = MyDate::formatDate($data['coupon_start_date'],'Y-m-d '.$timeFormat);
            $data['coupon_end_date'] = MyDate::formatDate($data['coupon_end_date'],'Y-m-d '.$timeFormat);
        }
        $frm->fill($data);
        $this->set('frm', $frm);
        $this->set('couponId', $couponId);
        $this->set('languages', Language::getAllNames());
        $this->_template->render(false, false);
    }

    /**
     * Setup Coupon
     */
    public function setup()
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $post['coupon_code'] = trim($post['coupon_code']);
        $post['coupon_start_date'] = MyDate::formatToSystemTimezone($post['coupon_start_date']);
        $post['coupon_end_date'] = MyDate::formatToSystemTimezone($post['coupon_end_date']);
        $couponId = FatUtility::int($post['coupon_id']);
        $coupon = new Coupon($couponId);
        $coupon->assignValues($post);
        if (!$coupon->save()) {
            FatUtility::dieJsonError($coupon->getError());
        }
        $newTabLangId = 0;
        if ($couponId > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = Coupon::getAttributesByLangId($langId, $couponId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $couponId = $coupon->getMainTableRecordId();
            $newTabLangId = $this->siteLangId;
        }
        $data = [
            'couponId' => $couponId,
            'langId' => $newTabLangId,
            'msg' => Label::getLabel('MSG_Coupon_Setup_Successful.'),
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Coupon Lang Form
     *
     * @param int $couponId
     * @param int $langId
     */
    public function langForm($couponId = 0, $langId = 0)
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $couponId = FatUtility::int($couponId);
        $langId = FatUtility::int($langId);
        if ($couponId == 0 || $langId == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($couponId, $langId);
        $langData = Coupon::getAttributesByLangId($langId, $couponId);
        if ($langData) {
            $langFrm->fill($langData);
        }
        $this->set('couponId', $couponId);
        $this->set('coupon_lang_id', $langId);
        $this->set('langFrm', $langFrm);
        $this->set('languages', Language::getAllNames());
        $this->set('formLayout', Language::getLayoutDirection($langId));
        $this->_template->render(false, false);
    }

    /**
     * Setup Coupon Lang Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $langId = FatApp::getPostedData('couponlang_lang_id', FatUtility::VAR_INT, 0);
        $couponId = FatApp::getPostedData('couponlang_coupon_id', FatUtility::VAR_INT, 0);
        if ($couponId == 0 || $langId == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($couponId, $langId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $coupon = new Coupon($couponId);
        if (!$coupon->updateLangData($langId, $post)) {
            FatUtility::dieJsonError($coupon->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = Coupon::getAttributesByLangId($langId, $couponId)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(Coupon::DB_TBL_LANG, $couponId, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('MSG_SETUP_SUCCESSFUL'),
            'couponId' => $couponId,
            'langId' => $newTabLangId
        ]);
    }

    /**
     * Remove Coupon
     */
    public function remove()
    {
        $this->objPrivilege->canEditDiscountCoupons();
        $couponId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        $coupon = Coupon::getAttributesById($couponId);
        if (empty($coupon)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $couponObj = new Coupon($couponId);
        if (!$couponObj->deleteRecord(false)) {
            FatUtility::dieJsonError($couponObj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Get Coupon Uses History
     *
     * @param int $couponId
     */
    public function searchUses()
    {
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $frmSrch = $this->getCpnUsesSrchFrm();
        if (!$post = $frmSrch->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frmSrch->getValidationErrors()));
        }
        $page = ($post['page'] > 1) ? $post['page'] : 1;
        $couponId = FatUtility::int($post['coupon_id']);
        if (empty($couponId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = Coupon::getHistorySearchObject();
        $srch->addCondition('couhis_coupon_id', '=', $couponId);
        $srch->addMultipleFields([
            'order_id',
            'order_total_amount',
            'couhis_released',
            'order_addedon',
            'user_first_name',
            'user_last_name'
        ]);
        $srch->addOrder('couhis_id', 'DESC');
        $srch->setPageSize($pagesize);
        $srch->setPageNumber($page);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($records as $key => $row) {
            $row['order_addedon'] = MyDate::formatDate($row['order_addedon']);
            $records[$key] = $row;
        }

        $this->set('postedData', $post);
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set("records", $records);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     *
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmCouponSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $f1 = $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '');
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'coupon_active', AppConstant::getActiveArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_EXPIRE'), 'coupon_expire', AppConstant::getYesNoArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnCancel = $frm->addButton("", "btn_clear", Label::getLabel('MSG_Clear'), ['onclick' => 'clearSearch()']);
        $btnSubmit->attachField($btnCancel);
        return $frm;
    }

    /**
     * Get Form
     *
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmCoupon');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'coupon_id', '', ['id' => 'coupon_id'])->requirements()->setIntPositive();
        $frm->addRequiredField(Label::getLabel('LBL_Coupon_Identifier'), 'coupon_identifier');
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Coupon_Code'), 'coupon_code');
        $fld->setUnique(Coupon::DB_TBL, 'coupon_code', 'coupon_id', 'coupon_id', 'coupon_id');
        $fld->requirements()->setUsername();
        $couponType = $frm->addSelectBox(Label::getLabel('LBL_DISCOUNT_TYPE'), 'coupon_discount_type', AppConstant::getPercentageFlatArr(), AppConstant::FLAT_VALUE, [], '');
        $couponType->requirements()->setRequired();
        $fld = $frm->addFloatField(Label::getLabel('LBL_DISCOUNT_VALUE'), 'coupon_discount_value');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setFloatPositive();
        $fld->requirements()->setRange(1, 9999999999);
        $percentageRequirement = new FormFieldRequirement('coupon_discount_value', Label::getLabel('LBL_DISCOUNT_VALUE'));
        $percentageRequirement->setRequired(true);
        $percentageRequirement->setFloatPositive();
        $percentageRequirement->setRange(1, 100);
        $flatRequirement = new FormFieldRequirement('coupon_discount_value', Label::getLabel('LBL_DISCOUNT_VALUE'));
        $flatRequirement->setRequired(true);
        $flatRequirement->setFloatPositive();
        $flatRequirement->setRange(1, 9999999999);
        $couponType->requirements()->addOnChangerequirementUpdate(AppConstant::PERCENTAGE, 'eq', 'coupon_discount_value', $percentageRequirement);
        $couponType->requirements()->addOnChangerequirementUpdate(AppConstant::FLAT_VALUE, 'eq', 'coupon_discount_value', $flatRequirement);
        $fld = $frm->addFloatField(Label::getLabel('LBL_MAX_DISCOUNT'), 'coupon_max_discount');
        $maxDiscountRequired = new FormFieldRequirement('coupon_max_discount', Label::getLabel('LBL_Max_Discount'));
        $maxDiscountRequired->setRequired(true);
        $maxDiscountRequired->setFloatPositive();
        $maxDiscountRequired->setRange(1, 9999999999);
        $maxDiscountOptional = new FormFieldRequirement('coupon_max_discount', Label::getLabel('LBL_Max_Discount'));
        $maxDiscountRequired->setRange(1, 9999999999);
        $maxDiscountOptional->setRequired(false);
        $couponType->requirements()->addOnChangerequirementUpdate(AppConstant::PERCENTAGE, 'eq', 'coupon_max_discount', $maxDiscountRequired);
        $couponType->requirements()->addOnChangerequirementUpdate(AppConstant::FLAT_VALUE, 'eq', 'coupon_max_discount', $maxDiscountOptional);
        $fld = $frm->addFloatField(Label::getLabel('LBL_MIN_ORDER'), 'coupon_min_order');
        $fld->requirements()->setFloatPositive();
        $fld->requirements()->setRange(1, 9999999999);
        $fld = $frm->addIntegerField(Label::getLabel('LBL_Max_uses'), 'coupon_max_uses', 1);
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRange(1, 9999);
        $fld = $frm->addIntegerField(Label::getLabel('LBL_Uses/User'), 'coupon_user_uses', 1);
        $fld->requirements()->setIntPositive();
        $fld->requirements()->setRange(1, 9999);
        $fld->requirements()->setCompareWith('coupon_max_uses', 'le', Label::getLabel('LBL_Max_uses'));
        $frm->addRequiredField(Label::getLabel('LBL_Date_From'), 'coupon_start_date', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender fld-date']);
        $endDate = $frm->addRequiredField(Label::getLabel('LBL_Date_Till'), 'coupon_end_date', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender fld-date']);
        $endDate->requirements()->setCompareWith('coupon_start_date', 'gt', Label::getLabel('LBL_Date_From'));
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'coupon_active', AppConstant::getActiveArr(), '', [], Label::getLabel('LBL_SELECT'))->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Lang Form
     *
     * @param int $couponId
     * @param int $langId
     * @return Form
     */
    private function getLangForm($couponId = 0, $langId = 0): Form
    {
        $frm = new Form('frmCouponLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'couponlang_lang_id', FatUtility::int($langId));
        $frm->addHiddenField('', 'couponlang_coupon_id', FatUtility::int($couponId));
        $frm->addRequiredField(Label::getLabel('LBL_Coupon_title', $langId), 'coupon_title');
        $frm->addTextArea(Label::getLabel('LBL_Description', $langId), 'coupon_description')->requirements()->setLength(0, 250);
        Translator::addTranslatorActions($frm, $langId, $couponId, Coupon::DB_TBL_LANG);
        return $frm;
    }

    /**
     * Get Coupon Uses History
     *
     * @param int $couponId
     */

    public function uses($id)
    {
        $frm = $this->getCpnUsesSrchFrm();
        $frm->fill(['coupon_id' => $id]);
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Get Coupon search form
     *
     * @return Form
     */
    private function getCpnUsesSrchFrm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'coupon_id');
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        return $frm;
    }

    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['coupon_start_date'] = MyDate::formatDate($row['coupon_start_date']);
            $row['coupon_end_date'] = MyDate::formatDate($row['coupon_end_date']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
