<?php

/**
 * Lesson Languages Controller is used for Lesson Languages handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class LessonLanguagesController extends AdminBaseController
{

    /**
     * Initialize LessonLanguages
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewLessonLanguages();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $srchFrm = $this->getSearchForm();
        $srchFrm->fill(FatApp::getPostedData());
        $this->set('srchFrm', $srchFrm);
        $this->_template->render();
    }

    /**
     * Search & List Lesson Languages
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBased(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'ordles');
        $srch->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'tlang.tlang_id = ordles.ordles_tlang_id', 'tlang');
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id '
        . ' and tlanglang.tlanglang_lang_id =' . $this->siteLangId, 'tlanglang');
        $srch->addCondition('orders.order_type', 'IN', [Order::TYPE_LESSON, Order::TYPE_SUBSCR]);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addMultipleFields([
            'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as language', 'COUNT(ordles_tlang_id) AS totalsold', 'ordles_tlang_id',
            'COUNT(IF(ordles_status = ' . Lesson::UNSCHEDULED . ', 1, NULL)) AS unscheduled',
            'COUNT(IF(ordles_status = ' . Lesson::SCHEDULED . ', 1, NULL)) AS scheduled',
            'COUNT(IF(ordles_status = ' . Lesson::COMPLETED . ', 1, NULL)) AS completed',
            'COUNT(IF(ordles_status = ' . Lesson::CANCELLED . ', 1, NULL)) AS cancelled'
        ]);
        $srch->addCondition('ordles_type', '!=', Lesson::TYPE_FTRAIL);
        if (!empty($post['ordles_tlang_id'])) {
            $srch->addDirectCondition('(ordles_tlang_id = ' . $post['ordles_tlang_id'] . ' OR FIND_IN_SET(' . $post['ordles_tlang_id'] . ', tlang_parentids))');
        } elseif (!empty($post['ordles_tlang'])) {
            $cond = $srch->addCondition('tlanglang.tlang_name', 'LIKE', '%' . trim($post['ordles_tlang']) . '%');
            $cond->attachCondition('tlang.tlang_identifier', 'LIKE', '%' . trim($post['ordles_tlang']) . '%');

            $tlangIds = TeachLanguage::searchByKeyword(trim($post['ordles_tlang']), $this->siteLangId);
            $tlangIds = !empty($tlangIds) ? array_keys($tlangIds) : [-1];
            $cond->attachCondition('tlang_id' , 'IN', $tlangIds, 'OR', true);
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
        $srch->addGroupBy('ordles_tlang_id');
        $srch->addOrder('totalsold', 'DESC');
        $srch->addOrder('ordles_tlang_id', 'ASC');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $techLanguges = TeachLanguage::getNames($this->siteLangId, array_column($records, 'tlang_id'), false);
        $this->set('postedData', $post);
        $this->set("records", $records);
        $this->set('techLanguges', $techLanguges);
        $this->set('page', $post['pageno']);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set('canViewLessons', $this->objPrivilege->canViewLessonsOrders(true));
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
        $frm->addTextBox(Label::getLabel('LBL_LANGUAGE'), 'ordles_tlang', '', ['id' => 'ordles_tlang', 'autocomplete' => 'off']);
        $frm->addHiddenField('', 'ordles_tlang_id', '', ['id' => 'ordles_tlang_id', 'autocomplete' => 'off']);
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'order_addedon_from', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'order_addedon_to', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }

}
