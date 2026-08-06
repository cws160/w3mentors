<?php

/**
 * Slides Controller is used for Slides handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class SlidesController extends AdminBaseController
{

    /**
     * Initialize Slides
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewSlides();
    }

    /**
     * Render Slides Search Form
     */
    public function index()
    {
        $this->set("canEdit", $this->objPrivilege->canEditSlides(true));
        $this->set('frmSearch', $this->getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Slides
     */
    public function search()
    {
        $post = FatApp::getPostedData();
        $searchForm = $this->getSearchForm();
        $post = $searchForm->getFormDataFromArray($post);
        $srch = new SearchBase(Slide::DB_TBL, 'sl');
        $srch->addOrder('slide_active', 'DESC');
        $srch->addOrder('slide_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $arrListing = FatApp::getDb()->fetchAll($srch->getResultSet());
        $this->set('postedData', $post);
        $this->set("arrListing", $arrListing);
        $this->set('pageCount', $srch->pages());
        $this->set('recordCount', $srch->recordCount());
        $this->set("canEdit", $this->objPrivilege->canEditSlides(true));
        $this->_template->render(false, false);
    }

    /**
     * Render Slide Form 
     * 
     * @param int $slide_id
     */
    public function form($slide_id = 0)
    {
        $this->objPrivilege->canEditSlides();
        $slide_id = FatUtility::int($slide_id);
        $slideFrm = $this->getForm();
        if (0 < $slide_id) {
            $data = Slide::getAttributesById($slide_id);
            if ($data === false) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $slideFrm->fill($data);
        }
        $this->set('languages', Language::getAllNames());
        $this->set('slide_id', $slide_id);
        $this->set('slideFrm', $slideFrm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Slide
     */
    public function setup()
    {
        $this->objPrivilege->canEditSlides();
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $slideId = $post['slide_id'];
        unset($post['slide_id']);
        $slide = new Slide($slideId);
        $slide->assignValues($post);
        if (!$slide->save()) {
            FatUtility::dieJsonError($slide->getError());
        }
        $data = [
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL'),
            'slideId' => $slide->getMainTableRecordId(),
            'langId' => $slide->getMainTableRecordId(),
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Render Media Form
     * 
     * @param int $slide_id
     */
    public function mediaForm()
    {
        $slideId = FatApp::getPostedData('slideId', FatUtility::VAR_INT, 0);
        $data = Slide::getAttributesById($slideId, ['slide_id']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langId = FatApp::getPostedData('langId', FatUtility::VAR_INT, 0);
        $images = $this->getSliderImages($slideId, $langId);
        $form = $this->getMediaForm($images, $langId);
        $form->fill(['slide_id' => $slideId, 'lang_id' => $langId]);
        $this->sets([
            'slideId' => $slideId,
            'langId' => $langId,
            'form' => $form,
            'displayTypes' => Slide::getDisplaysArr(),
            'images' => $images,
            'dimensions' => $this->dimensionsArray(),
            'imageExts' => implode(',', Afile::getAllowedExts(Afile::TYPE_HOME_BANNER_DESKTOP)),
            'languages' => Language::getAllNames(),
            'formLayout' => Language::getLayoutDirection($langId)
        ]);
        $this->_template->render(false, false);
    }

    private function getSliderImages(int $slideId, int $langId): array
    {
        $srch = new SearchBase(Afile::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['file_type', 'file_id', 'file_lang_id', 'file_record_id']);
        $srch->addCondition('file_type', 'IN', array_keys(Slide::getDisplaysArr()));
        $srch->addCondition('file_lang_id', '=', $langId);
        $srch->addCondition('file_record_id', '=', $slideId);
        $srch->addCondition('file_path', '!=', '');
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'file_type');
    }

    private function dimensionsArray(): array
    {
        $dimensions = [];
        foreach (Slide::getDisplaysArr() as $type => $value) {
            $dimension = (new Afile($type))->getImageSizes(Afile::SIZE_LARGE);
            $dimensions[$type] = implode('x', $dimension);
        }
        return $dimensions;
    }

    public function setupMedia()
    {
        $slideId = FatApp::getPostedData('slide_id', FatUtility::VAR_INT, 0);
        $data = Slide::getAttributesById($slideId, ['slide_id']);
        if (empty($data)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langId = FatApp::getPostedData('lang_id', FatUtility::VAR_INT, 0);
        $images = $this->getSliderImages($slideId, $langId);
        $form = $this->getMediaForm($images);
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData() + $_FILES)) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        foreach (Slide::getDisplaysArr() as $type => $display) {
            if (empty($post['slide_image_' . $type]['name'])) {
                continue;
            }
            $file = new Afile($type, $post['lang_id']);
            if (!$file->saveFile($post['slide_image_' . $type], $post['slide_id'], true)) {
                FatUtility::dieJsonError($file->getError());
                $db->rollbackTransaction();
            }
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess([
            'slideId' => $post['slide_id'],
            'langId' => $post['lang_id'],
            'msg' => Label::getLabel('MSG_FILES_UPLOADED_SUCCESSFULLY')
        ]);
    }

    /**
     * Delete Record
     */
    public function deleteRecord()
    {
        $this->objPrivilege->canEditSlides();
        $slideId = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($slideId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $obj = new Slide($slideId);
        if (!$obj->deleteRecord()) {
            FatUtility::dieJsonError($obj->getError());
        }
        $this->deleleFiles($slideId);
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    /**
     * Delete Files
     * 
     * @param int $slideId
     */
    private function deleleFiles(int $slideId)
    {
        $file = new SearchBase(Afile::DB_TBL, 'af');
        $file->addCondition('file_type', 'IN', [
            Afile::TYPE_HOME_BANNER_DESKTOP,
            Afile::TYPE_HOME_BANNER_IPAD, Afile::TYPE_HOME_BANNER_MOBILE
        ]);
        $file->addCondition('file_record_id', '=', $slideId);
        $file->addMultipleFields(['file_id']);
        $slides = FatApp::getDb()->fetchAll($file->getResultSet());
        $file = new Afile(0);
        foreach ($slides as $value) {
            $file->removeById($value['file_id'], true);
        }
    }

    /**
     * Setup Slide Image
     * 
     * @param int $slide_id
     */
    public function setUpImage($slide_id)
    {
        $this->objPrivilege->canEditSlides();
        $slide_id = FatUtility::int($slide_id);
        if (1 > $slide_id) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $post = FatApp::getPostedData();
        $lang_id = FatUtility::int($post['lang_id']);
        $slide_screen = FatUtility::int($post['slide_screen']);
        if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
            FatUtility::dieJsonError(Label::getLabel('MSG_Please_Select_A_File'));
        }
        $fileType = Afile::TYPE_HOME_BANNER_DESKTOP;
        switch ($slide_screen) {
            case AppConstant::SCREEN_IPAD:
                $fileType = Afile::TYPE_HOME_BANNER_IPAD;
                break;
            case AppConstant::SCREEN_MOBILE:
                $fileType = Afile::TYPE_HOME_BANNER_MOBILE;
                break;
        }
        $file = new Afile($fileType, $lang_id);
        if (!$file->saveFile($_FILES['file'], $slide_id, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        $data = [
            'slideId' => $slide_id, 'file' => $_FILES['file']['name'],
            'msg' => $_FILES['file']['name'] . Label::getLabel('MSG_File_uploaded_successfully')
        ];
        FatUtility::dieJsonSuccess($data);
    }

    /**
     * Update Sort Order
     */
    public function updateOrder()
    {
        $this->objPrivilege->canEditSlides();
        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $slideObj = new Slide();
            if (!$slideObj->updateOrder($post['slideList'])) {
                FatUtility::dieJsonError($slideObj->getError());
            }
            FatUtility::dieJsonSuccess(Label::getLabel('LBL_Order_Updated_Successfully'));
        }
    }

    /**
     * Change Status
     */
    public function changeStatus()
    {
        $this->objPrivilege->canEditSlides();
        $slideId = FatApp::getPostedData('slideId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (0 >= $slideId) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $data = Slide::getAttributesById($slideId, ['slide_id', 'slide_active']);
        if ($data == false) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $obj = new Slide($slideId);
        if (!$obj->changeStatus($status)) {
            FatUtility::dieJsonError($obj->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'));
    }

    private function getForm(): Form
    {
        $frm = new Form('frmSlide');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'slide_id');
        $fld->requirements()->setIntPositive();
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Banner_Identifier'), 'slide_identifier');
        $fld->setUnique(Slide::DB_TBL, 'slide_identifier', 'slide_id', 'slide_id', 'slide_id');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'slide_active', AppConstant::getActiveArr(), AppConstant::ACTIVE, [], '');
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

    /**
     * Get Media Form
     * 
     * @param int $slide_id
     * @return Form
     */
    private function getMediaForm(array $images, $langId = 0): Form
    {
        $frm = new Form('frmSlideMedia');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'slide_id');
        $frm->addHiddenField('', 'lang_id');
        foreach (Slide::getDisplaysArr() as $type => $display) {
            $fld = $frm->addFileUpload($display, 'slide_image_' . $type, []);
            if (empty($images[$type])) {
                $fld->requirements()->setRequired();
            }
        }
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_UPDATE', $langId));
        return $frm;
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        return new Form('frmSlideSearch', ['id' => 'frmSlideSearch']);
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = [
            [
                'title' => Label::getLabel('LBL_BANNERS')
            ],
        ];
        return $nodes;
    }
}
