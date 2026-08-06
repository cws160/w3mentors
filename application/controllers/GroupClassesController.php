<?php

/**
 * Group Classes Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GroupClassesController extends MyAppController
{

    /**
     * Initialize Group Classes
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (!GroupClass::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
    }

    /**
     * Render Group Classes|Packages
     */
    public function index(string $slug = '')
    {
        $postedData = FatApp::getPostedData();
        if (!empty($slug)) {
            $teachLangs = TeachLanguage::getTeachLanguages($this->siteLangId);
            $teachlangArr = array_column($teachLangs, 'tlang_slug', 'tlang_id');
            $postedData['teachs'] = [array_search($slug, $teachlangArr)];
        }
        $searchSession = $_SESSION[AppConstant::SEARCH_SESSION] ?? [];
        $frm = GroupClassSearch::getSearchForm($this->siteLangId);

        $userAddress = UserAddresses::getDefault($this->siteUserId, $this->siteLangId);
        if ($userAddress) {
            $userAddress['formatted_address'] = UserAddresses::format($userAddress);
        }
        $this->set('userAddress', $userAddress);

        $frm->fill($postedData + $searchSession);
        unset($_SESSION[AppConstant::SEARCH_SESSION]);
        $this->set('srchFrm', $frm);
        $this->_template->render();
    }

    /**
     * Search Group Classes|Packages
     */
    public function search()
    {
        $langId = $this->siteLangId;
        $userId = $this->siteUserId;
        $userType = $this->siteUserType;
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = GroupClassSearch::getSearchForm($this->siteLangId);
        if (!$post = $frm->getFormDataFromArray($posts, ['teachs'])) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $srch = new GroupClassSearch($langId, $userId, $userType);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('grpcls_start_datetime');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $rows = $srch->fetchAndFormat();
        $this->sets([
            'classes' => $rows, 'post' => $post,
            'recordCount' => $srch->recordCount(),
            'bookingBefore' => FatApp::getConfig('CONF_CLASS_BOOKING_GAP')
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Group Classes|Packages Detail
     * 
     * @param string $slug
     */
    public function view(string $slug)
    {
        $langId = $this->siteLangId;
        $userId = $this->siteUserId;
        $userType = $this->siteUserType;
        $srch = new GroupClassSearch($langId, $userId, $userType);
        $srch->addCondition('grpcls.grpcls_slug', '=', $slug);
        $srch->applyOrderBy('grpcls_start_datetime');
        $srch->applyPrimaryConditions();
        $srch->addSearchDetailFields();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        $rows = $srch->fetchAndFormat();
        if (count($rows) < 1) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $class = current($rows);
        if($class['grpcls_remaining_unix'] < 0) {
            if (API_CALL) {
                MyUtility::dieJsonError(Label::getLabel('LBL_CLASS_EXPIRED'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $srch = new GroupClassSearch($langId, $userId, $userType);
        $this->sets([
            'class' => $class,
            'moreClasses' => $srch->getMoreClasses($class['grpcls_teacher_id'], $class['grpcls_id']),
            'pkgclses' => PackageSearch::getClasses($class['grpcls_id'], $langId),
            'bookingBefore' => FatApp::getConfig('CONF_CLASS_BOOKING_GAP'),
        ]);
        $this->_template->render();
    }

    public function viewAddress()
    {
        $id = FatApp::getPostedData('id', FatUtility::VAR_INT, 0);
        if ($id < 1) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $userAddr = new UserAddresses(0, $id);
        $address = $userAddr->getAddressById($this->siteLangId);
        if (empty($address)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_ADDRESS_NOT_FOUND'));
        }
        $this->set('address', $address);
        $this->_template->render(false, false);
    }
}
