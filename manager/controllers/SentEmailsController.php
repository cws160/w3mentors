<?php

/**
 * Sent Emails Controller is used to view Archived Emails
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SentEmailsController extends AdminBaseController
{

    /**
     * Initialize Sent Emails
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Email Search Form
     */
    public function index()
    {
        $this->set('srchFrm', $this->sentEmailSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Emails
     */
    public function search()
    {
        $srchFrm = $this->sentEmailSearchForm();
        $post = $srchFrm->getFormDataFromArray(FatApp::getPostedData());
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $pagesize = FatApp::getConfig('CONF_ADMIN_PAGESIZE', FatUtility::VAR_INT, 10);
        $sentEmailObj = new SentEmail();
        $srch = $sentEmailObj->getSearchObject(true);
        $srch->setPageNumber($page);
        $srch->setPageSize($pagesize);
        $arr_listing = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('page', $page);
        $this->set('pageSize', $pagesize);
        $this->set('postedData', $post);
        $this->set("arr_listing", $arr_listing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->_template->render(false, false);
    }

    /**
     * View Sent Mail
     * 
     * @param int  $id
     */
    public function view($id)
    {
        $this->set('data', SentEmail::getAttributesById($id));
        $this->_template->render(false, false);
    }

    /**
     * Sent Email Search Form
     * 
     * @return Form
     */
    private function sentEmailSearchForm(): Form
    {
        $frm = new Form('sentEmailSrchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'page');
        return $frm;
    }

}
