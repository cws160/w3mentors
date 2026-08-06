<?php

/**
 * Currency Management is used for Currency handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class CurrencyManagementController extends AdminBaseController
{

    /**
     * Initialize Currency
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewCurrencyManagement();
    }

    public function index()
    {
        $this->sets([
            "canEdit" => $this->objPrivilege->canEditCurrencyManagement(true),
            "fixerConfig" => Fixer::getConfig()
        ]);
        $this->_template->render();
    }

    /**
     * Search & List Currencies
     */
    public function search()
    {
        $srch = Currency::getSearchObject($this->siteLangId, false);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addOrder('currency_active', 'DESC');
        $srch->addOrder('currency_order', 'ASC');
        $this->sets([
            'arr_listing' => FatApp::getDb()->fetchAll($srch->getResultSet()),
            'canEdit' => $this->objPrivilege->canEditCurrencyManagement(true),
            'activeInactiveArr' => AppConstant::getActiveArr()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Currency Form
     * 
     * @param int  $currencyId
     */
    public function form($currencyId = 0)
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $currencyId = FatUtility::int($currencyId);
        if (0 > $currencyId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $symbol = '$';
        $data = [];
        $defaultCurrency = 0;
        if ($currencyId > 0) {
            $data = Currency::getAttributesById($currencyId);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $defaultCurrency = $data['currency_is_default'];
            $symbol = $data['currency_symbol'];
        }
        $frm = $this->getForm($currencyId, $symbol);
        $frm->fill($data);
        $this->sets([
            'frm' => $frm,
            'currency_id' => $currencyId,
            'languages' => Language::getAllNames(),
            'defaultCurrency' => $defaultCurrency,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Currency 
     */
    public function setup()
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if ($post['currency_value'] > 9999999999.99999999) {
            $msg = Label::getLabel('LBL_VALUE_OF_CURRENCY_CONVERSION_VALUE_MUST_BE_BETWEEN_{minval}_AND_{maxval}.');
            FatUtility::dieJsonError(str_replace(['{minval}', '{maxval}'], [1, '9999999999.99999999'], $msg));
        }
        $currencyId = FatUtility::int($post['currency_id']);
        unset($post['currency_id']);
        if ($currencyId > 0) {
            $data = Currency::getAttributesById($currencyId, ['currency_id', 'currency_is_default']);
            if (empty($data)) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            if ($data['currency_is_default'] == AppConstant::YES) {
                unset($post['currency_value'], $post['currency_code'], $post['currency_active']);
            }
            if (
                isset($post['currency_active']) && $post['currency_active'] == 0 && 
                FatUtility::int(FatApp::getConfig('CONF_SITE_CURRENCY')) == $currencyId
            ) {
                FatUtility::dieJsonError(Label::getLabel('LBL_CANNOT_CHANGE_STATUS_OF_CURRENCY_ALREADY_IN_USE'));
            }
        }
        if ($post['currency_decimal_symbol'] == $post['currency_grouping_symbol']) {
            FatUtility::dieJsonError(Label::getLabel('LBL_DECIMAL_AND_GROUPING_SYMBOLS_MUST_BE_DIFFERENT'));
        }
        $post['currency_updated'] = date('Y-m-d H:i:s');
        $post['currency_symbol'] = trim($post['currency_symbol']);
        $currency = new Currency($currencyId);
        $currency->assignValues($post);
        if (!$currency->save()) {
            FatUtility::dieJsonError($currency->getError());
        }
        $newTabLangId = 0;
        if ($currencyId > 0) {
            $languages = Language::getAllNames();
            foreach ($languages as $langId => $langName) {
                if (!$row = Currency::getAttributesByLangId($langId, $currencyId)) {
                    $newTabLangId = $langId;
                    break;
                }
            }
        } else {
            $currencyId = $currency->getMainTableRecordId();
            $newTabLangId = FatApp::getConfig('CONF_ADMIN_DEFAULT_LANG', FatUtility::VAR_INT, 1);
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'currencyId' => $currencyId,
            'langId' => $newTabLangId
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Currency Lang Form
     * 
     * @param int $currencyId
     * @param int $lang_id
     */
    public function langForm($currencyId = 0, $lang_id = 0)
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $currencyId = FatUtility::int($currencyId);
        $lang_id = FatUtility::int($lang_id);
        if ($currencyId == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langFrm = $this->getLangForm($currencyId, $lang_id);
        $langData = Currency::getAttributesByLangId($lang_id, $currencyId);
        if ($langData) {
            $langFrm->fill($langData);
        }
        $this->sets([
            'languages' => Language::getAllNames(),
            'currencyId' => $currencyId,
            'lang_id' => $lang_id,
            'langFrm' => $langFrm,
            'formLayout' => Language::getLayoutDirection($lang_id),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Currency Language Data
     */
    public function langSetup()
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $post = FatApp::getPostedData();
        $currencyId = $post['currency_id'];
        $lang_id = $post['lang_id'];
        if ($currencyId == 0 || $lang_id == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $frm = $this->getLangForm($currencyId, $lang_id);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        unset($post['currency_id']);
        unset($post['lang_id']);
        $data = [
            'currencylang_lang_id' => $lang_id,
            'currencylang_currency_id' => $currencyId,
            'currency_name' => $post['currency_name']
        ];
        $currency = new Currency($currencyId);
        if (!$currency->updateLangData($lang_id, $data)) {
            FatUtility::dieJsonError($currency->getError());
        }
        $newTabLangId = 0;
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $langName) {
            if (!$row = Currency::getAttributesByLangId($langId, $currencyId)) {
                $newTabLangId = $langId;
                break;
            }
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(Currency::DB_TBL_LANG, $currencyId, $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'currencyId' => $currencyId,
            'langId' => $newTabLangId
        ]);
    }

    /**
     * Update Sort Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $currency = new Currency();
            if (!$currency->updateOrder($post['currencyList'])) {
                FatUtility::dieJsonError($currency->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_UPDATED_SUCCESSFULLY'));
        }
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $currencyId = FatApp::getPostedData('currencyId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (FatUtility::int(FatApp::getConfig('CONF_SITE_CURRENCY')) == $currencyId && $status == 0) {
            FatUtility::dieJsonError(Label::getLabel('LBL_CANNOT_CHANGE_STATUS_OF_CURRENCY_ALREADY_IN_USE'));
        }
        $currency = new Currency($currencyId);
        if (!$currency->loadFromDb()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (!$currency->changeStatus($status)) {
            FatUtility::dieJsonError($currency->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    public function configurationForm()
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $config = Fixer::getConfig();
        $form = $this->getConfigurationForm();
        $form->fill($config);
        $this->sets([
            'form' => $form,
            'config' => $config
        ]);
        $this->_template->render(false, false);
    }

    public function setupConfig()
    {
        $this->objPrivilege->canEditCurrencyManagement();
        $frm = $this->getConfigurationForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $config = Fixer::getConfig();
        $config = [
            'api_key' => $post['api_key'],
            'status' => $post['status'],
            'info' => $config['info'],
            'last_synced' => $config['last_synced'],
        ];
        $conf = new Configurations();
        if (!$conf->update(['CONF_FIXER' => json_encode($config)])) {
            FatUtility::dieJsonError($conf->getError());
        }
        if ($config['status'] == AppConstant::ACTIVE && !empty($config['api_key'])) {
            $currency = new Currency();
            if (!$currency->syncRates()) {
                $error = $currency->getError();
                $error = $error['info'] ?? Label::getLabel('LBL_AN_ERROR_OCCURRED._PLEASE_TRY_AGAIN');
                $db->rollbackTransaction();
                FatUtility::dieJsonError($error);
            }
        }
        $db->commitTransaction();
        $fixerConfig = Fixer::getConfig();
        $lastsync = str_replace(
            '{datetime}',
            MyDate::formatDate($fixerConfig['last_synced']),
            Label::getLabel('LBL_LAST_SYNCED_ON_{datetime}')
        );
        FatUtility::dieJsonSuccess([
            'lastsync' => $lastsync,
            'msg' => Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY')
        ]);
    }

    private function getConfigurationForm(): form
    {
        $frm = new Form('frmConfig');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_FIXER_API_KEY'), 'api_key');
        $status = $frm->addRadioButtons(Label::getLabel("LBL_STATUS"), "status", AppConstant::getActiveArr(), '', ['class' => 'list-inline']);
        $status->requirements()->setRequired();
        $requirement = new FormFieldRequirement('api_key', Label::getLabel('LBL_FIXER_API_KEY'));
        $requirement->setRequired(true);
        $status->requirements()->addOnChangerequirementUpdate(1, 'eq', 'api_key', $requirement);
        $requirement = new FormFieldRequirement('api_key', Label::getLabel('LBL_FIXER_API_KEY'));
        $requirement->setRequired(false);
        $status->requirements()->addOnChangerequirementUpdate(0, 'eq', 'api_key', $requirement);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    public function syncRates()
    {
        $currency = new Currency();
        if (!$currency->syncRates()) {
            FatUtility::dieJsonError($currency->getError());
        }
        $fixerConfig = Fixer::getConfig();
        $lastsync = str_replace(
            '{datetime}',
            MyDate::formatDate($fixerConfig['last_synced']),
            Label::getLabel('LBL_LAST_SYNCED_ON_{datetime}')
        );
        FatUtility::dieJsonSuccess([
            'lastsync' => $lastsync,
            'msg' => Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY')
        ]);
    }

    /**
     * Get Form
     * 
     * @param int $currencyId
     * @return Form
     */
    private function getForm($currencyId = 0, $symbol = '$'): Form
    {
        $frm = new Form('frmCurrency');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'currency_id', FatUtility::int($currencyId));
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Currency_code'), 'currency_code', Currency::getCodeArray(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addTextBox(Label::getLabel('LBL_CURRENCY_SYMBOL'), 'currency_symbol');
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_POSTIVE_FORMAT'), 'currency_positive_format', Currency::getPositiveFormat(true, $symbol), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_NEGATIVE_FORMAT'), 'currency_negative_format', Currency::getNegativeFormat(true, $symbol), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_DECIMAL_SYMBOL'), 'currency_decimal_symbol', Currency::getDecimalSeparator(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_GROUPING_SYMBOL'), 'currency_grouping_symbol', Currency::getGroupingSeparator(), '', [], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired();
        $fld = $frm->addFloatField(Label::getLabel('LBL_CURRENCY_CONVERSION_VALUE'), 'currency_value');
        $fld = $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'currency_active', AppConstant::getActiveArr(), '', [], '');
        $fld->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Lang Form
     * 
     * @param int $currencyId
     * @param int $langId
     * @return Form
     */
    private function getLangForm($currencyId = 0, $langId = 0): Form
    {
        $frm = new Form('frmCurrencyLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'currency_id', $currencyId);
        $frm->addHiddenField('', 'lang_id', $langId);
        $frm->addRequiredField(Label::getLabel('LBL_Currency_Name', $langId), 'currency_name');
        Translator::addTranslatorActions($frm, $langId, $currencyId, Currency::DB_TBL_LANG);
        return $frm;
    }
}
