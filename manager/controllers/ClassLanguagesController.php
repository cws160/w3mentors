<?php

/**
 * Class Languages Controller is used for Class Languages handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class ClassLanguagesController extends AdminBaseController
{

    /**
     * Initialize Class Languages
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        if (!GroupClass::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
        parent::__construct($action);
        $this->objPrivilege->canViewClassLanguages();
    }

    /**
     * Render Class Language Search Form
     */
    public function index()
    {
        $srchFrm = $this->getSearchForm();
        $srchFrm->fill(FatApp::getPostedData());
        $this->set('srchFrm', $srchFrm);
        $this->_template->render();
    }

    /**
     * Class Languages Search
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBased(Order::DB_TBL, 'orders');
        $srch->joinTable(OrderClass::DB_TBL, 'INNER JOIN', 'ordcls.ordcls_order_id = orders.order_id', 'ordcls');
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'tlang.tlang_id = grpcls.grpcls_tlang_id', 'tlang');
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id =' . $this->siteLangId, 'tlanglang');
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_GCLASS, Order::TYPE_PACKGE]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addMultipleFields([
            'grpcls_tlang_id as tlang_id', 'COUNT(grpcls_tlang_id) AS totalsold',
            'COUNT(IF(ordcls_status = ' . OrderClass::SCHEDULED . ', 1, NULL)) AS scheduled',
            'COUNT(IF(ordcls_status = ' . OrderClass::COMPLETED . ', 1, NULL)) AS completed',
            'COUNT(IF(ordcls_status = ' . OrderClass::CANCELLED . ', 1, NULL)) AS cancelled'
        ]);
        if (!empty($post['grpcls_tlang_id'])) {
            $srch->addDirectCondition('(grpcls_tlang_id = ' . $post['grpcls_tlang_id'] . ' OR FIND_IN_SET(' . $post['grpcls_tlang_id'] . ', tlang_parentids))');
        } elseif (!empty($post['grpcls_tlang'])) {
            $cond = $srch->addCondition('tlanglang.tlang_name', 'LIKE', '%' . trim($post['grpcls_tlang']) . '%');
            $cond->attachCondition('tlang.tlang_identifier', 'LIKE', '%' . trim($post['grpcls_tlang']) . '%');

            $tlangIds = TeachLanguage::searchByKeyword(trim($post['grpcls_tlang']), $this->siteLangId);
            $tlangIds = !empty($tlangIds) ? array_keys($tlangIds) : [-1];
            $cond->attachCondition('tlang_id' , 'IN', $tlangIds, 'OR', true);
            $qryStr = [];
            foreach ($tlangIds as $id) {
                $qryStr[] = 'FIND_IN_SET(' . $id . ', tlang_parentids)';
            }
            $cond->attachCondition('mysql_func_' . implode(' OR ', $qryStr) , '', 'mysql_func_', 'OR', true);
        }
        if (!empty($post['order_addedon_from'])) {
            $start = $post['order_addedon_from'] . ' 00:00:00';
            $srch->addCondition('order_addedon', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['order_addedon_to'])) {
            $end = $post['order_addedon_to'] . ' 23:59:59';
            $srch->addCondition('order_addedon', '<=', MyDate::formatToSystemTimezone($end));
        }
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $srch->addGroupBy('grpcls_tlang_id');
        $srch->addOrder('totalsold', 'DESC');
        $srch->addOrder('grpcls_tlang_id', 'ASC');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $tlangs = TeachLanguage::getNames($this->siteLangId, array_column($records, 'tlang_id'), false);
        $this->set('postedData', $post);
        $this->set("records", $records);
        $this->set('tlangs', $tlangs);
        $this->set('page', $post['pageno']);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('canViewClasses', $this->objPrivilege->canViewClassesOrders(true));
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_LANGUAGE'), 'grpcls_tlang', '', ['id' => 'grpcls_tlang', 'autocomplete' => 'off']);
        $frm->addHiddenField('', 'grpcls_tlang_id', '', ['id' => 'grpcls_tlang_id', 'autocomplete' => 'off']);
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'order_addedon_from', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'order_addedon_to', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }

}
