<?php

/**
 * Sales Report Controller is used for Sales Report handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SalesReportController extends AdminBaseController
{

    /**
     * Initialize Sales Report
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Get Search Form
     */
    public function index()
    {
        $this->objPrivilege->canViewSalesReport();
        $date = FatApp::getConfig('CONF_SALES_REPORT_GENERATED_DATE');
        $timezone = Admin::getAttributesById($this->siteAdminId, ['admin_timezone'])['admin_timezone'];
        $datetime = MyDate::convert($date, $timezone);
        $regendatedtime = str_replace('{datetime}', MyDate::showDate($datetime, true), Label::getLabel('LBL_REPORT_GENERATED_ON_{datetime}'));
        $this->set('regendatedtime', $regendatedtime);
        $this->set('frm', $this->getSearchForm());
        $this->set('isCourseRemoved', Course::isEnabled(1));
        $this->set('isGroupClassRemoved', GroupClass::isEnabled(1));
        $this->set('canEditReportStatsRegenerate', $this->objPrivilege->canEditReportStatsRegenerate(true));
        $this->_template->render();
    }

    /**
     * Search & List Sale Report
     */
    public function search()
    {
        $this->objPrivilege->canViewSalesReport();
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBased('tbl_sales_stats', 'slstat');
        if (!empty($post['slstat_date_from'])) {
            $srch->addCondition('slstat_date', '>=', $post['slstat_date_from']);
        }
        if (!empty($post['slstat_date_to'])) {
            $srch->addCondition('slstat_date', '<=', $post['slstat_date_to']);
        }
        $srch->addMultipleFields([
            'slstat_date',
            '(IFNULL(slstat_les_sales,0) + IFNULL(slstat_cls_sales,0) + IFNULL(slstat_crs_sales,0) + IFNULL(slstat_les_discount,0) '
            . ' + IFNULL(slstat_cls_discount,0) + IFNULL(slstat_les_credit_discount,0) + IFNULL(slstat_cls_credit_discount,0) + IFNULL(slstat_crs_discount,0) + IFNULL(slstat_crs_credit_discount,0)  + IFNULL(slstat_subplan_sales,0)  + IFNULL(slstat_subplan_discount,0) + IFNULL(slstat_subplan_credit_discount,0)) AS slstat_total_sales',
            '(IFNULL(slstat_les_sales,0) + IFNULL(slstat_cls_sales,0) + IFNULL(slstat_crs_sales,0) + IFNULL(slstat_subplan_sales,0)) AS slstat_net_sales',
            '(IFNULL(slstat_les_discount,0) + IFNULL(slstat_cls_discount,0) + IFNULL(slstat_crs_discount,0) + IFNULL(slstat_subplan_discount,0)) AS slstat_discount',
            '(IFNULL(slstat_les_credit_discount,0) + IFNULL(slstat_cls_credit_discount,0) + IFNULL(slstat_crs_credit_discount,0)  + IFNULL(slstat_subplan_credit_discount,0)) AS slstat_credit_discount'
        ]);
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $srch->addOrder('slstat_date', 'DESC');
        $srch->addGroupBy('slstat_date');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        if ($records) {
            foreach ($records as $key => $row) {
                $records[$key]['slstat_date'] = MyDate::formatDate($row['slstat_date']);
            }
        }
            
        $this->set('postedData', $post);
        $this->set("records", $records);
        $this->set('page', $post['pageno']);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
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
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'slstat_date_from', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'slstat_date_to', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setInt();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setInt();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }

    /**
     * Regenerate Report
     */
    public function regenerate()
    {
        $this->objPrivilege->canEditReportStatsRegenerate();
        $saleStat = new SaleStat();
        if (!$date = $saleStat->regenerate()) {
            FatUtility::dieJsonError($saleStat->getError());
        }
        $timezone = Admin::getAttributesById($this->siteAdminId, ['admin_timezone'])['admin_timezone'];
        $datetime = MyDate::convert($date, $timezone);
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_REPORT_REGENERATED_SUCCESSFULLY'),
            'regendatedtime' => str_replace('{datetime}', MyDate::showDate($datetime, true), Label::getLabel('LBL_REPORT_GENERATED_ON_{datetime}'))
        ]);
    }

}
