<?php 
/**
 * Abusive Word Controller is used to handle abusive words
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AbusiveWordsController extends AdminBaseController
{
    /**
     * Initialize Abusive Words
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAbusiveWords();
    }


    public function index()
    {
        $srchFrm = $this->getSearchForm();
        $this->sets([
            'srchFrm' => $srchFrm,
            'canEdit' => $this->objPrivilege->canEditAbusiveWords(true),
        ]);
        $this->_template->render();
    }

    public function search()
    {
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBase(AbusiveWord::DB_TBL);
        $srch->addMultipleFields(['abusive_id', 'abusive_keyword']);
        $keyword = trim($post['abusive_keyword']);
        if ($keyword) {
            $srch->addCondition('abusive_keyword', 'LIKE', '%'.$keyword.'%');
        }
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->sets([
            "records" => $records,
            'page' => $post['pageno'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'postedData' => $post,
            'canEdit' => $this->objPrivilege->canEditAbusiveWords(true),
        ]);
        $this->_template->render(false, false);
    }

    public function form()
    {
        $this->objPrivilege->canEditAbusiveWords();
        $abusiveId = FatApp::getPostedData('abusive_id', FatUtility::VAR_INT, 0);
        $frm = $this->getForm();
        if($abusiveId > 0 && $abusive = AbusiveWord::getAttributesById($abusiveId)){
            $frm->fill($abusive);
        }
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    public function setup()
    {
        $this->objPrivilege->canEditAbusiveWords();
        $frm = $this->getForm();
        if(!$post = $frm->getFormDataFromArray(FatApp::getPostedData())){
            FatUtility::dieJsonError($frm->getValidationErrors());
        }
        $post['abusive_keyword'] = trim($post['abusive_keyword']);
        if($post['abusive_id'] > 0 && !AbusiveWord::getAttributesById($post['abusive_id'], 'abusive_id')){
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $abusive = new AbusiveWord($post['abusive_id']);
        $abusive->assignValues($post);
        if(!$abusive->save()){
            FatUtility::dieJsonError($abusive->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ABUSIVE_WORD_SETUP_SUCCESSFULLY'));
    }

    public function remove()
    {
        $this->objPrivilege->canEditAbusiveWords();
        $abusiveId = FatApp::getPostedData('abusive_id', FatUtility::VAR_INT, 0);
        if(0 >= $abusiveId){
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $abusive = new AbusiveWord($abusiveId);
        if(!$abusive->deleteRecord()){
            FatUtility::dieJsonError($abusive->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    private function getForm() : Form
    {
        $frm = new Form('abusiveGetForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'abusive_id', 0, ['id' => 'abusive_id'])->requirements()->setPositive();
        $fld = $frm->addTextBox(Label::getLabel('LBL_ABUSIVE_KEYWORD'), 'abusive_keyword');
        $fld->requirements()->setRequired();
        $fld->setUnique(AbusiveWord::DB_TBL, 'abusive_keyword', 'abusive_id', 'abusive_id', 'abusive_id');
        $fld->requirements()->setRegularExpressionToValidate(AppConstant::ABUSIVE_REGEX);
        $fld->requirements()->setCustomErrorMessage(Label::getLabel('LBL_FIELD_REQUIRED_WITHOUT_SPACES_IN_WORD'));
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE'));
        return $frm;
    }

    private function getSearchForm() : Form
    {
        $frm = new Form('getSrchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_SEARCH_BY_ABUSIVE_KEYWORD'), 'abusive_keyword');
        $frm->addHiddenField(Label::getLabel('LBL_PAGESIZE'), 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setPositive();
        $frm->addHiddenField(Label::getLabel('LBL_PAGENO'), 'pageno', 1)->requirements()->setPositive();
        $sbmtBtn = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $sbmtBtn->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }
}