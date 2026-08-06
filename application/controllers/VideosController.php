<?php

/**
 * Video Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class VideosController extends MyAppController
{

    /**
     * Initialize Video
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function index()
    {
        $this->_template->render();
    }

    /**
     * Search
     */
    public function search()
    {
        $json = [];
        $post = FatApp::getPostedData();
        $page = FatApp::getPostedData('page', FatUtility::VAR_INT, 1);
        $json['status'] = true;
        $json['msg'] = '';
        $videoObj = new VideoContent();
        $srch = $videoObj->getList($this->siteLangId);
        $srch->addOrder('biblecontent_order', 'ASC');
        $pageSize = AppConstant::PAGESIZE;
        $srch->setPageSize($pageSize);
        $srch->setPageNumber($page);
        $videoList = FatApp::getDb()->fetchAll($srch->getResultSet());
        $totalRecords = $srch->recordCount();
        $pagingArr = [
            'page' => $page,
            'pageSize' => $pageSize,
            'recordCount' => $totalRecords,
            'pageCount' => $srch->pages(),
        ];
        $this->set('bibles', $videoList);
        $post['page'] = $page;
        $this->set('postedData', $post);
        $this->set('pagingArr', $pagingArr);
        $json['html'] = $this->_template->render(false, false, 'videos/search.php', true, false);
        $startRecord = ($page - 1) * $pageSize + 1;
        $endRecord = $page * $pageSize;
        if ($totalRecords < $endRecord) {
            $endRecord = $totalRecords;
        }
        if($totalRecords == 0){
            $startRecord = 0;
        }
        $json['startRecord'] = $startRecord;
        $json['endRecord'] = $endRecord;
        $json['totalRecords'] = $totalRecords;
        FatUtility::dieJsonSuccess($json);
    }

}
