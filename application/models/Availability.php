<?php

/**
 * This class is used to handle Learners|Teachers availabilities
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Availability extends FatModel
{

    private $userId;
    private $timezone;

    const DB_TBL = 'tbl_availability';
    const DB_TBL_WEEKLY = 'tbl_weekly_availability';
    const DB_TBL_GENERAL = 'tbl_general_availability';

    /**
     * Do not change these date otherwise it will cause DST issue
     */
    const GENERAL_WEEKSTART = '2018-01-21 00:00:00';
    const GENERAL_WEEKEND = '2018-01-28 00:00:00';

    /**
     * Initialize Availability
     * 
     * @param int $userId           It is a teacher id for which getting|setting availability
     */
    public function __construct(int $userId)
    {
        parent::__construct();
        $this->userId = $userId;
    }

    /**
     * Set General Availability
     * 
     * 1. Delete general availability
     * 2. Insert general availability
     * 3. Delete Teacher availability
     * 4. Get Teacher's weekly availability
     * 5. Update Teacher availability
     *
     * @param array $availability       It is an array containing start and end dates of availability slots
     * @return boolean                  Return true on success and false on failure and set error 
     */
    public function setGeneral(array $availability): bool
    {
        $availability = $this->formatGeneral($availability);
        $db = FatApp::getDb();
        $db->startTransaction();
        /* Delete general availability */
        if (!$db->deleteRecords(static::DB_TBL_GENERAL, ['smt' => 'gavail_user_id = ?', 'vals' => [$this->userId]])) {
            $this->error = $db->getError();
            $db->rollbackTransaction();
            return false;
        }
        /* Insert general availability */
        foreach ($availability as $value) {
            $insertArr = [
                'gavail_user_id' => $this->userId,
                'gavail_starttime' => $value['start'],
                'gavail_endtime' => $value['end']
            ];
            if (!$db->insertFromArray(static::DB_TBL_GENERAL, $insertArr)) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
        }
        /* Delete Teacher availability */
        if (!$db->deleteRecords(static::DB_TBL, ['smt' => 'avail_user_id = ?', 'vals' => [$this->userId]])) {
            $db->rollbackTransaction();
            return false;
        }
        $weekData = MyDate::getStartEndDate(MyDate::TYPE_THIS_WEEK);
        /* Get Teacher's weekly availability from the current week start date */
        $weekly = $this->getWeekly([$this->userId], MyDate::formatToSystemTimezone($weekData['startDate']));
        $weeklyAvailabilit = $weekly[$this->userId] ?? [];
        $this->timezone = MyUtility::getSiteTimezone();
        $weekNo = FatApp::getConfig('CONF_AVAILABILITY_UPDATE_WEEK_NO');
        /* Update teacher availbility */
        if (!$this->updateAvailability($availability, $weekData['startDate'], $weekNo, $weeklyAvailabilit)) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Format general availability 
     *
     * @param array $availbility        It is an array containing start and end dates of available slots
     * @return array                    It will convert timezone to system timezone and return availability slots
     */
    private function formatGeneral(array $availbility): array
    {
        $weekStartUnix = strtotime(static::GENERAL_WEEKSTART);
        $weekEndUnix = strtotime(static::GENERAL_WEEKEND);
        $generalAvailability = [];
        foreach ($availbility as $value) {
            /* continue the iteration if dates are empty */
            if (empty($value['start']) || empty($value['end'])) {
                continue;
            }
            /* validate the start and end dates, if the dates not lies under the "GENERAL_WEEKSTART" and "GENERAL_WEEKEND" then continue the iteration */
            $startUnix = strtotime($value['start']);
            $endUnix = strtotime($value['end']);
            if ($startUnix >= $endUnix || $startUnix >= $weekEndUnix || $weekStartUnix >= $endUnix) {
                continue;
            }
            $start = ($weekStartUnix > $startUnix) ? static::GENERAL_WEEKSTART : $value['start'];
            $end = ($endUnix > $weekEndUnix) ? static::GENERAL_WEEKEND : $value['end'];
            /* format the date to system timezone */
            $value['start'] = MyDate::formatToSystemTimezone($start);
            $value['end'] = MyDate::formatToSystemTimezone($end);
            array_push($generalAvailability, $value);
        }
        return $generalAvailability;
    }

    /**
     * Set Availability
     * 1. Format and validate the Availability
     * 2. Delete the Availability which are lies between prev week start and next week end DateTime
     * 3. Update the Availability
     * 4. Add weekly Availability for selected week
     *
     * @param string $startDate     Start date of slot
     * @param string $endDate       End date of slot
     * @param array $availability   It is an array containing start and end dates of available slots
     * @return boolean              Return true on success and false on failure and set error 
     */
    public function setAvailability(string $startDate, string $endDate, array $availability): bool
    {
        /* Format and validate the Availavility */
        $availability = $this->formatAvailability($availability);
        $startDateUnix = strtotime($startDate);
        $endDateUnix = strtotime($endDate);
        $prveWeekStart = date('Y-m-d H:i:s', strtotime('-1 week', $startDateUnix));
        $nextWeekEnd = date('Y-m-d H:i:s', strtotime('+1 week', $endDateUnix));
        $db = FatApp::getDb();
        $db->startTransaction();
        /* Delete the Availability which are lies between prev week start and next week end DateTime */
        if (!$db->deleteRecords(static::DB_TBL, [
            'smt' => 'avail_user_id = ? and avail_starttime < ? and avail_endtime > ?',
            'vals' => [$this->userId, $nextWeekEnd, $prveWeekStart]
        ])) {
            $this->error = Label::getLabel('LBL_ERROR_TO_UPDATE_DATA');
            return false;
        }
        $weeklyAvailability = [];
        /* Update the Availability */
        foreach ($availability as $value) {
            $insertArr = [
                'avail_user_id' => $this->userId,
                'avail_starttime' => $value['start'],
                'avail_endtime' => $value['end']
            ];
            if (!$db->insertFromArray(static::DB_TBL, $insertArr)) {
                $db->rollbackTransaction();
                $this->error = $db->getError();
                return false;
            }
            $startUnix = strtotime($insertArr['avail_starttime']);
            $endUnix = strtotime($insertArr['avail_endtime']);
            if ($endDateUnix > $startUnix && $endUnix > $startDateUnix) {
                $weekly = [
                    'start' => ($startDateUnix > $startUnix) ? $startDate : $insertArr['avail_starttime'],
                    'end' => ($endDateUnix < $endUnix) ? $endDate : $insertArr['avail_endtime'],
                ];
                array_push($weeklyAvailability, $weekly);
            }
        }
        /* Add weekly Availability for selected week */
        if (!$this->setWeekly($startDate, $endDate, $weeklyAvailability)) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Format Availability
     *
     * @param array $availability           It is an array containing start and end dates of available slots
     * @return array                        It will convert timezone to system timezone and return availability slots
     */
    private function formatAvailability(array $availability): array
    {
        $formatAvailability = [];
        foreach ($availability as $value) {
            /* continue the iteration if dates are empty */
            if (empty($value['start']) || empty($value['end'])) {
                continue;
            }
            /* format the date to system timezone */
            array_push($formatAvailability, [
                'start' => MyDate::formatToSystemTimezone($value['start']),
                'end' => MyDate::formatToSystemTimezone($value['end'])
            ]);
        }
        return $formatAvailability;
    }

    /**
     * Remove Availability
     * 
     * @return bool             Return true on success and false on failure and set error 
     */
    public function removeAvailability(): bool
    {
        $db = FatApp::getDb();
        if (!$db->deleteRecords(static::DB_TBL_GENERAL, ['smt' => 'gavail_user_id = ?', 'vals' => [$this->userId]])) {
            $this->error = $db->getError();
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->deleteRecords(static::DB_TBL_WEEKLY, ['smt' => 'wavail_user_id = ?', 'vals' => [$this->userId]])) {
            $this->error = $db->getError();
            $db->rollbackTransaction();
            return false;
        }
        if (!$db->deleteRecords(static::DB_TBL, ['smt' => 'avail_user_id = ?', 'vals' => [$this->userId]])) {
            $this->error = $db->getError();
            $db->rollbackTransaction();
            return false;
        }
        $userSetting = new UserSetting($this->userId);
        if (!$userSetting->saveData(['user_availability_date' => null])) {
            $this->error = $userSetting->getError();
            $db->rollbackTransaction();
            return false;
        }
        $teacherStatus = new TeacherStat($this->userId);
        if (!$teacherStatus->setAvailability(0)) {
            $this->error = $teacherStatus->getError();
            $db->rollbackTransaction();
            return false;
        }
        return true;
    }

    /**
     * Set Weekly Availability
     * 
     * @param string $startDate     Start date of slot
     * @param string $endDate       End date of slot
     * @param array $availability   It is an array containing start and end dates of available slots
     * @return bool                 Return true on success and false on failure and set error 
     */
    private function setWeekly(string $startDate, string $endDate, array $availability): bool
    {
        $record = new TableRecord(static::DB_TBL_WEEKLY);
        $record->assignValues([
            'wavail_user_id' => $this->userId,
            'wavail_startdate' => $startDate,
            'wavail_enddate' => $endDate,
            'wavail_availability' => json_encode($availability),
        ]);
        if (!$record->addNew([], $record->getFlds())) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Availability
     *
     * @param string $startdate     Start date of slot
     * @param string $enddate       End date of slot
     * @return array                It is an array containing start and end dates of available slots
     */
    public function getAvailability(string $startdate, string $enddate, int $sub = 0, string $subEndDate = ''): array
    {
        if (AppConstant::YES == $sub && $subEndDate == '') {
            $subdays = (FatApp::getConfig('CONF_RECURRING_SUBSCRIPTION_WEEKS') * 7);
            $dateTime = new DateTime('now', new DateTimeZone('UTC'));
            $dateTime->modify('+' . $subdays . ' days');
            $subEndDate = $dateTime->format('Y-m-d H:i:s');
        }
        $availability = [];
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('avail_user_id', '=', $this->userId);
        $srch->addCondition('avail_starttime', '<', $enddate);
        $srch->addCondition('avail_endtime', '>', $startdate);
        $srch->addOrder('avail_starttime');
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            if ((AppConstant::YES == $sub && $row['avail_endtime'] > $subEndDate) || (!empty($subEndDate)  && $row['avail_endtime'] > $subEndDate)) {
                $row['avail_endtime'] = $subEndDate;
            }
            if ((!empty($subEndDate)  && $row['avail_starttime'] > $subEndDate)) {
                continue;
            }
            array_push($availability, [
                "start" => MyDate::formatDate($row['avail_starttime']),
                "end" => MyDate::formatDate($row['avail_endtime']),
                'className' => "slot_available"
            ]);
        }
        return $availability;
    }

    /**
     * Get General Availability
     *
     * @return array $availability      It is an array containing start and end dates of available slots
     */
    public function getGeneral(): array
    {
        $availability = [];
        $srch = new SearchBase(static::DB_TBL_GENERAL);
        $srch->addCondition('gavail_user_id', '=', $this->userId);
        $srch->addOrder('gavail_starttime', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $resultSet = $srch->getResultSet();
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            array_push($availability, [
                "start" => MyDate::formatDate($row['gavail_starttime']),
                "end" => MyDate::formatDate($row['gavail_endtime']),
                'className' => "slot_available"
            ]);
        }
        return $availability;
    }

    /**
     * Check User's Availability
     * 
     * 1. Check Teacher Availability
     * 2. Check the Users has any class & lesson on passed time rage
     * 
     * @param string $startdate     Start date of slot
     * @param string $enddate       End date of slot
     * @return bool                 Return true on success and false on failure and set error 
     */
    public function isAvailable(string $startdate, string $enddate): bool
    {
        /* Check Tecaher Availablility */
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('avail_user_id', '=', $this->userId);
        $srch->addCondition('avail_starttime', '<=', $startdate);
        $srch->addCondition('avail_endtime', '>=', $enddate);
        $srch->addFld('count(*) as isAvailable');
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();

        $record = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($record['isAvailable'])) {
            $this->error = Label::getLabel('LBL_NO_SLOT_AVAILABEL_ON_THIS_TIME_RANGE');
            return false;
        }
        return true;
    }

    /**
     * Update Availability
     * 
     * update the availability by cron or after update the general availability
     *
     * 1. Get the weeks difference from GENERAL_WEEKSTART to $weekStartDate
     * 2. Get the last slab for merging of the last week from the date "$weekStartDate"
     * 3. Run the for loop for update the week data
     * 4. Check weekly availability for the particular week
     * 5. format and the availability
     * 6. Add week last slab in the $weekLastSlab
     * 
     * @param array $generalAvailability
     * @param string $weekStartDate
     * @param int $totalWeeks
     * @param array $weeklyAvailability
     * @return bool                 Return true on success and false on failure and set error 
     */
    public function updateAvailability(array $generalAvailability, string $weekStartDate, int $totalWeeks, array $weeklyAvailability): bool
    {
        /* Get the weeks diffrents from GENERAL_WEEKSTART to $weekStartDate */
        $weekDiff = MyDate::weekDiff(static::GENERAL_WEEKSTART, $weekStartDate);
        /* get prev week last slab from $weekStartDate for merging/combim the slab with new */
        $prevWeekLastSlab = $this->getPrevWeekLastSlab(MyDate::formatToSystemTimezone($weekStartDate, 'Y-m-d H:i:s', $this->timezone));
        $weekLastSlab = $prevWeekLastSlab;
        for ($week = $weekDiff; $week < ($weekDiff + $totalWeeks); $week++) {
            $weekStartUnix = strtotime(static::GENERAL_WEEKSTART . ' + ' . $week . ' weeks');
            $weekEndUnix = strtotime(static::GENERAL_WEEKEND . ' + ' . $week . ' weeks');
            $weekStart = MyDate::formatToSystemTimezone(date('Y-m-d H:i:s', $weekStartUnix), 'Y-m-d H:i:s', $this->timezone);
            $addWeekToTime = true;
            $availability = $generalAvailability;
            /* if the user saves the week data for particular week then update weekly availability data on that week */
            if (!empty($weeklyAvailability[$weekStart])) {
                $availability = json_decode($weeklyAvailability[$weekStart]['wavail_availability'], true);
                $addWeekToTime = false;
            }
            /* format/validate the data and update the availability */
            if (!$this->formatAndAdd($availability, $week, $addWeekToTime, $weekLastSlab)) {
                return false;
            }
        }
        /** update the last week end date on user setting */
        $userSetting = new UserSetting($this->userId);
        $endDate = date('Y-m-d H:i:s', $weekEndUnix);
        $date = MyDate::formatToSystemTimezone($endDate, 'Y-m-d H:i:s', $this->timezone);
        if (!$userSetting->saveData(['user_availability_date' => $date])) {
            $this->error = $userSetting->getError();
            return false;
        }
        return true;
    }

    /**
     * Format and update the Availability function
     *
     * @param array $availability       
     * @param integer $week
     * @param boolean $addWeekToTime
     * @param array $weekLastSlab
     * @return bool                 Return true on success and false on failure and set error 
     */
    private function formatAndAdd(array $availability, int $week, bool $addWeekToTime, array &$weekLastSlab): bool
    {
        $db = FatApp::getDb();
        foreach ($availability as $value) {
            $start = strtotime($value['start']);
            $end = strtotime($value['end']);
            /* if general availability update for the particular week then add the week on dates */
            if ($addWeekToTime) {
                $start = strtotime('+ ' . $week . ' weeks', $start);
                $end = strtotime('+ ' . $week . ' weeks', $end);
                if (MyDate::isDST(date('Y-m-d H:i:s', $start), $this->timezone)) {
                    $start = strtotime('-1 hours', $start);
                }
                if (MyDate::isDST(date('Y-m-d H:i:s', $end), $this->timezone)) {
                    $end = strtotime('-1 hours', $end);
                }
            }
            $insertArr = [
                'avail_starttime' => date('Y-m-d H:i:s', $start),
                'avail_endtime' => date('Y-m-d H:i:s', $end),
                'avail_user_id' => $this->userId
            ];
            /* if the dates collapses with last slab dates then update the start and end time for that slot */
            if (!empty($weekLastSlab) && strtotime($weekLastSlab['end']) >= $start && strtotime($weekLastSlab['start']) <= $end) {
                $insertArr['avail_id'] = $weekLastSlab['id'];
                $insertArr['avail_starttime'] = min($insertArr['avail_starttime'], $weekLastSlab['start']);
                $insertArr['avail_endtime'] = max($insertArr['avail_endtime'], $weekLastSlab['end']);
            }
            if (!$db->insertFromArray(static::DB_TBL, $insertArr, false, [], $insertArr)) {
                $this->error = $db->getError();
                return false;
            }
            if (empty($weekLastSlab) || $end >= strtotime($weekLastSlab['end'])) {
                $weekLastSlab = [
                    'start' => $insertArr['avail_starttime'],
                    'end' => $insertArr['avail_endtime'],
                    'id' => $db->getInsertId()
                ];
            }
        }

        return true;
    }

    /**
     * update user availability by cron job
     * Get Users who has left "CONF_AVAILABILITY_UPDATE_WEEK_NO - 1" weeks availability
     * Get the users General availability
     * get users weekly availability data after 49 weeks, if they saved any
     * updated the weekly availability
     *
     * @return bool                 Return true on success and false on failure and set error 
     */
    public function updateBySystem(): bool
    {
        $totalWeeks = FatApp::getConfig('CONF_AVAILABILITY_UPDATE_WEEK_NO') - 1;
        $date = date('Y-m-d H:i:s', strtotime(' + ' . $totalWeeks . ' week'));
        $users = $this->getUsersForUpdate($date);
        if (empty($users)) {
            return true;
        }
        $userIds = array_column($users, 'user_id');
        $general = $this->getUsersGenAvailability($userIds);
        $weekly = $this->getWeekly($userIds, $date);
        foreach ($users as $key => $user) {
            if (empty($general[$key])) {
                continue;
            }
            $weekStartDate = MyDate::formatDate($user['user_availability_date'], 'Y-m-d H:i:s', $user['user_timezone']);
            $this->userId = $user['user_id'];
            $this->timezone = $user['user_timezone'];
            $weeklyData = $weekly[$user['user_id']] ?? [];
            $db = FatApp::getDb();
            $db->startTransaction();
            if (!$this->updateAvailability($general[$key], $weekStartDate, 1, $weeklyData)) {
                $db->rollbackTransaction();
                return false;
            }
            $db->commitTransaction();
        }
        return true;
    }

    /**
     * Get user to update the availability bu cron
     *
     * @param string $date          String Date
     * @return array
     */
    private function getUsersForUpdate(string $date): array
    {
        $srch = new SearchBase(User::DB_TBL, 'users');
        $srch->addMultipleFields(['user_availability_date', 'user_timezone', 'users.user_id']);
        $srch->joinTable(TeacherStat::DB_TBL, 'INNER JOIN', 'users.user_id = testat.testat_user_id', 'testat');
        $srch->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'users.user_id = us.user_id', 'us');
        $srch->addCondition('users.user_is_teacher', '=', AppConstant::YES);
        $srch->addDirectCondition('users.user_timezone IS NOT NULL');
        $srch->addDirectCondition('us.user_availability_date IS NOT NULL');
        $srch->addCondition('testat.testat_availability', '=', AppConstant::YES);
        $srch->addCondition('us.user_availability_date', ' <= ', $date);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(10);
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
    }

    /**
     * Get multiple users General Availability
     *
     * @param array $userIds
     * @return array
     */
    private function getUsersGenAvailability(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $availability = [];
        $srch = new SearchBase(static::DB_TBL_GENERAL);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addCondition('gavail_user_id', 'IN', $userIds);
        $resultSet = $srch->getResultSet();
        $db = FatApp::getDb();
        while ($row = $db->fetch($resultSet)) {
            $availability[$row['gavail_user_id']][] = [
                "start" => $row['gavail_starttime'],
                "end" => $row['gavail_endtime']
            ];
        }
        return $availability;
    }

    /**
     * Get multiple users weekly availability
     *
     * @param array $userIds
     * @param string $weekStartDate
     * @return array
     */
    private function getWeekly(array $userIds, string $weekStartDate): array
    {
        $srch = new SearchBase(static::DB_TBL_WEEKLY);
        $srch->addCondition('wavail_startdate', '>=', $weekStartDate);
        $srch->addCondition('wavail_user_id', 'IN', $userIds);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $resultSet = $srch->getResultSet();
        $availability = [];
        while ($row = FatApp::getDb()->fetch($resultSet)) {
            $availability[$row['wavail_user_id']][$row['wavail_startdate']] = $row;
        }
        return $availability;
    }

    /**
     * Get user previous week last date availability for merging the data with next week 
     *
     * @param string $dateTime          Datetime in string format
     * @return array                    Array containing start and end date time
     */
    private function getPrevWeekLastSlab(string $dateTime): array
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('avail_endtime', '=', $dateTime);
        $srch->addCondition('avail_user_id', '=', $this->userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $lastDateData = FatApp::getDb()->fetch($srch->getResultSet());
        $weekLastSlab = [];
        if (!empty($lastDateData)) {
            $weekLastSlab = [
                'start' => $lastDateData['avail_starttime'],
                'end' => $lastDateData['avail_endtime'],
                'id' => $lastDateData['avail_id']
            ];
        }
        return $weekLastSlab;
    }

    /**
     * Check User's Availability
     * 
     * @param string $starttime         Start date time of slot
     * @param string $endtime           End date time of slot
     * @param int $gclassId             Group class id, if checking for particular group class
     * @return bool                     Return true on success and false on failure and set error 
     */
    public function isUserAvailable(string $starttime, string $endtime, int $gclassId = 0): bool
    {
        if ($this->isBookedLesson($starttime, $endtime)) {
            $this->error = Label::getLabel('LBL_BOOKED_LESSON_FOR_SAME_TIME');
            return false;
        }
        if ($this->isBookedGclass($starttime, $endtime)) {
            $this->error = Label::getLabel('LBL_BOOKED_CLASS_FOR_SAME_TIME');
            return false;
        }
        if ($this->isScheduledLesson($starttime, $endtime)) {
            $this->error = Label::getLabel('LBL_SCHEDULED_LESSON_FOR_SAME_TIME');
            return false;
        }
        if ($this->isScheduledGclass($starttime, $endtime, $gclassId)) {
            $this->error = Label::getLabel('LBL_SCHEDULED_CLASS_FOR_SAME_TIME');
            return false;
        }
        if ($this->isGoogleEventAdded($starttime, $endtime, $gclassId)) {
            $this->error = Label::getLabel('LBL_EVENT_ADDED_FOR_SAME_TIME');
            return false;
        }
        return true;
    }

    /**
     * User as Learner Booked a Lesson
     * 
     * @param string $starttime         Start date time of slot
     * @param string $endtime           End date time of slot
     * @return bool                     Return true on success and false on failure and set error 
     */
    private function isBookedLesson(string $starttime, string $endtime): bool
    {
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->addFld('count(*) as totalCount');
        $srch->addCondition('order_user_id', '=', $this->userId);
        $srch->addCondition('ordles_lesson_starttime', '<', $endtime);
        $srch->addCondition('ordles_lesson_endtime', '>', $starttime);
        $srch->addCondition('ordles_status', '=', Lesson::SCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return ($row['totalCount'] > 0);
    }

    /**
     * User as Learner Booked a Group Class
     * 
     * @param string $starttime         Start date time of slot
     * @param string $endtime           End date time of slot
     * @return bool                     Return true on success and false on failure and set error 
     */
    private function isBookedGclass(string $starttime, string $endtime): bool
    {
        $srch = new SearchBase(OrderClass::DB_TBL, 'ordcls');
        $srch->addMultipleFields(['count(*) totalCount']);
        $srch->joinTable(GroupClass::DB_TBL, 'INNER JOIN', 'grpcls.grpcls_id = ordcls.ordcls_grpcls_id', 'grpcls');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcls.ordcls_order_id', 'orders');
        $srch->addCondition('orders.order_user_id', '=', $this->userId);
        $srch->addCondition('grpcls_start_datetime', '<', $endtime);
        $srch->addCondition('grpcls_end_datetime', '>', $starttime);
        $srch->addCondition('ordcls_status', '=', OrderClass::SCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return ($row['totalCount'] > 0);
    }

    /**
     * User as Teacher Scheduled a Lesson
     * 
     * @param string $starttime         Start date time of slot
     * @param string $endtime           End date time of slot
     * @return bool                     Return true on success and false on failure and set error 
     */
    private function isScheduledLesson(string $starttime, string $endtime): bool
    {
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->addFld('count(*) as totalCount');
        $srch->addCondition('ordles_teacher_id', '=', $this->userId);
        $srch->addCondition('ordles_lesson_starttime', '<', $endtime);
        $srch->addCondition('ordles_lesson_endtime', '>', $starttime);
        $srch->addCondition('ordles_status', '=', Lesson::SCHEDULED);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('orders.order_status', '=', Order::STATUS_COMPLETED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return ($row['totalCount'] > 0);
    }

    /**
     * User as Teacher Scheduled a Group Class
     * 
     * @param string $starttime         Start date time of slot
     * @param string $endtime           End date time of slot
     * @param int $gclassId             Group class id, if checking for particular group class
     * @return bool                     Return true on success and false on failure and set error 
     */
    private function isScheduledGclass(string $starttime, string $endtime, int $gclassId = 0): bool
    {
        $srch = new SearchBase(GroupClass::DB_TBL, 'grpcls');
        $srch->addFld('count(*) as totalCount');
        $srch->addCondition('grpcls_teacher_id', '=', $this->userId);
        $srch->addCondition('grpcls_status', '=', GroupClass::SCHEDULED);
        $srch->addCondition('grpcls_type', '=', GroupClass::TYPE_REGULAR);
        $srch->addCondition('grpcls_start_datetime', '<', $endtime);
        $srch->addCondition('grpcls_end_datetime', '>', $starttime);
        if ($gclassId > 0) {
            $srch->addCondition('grpcls_id', '!=', $gclassId);
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return ($row['totalCount'] > 0);
    }

    /**
     * An event is present in Google Calendar
     *
     * @param string $starttime
     * @param string $endtime
     * @param int $gclassId
     * @return boolean
     */
    public function isGoogleEventAdded(string $starttime, string $endtime, int $gclassId = 0): bool
    {
        $srch = new SearchBase(GoogleCalendarEvent::DB_TBL, 'gocaev');
        $srch->addCondition('gocaev.gocaev_user_id', '=', $this->userId);
        $srch->addCondition('gocaev.gocaev_starttime', '<',  $endtime);
        $srch->addCondition('gocaev.gocaev_endtime', '>',  $starttime);
        if ($gclassId > 0) {
            $srch->addDirectCondition("((gocaev_record_id != '" . $gclassId . "' AND `gocaev_record_type` = '" . AppConstant::GCLASS . "') OR gocaev_record_type != '" . AppConstant::GCLASS . "')");
        }
        $srch->addFld('count(*) as totalCount');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return ($row['totalCount'] > 0);
    }

    /**
     * function to fetch slots for scheduling calendars
     *
     * @param string $startTime
     * @param string $endTime
     * @param array $scheduledData
     * @param array $userAvailability
     * @param array $userData
     * @return array
     */
    public static function getAvailabiltySlots(string $startTime, string $endTime, array $scheduledData, array $userAvailability, array $userData)
    {
        /* rounding off the minutes to 00, 15, 30, 45 */
        $minutes = date('i', ceil(time() / (15 * 60)) * (15 * 60));
        $currentDateTime = strtotime(MyDate::formatDate(date('Y-m-d H:' . $minutes . ':00'), 'Y-m-d H:i:s', $userData['timezone']));

        /* get slots starting date according to book before setting */
        $bookingBeforeDate = strtotime('+ ' . $userData['user_book_before'] . ' Hours', $currentDateTime);

        /* default slots difference */
        $slotsDuration = 15;
        
        /* converting scheduled dates to strtotime to avoid multiple time conversion in loops */
        foreach ($scheduledData as $key => $scheduled) {
            $scheduledData[$key]['start'] = strtotime($scheduled['start']);
            $scheduledData[$key]['end'] = strtotime($scheduled['end']);
        }

        $slots = [];
        foreach ($userAvailability as $avail) {
            $startDateTime = strtotime($avail['start']);
            $endDateTime = strtotime($avail['end']);

            if ($bookingBeforeDate  < $endDateTime) {
                if ($bookingBeforeDate > $startDateTime) {
                    $startDateTime = $bookingBeforeDate;
                }
                while ($startDateTime <= $endDateTime) {
                    /* skip slots of different dates. Proceed for the selected date only */
                    if (date('Y-m-d', $startDateTime) != date('Y-m-d', strtotime($startTime))) {
                        /* set next slot start date */
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        continue;
                    }

                    $end = strtotime('+ ' . $userData['duration'] . ' minutes', $startDateTime);

                    /* check if slot is already booked */
                    $isScheduled = false;
                    foreach ($scheduledData as $scheduled) {
                        if ((($startDateTime <= $scheduled['start'] && $scheduled['start'] < $end) || ($scheduled['end'] > $startDateTime && $scheduled['end'] <= $end)) || ($startDateTime >= $scheduled['start'] && $startDateTime < $scheduled['end']) || ($end > $scheduled['start'] && $end <= $scheduled['end'])) {
                            $isScheduled = true;
                            break;
                        }
                    }

                    /* skip if already booked */
                    if ($isScheduled) {
                        /* set next slot start date */
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        continue;
                    }

                    /* skip if slot end time is greater than the availbility end time. For eg, slot is of 30 mins but left 15 mins till end time */
                    if ($end > $endDateTime) {
                        /* set next slot start date */
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        break;
                    }

                    /* skip if the slots are of next day */
                    if (($startDateTime < strtotime($startTime) || $startDateTime > strtotime($endTime)) || $end < strtotime($startTime)) {
                        /* set next slot start date */
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        continue;
                    }

                    /* Skip if lesson is subscription/recurring and slot end time is greater than the subscription/recurring end time */
                    if($userData['useSubEndDate'] && ($end > strtotime($endTime))) {
                        break;
                    }

                    /* add to slots array */
                    $slots[] = [
                        'start' => date('Y-m-d H:i:s', $startDateTime),
                        'end' => date('Y-m-d H:i:s', $end),
                    ];
                    /* set next slot start date */
                    $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                }
            }
        }
        $availability = [];
        foreach ($slots as $k => $slot) {
            $key = date('Y-m-d', strtotime($slot['start']));
            $availability[$key]['date'] = $key;
            $availability[$key]['slots'][] =  $slot;
        }
        if (empty($availability)) {
            $availability[] = [
                'date' => MyDate::formatDate($startTime, 'Y-m-d', $userData['timezone']),
                'slots' => []
            ];
        }

        return $availability;
    }


}
