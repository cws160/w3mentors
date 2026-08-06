<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');

$userData = ['user_book_before' => $bookingBefore, 'duration' => $duration, 'timezone' => $timezone];
$availability = Availability::getAvailabiltySlots($startTime, $endTime, $scheduledData, $userAvailability, $userData);

$data['calendarDays'] = $calendarDays;
$data['availability'] = array_values($availability);
$data['msg'] = Label::getLabel('API_TEACHER_AVAILABILITY');
MyUtility::dieJsonSuccess($data);
