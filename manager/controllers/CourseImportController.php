<?php

class CourseImportController extends AdminBaseController
{
    const IMPORT_ORDER_DATE = '2023-02-01 23:59:00';
    const IMPORT_ORDER_START_INTERVAL = '-4 months';
    const IMPORT_ORDER_END_INTERVAL = '+6 months';

    public function index()
    {
        $this->_template->render();
    }

    public function courseImportForm()
    {
        $courseFrm = $this->courseForm();
        $reviewFrm = $this->reviewForm();
        $this->set('frm', $courseFrm);
        $this->set('reviewFrm', $reviewFrm);
        $this->_template->render(false,false);
    }

    private function courseForm()
    {
        $frm = new Form('courseImportForm', ['id' => 'courseImportForm']);
        $frm = CommonHelper::setFormProperties($frm);
        $fldImg = $frm->addFileUpload(Label::getLabel('LBL_File_to_be_uploaded:'), 'import_file', ['id' => 'import_file']);
        $fldImg->setFieldTagAttribute('onChange', '$(\'#importFileName\').html(this.value)');
        $fldImg->htmlBeforeField = '<div class="filefield"><span class="filename" id="importFileName"></span>';
        $fldImg->htmlAfterField = '<label class="filelabel">' . Label::getLabel('LBL_Browse_File') . '</label></div><br/><small>' . nl2br(Label::getLabel('LBL_Import_Labels_Instructions')) . '</small>';
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_IMPORT'));
        return $frm;
    }

    public function reviewForm()
    {
        $frm = new Form('reviewImportForm', ['id' => 'reviewImportForm']);
        $frm = CommonHelper::setFormProperties($frm);
        $fldImg = $frm->addFileUpload(Label::getLabel('LBL_File_to_be_uploaded:'), 'import_review_file', ['id' => 'import_review_file']);
        $fldImg->setFieldTagAttribute('onChange', '$(\'#reviewImportFile\').html(this.value)');
        $fldImg->htmlBeforeField = '<div class="filefield"><span class="filename" id="reviewImportFile"></span>';
        $fldImg->htmlAfterField = '<label class="filelabel">' . Label::getLabel('LBL_Browse_File') . '</label></div><br/><small>' . nl2br(Label::getLabel('LBL_Import_Labels_Instructions')) . '</small>';
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_IMPORT'));
        return $frm;
    }

    public function submitImportUploadedCourse() 
    {
        //echo "<pre>";print_r($this->setupLectureResources());exit;
        set_time_limit(0);
        $this->objPrivilege->canEditLanguageLabel();
        if (!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Please_Select_A_CSV_File'));
        }
        if (!in_array($_FILES['import_file']['type'], CommonHelper::isCsvValidMimes())) {
            FatUtility::dieJsonError(Label::getLabel("LBL_Not_a_Valid_CSV_File"));
        }
        $csvFilePointer = fopen($_FILES['import_file']['tmp_name'], 'r');
        $firstLine = fgetcsv($csvFilePointer);
        if (empty($firstLine)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NOT_A_VALID_CSV_FILE'));
        }
        $header = array_flip($firstLine);

        $db = FatApp::getDb();
        $langId = 1;
        $teachers = $this->getTeachers($langId);
        if (empty($teachers)) {
            print_r('============ No teacher found ===========================');
            return true;
        }
        $speakLangNames = SpeakLanguage::getAllLangs($langId, true);
        $teacherIds = array_column($teachers, 'teacher_id');
        $speakLangNames = array_flip($speakLangNames);
        $categories = $this->getCategories(0, $langId);
        $subcategories = $this->getCategories(1, $langId);
        $courseLevels = Course::getCourseLevels();
        unset($courseLevels[1]);
        $currency = MyUtility::getSystemCurrency();
       
        $i=0;
        while (($line = fgetcsv($csvFilePointer)) !== FALSE) {
            if ($line[0] != '') {
                $coursePrice = range(500, 999);
                $coursePrice = $coursePrice[array_rand($coursePrice, 1)];
                $tags = explode(',', $line[$header['tags']]);
                $tags = json_encode($tags);
                $teacher = $teachers[$teacherIds[$i]];
                $teacherName = $teacher['user_first_name'] . ' ' . $teacher['user_last_name'];
                $courseTitle = $this->processStringData(str_replace(['{teacher}'], [$teacherName], $line[$header['course_title']]));
                $courseSubTitle = $this->processStringData(str_replace(['{teacher}'], [$teacherName], $line[$header['course_subtitle']]));
                $data = [
                    'course_title' => $courseTitle,
                    'course_subtitle' => $this->processStringData($this->truncateString($courseSubTitle, 90)),
                    'course_details' => $this->processStringData(str_replace(['{teacher}'], [$teacherName], $line[$header['description']])),
                    'course_cate_id' => $categories[trim($line[$header['category']])],
                    'course_subcate_id' => $this->processStringData($subcategories[trim($line[$header['subcategory']])]),
                    'course_level' => array_rand($courseLevels, 1),
                    'course_clang_id' => $speakLangNames['English'],
                    'course_srchtags' => $this->processStringData($tags),
                    'course_type' => Course::TYPE_PAID,
                    'course_price' => $coursePrice,
                    'course_currency_id' => $currency['currency_id'],
                    'course_certificate' => AppConstant::YES,
                    'course_preview_video' => $this->getPreviewVideoUrl(),
                    'course_slug' =>  CommonHelper::seoUrl($courseTitle),
                    'course_status' => Course::PUBLISHED,
                    'course_active' => AppConstant::ACTIVE,
                    'course_created' => date('Y-m-d H:i:s'),
                    'course_updated' => date('Y-m-d H:i:s'),
                    'course_user_id' => $teacherIds[$i],
                    'intended1' => explode("\n", $line[$header['intended1']]),
                    'intended2' =>  explode("\n", $line[$header['intended2']]),
                    'intended3' => explode("\n", $line[$header['intended3']]),
                    'course_curriculum' => $line[$header['curriculum']],
                ];
                if(!$this->setupCourse($data)) {
                    FatUtility::dieJsonError($db->getError());
                }
                if($i < (count($teachers) - 1)) {
                    $i++;
                    continue;
                }
                $i = 0;    
            }
        }
        $this->createDiscountCoupons()  ;
        $this->createCourseOrders();
        $this->addFavorites();
        //$this->addPreviewImages();
        //$this->setupLectureResources();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_COURSES_IMPORTED_SUCCESSFULLY'));
    }

