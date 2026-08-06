<?php

/**
 * Categories Controller
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CategoriesController extends AdminBaseController
{

    /**
     * Initialize Categories
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Search Form
     *
     * @param int $cateId
     */
    public function index($cateId = 0)
    {
        $this->canView(Category::TYPE_COURSE);


        if ($cateId > 0 && !Category::getAttributesById($cateId, 'cate_id')) {
            FatUtility::exitWithErrorCode(404);
        }
        $frm = $this->getSearchForm();
        $frm->fill(['parent_id' => $cateId, 'cate_type' => Category::TYPE_COURSE]);
        $this->sets([
            "frmSearch" => $frm,
            "canEdit" => $this->objPrivilege->canEditCourseCategories(true),
            "parentId" => $cateId
        ]);
        $this->_template->render();
    }

    /**
     * Render Search Form
     *
     * @param int $cateId
     */
    public function quiz($cateId = 0)
    {
        $this->objPrivilege->canViewQuizCategories();

        if ($cateId > 0 && !Category::getAttributesById($cateId, 'cate_id')) {
            FatUtility::exitWithErrorCode(404);
        }
        $frm = $this->getSearchForm();
        $frm->fill(['parent_id' => $cateId, 'cate_type' => Category::TYPE_QUESTION]);
        $this->sets([
            "frmSearch" => $frm,
            "canEdit" => $this->objPrivilege->canEditQuizCategories(true),
            "parentId" => $cateId
        ]);
        $this->_template->render(true, true, 'categories/index.php');
    }

    /**
     * Search & List Categories
     */
    public function search()
    {
        $this->canView(FatApp::getPostedData('cate_type', FatUtility::VAR_INT, Category::TYPE_COURSE));
        
        $form = $this->getSearchForm();
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new SearchBased(Category::DB_TBL, 'catg');
        $srch->joinTable(Category::DB_LANG_TBL, 'LEFT OUTER JOIN', 'catg.cate_id = '
            . ' catg_l.catelang_cate_id AND catg_l.catelang_lang_id = ' . $this->siteLangId, 'catg_l');
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        if (isset($post['parent_id'])) {
            $srch->addCondition('cate_parent', '=', $post['parent_id']);
        }
        if (isset($post['cate_type']) && $post['cate_type'] > 0) {
            $srch->addCondition('catg.cate_type', '=', $post['cate_type']);
        }
        $srch->addMultipleFields([
            'catg.cate_id', 'catg.cate_type', 'catg.cate_parent', 'catg.cate_status',
            'catg.cate_subcategories', 'catg.cate_records', 'catg.cate_featured', 'catg.cate_created', 'catg.cate_updated',
            'catg_l.cate_name', 'catg.cate_identifier', 'catg_l.catelang_lang_id'
        ]);
        $srch->doNotCalculateRecords();
        $srch->addOrder('cate_status', 'DESC');
        $srch->addOrder('cate_order');
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $data = FatApp::getDb()->fetchAll($srch->getResultSet(), 'cate_id');
        $data = $this->fetchAndFormat($data);
        $this->sets([
            'arrListing' => $data, 'postedData' => $post,
            'canEdit' => $this->canEdit(FatApp::getPostedData('cate_type', FatUtility::VAR_INT, Category::TYPE_COURSE), true),
            'canViewCourses' => $this->objPrivilege->canViewCourses(true),
            'canViewQuestions' => $this->objPrivilege->canViewQuestions(true)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Categories Form
     *
     * @param int $categoryId
     * @param int $type
     */
    public function form(int $categoryId, int $type = 0)
    {
        $this->canEdit($type);

        $categoryId = FatUtility::int($categoryId);
        $data = ['cate_type' => $type];
        if ($categoryId > 0) {
            $category = new Category($categoryId);
            $data = $category->getDataById();
            if (count($data) < 1) {
                FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            $type = $data['cate_type'];
        }
        $frm = $this->getForm($categoryId, $type);
        $frm->fill($data);
        $this->sets([
            'frm' => $frm,
            'data' => $data,
            'categoryId' => $categoryId,
            'languages' => Language::getAllNames(),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Setup Categories
     */
    public function setup()
    {
        $this->canEdit(FatApp::getPostedData('cate_type'));

        $frm = $this->getForm($this->siteLangId, FatApp::getPostedData('cate_type'));
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['cate_parent'])) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $category = new Category($post['cate_id']);
        if ($post['cate_id'] > 0) {
            if (!$data = $category->getDataById()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_CATEGORY_NOT_FOUND'));
            }
            if ($post['cate_parent'] > 0 && $data['cate_subcategories'] > 0) {
                FatUtility::dieJsonError(Label::getLabel('LBL_CANNOT_ASSIGN_PARENT_AS_THIS_CATEGORY_HAS_ITS_OWN_SUBCATEGORIES'));
            }
        }
        $post['cate_featured'] = ($post['cate_parent'] > 0 || $post['cate_type'] == Category::TYPE_QUESTION) ? 0 : $post['cate_featured'];
        if (!$category->setup($post)) {
            FatUtility::dieJsonError($category->getError());
        }
        FatUtility::dieJsonSuccess([
            'cateId' => $category->getMainTableRecordId(),
            'msg' => Label::getLabel('MSG_SETUP_SUCCESSFUL')
        ]);
    }

    /**
     * Language Form
     *
     * @param int $categoryId
     * @param int $langId
     */
    public function langForm(int $categoryId, int $langId = 0)
    {
        $this->validate($categoryId);

        /* get lang data */
        $srch = new SearchBase(Category::DB_LANG_TBL);
        $srch->addCondition('catelang_lang_id', '=', $langId);
        $srch->addCondition('catelang_cate_id', '=', $categoryId);
        $srch->addMultipleFields(['cate_name', 'cate_details', 'catelang_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        /* fill form data */
        $frm = $this->getLangForm($langId, $categoryId);
        $frm->fill($data);
        $frm->fill(['catelang_lang_id' => $langId, 'catelang_cate_id' => $categoryId]);
        $this->sets([
            'categoryId' => $categoryId,
            'frm' => $frm,
            'formLayout' => Language::getLayoutDirection($langId),
            'languages' => Language::getAllNames(),
        ]);
        $this->_template->render(false, false);
    }

    private function validate(int $categoryId)
    {
        if (!$category = Category::getAttributesById($categoryId, ['cate_id', 'cate_type'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->canEdit($category['cate_type']);
    }

    /**
     * Setup Lang Data
     */
    public function langSetup()
    {
        $post = FatApp::getPostedData();
        $frm = $this->getLangForm($post['catelang_lang_id'] ?? 0);
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $this->validate($post['catelang_cate_id']);

        $category = new Category($post['catelang_cate_id']);
        if (!$category->addUpdateLangData($post)) {
            FatUtility::dieJsonError($category->getError());
        }
        $translator = new Translator();
        if (!$translator->validateAndTranslate(Category::DB_LANG_TBL, $post['catelang_cate_id'], $post)) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'cateId' => $post['catelang_cate_id'],
            'msg' => Label::getLabel('LBL_SETUP_SUCCESSFUL')
        ]);
    }

    /**
     * Render Media Form
     *
     * @param int $categoryId
     * @param int $langId
     */
    public function mediaForm($categoryId)
    {
        $categoryId = FatUtility::int($categoryId);
        if (!$categoryId) {
            FatUtility::dieJsonError(Label::getLabel('MSG_INVALID_REQUEST'));
        }
        $this->validate($categoryId);

        $frm = $this->getMediaForm($categoryId);
        $file = new Afile(Afile::TYPE_CATEGORY_IMAGE);
        $categoryImage = $file->getFiles($categoryId, false);
        $this->set('languages', Language::getAllNames());
        $this->set('categoryId', $categoryId);
        $this->set('categoryImage', $categoryImage);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    /**
     * Setup Media
     *
     * @param int $postId
     * @param int $langId
     */
    public function setupMedia(int $categoryId)
    {
        $categoryId = FatUtility::int($categoryId);
        if ($categoryId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->validate($categoryId);

        $post = FatApp::getPostedData();
        if (empty($post)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST_OR_FILE_NOT_SUPPORTED'));
        }
        $file = new Afile(Afile::TYPE_CATEGORY_IMAGE);
        if (!$file->saveFile($_FILES['file'], $categoryId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_IMAGE_UPLOADED_SUCCESSFULLY'));
    }

    /**
     * Delete Category Image
     *
     * @param int $categoryId
     * @param int $fileId
     * @param int $langId
     */
    public function removeMedia()
    {
        $categoryId = FatApp::getPostedData('categoryId', FatUtility::VAR_INT, 0);
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);
        if ($categoryId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->validate($categoryId);
        
        if ($type != Afile::TYPE_CATEGORY_IMAGE) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $file = new Afile($type);
        if (!$file->removeFile($categoryId, true)) {
            FatUtility::dieJsonError($file->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('MSG_DELETED_SUCCESSFULLY'));
    }

    /**
     * Delete category
     *
     * @param int $cateId
     */
    public function delete(int $cateId)
    {
        $this->validate($cateId);

        $category = new Category($cateId);
        if (!$category->delete()) {
            FatUtility::dieJsonError($category->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_RECORD_DELETED_SUCCESSFULLY'));
    }

    public function get()
    {
        $type = FatApp::getPostedData('type', FatUtility::VAR_INT, 0);
        $this->set('categories', Category::getCategoriesByParentId($this->siteLangId, 0, $type, false, false));
        $this->_template->render(false, false);
    }

    /**
     * Get Form
     *
     * @return Form
     */
    private function getForm(int $catgId = 0, int $type = 0): Form
    {
        $frm = new Form('frmCategory');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'cate_id')->requirements()->setIntPositive();
        $fld = $frm->addTextBox(Label::getLabel('LBL_IDENTIFIER'), 'cate_identifier');
        $fld->requirements()->setRequired();

        $fld = $frm->addHiddenField('', 'cate_type')->requirements()->setRequired();

        $parentCategories = Category::getCategoriesByParentId($this->siteLangId, 0, $type, false, false);
        if ($catgId > 0) {
            unset($parentCategories[$catgId]);
        }
        $fld = $frm->addSelectBox(Label::getLabel('LBL_PARENT'), 'cate_parent', $parentCategories, '', [], Label::getLabel('LBL_ROOT_CATEGORY'));
        $fld->requirements()->setInt();
        if ($type == Category::TYPE_COURSE) {
            $frm->addCheckBox(Label::getLabel('LBL_FEATURED'), 'cate_featured', 1, [], false, 0);
        }
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'cate_status', AppConstant::getActiveArr(), '', [], '')->requirements()->setRequired();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $frm;
    }

    /**
     * Get Language form
     *
     * @param int $langId
     */
    private function getLangForm(int $langId = 0, int $recordId = 0)
    {
        $frm = new Form('frmLang');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'catelang_id');
        $frm->addHiddenField('', 'catelang_cate_id');
        $frm->addHiddenField('', 'catelang_lang_id');
        $frm->addTextBox(Label::getLabel('LBL_NAME', $langId), 'cate_name')->requirements()->setRequired();
        $frm->addTextarea(Label::getLabel('LBL_DESCRIPTION', $langId), 'cate_details')->requirements()->setRequired();
        Translator::addTranslatorActions($frm, $langId, $recordId, Category::DB_LANG_TBL);
        return $frm;
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
        $frm->addHiddenField('', 'parent_id', '');
        $frm->addHiddenField(Label::getLabel('LBL_TYPE'), 'cate_type', '');
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        return $frm;
    }

    /**
     * Get Media Form
     *
     * @return Form
     */
    private function getMediaForm(int $categoryId = 0): Form
    {
        $frm = new Form('frmCategoryMedia', ['id' => 'imageFrm']);
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'category_id', $categoryId);
        $fld = $frm->addButton(
            Label::getLabel('LBL_IMAGE'),
            'category_image',
            Label::getLabel('LBL_Upload_Image'),
            ['class' => 'categoryFile-Js', 'id' => 'post_image', 'data-file_type' => Afile::TYPE_CATEGORY_IMAGE, 'data-frm' => 'frmCategoryMedia']
        );
        return $frm;
    }

    /**
     * Update status
     *
     * @param int $cateId
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $cateId, int $status)
    {
        $this->validate($cateId);

        $cateId = FatUtility::int($cateId);
        $status = FatUtility::int($status);
        $status = ($status == AppConstant::YES) ? AppConstant::NO : AppConstant::YES;

        if ($cateId < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $category = new Category($cateId);
        $category->setFldValue('cate_status', $status);
        if (!$category->updateStatus()) {
            FatUtility::dieJsonError($category->getError());
        }

        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Update Sort Order
     *
     * @param int $onDrag
     * @return json
     */
    public function updateOrder(int $onDrag = 1, int $type = Category::TYPE_COURSE)
    {
        $this->canEdit($type);

        $post = FatApp::getPostedData();
        if (!empty($post)) {
            $cateObj = new Category();
            if (!$cateObj->updateOrder($post['categoriesList'])) {
                FatUtility::dieJsonError($cateObj->getError());
            }
            if ($onDrag == 0) {
                FatUtility::dieJsonSuccess('');
            } else {
                FatUtility::dieJsonSuccess(Label::getLabel('LBL_Order_Updated_Successfully'));
            }
        }
    }

    public function getBreadcrumbNodes($action)
    {
        $nodes = [];
        $parameters = FatApp::getParameters();
        if (isset($parameters[0]) && $parameters[0] > 0) {
            $row = Category::getNames([$parameters[0]], $this->siteLangId);
            $nodes = [
                [
                    'title' => Label::getLabel('LBL_ROOT_CATEGORIES'),
                    'href' => MyUtility::generateUrl('categories', $action)
                ],
                [
                    'title' => $row[$parameters[0]],
                ]
            ];
        } else {
            $nodes = [['title' => Label::getLabel('LBL_ROOT_CATEGORIES')]];
        }
        return $nodes;
    }

    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['cate_updated'] = MyDate::formatDate($row['cate_updated']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }

    /**
     * Check can view permissions
     *
     * @param int $type
     * @return boolean
     */
    private function canView(int $type)
    {
        if ($type == Category::TYPE_COURSE) {
            if (!Course::isEnabled()) {
                if (FatUtility::isAjaxCall()) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_IS_NOT_AVAILABLE'));
                }
                FatUtility::exitWithErrorCode(404);
            }
            $this->objPrivilege->canViewCourseCategories();
            return true;
        }
        $this->objPrivilege->canViewQuizCategories();
    }

    /**
     * Check can edit permissions
     *
     * @param int $type
     * @param bool $returnResponse
     * @return boolean
     */
    private function canEdit(int $type, $returnResponse = false)
    {
        if ($type == Category::TYPE_COURSE) {
            if (!Course::isEnabled()) {
                if (FatUtility::isAjaxCall()) {
                    FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_IS_NOT_AVAILABLE'));
                }
                FatUtility::exitWithErrorCode(404);
            }
            return $this->objPrivilege->canEditCourseCategories($returnResponse);
        }
        return $this->objPrivilege->canEditQuizCategories($returnResponse);
    }
}
