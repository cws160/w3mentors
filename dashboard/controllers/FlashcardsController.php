<?php

/**
 * Flashcards Controller is used for handling Flashcards
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class FlashcardsController extends DashboardController
{

    /**
     * Initialize Flashcard
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (empty(FatApp::getConfig('CONF_ENABLE_FLASHCARD'))) {
            if (!FatUtility::isAjaxCall()) {
                FatUtility::exitWithErrorCode(404);
            }
            FatUtility::dieJsonError(Label::getLabel('LBL_FLASH_CARD_DISABLED_BY_ADMIN'));
        }
    }

    /**
     * Render Flashcard Search Form
     */
    public function index()
    {
        $frm = Flashcard::getSearchForm($this->siteLangId);
        $frm->fill(FatApp::getPostedData());
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Search & List Flashcards
     */
    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = Flashcard::getSearchForm($this->siteLangId);
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieWithError(current($frm->getValidationErrors()));
        }
        $srch = new SearchBase(Flashcard::DB_TBL, 'flashcard');
        $srch->joinTable(TeachLanguage::DB_TBL, 'LEFT JOIN', 'tlang.tlang_id = flashcard.flashcard_tlang_id', 'tlang');
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id AND tlanglang.tlanglang_lang_id = ' . $this->siteLangId, 'tlanglang');
        $srch->addMultipleFields(['flashcard_id', 'flashcard_tlang_id', 'flashcard_title', 'flashcard_detail', 'flashcard_addedon', 'IFNULL(tlang_name, tlang_identifier) as tlang_name']);
        $srch->addCondition('flashcard_user_id', '=', $this->siteUserId);
        $srch->addOrder('flashcard_id', 'DESC');
        if ($post['view'] != Flashcard::VIEW_SHORT) {
            $srch->setPageSize($post['pagesize']);
            $srch->setPageNumber($post['pageno']);
        } else {
            $srch->doNotCalculateRecords();
        }
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $cond = $srch->addCondition('flashcard_title', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('flashcard_detail', 'LIKE', '%' . $keyword . '%');
        }
        if (!empty($post['flashcard_tlang_id'])) {
            $srch->addCondition('flashcard_tlang_id', '=', $post['flashcard_tlang_id']);
        }
        if ($post['flashcard_type_id'] != '') {
            $srch->addCondition('flashcard_type_id', '=', $post['flashcard_type_id']);
        }
        if ($post['flashcard_type'] != '') {
            $srch->addCondition('flashcard_type', '=', $post['flashcard_type']);
        }
        $flashCards = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($flashCards as $key => $flashCard) {
            $flashCards[$key]['flashcard_addedon'] = MyDate::convert($flashCard['flashcard_addedon']);
        }
        $this->sets([
            "cards" => $flashCards,
            "postedData" => $post,
            "pageCount" => $srch->pages(),
            "recordCount" => $srch->recordCount()
        ]);
        if ($post['view'] == Flashcard::VIEW_SHORT) {
            $this->_template->render(false, false, 'flashcards/shortsearch.php');
        } else {
            $this->_template->render(false, false);
        }
    }

    /**
     * Render Flashcard Form
     */
    public function form()
    {
        $frm = Flashcard::getForm($this->siteLangId);
        $flashcardId = FatApp::getPostedData('flashcardId', FatUtility::VAR_INT, 0);
        if ($flashcardId > 0) {
            $row = Flashcard::getAttributesById($flashcardId);
            if (empty($row) || $row['flashcard_user_id'] != $this->siteUserId) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $frm->fill($row);
        }
        $this->set('frm', $frm);
        if (FatApp::getPostedData('view') == Flashcard::VIEW_SHORT) {
            $this->_template->render(false, false, 'flashcards/shortform.php');
        } else {
            $this->_template->render(false, false);
        }
    }

    /**
     * Setup Flashcard
     * 
     * @param int $type
     */
    public function setup($type = Flashcard::TYPE_OTHER)
    {
        $frm = Flashcard::getForm($this->siteLangId);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['flashcard_tlang_id'])) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $cardId = FatUtility::int($post['flashcard_id']);
        if ($cardId > 0 && $this->siteUserId != Flashcard::getAttributesById($cardId, 'flashcard_user_id')) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $card = new Flashcard($post['flashcard_id']);
        $card->setFldValue('flashcard_user_id', $this->siteUserId);
        $card->setFldValue('flashcard_type', $type);
        $card->assignValues($post);
        if (!$card->save()) {
            MyUtility::dieJsonError($card->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_SAVED_SUCCESSFULLY'));
    }

    /**
     * Setup From Lesson
     */
    public function setupFromLesson()
    {
        $lessonId = FatApp::getPostedData('lessonId', FatUtility::VAR_INT, 0);
        if ($lessonId < 1) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new SearchBase(Order::DB_TBL, 'orders');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'ordles.ordles_order_id = orders.order_id', 'ordles');
        $srch->addMultipleFields(['order_user_id', 'ordles_teacher_id']);
        $srch->addCondition('ordles_id', '=', $lessonId);
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!in_array($this->siteUserId, array_filter([$row['order_user_id'] ?? 0, $row['ordles_teacher_id'] ?? 0]))) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->setup(Flashcard::TYPE_LESSON);
    }

    /**
     * Setup From Class
     */
    public function setupFromClass()
    {
        $classId = FatApp::getPostedData('classId', FatUtility::VAR_INT, 0);
        if ($classId < 1) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->joinTable(OrderClass::DB_TBL, 'LEFT JOIN', 'ordcls.ordcls_grpcls_id = grpcls.grpcls_id', 'ordcls');
        $srch->joinTable(Order::DB_TBL, 'LEFT JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->addMultipleFields(['grpcls_teacher_id', 'order_user_id']);
        $srch->addCondition('grpcls_id', '=', $classId);
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!in_array($this->siteUserId, array_filter([$row['order_user_id'] ?? 0, $row['grpcls_teacher_id'] ?? 0]))) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->setup(Flashcard::TYPE_GCLASS);
    }

    /**
     * Remove Flashcard
     */
    public function remove()
    {
        $cardId = FatApp::getPostedData('cardId', FatUtility::VAR_INT, 0);
        $userId = Flashcard::getAttributesById($cardId, 'flashcard_user_id');
        if ($userId != $this->siteUserId) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $card = new Flashcard($cardId);
        if (!$card->deleteRecord()) {
            MyUtility::dieJsonError($card->getError());
        }
        MyUtility::dieJsonSuccess(Label::getLabel('LBL_DELETED_SUCCESSFULLY!'));
    }

}
