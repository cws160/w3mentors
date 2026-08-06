<?php

/**
 * This class is used to handle Offer Price
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class OfferPrice extends MyAppModel
{

    const DB_TBL = 'tbl_offer_prices';
    const DB_TBL_PREFIX = 'offpri_';

    protected $learnerId;

    /**
     * Initialize Offer Price
     * 
     * @param int $learnerId
     * @param int $id
     */
    public function __construct(int $learnerId = 0, int $id = 0)
    {
        $this->learnerId = $learnerId;
        parent::__construct(static::DB_TBL, 'offpri_id', $id);
    }

    /**
     * Setup Offer Price
     * 
     * @param array $offers ["lessonPrice" => [], "classPrice" => [], offpri_package_price => 0.00 ]
     * @param array $lessonPrice
     * @param int $teacherId
     * @return bool
     */
    public function setupPrice(array $offers, int $teacherId): bool
    {
        $lessonPrice = (empty($offers['lessonPrice'])) ? NULL : json_encode($offers['lessonPrice']);
        $classPrice = (empty($offers['classPrice'])) ? NULL : json_encode($offers['classPrice']);
        $packagePrice = (empty($offers['offpri_package_price'])) ? NULL : $offers['offpri_package_price'];
        $data = [
            'offpri_learner_id' => $this->learnerId,
            'offpri_teacher_id' => $teacherId,
            'offpri_class_price' => $classPrice,
            'offpri_lesson_price' => $lessonPrice,
            'offpri_package_price' => $packagePrice,
        ];
        $this->assignValues($data);
        return $this->addNew([], $data);
    }

    /**
     * Increase Lesson
     * 
     * @param int $teacherId
     * @param int $lessons
     * @return bool
     */
    public function increaseLesson(int $teacherId, int $lessons = 1): bool
    {
        if ($this->learnerId < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $query = 'INSERT INTO ' . static::DB_TBL . '(offpri_teacher_id, offpri_learner_id, offpri_lessons) VALUES ("' .
                $teacherId . '","' . $this->learnerId . '" ,"' . $lessons . '") ON DUPLICATE KEY UPDATE offpri_lessons = `offpri_lessons` + ' . $lessons;
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Increase Class
     * 
     * @param int $teacherId
     * @param int $classes
     * @return bool
     */
    public function increaseClass(int $teacherId, int $classes = 1): bool
    {
        if ($this->learnerId < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $query = 'INSERT INTO ' . static::DB_TBL . '(offpri_teacher_id, offpri_learner_id, offpri_classes) VALUES ("' .
                $teacherId . '","' . $this->learnerId . '" ,"' . $classes . '") ON DUPLICATE KEY UPDATE offpri_classes = `offpri_classes` + ' . $classes;
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Search Object
     * 
     * @param int $teacherId
     * @return SearchBase
     */
    public function getSrchObj(int $teacherId): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'teofpr');
        $srch->addCondition('offpri_teacher_id', '=', $teacherId);
        $srch->addCondition('offpri_learner_id', '=', $this->learnerId);
        if ($this->mainTableRecordId > 0) {
            $srch->addCondition('offpri_id', '=', $this->mainTableRecordId);
        }
        $srch->doNotCalculateRecords();
        return $srch;
    }

    /**
     * Get Lesson Offer
     * 
     * @param int $learnerId
     * @param int $teacherId
     * @return null|array
     */
    public static function getLessonOffer(int $learnerId, int $teacherId)
    {
        $srch = new SearchBase(static::DB_TBL, 'teofpr');
        $srch->addCondition('offpri_learner_id', '=', $learnerId);
        $srch->addCondition('offpri_teacher_id', '=', $teacherId);
        $srch->addFld('IFNULL(offpri_lesson_price, "") as offpri_lesson_price');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Class Offer
     * 
     * @param int $learnerId
     * @param int $teacherId
     * @return null|array
     */
    public static function getClassOffer(int $learnerId, int $teacherId)
    {
        $srch = new SearchBase(static::DB_TBL, 'teofpr');
        $srch->addCondition('offpri_learner_id', '=', $learnerId);
        $srch->addCondition('offpri_teacher_id', '=', $teacherId);
        $srch->addFld('IFNULL(offpri_class_price, "") as offpri_class_price');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Package Offer
     * 
     * @param int $learnerId
     * @param int $teacherId
     * @return null|array
     */
    public static function getPackageOffer(int $learnerId, int $teacherId)
    {
        $srch = new SearchBase(static::DB_TBL, 'teofpr');
        $srch->addCondition('offpri_learner_id', '=', $learnerId);
        $srch->addCondition('offpri_teacher_id', '=', $teacherId);
        $srch->addFld('offpri_package_price');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Apply Lesson Offer
     * 
     * @param int $learnerId
     * @param array $lesson
     */
    public static function applyLessonOffer(int $learnerId, array $lesson)
    {
        if (empty($lesson['ordles_amount'])) {
            return FatUtility::float($lesson['ordles_amount']);
        }
        $teacherId = FatUtility::int($lesson['ordles_teacher_id']);
        $data = static::getLessonOffer($learnerId, $teacherId);
        if (empty($data['offpri_lesson_price'])) {
            return FatUtility::float($lesson['ordles_amount']);
        }
        $offers = json_decode($data['offpri_lesson_price'], 1);
        $offers = array_column($offers, 'offer', 'duration');
        $duration = FatUtility::int($lesson['ordles_duration']);
        $discountPercent = Fatutility::float(($offers[$duration] ?? 0));
        $totalDiscount = round(($discountPercent * $lesson['ordles_amount']) / 100, 2);
        return Fatutility::float($lesson['ordles_amount'] - $totalDiscount);
    }

    /**
     * Apply Class Offer
     * 
     * @param int $learnerId
     * @param array $class
     * @return float class price
     */
    public static function applyClassOffer(int $learnerId, array $class): float
    {
        $teacherId = FatUtility::int($class['grpcls_teacher_id']);
        $data = static::getClassOffer($learnerId, $teacherId);
        if (empty($data['offpri_class_price'])) {
            return FatUtility::float($class['ordcls_amount']);
        }
        $offers = json_decode($data['offpri_class_price'], 1);
        $offers = array_column($offers, 'offer', 'duration');
        $duration = FatUtility::int($class['grpcls_duration']);
        $discountPercent = Fatutility::float(($offers[$duration] ?? 0));
        $totalDiscount = round(($discountPercent * $class['ordcls_amount']) / 100, 2);
        return Fatutility::float($class['ordcls_amount'] - $totalDiscount);
    }

    /**
     * Apply Package Offer
     * 
     * @param int $learnerId
     * @param array $package
     * @return float class price
     */
    public static function applyPackageOffer(int $learnerId, array $package): float
    {
        $teacherId = FatUtility::int($package['grpcls_teacher_id']);
        $data = static::getPackageOffer($learnerId, $teacherId);
        if (empty($data['offpri_package_price'])) {
            return FatUtility::float($package['grpcls_amount']);
        }
        $discount = FatUtility::float($data['offpri_package_price'] ?? 0);
        $totalDiscount = round(($discount * $package['grpcls_amount']) / 100, 2);
        return Fatutility::float($package['grpcls_amount'] - $totalDiscount);
    }

    /**
     * getOfferPercentage
     * 
     * @param integer $teacherId
     * @param integer $duration
     * @param integer $type
     * @return float
     */
    public function getByDuration(int $teacherId, int $duration, int $type): float
    {
        if ($type == AppConstant::LESSON) {
            $field = 'IFNULL(offpri_lesson_price, "") as offpri_lesson_price';
        } elseif ($type == AppConstant::GCLASS) {
            $field = 'IFNULL(offpri_class_price, "") as offpri_class_price';
        } else {
            return 0.00;
        }
        $srch = new SearchBase(static::DB_TBL, 'teofpr');
        $srch->addCondition('offpri_teacher_id', '=', $teacherId);
        $srch->addCondition('offpri_learner_id', '=', $this->learnerId);
        $srch->addDirectCondition($field . ' IS NOT NULL');
        $srch->doNotCalculateRecords();
        $srch->addFld($field);
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($data[$field])) {
            return 0.00;
        }
        $offers = json_decode($data[$field], true);
        $offers = array_column($offers, 'offer', 'duration');
        return Fatutility::float(($offers[$duration] ?? 0));
    }

    /**
     * Get Learner Offers
     * 
     * @param int $teacherId
     * @return null|array
     */
    public function getLearnerOffers(int $teacherId)
    {
        $srch = $this->getSrchObj($teacherId);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = teofpr.offpri_learner_id', 'learner');
        $srch->addDirectCondition('learner.user_deleted IS NULL');
        $srch->addMultipleFields(['learner.user_first_name', 'learner.user_last_name', 'teofpr.*']);
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Teachers Offers
     * 
     * @param int $userId
     * @param array $teacherIds
     * @return array
     */
    public static function getOffers(int $userId, array $teacherIds): array
    {
        $srch = new SearchBase(static::DB_TBL, 'teofpr');
        $srch->addCondition('offpri_learner_id', '=', $userId);
        $srch->addCondition('offpri_teacher_id', 'IN', $teacherIds);
        $srch->addMultipleFields(['offpri_lesson_price', 'offpri_class_price',
            'offpri_package_price', 'offpri_teacher_id']);
        $srch->doNotCalculateRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $offers = [];
        foreach ($records as $record) {
            $lessonOffer = json_decode($record['offpri_lesson_price'] ?? '', true) ?? [];
            $classOffer = json_decode($record['offpri_class_price'] ?? '', true) ?? [];
            $offers[$record['offpri_teacher_id']] = [
                'lesson' => array_column($lessonOffer, 'offer', 'duration'),
                'class' => array_column($classOffer, 'offer', 'duration'),
                'package' => $record['offpri_package_price'],
            ];
        }
        return $offers;
    }

    public function updateTeacherStats(int $teacherId): bool
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->addFld('SUM(offpri_lessons) AS testat_lessons');
        $srch->addFld('SUM(offpri_classes) AS testat_classes');
        $srch->addFld('COUNT(offpri_learner_id) AS testat_students');
        $srch->addCondition('offpri_teacher_id', '=', $teacherId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        $row['testat_user_id'] = $teacherId;
        $record = new TableRecord(User::DB_TBL_STAT);
        $record->assignValues($row);
        if (!$record->addNew([], $row)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

}
