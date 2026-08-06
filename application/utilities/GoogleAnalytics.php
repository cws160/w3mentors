<?php

function warning_handler()
{
    throw new Exception();
}

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;

/**
 * A Common Google Analytic Utility  
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GoogleAnalytics extends FatModel
{

    protected $client;
    protected $startDate;
    protected $endDate;
    protected $dimensions = [];
    protected $metrics = [];

    const Events = ['first_visit', 'book_lesson', 'book_class', 'book_course', 'book_trial_lesson', 'book_trial_course', 'confirm_order','book_subscription_plan'];

    /**
     * Returns Generated Report data available from Google Analytic
     * 
     * @return mix
     */
    private function getMeasurements()
    {
        $propertyId = FatApp::getConfig('CONF_ANALYTICS_TABLE_ID', FatUtility::VAR_STRING, '');
        if (empty($propertyId)) {
            $this->error = Label::getLabel('LBL_PROPERTY_ID_NOT_CONFIGURED');
            return false;
        }
        // Make an API call.
        try {
            set_error_handler('warning_handler', E_WARNING);
            $data = $this->client->runReport([
                'property' => 'properties/' . $propertyId,
                'dateRanges' => [
                    new DateRange(['start_date' => $this->startDate, 'end_date' => $this->endDate]),
                ],
                'dimensions' => $this->dimensions,
                'metrics' => $this->metrics
            ]);
            restore_error_handler();
            return $data;
        } catch (\Exception $e) {
            $this->error = Label::getLabel('LBL_SOMETHING_WENT_WRONG_TRY_AGAIN');
            return false;
        }
    }

    /**
     * Get Events tracking data from Google Analytic
     * @param $startDate
     * @param $endDate
     * @return array|boolean
     */
    public function getEventMeasurements(string $startDate = '', string $endDate = '')
    {
        if (!$this->getClient()) {
            return false;
        }
        $this->startDate = (empty($startDate)) ? '7daysAgo' : $startDate;
        $this->endDate = (empty($endDate)) ? 'today' : $endDate;
        $this->dimensions = [new Dimension(['name' => 'eventName'])];
        $this->metrics = [new Metric(['name' => 'eventCount'])];
        if (!$response = $this->getMeasurements()) {
            return false;
        }
        $records = $this->format($response);
        foreach ($records as $key => $value) {
            if (!in_array($key, static::Events)) {
                unset($records[$key]);
            }
        }
        return $records;
    }

    /**
     * Get user acquisition tracking data from Google Analytic
     * @param $startDate
     * @param $endDate
     * @return array|boolean
     */
    public function getTrafficMeasurements(string $startDate = '', string $endDate = '')
    {
        if (!$this->getClient()) {
            return false;
        }
        $this->startDate = (empty($startDate)) ? '7daysAgo' : $startDate;
        $this->endDate = (empty($endDate)) ? 'today' : $endDate;
        $this->dimensions = [new Dimension(['name' => 'sessionDefaultChannelGroup'])];
        $this->metrics = [new Metric(['name' => 'totalUsers'])];
        if (!$response = $this->getMeasurements()) {
            return false;
        }
        return $this->format($response);
    }

    private function format($response)
    {
        $recordKeys = [];
        $recordVals = [];
        foreach ($response->getRows() as $row) {
            foreach ($row->getDimensionValues() as $dimensionValue) {
                $recordKeys[] = $dimensionValue->getValue();
            }
            foreach ($row->getMetricValues() as $metricValue) {
                $recordVals[] = $metricValue->getValue();
            }
        }
        return array_combine($recordKeys, $recordVals);
    }

    private function getClient()
    {
        if (!$authConfig = static::getClientJson()) {
            $this->error = Label::getLabel('LBL_GOOGLE_ANALYTICS_CONFIGURATION_ERROR_MSG');
            return false;
        }
        try {
            $this->client = new BetaAnalyticsDataClient(['credentials' => $authConfig]);
        } catch (\Exception $e) {
            $this->error = Label::getLabel('LBL_COULD_NOT_VERIFY_CREDENTIALS');
            return false;
        }
        return $this->client;
    }

    /**
     * Get Client JSON
     * 
     * @return bool|string
     */
    private static function getClientJson()
    {
        $authConfig = FatApp::getConfig('CONF_GOOGLE_ANALYTICS_CLIENT_JSON', FatUtility::VAR_STRING, '');
        $authConfig = json_decode($authConfig, true);
        if (!$authConfig || empty($authConfig)) {
            return false;
        }
        return $authConfig;
    }

}
