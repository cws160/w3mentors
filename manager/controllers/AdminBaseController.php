<?php

class AdminBaseController extends AdminController
{

    /**
     * Initialize Admin Base
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (empty($this->siteAdminId)) {
            if (FatUtility::isAjaxCall()) {
                http_response_code(401);
                FatUtility::dieJsonError(Label::getLabel('LBL_YOUR_SESSION_SEEMS_TO_BE_EXPIRED'));
            }
            FatApp::redirectUser(MyUtility::makeUrl('AdminGuest', 'loginForm'));
        }
        $this->set("bodyClass", '');
    }

    public function getBreadcrumbNodes(string $action)
    {
        $nodes = [];
        $className = get_class($this);
        $arr = explode('-', FatUtility::camel2dashed($className));
        array_pop($arr);
        $urlController = implode('-', $arr);
        $className = strtoupper(implode('_', $arr));
        $className = Label::getLabel('LBL_'.$className);
        if ($action == 'index') {
            $nodes[] = ['title' => $className];
        } else {
            $arr = explode('-', FatUtility::camel2dashed($action));
            $action = ucwords(implode('_', $arr));
            $action = Label::getLabel('LBL_'.$action);
            $nodes[] = ['title' => $className, 'href' => MyUtility::makeUrl($urlController)];
            $nodes[] = ['title' => $action];
        }
        return $nodes;
    }

    public function translateAndAutoFill()
    {
        $tabelName = FatApp::getPostedData('tableName', FatUtility::VAR_STRING, '');
        $recordId = FatApp::getPostedData('recordId', FatUtility::VAR_STRING, 0);
        $toLangId = FatApp::getPostedData('toLangId', FatUtility::VAR_INT, 0);
        if (empty($tabelName) || empty($recordId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $toLangId = empty($toLangId) ? null : [$toLangId];
        $translator = new Translator(FatApp::getConfig('CONF_DEFAULT_LANG'), $toLangId);
        if (!$translator->translateAndAutoFill($tabelName, $recordId, FatApp::getPostedData())) {
            FatUtility::dieJsonError($translator->getError());
        }
        FatUtility::dieJsonSuccess([
            'msg' => Label::getLabel('LBL_LANGUAGE_CONTENT_UPDATE'),
            'fields' => $translator->getTranslatedFields(),
            'table' => $tabelName
        ]);
    }

    public function export()
    {
        $exportClass = 'Export' . str_replace("Controller", "", $this->_controllerName);
        $export = new $exportClass($this->siteLangId);
        $export->setSearchObject($this->search());
        if (!$export->setup()) {
            FatUtility::dieJsonError($export->getError());
        }
        if (!$export->start()) {
            FatUtility::dieJsonError($export->getError());
        }
        $rows = $export->create();
        if ($rows === false) {
            FatUtility::dieJsonError($export->getError());
        }
        if (!$export->complete($rows)) {
            FatUtility::dieJsonError($export->getError());
        }
        FatUtility::dieJsonSuccess([
            'exportId' => $export->getMainTableRecordId(),
            'msg' => Label::getLabel('LBL_Export_Completed_Successfully')
        ]);
    }

}