    public function submitImportUploadedReviews()
    {
        set_time_limit(0);
        $this->objPrivilege->canEditLanguageLabel();
        if (!is_uploaded_file($_FILES['import_review_file']['tmp_name'])) {
            FatUtility::dieJsonError(Label::getLabel('LBL_Please_Select_A_CSV_File'));
        }
        if (!in_array($_FILES['import_review_file']['type'], CommonHelper::isCsvValidMimes())) {
            FatUtility::dieJsonError(Label::getLabel("LBL_Not_a_Valid_CSV_File"));
        }
        $csvFilePointer = fopen($_FILES['import_review_file']['tmp_name'], 'r');
        $firstLine = fgetcsv($csvFilePointer);
        if (empty($firstLine)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_NOT_A_VALID_CSV_FILE'));
        }
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->joinTable(CourseProgress::DB_TBL, 'INNER JOIN','ordcrs.ordcrs_id = crspro.crspro_ordcrs_id', 'crspro');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ord.order_id = ordcrs.ordcrs_order_id', 'ord');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'course.course_id = ordcrs.ordcrs_course_id', 'course');
        $srch->joinTable(Course::DB_TBL_LANG, 'INNER JOIN', 'course.course_id = crslang.course_id', 'crslang');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = course.course_user_id', 'teacher');
        $srch->joinTable(Issue::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_id = ordcrs.ordcrs_id and repiss.repiss_record_type = ' . AppConstant::COURSE, 'repiss');
        $srch->addMultipleFields([
            'course_user_id as teacher_id',
            'ordcrs_id', 
            'order_user_id', 
            'user_first_name as teacher_first_name', 
            'user_last_name as teacher_last_name',
            'crspro_id',
            'course.course_id',
            'crspro_completed',
            'crslang.course_title',
            
        ]);
        $srch->addDirectCondition('repiss.repiss_id IS NULL');
        $srch->addCondition('crspro_status', '=', CourseProgress::COMPLETED);
        $srch->addCondition('ordcrs_status', '=', OrderCourse::COMPLETED);
        $srch->addCondition('ordcrs_reviewed', '=', 0);
        $srch->doNotCalculateRecords();
        $courses = FatApp::getDb()->fetchAll($srch->getResultSet(), 'ordcrs_id');
        $courseIds = array_column($courses, 'ordcrs_id');
        $header = array_flip($firstLine);
        $i = 0;
        $platform_name = FatApp::getConfig("CONF_WEBSITE_NAME_" . MyUtility::getSiteLangId(), FatUtility::VAR_STRING);
        while (($line = fgetcsv($csvFilePointer)) !== false) {
            if ($line[0] != '') {
                if(count($courseIds) < 1) {
                    return true;
                }
                $courseToReview = count($courseIds) > 1 ? array_rand($courseIds, mt_rand(2, 3)) : [array_rand($courseIds, 1)];
                foreach($courseToReview as $crsrev) {
                    //if ($i % 2 == 0) {
                        if ($i % 2 == 0) {
                            $ratingStatus = RatingReview::STATUS_APPROVED;
                        } else {
                            $ratingStatus = ($i % 3 == 0) ? RatingReview::STATUS_DECLINED : RatingReview::STATUS_PENDING;
                        }
                        $course = $courses[$courseIds[$crsrev]];
                        $ratingTitle = $line[$header['title']];
                        $ratingDesc = $line[$header['description']];
                        $name = $course['teacher_first_name'] . ' ' . $course['teacher_last_name'];
                        $title = str_replace(['{teacher_name}', '{platform_name}'], [$name, $platform_name], $ratingTitle);
                        $desc = str_replace(['{teacher_name}', '{platform_name}'], [$name, $platform_name], $ratingDesc);
                        $endTime = strtotime($course['crspro_completed'] . ' +' . rand(0, 10) . ' minutes');
                        $ratingArray = [
                            'ratrev_type' => AppConstant::COURSE,
                            'ratrev_type_id' => $course['ordcrs_id'],
                            'ratrev_user_id' => $course['order_user_id'],
                            'ratrev_teacher_id' => $course['teacher_id'],
                            'ratrev_lang_id' => 1,
                            'ratrev_overall' => rand(4, 5),
                            'ratrev_title' => $title,
                            'ratrev_detail' => $desc,
                            'ratrev_created' => date('Y-m-d H:i:s', $endTime),
                            'ratrev_status' => $ratingStatus,
                        ];
                        $ratingRec = new TableRecord(RatingReview::DB_TBL);
                        $ratingRec->assignValues($ratingArray);
                        if (!$ratingRec->addNew()) {
                            return false;
                        }
                        if ($ratingStatus == RatingReview::STATUS_APPROVED) {
                            (new TeacherStat($course['teacher_id']))->setRatingReviewCount();
                        }
                        $record = new TableRecord(OrderCourse::DB_TBL);
                        $record->assignValues(['ordcrs_reviewed' => $ratingStatus]);
                        if (!$record->update(['smt' => 'ordcrs_id = ?', 'vals' => [$course['ordcrs_id']]])) {
                          return false;
                        }
                   // }
                    unset($courseIds[$crsrev]);
                    $i++;
                }
                
            }
        }
        $this->setRatingReviewCount();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_REVIEWS_IMPORTED_SUCCESSFULLY'));
    }

    private function addFavorites()
    {
        $srch = new SearchBase(User::DB_TBL, 'users');
        $srch->joinTable(Order::DB_TBL, 'LEFT JOIN', 'users.user_id = orders.order_user_id and orders.order_type = ' . Order::TYPE_COURSE, 'orders');
        $srch->addFld('DISTINCT user_id');
        $srch->addDirectCondition('user_is_teacher = 0');
        $srch->addDirectCondition('user_verified IS NOT NULL');
        $srch->addCondition('user_active', '=', AppConstant::ACTIVE);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $resultSet = FatApp::getDb()->query($srch->getQuery().' ORDER BY RAND() LIMIT 10');
        $users = FatApp::getDb()->fetchAll($resultSet);
        $courses = $this->getCourses();
        $courseIds =  array_keys($courses);
        if(!empty($courses)) {
            foreach($users as $user) {
                $limit = mt_rand(2, 3);
                $userCourses = array_rand($courseIds, $limit);
                foreach($userCourses as $courseKey) {
                    $data = ['ufc_user_id' => $user['user_id'], 'ufc_course_id' => $courseIds[$courseKey]];
                    if (!FatApp::getDb()->insertFromArray(User::DB_TBL_COURSE_FAVORITE, $data)) {
                        //FatUtility::dieJsonError(FatApp::getDb()->getError());
                        continue;
                    }
                }
                
            }
        }
        return true;
    }


    public function setupLectureResources()
    {
        $files = scandir(CONF_UPLOADS_PATH . 'compressed/lecture-resources');
        unset($files[array_search('.', $files)]);
        unset($files[array_search('..', $files)]);
        $files = array_values($files);
        if (empty($files)) {
            return true;
        }
        $srch = new SearchBase(Course::DB_TBL, 'course');
        $srch->joinTable(Lecture::DB_TBL, 'INNER JOIN', 'lecture.lecture_course_id = course.course_id', 'lecture');
        $srch->addMultipleFields(['course.course_id', 'GROUP_CONCAT(lecture.lecture_id SEPARATOR ",") as lecture_ids', 'course_user_id']);
        $srch->addGroupBy('course.course_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $courses = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($courses as $course) {
            $resourceIds = [];
            foreach ($files as $file) {
                $from = CONF_UPLOADS_PATH . 'compressed/lecture-resources/' . $file;
                $fileName = preg_replace('/[^a-zA-Z0-9.]/', '', $file);
                $filePath = '2023/04/';
                if (!file_exists(CONF_UPLOADS_PATH . $filePath)) {
                    mkdir(CONF_UPLOADS_PATH . $filePath, 0777, true);
                }
                while (file_exists(CONF_UPLOADS_PATH . $filePath . $fileName)) {
                    $fileName = time() . '-' . $fileName;
                }
                $filePath = $filePath . $fileName;
                copy($from, CONF_UPLOADS_PATH . $filePath);

                $lbl = Label::getLabel('LBL_MB');
                $resource = new Resource();
                $resource->assignValues([
                    'resrc_user_id' => $course['course_user_id'],
                    'resrc_type' => pathinfo($file, PATHINFO_EXTENSION),
                    'resrc_size' => MyUtility::convertBitesToMb(filesize($from)) . ' ' . $lbl,
                    'resrc_name' => $file,
                    'resrc_path' => $filePath,
                    'resrc_created' => date('Y-m-d H:i:s')
                ]);

                if (!$resource->save()) {
                    FatUtility::dieJsonError($resource->getError());
                    return false;
                }
                $resourceIds[] = $resource->getMainTableRecordId();
            }
            $lectureIds = explode(',', $course['lecture_ids']);
            $selLectures = array_rand($lectureIds, FatUtility::int(ceil(count($lectureIds) / 4)));
            foreach($selLectures as $lecIn) {
                $lectureId = $lectureIds[$lecIn]; 
                $noOfResources = rand(2, count($files));
                $resourcesList = array_rand($resourceIds, $noOfResources);
                foreach ($resourcesList as $resrc) {
                    $obj = new TableRecord(Lecture::DB_TBL_LECTURE_RESOURCE);
                    $obj->assignValues([
                        'lecsrc_type' => Lecture::TYPE_RESOURCE_LIBRARY,
                        'lecsrc_resrc_id' => $resrc,
                        'lecsrc_lecture_id' => $lectureId,
                        'lecsrc_course_id' => $course['course_id'],
                        'lecsrc_created' => date('Y-m-d H:i:s')
                    ]);
                    if (!$obj->addNew()) {
                        FatUtility::dieJsonError($obj->getError());
                        return false;
                    }
                }
            } 
        }
        return true;

    }



    private function setupCourse(array $data)
    {
        $db = FatApp::getDb();
        $rand = mt_rand(0,9);
        if(in_array($rand, [3, 6])) {
            $data['course_status'] = Course::DRAFTED;
        } elseif(in_array($rand, [5, 9])) {
            $data['course_status'] = Course::SUBMITTED;
        }   

        $course = new Course(0, 0, User::TEACHER, $this->siteLangId);
        $course->assignValues($data);
        if (!$course->save()) {
           return false;
        }
        $data['course_id'] = $course->getMainTableRecordId();
        $langData = [
            'course_id' => $data['course_id'],
            'course_title' => $data['course_title'],
            'course_subtitle' => $data['course_subtitle'],
            'course_details' => $data['course_details'],
            'course_srchtags' => $data['course_srchtags'],
        ];
        if (!$db->insertFromArray(Course::DB_TBL_LANG, $langData, false, [], $langData)) {
            return false;
        }
        $data['intendedLearners'] = $this->getIntendedLearnersData($data);
        if (!$this->setupCurriculum($data['course_id'], $data['course_curriculum'])) {
            return false;
        }
        if (!$this->setupApprovalRequest($data)) {       
            return false;
        }
        if (!$this->setupCategoriesCount()) {
            return false;
        }
        if (!$this->setupIntendedLearners($data)) {
            return false;
        }
        return true;
    }

    private function setupApprovalRequest($data)
    {
        $coapre_status =  Course::REQUEST_APPROVED;
        if ($data['course_status'] == Course::DRAFTED) {
            if(mt_rand(1, 9) % 2 == 0) {
                return true;
            }
            $coapre_status = Course::REQUEST_DECLINED;
        }
        if($data['course_status'] == Course::SUBMITTED) {
            $coapre_status = Course::REQUEST_PENDING;
        }

        $db = FatApp::getDb();
        $data = [
            'coapre_course_id' => $data['course_id'],
            'coapre_cate_id' => $data['course_cate_id'],
            'coapre_subcate_id' => $data['course_subcate_id'],
            'coapre_clang_id' => $data['course_clang_id'],
            'coapre_level' => $data['course_level'],
            'coapre_certificate' => $data['course_certificate'],
            'coapre_status' => $coapre_status,
            'coapre_created' => date('Y-m-d H:i:s'),
            'coapre_title' => $data['course_title'],
            'coapre_subtitle' => $data['course_subtitle'],
            'coapre_details' => $data['course_details'],
            'coapre_price' => $data['course_price'],
            'coapre_srchtags' => $data['course_srchtags'],
            'coapre_learners' => json_encode($data['intendedLearners'][IntendedLearner::TYPE_LEARNERS]),
            'coapre_learnings' => json_encode($data['intendedLearners'][IntendedLearner::TYPE_LEARNING]),
            'coapre_requirements' => json_encode($data['intendedLearners'][IntendedLearner::TYPE_REQUIREMENTS]),
            'coapre_preview_video' => $data['course_preview_video'],
        ];
        if (!$db->insertFromArray(Course::DB_TBL_APPROVAL_REQUEST, $data, false, [], $data)) {
            return false;
        }
        return true;
    }

    private function setupCategoriesCount()
    {
        /* parent categories count */
        $query = "UPDATE tbl_categories INNER JOIN(
                SELECT COUNT(course_cate_id) AS totalCount,
                    course_cate_id
                FROM
                    `tbl_courses`
                GROUP BY
                    course_cate_id
            ) AS course
            ON
                cate_id = course.course_cate_id
            SET
                cate_records = course.totalCount";
        $db = FatApp::getDb();
        if (!$db->query($query)) {
            FatUtility::dieJsonError($db->getError());
            return false;
        }
        /* sub categories count */
        $query = "UPDATE tbl_categories INNER JOIN(
                SELECT COUNT(course_subcate_id) AS totalCount,
                    course_subcate_id
                FROM
                    `tbl_courses`
                GROUP BY
                    course_subcate_id
            ) AS course
            ON
                cate_id = course.course_subcate_id
            SET
                cate_records = course.totalCount";
        if (!$db->query($query)) {
            FatUtility::dieJsonError($db->getError());
            return false;
        }
        return true;
    }

    private function createCourseOrders()
    {
        $users = $this->getUsers();
        $userIds = array_keys($users);
        $courses = $this->getCourses();
        $courseIds = array_keys($courses);
        $lectures = $this->getCourseLectures($courseIds);
    
        $currency = MyUtility::getSystemCurrency();
        $paymentMethods = PaymentMethod::getPayins();
        $coupons = $this->getCoupons();
        $commissions = $this->getCommission($userIds);

        $avgOrderPerCourse = 8;
        $totalCouponsUsage = FatUtility::int((count($courseIds) * $avgOrderPerCourse) / 0.10);
        $totalCancelledCourses = FatUtility::int((count($courseIds) * $avgOrderPerCourse) / 0.10);
        $a = 0;
        $cancelCount = 0;
        $addCoupon = mt_rand(0,1);
   
        foreach ($courses as $course) {
            $ordersPerCourse = rand(6, min(count($userIds), 9));
            $courseUserIds = $this->getUserCoursesIds($userIds, $ordersPerCourse);
            for ($i = 1; $i <= $ordersPerCourse; $i++) {
                $a++;
                $discount = 0;
                $randCourseUserId = array_rand($courseUserIds, 1);
                $user = $users[$courseUserIds[$randCourseUserId]];
                unset($courseUserIds[$randCourseUserId]);
                $verifyTime = strtotime($user['user_verified']);
                $verifyTime = max($verifyTime, strtotime(self::IMPORT_ORDER_START_INTERVAL));
                $orderDate = mt_rand($verifyTime, strtotime(self::IMPORT_ORDER_DATE));
                $comm = (!empty($commissions[$course['course_teacher_id']])) ? $commissions[$course['course_teacher_id']] : $commissions[null];

                if ($a % 10 == 0 && $totalCouponsUsage > 0 && $addCoupon) {
                    $discount = $this->calculateDiscount($coupons, $course['course_price'], $orderDate);
                }
                if ($discount > 0) {
                    $totalCouponsUsage--; 
                }

                $coursePrice = $course['course_price'];
                $orderTotal = $coursePrice - $discount;
                $orderStatus = Order::STATUS_COMPLETED;
                $orderPaymentStatus = AppConstant::ISPAID;
                if ($a % 7 == 0 ) {
                    if ($a % 2 != 0 && $cancelCount < $totalCancelledCourses) {
                        $cancelCount++;
                        $orderStatus = Order::STATUS_CANCELLED;
                    } else {
                        $orderStatus = Order::STATUS_INPROCESS;
                        $orderPaymentStatus = AppConstant::UNPAID;
                    }
                }

                $orderData = [
                    'order_type' => Order::TYPE_COURSE,
                    'order_user_id' => $user['user_id'],
                    'order_addedon' => date('Y-m-d H:i:s', $orderDate),
                    'order_total_amount' => $orderTotal,
                    'order_net_amount' => $coursePrice,
                    'order_item_count' => 1,
                    'order_payment_status' => $orderPaymentStatus,
                    'order_discount_value' => $discount,
                    'order_pmethod_id' => array_rand($paymentMethods, 1),
                    'order_currency_code' => $currency['currency_code'],
                    'order_currency_value' => $currency['currency_value'],
                    'order_status' => $orderStatus,
                ];    

                $order = new Order();
                $order->assignValues($orderData);
                if (!$order->save()) {
                    FatUtility::dieJsonError($order->getError());
                }
                $orderId = $order->getMainTableRecordId();

                $orderCourseData = [
                    'ordcrs_order_id' => $orderId,
                    'ordcrs_course_id' => $course['course_id'],
                    'ordcrs_commission' => $comm['comm_courses'] ?? 0,
                    'ordcrs_amount' => $coursePrice,
                    'ordcrs_discount' => $discount, 
                    'ordcrs_updated' => date('Y-m-d H:i:s', $orderDate),
                ];

                if ($orderStatus == Order::STATUS_CANCELLED) {
                    $orderCourseData['ordcrs_refund'] = $orderTotal;
                    $orderCourseData['ordcrs_status'] = OrderCourse::CANCELLED;
                } elseif($orderStatus == Order::STATUS_COMPLETED) {
                    $orderCourseData['ordcrs_earnings'] = ($orderTotal * $orderCourseData['ordcrs_commission']) / 100;
                    $orderCourseData['ordcrs_teacher_paid'] = $orderTotal - $orderCourseData['ordcrs_earnings'];
                    $orderCourseData['ordcrs_payment'] = AppConstant::ISPAID;
                    $orderCourseData['ordcrs_status'] = array_rand([OrderCourse::PENDING, OrderCourse::IN_PROGRESS, OrderCourse::COMPLETED], 1);
                } else {
                    $orderCourseData['ordcrs_status'] = OrderCourse::PENDING;
                }

                $orderCourse = new OrderCourse();
                $orderCourse->assignValues($orderCourseData);
                if (!$orderCourse->save()) {
                    FatUtility::dieJsonError($orderCourse->getError());
                }
                $orderCourseId =$orderCourse->getMainTableRecordId();
                $courseLectures = explode(',', $lectures[$course['course_id']]['lec_ids']);
                $orderProgress = [
                    'crspro_ordcrs_id'      => $orderCourseId,
                    'crspro_status'         => CourseProgress::PENDING,
                ];

                if (in_array($orderCourseData['ordcrs_status'], [OrderCourse::IN_PROGRESS, OrderCourse::COMPLETED])) {
                    $orderProgress['crspro_status'] = $orderCourseData['ordcrs_status'];
                    $orderProgress['crspro_started'] = date('Y-m-d H:i:s', strtotime('+1 hour', $orderDate));
                    if($orderCourseData['ordcrs_status'] == OrderCourse::COMPLETED) {
                        $orderProgress['crspro_covered'] = json_encode($courseLectures);
                        $orderProgress['crspro_lecture_id'] = end($courseLectures);
                        $orderProgress['crspro_progress'] = 100;
                        $orderProgress['crspro_completed'] =  date('Y-m-d H:i:s', strtotime('+2 days', $orderDate));  
                    } else {
                        $lecIndex = array_rand($courseLectures, 1);
                        if($lecIndex == 0) {
                            $lecIndex = FatUtility::int(count($courseLectures) / 2);
                        }
                        $coveredLectures = array_slice($courseLectures, 0, $lecIndex, true);
                        $currentLecture = array_pop($coveredLectures);
                        $courseProgress = count($coveredLectures) > 0 ? (count($courseLectures) / count($coveredLectures)): 0;
                        $orderProgress['crspro_covered'] = json_encode($coveredLectures);
                        $orderProgress['crspro_lecture_id'] = $currentLecture;
                        $orderProgress['crspro_progress'] = $courseProgress;
                    }                   
                } elseif($orderCourseData['ordcrs_status'] == OrderCourse::CANCELLED) {
                    $orderProgress['crspro_status'] = CourseProgress::CANCELLED;
                }
                $crsPro = new CourseProgress();
                $crsPro->assignValues($orderProgress);
                if (!$crsPro->save()) {
                    FatUtility::dieJsonError($crsPro->getError());
                }

                
                    if ($orderCourseData['ordcrs_status'] == OrderCourse::CANCELLED) {
                        $refundRequestData = [
                            'corere_ordcrs_id' => $orderCourseId,
                            'corere_user_id'   => $user['user_id'],
                            'corere_status'    => Course::REFUND_APPROVED,
                            'corere_remark'    => 'Test Remark',
                            'corere_comment'   => 'Test Comment',
                            'corere_created'   => date('Y-m-d H:i:s', strtotime('+2 hours', $orderDate)),
                            'corere_updated'   => date('Y-m-d H:i:s', strtotime('+3 hours', $orderDate)),   
                        ];
                        
                        $refundReq = new TableRecord(Course::DB_TBL_REFUND_REQUEST);
                        $refundReq->assignValues($refundRequestData);
                        if (!$refundReq->addNew(['HIGH_PRIORITY'])) {
                            FatUtility::dieJsonError($refundReq->getError());
                        }   
                    } else {
                        if (mt_rand(1, 9) == 5) {
                            $refundRequestData = [
                                'corere_ordcrs_id' => $orderCourseId,
                                'corere_user_id'   => $user['user_id'],
                                'corere_status'    => Course::REFUND_PENDING,
                                'corere_remark'    => 'Test Remark',
                                'corere_comment'   => 'Test Comment',
                                'corere_created'   => date('Y-m-d H:i:s', strtotime('+2 hours', $orderDate)),
                                'corere_updated'   => date('Y-m-d H:i:s', strtotime('+3 hours', $orderDate)),   
                            ];
                            
                            $refundReq = new TableRecord(Course::DB_TBL_REFUND_REQUEST);
                            $refundReq->assignValues($refundRequestData);
                            if (!$refundReq->addNew(['HIGH_PRIORITY'])) {
                                FatUtility::dieJsonError($refundReq->getError());
                            }   

                        } elseif (mt_rand(1, 9) == 3) {
                            $refundRequestData = [
                                'corere_ordcrs_id' => $orderCourseId,
                                'corere_user_id'   => $user['user_id'],
                                'corere_status'    => Course::REFUND_DECLINED,
                                'corere_remark'    => 'Test Remark',
                                'corere_comment'   => 'Test Comment',
                                'corere_created'   => date('Y-m-d H:i:s', strtotime('+2 hours', $orderDate)),
                                'corere_updated'   => date('Y-m-d H:i:s', strtotime('+3 hours', $orderDate)),   
                            ];
                            
                            $refundReq = new TableRecord(Course::DB_TBL_REFUND_REQUEST);
                            $refundReq->assignValues($refundRequestData);
                            if (!$refundReq->addNew(['HIGH_PRIORITY'])) {
                                FatUtility::dieJsonError($refundReq->getError());
                            }   
                        }
                    }
            

                        
            }
        }
        return true;    
    }

    public function createDiscountCoupons() 
    {
        $couponsArr = [
            'SUPER' => 'Super Offer', 'WELCOME' => '{amount} Off Welcome Offer', 'FLAT' => 'Flat {amount} Discount', 'DISCOUNT' => 'Get Upto {amount} Off', 'FIRST' => 'Get {amount} discount on First Order'
        ];
        $amountArr = [ 10, 20, 25, 30, 40, 50, 60];
        $maxDiscountArr = [40, 45, 50, 55, 60, 65, 70, 75, 80, 85];
        $discountTypeArr = AppConstant::getPercentageFlatArr();
        $totalCoupons = 100;
        for ($i=0; $i < $totalCoupons; $i++) {
            $amount = $amountArr[array_rand($amountArr, 1)];
            $discountType = array_rand($discountTypeArr, 1);
            $leftSymbol = $rightSymbol = ''; 
            if($discountType == AppConstant::FLAT_VALUE) {
                $leftSymbol = '$';
            } else {
                $rightSymbol = '%';
            }
            $coupon = array_rand($couponsArr, 1);
            $couponIdentifierAmount = $leftSymbol.$amount.$rightSymbol;
            $couponIdentifier = str_replace('{amount}', $couponIdentifierAmount, $couponsArr[$coupon]);
            $couponCode = $coupon.$amount;

            $couponStartDate = mt_rand(strtotime(self::IMPORT_ORDER_START_INTERVAL), strtotime('-1 week'));
            if($i == 7) {
                $couponEndDate = strtotime('-1 day');
            } else {
                $couponEndDate = mt_rand(strtotime('now'), strtotime(self::IMPORT_ORDER_END_INTERVAL));
            }

            $couponStatus = AppConstant::ACTIVE;
            if($i % 4 == 0) {
                $couponStatus = AppConstant::INACTIVE;
            }
            $couponData = [
                'coupon_identifier'     => $couponIdentifier,
                'coupon_code'           => $couponCode,
                'coupon_user_id'        => 0,   
                'coupon_min_order'      => ($amount * mt_rand(2, 3)),
                'coupon_max_discount'   => $maxDiscountArr[array_rand($maxDiscountArr, 1)],
                'coupon_discount_type'  => $discountType,
                'coupon_discount_value' => $amount,
                'coupon_max_uses'       => mt_rand(20, 30),
                'coupon_user_uses'      => 1,
                'coupon_used_uses'      => 0,
                'coupon_start_date'     => date('Y-m-d', $couponStartDate),
                'coupon_end_date'       => date('Y-m-d', $couponEndDate),
                'coupon_active'         => $couponStatus,
                'coupon_created'        => date('Y-m-d H:i:s', strtotime('-1 days', $couponStartDate)),
            ];

            $record = new TableRecord(Coupon::DB_TBL);
            $record->assignValues($couponData);
            if (!$record->addNew([], $couponData)) {
                FatUtility::dieJsonError($record->getError());
                return true;
            }

        }   
        return true;
    }

    

    private function getUserCoursesIds($userIds, $count)
    {       
        $keys = array_rand($userIds, $count);
        $selectedUsers = [];
        foreach($keys as $key) {
            $selectedUsers[] = $userIds[$key];
        }
        return $selectedUsers;
    }

    private function calculateDiscount($coupons, $course_price, $orderDate)
    {
        $discount = 0;
        $coupon = $coupons[array_rand($coupons, 1)];
        if (strtotime($coupon['coupon_start_date']) < $orderDate && strtotime($coupon['coupon_end_date']) > $orderDate) {
            $selectedCoupon = $coupon;
        }
        if (isset($selectedCoupon)) {
            if ($selectedCoupon['coupon_discount_type'] == AppConstant::FLAT_VALUE) {
                $discount = $selectedCoupon['coupon_discount_value'];
            } elseif ($selectedCoupon['coupon_discount_type'] == AppConstant::PERCENTAGE) {
                $discount = $course_price * $selectedCoupon['coupon_discount_value'] / 100;
                if ($discount > $selectedCoupon['coupon_max_discount']) {
                    $discount = $selectedCoupon['coupon_max_discount'];
                }
            }
        }
        return $discount;
    }
    
    private function setupCurriculum($courseId, $curriculumUrl)
    {
        set_time_limit(0);
        if (empty($curriculumUrl)) {
            return true;
        }
        $curriculum = file_get_contents($curriculumUrl);
        $curriculumSections = json_decode($curriculum, 1)['curriculum_context']['data']['sections'];
        foreach ($curriculumSections as $sections) {
            $sectionData = [
                'section_course_id' => $courseId,
                'section_title'     => $this->processStringData($sections['title']),
                'section_details'   => $this->processStringData($sections['title']),
                'section_duration'  => $sections['content_length'],
                'section_order'     => $sections['index'],
            ];
            $section = new Section();
            $section->assignValues($sectionData);
            if (!$section->save()) {
                return false;
            }
            $sectionId = $section->getMainTableRecordId();
            $i = 1;
            foreach ($sections['items'] as $lecture) {
                $lectureData = [
                    'lecture_title'      => $this->processStringData($lecture['title']),
                    'lecture_details'    => $this->processStringData($lecture['title']),
                    'lecture_is_trial'   => $lecture['can_be_previewed'] ? AppConstant::YES: AppConstant::NO,
                    'lecture_duration'   => $this->timeToSeconds($lecture['content_summary']),
                    'lecture_course_id'  => $courseId,
                    'lecture_section_id' => $sectionId,
                    'lecture_order'      => $i++,
                    'lecture_created' => date('Y-m-d H:i:s'),
                ] ;
                $lecture = new Lecture();
                $lecture->assignValues($lectureData);
                if (!$lecture->save()) {
                    return false; 
                }
                $lectureRes = new TableRecord(Lecture::DB_TBL_LECTURE_RESOURCE);
                $lectureRes->assignValues([
                    'lecsrc_type' => Lecture::TYPE_RESOURCE_EXTERNAL_URL,
                    'lecsrc_lecture_id' => $lecture->getMainTableRecordId(),
                    'lecsrc_course_id' => $courseId,
                    'lecsrc_link' => $this->getPreviewVideoUrl(),
                    'lecsrc_created' => date('Y-m-d H:i:s'),
                ]);
                if (!$lectureRes->addNew()) {
                    return false;
                }
            }

            if (!$this->setLectureCount($sectionId)) {
                return false;
            }
        }
        if (!$this->setCourseSectionCount($courseId)) {
            return false;
        }
        if (!$this->setCourseLectureCount($courseId)) {
            return false;
        }
        if (!$this->setDuration($courseId)) {
            return false;
        }
        return true;
    }

    

    private function getIntendedLearnersData($data)
    {
        $requestData = [];
        foreach($data['intended1'] as $key => $value) {
            $respData['coinle_response'] = $this->processStringData($value);
            $respData['coinle_type'] = IntendedLearner::TYPE_LEARNING;
            $requestData[IntendedLearner::TYPE_LEARNING][] = $respData;
        }
        foreach($data['intended2'] as $key => $value) {
            $respData['coinle_response'] = $this->processStringData($value);
            $respData['coinle_type'] = IntendedLearner::TYPE_REQUIREMENTS;
            $requestData[IntendedLearner::TYPE_REQUIREMENTS][] = $respData;

        }
        foreach($data['intended3'] as $key => $value) {
            $respData['coinle_response'] = $this->processStringData($value);
            $respData['coinle_type'] = IntendedLearner::TYPE_LEARNERS;
            $requestData[IntendedLearner::TYPE_LEARNERS][] = $respData;
        }
        return $requestData;
    }

    private function setupIntendedLearners($data) 
    {
        $db = FatApp::getDb();
        $i=0;
        foreach($data['intendedLearners'][IntendedLearner::TYPE_LEARNING] as $key => $value) {
            $value['coinle_course_id'] =  $data['course_id'];
            $value['coinle_created'] = date('Y-m-d H:i:s');
            $value['coinle_order'] = $i++;
            if (!$db->insertFromArray(IntendedLearner::DB_TBL, $value, false, [], $value)) {
                return false;
            }
        }
        foreach($data['intendedLearners'][IntendedLearner::TYPE_REQUIREMENTS] as $key => $value) {
            $value['coinle_course_id'] =  $data['course_id'];
            $value['coinle_created'] = date('Y-m-d H:i:s');
            $value['coinle_order'] = $i++;
            if (!$db->insertFromArray(IntendedLearner::DB_TBL, $value, false, [], $value)) {
                return false;
            }
        }
        foreach($data['intendedLearners'][IntendedLearner::TYPE_LEARNERS] as $key => $value) {
            $value['coinle_course_id'] =  $data['course_id'];
            $value['coinle_created'] = date('Y-m-d H:i:s');
            $value['coinle_order'] = $i++;
            if (!$db->insertFromArray(IntendedLearner::DB_TBL, $value, false, [], $value)) {
                return false;
            }
        }
        return true;
    }

    private function addCoursesReview()
    {
        $ratingData = [
            [
                'titles' => 'Helped me undertand how to use social media in my new business',
                'descriptions' => 'This Platform helped me understand how to use social media in my new business. This company is an excellent resource for anyone starting a new business.',
            ],
            [
                'titles' => '{Consultant_name} was great to work',
                'descriptions' => 'The  {consultant_name} was great to work with providing us with a future road map to meet our expansion goals. He provided great service and kept us on track with ongoing initiatives and opportunities.',
            ],
            [
                'titles' => '{Consultant_name} helped my business',
                'descriptions' => '{consultant_name} helped my business and I know He’ll help yours. After our meetings I now have a much better plan and can actually see the end result.',
            ],
            [
                'titles' => '{Consultant_name} truly cares about their clients',
                'descriptions' => 'I highly recommend {platform} for anyone who is struggling with the next steps of their business. I was at a stalemate with my business, and was only able to move my business forward in ways that I never knew how until I received {Consultant_name} guidance. He truly cares about their clients.',
            ],
            [
                'titles' => '{platform} was able to take me and my company to a new level',
                'descriptions' => '{platform} was able to take me and my company to a new level. From behind the scenes of administration, HR policy and procedures to Employee Retention, Customer Satisfaction and R&D. Every aspect of {platform} has been well developed and hyper-focused on the goals of our business.',
            ],
        ];

        $srch = new SearchBase(CourseProgress::DB_TBL, 'crspro');
        $srch->joinTable(OrderCourse::DB_TBL, 'INNER JOIN','ordcrs.ordcrs_id = crspro.crspro_ordcrs_id', 'ordcrs');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ord.order_id = ordcrs.ordcrs_order_id', 'ord');
        $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'course.course_id = ordcrs.ordcrs_course_id', 'course');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = course.course_user_id', 'teacher');
        $srch->joinTable(Issue::DB_TBL, 'LEFT JOIN', 'repiss.repiss_record_id = ordcrs.ordcrs_id and repiss.repiss_record_type = ' . AppConstant::COURSE, 'repiss');
        $srch->addMultipleFields([
            'course_user_id as teacher_id',
            'ordcrs_id', 
            'order_user_id', 
            'user_first_name as teacher_first_name', 
            'user_last_name as teacher_last_name',
            'crspro_completed'
        ]);
        $srch->addDirectCondition('repiss.repiss_id IS NULL');
        $srch->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        $srch->addCondition('crspro_status', '=', CourseProgress::COMPLETED);
        $srch->addCondition('ordcrs_status', '=', OrderCourse::COMPLETED);
        $srch->addCondition('ordcrs_reviewed', '=', 0);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(500);
        $courses = FatApp::getDb()->fetchAll($srch->getResultSet());
        $i = 0;
        foreach ($courses as $row) {
            if ($i % 2 == 0) {
                if ($i % 4 == 0) {
                    $ratingStatus = RatingReview::STATUS_APPROVED;
                } else {
                    $ratingStatus = ($i % 6 == 0) ? RatingReview::STATUS_DECLINED : RatingReview::STATUS_PENDING;
                }
                $rating = $ratingData[array_rand($ratingData, 1)];
                $name = $row['teacher_first_name'] . ' ' . $row['teacher_last_name'];
                $title = str_replace(['{Consultant_name}', '{platform}'], [$name, FatApp::getConfig("CONF_WEBSITE_NAME_" . MyUtility::getSiteLangId(), FatUtility::VAR_STRING)], $rating['titles']);
                $desc = str_replace(['{Consultant_name}', '{platform}'], [$name, FatApp::getConfig("CONF_WEBSITE_NAME_" . MyUtility::getSiteLangId(), FatUtility::VAR_STRING)], $rating['descriptions']);
                $rating = $ratingData[array_rand($ratingData, 1)];
                $endTime = strtotime($row['crspro_completed'] . ' +' . rand(0, 10) . ' minutes');
                $ratingArray = [
                    'ratrev_type' => AppConstant::COURSE,
                    'ratrev_type_id' => $row['ordcrs_id'],
                    'ratrev_user_id' => $row['order_user_id'],
                    'ratrev_teacher_id' => $row['teacher_id'],
                    'ratrev_lang_id' => 1,
                    'ratrev_overall' => rand(4, 5),
                    'ratrev_title' => $title,
                    'ratrev_detail' => $desc,
                    'ratrev_created' => date('Y-m-d H:i:s', $endTime),
                    'ratrev_status' => $ratingStatus,
                ];
                $ratingRec = new TableRecord(RatingReview::DB_TBL);
                $ratingRec->assignValues($ratingArray);
                if (!$ratingRec->addNew()) {
                    return false;
                }
                if ($ratingStatus == RatingReview::STATUS_APPROVED) {
                    (new TeacherStat($row['teacher_id']))->setRatingReviewCount();
                }
                $record = new TableRecord(OrderCourse::DB_TBL);
                $record->assignValues(['ordcrs_reviewed' => $ratingStatus]);
                if (!$record->update(['smt' => 'ordcrs_id = ?', 'vals' => [$row['ordcrs_id']]])) {
                  return false;
                }
            }
            $i++;
        }
        return true;

    }

    public function setRatingReviewCount()
    {
        $srch = new SearchBase(CourseRatingReview::DB_TBL, 'ratrev');
        $srch->joinTable(OrderCourse::DB_TBL,'INNER JOIN' ,'ordcrs.ordcrs_id = ratrev.ratrev_type_id', 'ordcrs');
        $srch->addMultipleFields([
            'COUNT(*) as course_reviews',
            'IFNULL(ROUND(AVG(ratrev.ratrev_overall), 2), 0) as course_ratings',
            'ordcrs_course_id',
        ]);
        $srch->addCondition('ratrev.ratrev_status', '=', CourseRatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_type', '=', AppConstant::COURSE);
        $srch->doNotCalculateRecords();
        $srch->addGroupBy('ordcrs.ordcrs_course_id');
        $courses = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($courses as $value) {
            $course = new Course($value['ordcrs_course_id']);
            $course->assignValues(['course_ratings' => $value['course_ratings'], 'course_reviews' => $value['course_reviews']]);
            if (!$course->save()) {
                return false;
            }
        }   
       return true;
    }

    /* public function uploadImages()
    {
        $this->addPreviewImages();
        die;
    } */

    private function addPreviewImages()
    {
        $index = 0;
        $images = scandir(CONF_UPLOADS_PATH . 'compressed/course-preview-images');
        unset($images[array_search('.', $images)]);
        unset($images[array_search('..', $images)]);
        $images = array_values($images);
        if (empty($images)) {
            return true;
        }
        $srch = new SearchBase(Course::DB_TBL, 'course');
        $oncondition = 'file.file_record_id = course.course_id AND file.file_type = ' . Afile::TYPE_COURSE_IMAGE;
        $srch->joinTable(Afile::DB_TBL, 'LEFT JOIN', $oncondition, 'file');
        $srch->addDirectCondition('file.file_record_id IS NULL');
        $srch->addMultipleFields(['course.course_id as course_id']);
        $srch->addOrder('course_id');
        $srch->setPageSize(count($images));
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        while ($course = FatApp::getDb()->fetch($resultSet)) {
            $from = CONF_UPLOADS_PATH . 'compressed/course-preview-images/' . $images[$index];
            $uniqid = str_replace('.', '', uniqid('', true));
            $filePath =  '2023/03/';
            if (!file_exists(CONF_UPLOADS_PATH . $filePath)) {
                mkdir(CONF_UPLOADS_PATH . $filePath, 0777, true);
            }
            $path = $filePath . $uniqid . '.jpg';
            $approvPath = $filePath.$uniqid.Afile::TYPE_COURSE_REQUEST_IMAGE.'.jpg';
            $file = new TableRecord(Afile::DB_TBL);
            $file->assignValues([
                'file_type' => Afile::TYPE_COURSE_IMAGE,
                'file_record_id' => $course['course_id'],
                'file_name' => $uniqid . '_' . $images[$index],
                'file_path' => $path,
                'file_order' => 0,
                'file_lang_id' => 0,
                'file_added' => date('Y-m-d H:i:s')
            ]);
            if (!$file->addNew()) {
                die('Completed profile photo for ' . $index . ' users 1');
            }
            $file = new TableRecord(Afile::DB_TBL);
            $file->assignValues([
                'file_type' => Afile::TYPE_COURSE_REQUEST_IMAGE,
                'file_record_id' => $course['course_id'],
                'file_name' => $uniqid . '_' . $images[$index],
                'file_path' => $path,
                'file_order' => 0,
                'file_lang_id' => 0,
                'file_added' => date('Y-m-d H:i:s')
            ]);
            if (!$file->addNew()) {
                die('Completed profile photo for ' . $index . ' users 1');
            }
            copy($from, CONF_UPLOADS_PATH . $path);
            copy($from, CONF_UPLOADS_PATH . $approvPath);
            unlink($from);
            unset($images[$index]);
            if(count($images) <= 0) {
                return true;
            }
            $index = array_key_first($images);
        }
        return true;
    }

    private function getPreviewVideoUrl()
    {
        $previewVideoUrl = [
            'https://www.youtube.com/embed/w6D1he94wgY',
            'https://www.youtube.com/embed/3FsTchilu2U',
            'https://www.youtube.com/embed/eLYl7LM5jLg',
            'https://www.youtube.com/embed/CtWsllndsV4',
            'https://www.youtube.com/embed/tFZ8cGqd7Dw',
            'https://www.youtube.com/embed/Nhj60D2vatA',
            'https://www.youtube.com/embed/cBVY-q-9PL8',
            'https://www.youtube.com/embed/Ctf5NIxKWe0',
            'https://www.youtube.com/embed/OPun84UWpfE',
            'https://www.youtube.com/embed/KNJcwtILzu8',
            'https://www.youtube.com/embed/pl0fUN_Ye1E',
            'https://www.youtube.com/embed/9BE13Q0NI-g',
            'https://www.youtube.com/embed/80iZRrCXCDQ',
            'https://www.youtube.com/embed/iXvGKMPEjgo',
            'https://www.youtube.com/embed/BNoulWAZ-Ig',
            'https://www.youtube.com/embed/InLOM2BNskY',
            'https://www.youtube.com/embed/q_Fy8DceWZM',
            'https://www.youtube.com/embed/90EBvAfxC1Y',
            'https://www.youtube.com/embed/nGM8mY22eec',
            'https://www.youtube.com/embed/m5TC3fKnfsM',
            'https://www.youtube.com/embed/Sb_palw3in8',
            'https://www.youtube.com/embed/Ib1BDEF-Umg',
            'https://www.youtube.com/embed/w5xjH03-OUw',
            'https://www.youtube.com/embed/Vwn3tQHML-I',
            'https://www.youtube.com/embed/3XvAqFcfenQ',
            'https://www.youtube.com/embed/8YBOlzOzpkY',
        ];
        return $previewVideoUrl[array_rand($previewVideoUrl, 1)];
    }

    private function getCategories($isChild, $langId)
    {
        $srch = Category::getSearchObject();
        $srch->joinTable(
            Category::DB_LANG_TBL,
            'LEFT OUTER JOIN',
            'catg.cate_id = catg_l.catelang_cate_id AND catg_l.catelang_lang_id = ' . $langId,
            'catg_l'
        );
        $srch->addMultipleFields(
            ['IFNULL(cate_name, cate_identifier) AS cate_name', 'cate_id']
        );
        $srch->addOrder('cate_order');
        if($isChild == 1) {
            $srch->addCondition('cate_parent', '!=', 0);
        } else {
            $srch->addCondition('cate_parent', '=', 0);
        }
        $srch->addCondition('cate_type', '=', Category::TYPE_COURSE);
        $srch->addCondition('cate_status', '=', AppConstant::ACTIVE);
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true); 
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    private function getCurrencies($currencyCode = '')
    {
        $srch = new SearchBase(Currency::DB_TBL);
        $srch->addCondition('currency_active', '=', AppConstant::YES);
        if (!empty($currencyCode)) {
            $srch->addCondition('currency_code', '=', $currencyCode);
        }
        $srch->addMultipleFields([
            'currency_code',
            'currency_id',
        ]);
        $srch->addOrder('currency_order', 'ASC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    private function getTeachers($langId)
    {
        $db = FatApp::getDb();
        $srch = new TeacherSearch($langId, 0, User::LEARNER);
        $srch->addMultipleFields(['teacher.user_id as teacher_id', 'user_verified', 'user_first_name', 'user_last_name']);
        $srch->applyPrimaryConditions();
        $srch->addOrder('teacher.user_id', 'ASC');
        $srch->setPageSize(500);
        $srch->doNotCalculateRecords();
        $teachers = $db->fetchAll($srch->getResultSet(), 'teacher_id');
        return $teachers;
    }

    private function setCourseSectionCount(int $courseId)
    {
        $srch = new SearchBase(Section::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addFld('COUNT(section_id) AS course_sections');
        $srch->addCondition('section_course_id', '=', $courseId);
        $srch->addCondition('section_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $course = new Course($courseId);
        $course->assignValues($row);
        if (!$course->save()) {
            FatUtility::dieJsonError($course->getError());
            return false;
        }
        return true;
    }

    private function setCourseLectureCount(int $courseId)
    {
        $srch = new SearchBase(Lecture::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('COUNT(lecture_id) AS course_lectures');
        $srch->addCondition('lecture_course_id', '=', $courseId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $course = new Course($courseId);
        $course->assignValues($row);
        if (!$course->save()) {
            return false;
        }
        return true;
    }

    private function setLectureCount(int $sectionId)
    {
        $srch = new SearchBase(Lecture::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('lecture_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('COUNT(lecture_id) AS section_lectures');
        $srch->addFld('SUM(lecture_duration) AS section_duration');
        $srch->addFld('lecture_course_id');
        $srch->addCondition('lecture_section_id', '=', $sectionId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $section = new Section($sectionId);
        $section->setFldValue('section_lectures', $row['section_lectures']);
        $section->setFldValue('section_duration', $row['section_duration']);
        if (!$section->save()) {
            FatUtility::dieJsonError($section->getError());
            return false;
        }   
        return true;
    }

    private function setDuration(int $courseId)
    {
        $srch = new SearchBase(Section::DB_TBL);
        $srch->addCondition('section_course_id', '=', $courseId);
        $srch->addCondition('section_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('IFNULL(SUM(section_duration), 0) AS course_duration');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $course = new Course($courseId);
        $course->assignValues($row);
        if (!$course->save()) {
            return false;
        }
        return true;
    }

    public function getCoupons(): array
    {
        $srch = new SearchBase(Coupon::DB_TBL, 'coupon');
        $srch->joinTable(Coupon::DB_TBL_LANG, 'LEFT JOIN', 'couponlang.couponlang_coupon_id = coupon.coupon_id', 'couponlang');
        $srch->addMultipleFields(['coupon_id', 'coupon_code', 'IFNULL(coupon_title, coupon_identifier) as coupon_title', 
                'IFNULL(coupon_description, "") as coupon_description', 'coupon_start_date', 'coupon_end_date', 
                'coupon_discount_type', 'coupon_discount_value', 'coupon_max_discount', 'coupon_user_uses', 'coupon_max_uses', 'coupon_used_uses']);
        $srch->addCondition('coupon.coupon_used_uses', '<', 'mysql_func_coupon_max_uses', 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->setPageNumber(1);
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'coupon_id');
    }

    private function getCommission($teacherIds)
    {
        $teacherIds = implode('","', $teacherIds);
        $srch = new SearchBase(Commission::DB_TBL);
        $srch->addMultipleFields(['comm_lessons', 'comm_classes', 'comm_id', 'comm_user_id']);
        $srch->addDirectCondition('comm_user_id IN ("' . $teacherIds . '") OR comm_user_id IS NULL');
        $srch->addOrder('comm_user_id', 'DESC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'comm_user_id');
    }

    private function getCourseLectures($courseIds)
    {
        $srch = new SearchBase(Lecture::DB_TBL, 'lec');
        $srch->joinTable(Section::DB_TBL, 'INNER JOIN', 'lec.lecture_section_id = sec.section_id', 'sec');
        $srch->addDirectCondition('lec.lecture_course_id IN (' . implode(',', $courseIds) . ')');
        $srch->addMultipleFields(['lec.lecture_course_id', 'GROUP_CONCAT(DISTINCT lec.lecture_id SEPARATOR ",") as lec_ids', 'GROUP_CONCAT(DISTINCT sec.section_id SEPARATOR ",") as sec_ids']);
        $srch->addOrder('sec.section_order');
        $srch->addOrder('lec.lecture_order');
        $srch->addGroupBy('lec.lecture_course_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'lecture_course_id');
    }

    private function getCourses()
    {
        $srch = new CourseSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->applyPrimaryConditions();
        $srch->joinTable(
            Course::DB_TBL_APPROVAL_REQUEST,
            'INNER JOIN',
            'course.course_id = coapre.coapre_course_id',
            'coapre'
        );
        $srch->addSearchListingFields();
        $srch->addCondition('coapre.coapre_status', '=', Course::REQUEST_APPROVED);
        $srch->addCondition('course.course_status', '=', Course::PUBLISHED);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return $srch->fetchAndFormat();        
    }

    private function getUsers()
    {
        $srch = new SearchBase(User::DB_TBL, 'users');
        $srch->joinTable(Order::DB_TBL, 'LEFT JOIN', 'users.user_id = orders.order_user_id and orders.order_type = ' . Order::TYPE_COURSE, 'orders');
        $srch->addMultipleFields(['user_id', 'user_first_name', 'user_last_name', 'user_verified']);
        $srch->addDirectCondition('user_is_teacher = 0');
        $srch->addDirectCondition('user_verified IS NOT NULL');
        $srch->addDirectCondition('order_user_id IS NULL');
        $srch->addCondition('user_active', '=', AppConstant::ACTIVE);
        $srch->doNotCalculateRecords();
        $srch->addOrder('user_id');
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
    }

    private function timeToSeconds(string $time): int
    {
        $arr = explode(':', $time);
        $time = 60;
        if(count($arr) === 2) {
            $time = $arr[0] * 60 + $arr[1];
        } elseif (count($arr) === 3) {
            $time = $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
        }
        if($time < 60) {
            $time = 60;
        }
        return $time;
    }
    
    private function processStringData($str)
    {
        $replacements = ['Udemy' => 'W3mentors'];
        foreach ($replacements as $key => $val) {
            $str = str_replace($key, $val, $str);
            $str = str_replace(ucfirst($key), $val, $str);
            $str = str_replace(strtolower($key), $val, $str);
            $str = str_replace(strtoupper($key), $val, $str);
        }
        return $str;
    }

    // public function truncateString($s, $characterCount, $addEllipsis = ' …') 
    // {
    //     $return = $s;
    //     if (preg_match("/^.{1,$characterCount}\b/su", $s, $match)) 
    //         $return = $match[0];
    //     else
    //         $return = mb_substr($return, 0, $characterCount);
    //     $return = rtrim($return);
    //     if (strlen($s) > strlen($return)) $return .= $addEllipsis;
    //     return $return;
    // }

    public function truncateString($content, $chars) {
        if (strlen($content) > $chars) {
            $content = str_replace('&nbsp;', ' ', $content);
            $content = str_replace("\n", '', $content);
            $content = strip_tags(trim($content));
            $content = preg_replace('/\s+?(\S+)?$/', '', mb_substr($content, 0, $chars));
            //$content = preg_match('/.*[!@#$%^&*,.]$/', '', $content);
            //$content = trim($content) . '...';
            if (preg_match('/.*[!@#$%^&*,.]+$/', $content)) {
                $content = substr($content, 0, -1);
            }
            return $content;
        }
        return $content;
    }



}