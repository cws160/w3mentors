<?php

/**
 * Teacher Reports Controller is used for handling Teacher Reports
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeacherReportsController extends DashboardController
{

    /**
     * Initialize Teacher Reports
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    public function getStatisticalData()
    {
        $duration = FatApp::getPostedData('duration', FatUtility::VAR_INT, MyDate::TYPE_TODAY);
        $response = ['earning' => 0, 'sessionCount' => 0, 'graphData' => []];
        $statistics = new Statistics($this->siteUserId);
        $earningData = $statistics->getEarning($duration, Transaction::TYPE_TEACHER_PAYMENT, true);
        $soldSessions = $statistics->getSoldSessions($duration, $this->siteLangId, true);
        $response['earning'] = html_entity_decode(MyUtility::formatMoney($earningData['earning']));
        $response['sessionCount'] = $soldSessions['sessionCount'];
        $graphData = ['earningData' => $earningData['earningData'], 'sessionData' => $soldSessions['sessionData']];
        $response['graphData'] = $this->formatGraphArray($graphData, $duration);
        FatUtility::dieJsonSuccess($response);
    }

    /**
     * Format Graph Array
     * 
     * @param array $response
     * @param int $durationType
     * @return array
     */
    private function formatGraphArray(array $response, int $durationType): array
    {
        $durationTypeArray = MyDate::getDurationTypesArr();
        $datetime = MyDate::getStartEndDate($durationType, $this->siteTimezone);
        $startDate = date('Y-m-d', strtotime($datetime['startDate']));
        /* [-1 day ] this is only for view purpose */
        $days = ($durationType == MyDate::TYPE_ALL) ? 0 : 1;
        $endDate = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
        
        $durationRow = $durationTypeArray[$durationType] . ' ' . MyDate::formatDate($startDate, 'M d, Y');
        if($durationType != MyDate::TYPE_TODAY){
            $durationRow .= ' - '. MyDate::formatDate($endDate, 'M d, Y');
        }

        $graphArray = [
            'column' => [
                'durationType' => $durationRow,
                'earningLabel' => Label::getLabel('LBL_EARNING'),
                'sessionSoldLabel' => Label::getLabel('LBL_SESSIONS_SOLD')
            ],
            'rowData' => []
        ];
        $rowData = [];
        if (!empty($response['earningData'])) {
            $rowData = $this->formatEarningData($response['earningData'], $rowData, $durationType);
        }
        if (!empty($response['sessionData'])) {
            $rowData = $this->formatSessionData($response['sessionData'], $rowData, $durationType);
        }
        ksort($rowData);
        $graphArray['rowData'] = array_values($rowData);
        return $graphArray;
    }

    /**
     * Format Earning Data
     * 
     * @param array $earningData
     * @param array $rowData
     * @param int $durationType
     * @return array
     */
    private function formatEarningData(array $earningData, array $rowData, int $durationType): array
    {
        $earningLabel = Label::getLabel('LBL_EARNING');
        $sessionSoldLabel = Label::getLabel('LBL_LESSONS_SOLD');

        foreach ($earningData as $key => $value) {
            $date = $this->getDateFormat($durationType, $value['usrtxn_datetime']);
            $rowData[$key] = [
                $date,
                MyUtility::formatMoney($value['earning'], false),
                $earningLabel . " " . html_entity_decode(MyUtility::formatMoney($value['earning'])),
                0, $sessionSoldLabel . " 0"
            ];
        }
        return $rowData;
    }

    /**
     * Format Session Data
     * 
     * @param array $sessionData
     * @param array $rowData
     * @param int $durationType
     * @return array
     */
    private function formatSessionData(array $sessionData, array $rowData, int $durationType): array
    {
        $earningLabel = Label::getLabel('LBL_EARNING');
        $sessionSoldLabel = Label::getLabel('LBL_SESSIONS_SOLD');
        foreach ($sessionData as $key => $value) {
            if (array_key_exists($key, $rowData)) {
                $rowData[$key][3] = $value['sessionCount'];
                $rowData[$key][4] = $sessionSoldLabel . " " . $value['sessionCount'];
                continue;
            }
            $date = $this->getDateFormat($durationType, $value['order_addedon']);
            $rowData[$key] = [$date, 0, $earningLabel . " " . MyUtility::formatMoney(0), $value['sessionCount'], $sessionSoldLabel . " " . $value['sessionCount']];
        }
        return $rowData;
    }

    /**
     * Get Date Format
     * 
     * @param int $durationType
     * @param string $date
     * @return string
     */
    private function getDateFormat(int $durationType, string $date): string
    {
        switch ($durationType) {
            case MyDate::TYPE_TODAY:
                $format = (FatApp::getConfig('FRONTEND_TIME_FORMAT') == MyDate::FORMAT_12_HR) ? 'h:i A' : 'H:i';
                $date = MyDate::formatDate($date, $format);
                break;
            case MyDate::TYPE_THIS_YEAR:
            case MyDate::TYPE_LAST_YEAR:
                $date = MyDate::formatDate($date, 'M Y');
                break;
            default:
                $date = MyDate::formatDate($date, 'M d, Y');
                break;
        }
        return $date;
    }

}
