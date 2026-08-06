<?php

/**
 * Bots Controller is used for Bots handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class BotsController extends AdminBaseController
{

    private $fileName;

    /**
     * Initialize Bots
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewRobotsSection();
        $this->fileName = CONF_INSTALLATION_PATH . 'public/robots.txt';
    }

    /**
     * Render robots.txt Form
     */
    public function index()
    {
        if (file_exists($this->fileName) && !is_readable($this->fileName)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_READ_PERMISSION_DENIED'));
        }
        $this->set('canEdit', $this->objPrivilege->canEditRobotsSection(true));
        $frm = $this->getForm();
        if (MyUtility::isDemoUrl()) {
            MyUtility::maskAndDisableFormFields($frm, []);
        }
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Setup robots.txt file
     */
    public function setup()
    {
        $this->objPrivilege->canEditRobotsSection();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if (MyUtility::isDemoUrl()) {
            FatUtility::dieJsonError(Label::getLabel('LBL_YOU_CANNOT_CHANGE_SETTINGS'));
        }
        if (file_exists($this->fileName) && !is_writable($this->fileName)) {
            FatUtility::dieJsonError(Label::getLabel('MSG_WRITE_PERMISSION_DENIED'));
        }
        if (!file_put_contents($this->fileName, $post['botsTxt']) && !empty($post['botsTxt'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_SOMETHING_WENT_WRONG'));
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_SETUP_SUCCESSFUL'));
    }

    /**
     * Get Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $frm = new Form('frmRobots');
        $frm = CommonHelper::setFormProperties($frm);
        $botsTxt = file_exists($this->fileName) ? file_get_contents($this->fileName) : '';
        $frm->addTextArea('', 'botsTxt', $botsTxt, ['title' => Label::getLabel('LBL_Robots_File_Txt')]);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

}
