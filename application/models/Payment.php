<?php

/**
 * Base Payment Model
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class Payment extends FatModel
{

    protected $process;
    protected $success;
    protected $failed;
    protected $url;

    const DB_TBL_RESPONSE = 'tbl_payment_gateway_response';
    const PROCESS = 1;
    const SUCCESS = 2;
    const FAILED = 3;

    public function __construct()
    {
        parent::__construct();
        $this->process = [];
        $this->success = [];
        $this->failed = [];
    }

    /**
     * Log Payment Gateway Response
     * 
     * @param int $orderId
     */
    public static function logResponse(int $orderId)
    {
        $record = new TableRecord(static::DB_TBL_RESPONSE);
        $record->assignValues([
            'pgres_order_id' => $orderId,
            'pgres_created' => date('Y-m-d H:i:s'),
            'pgres_response' => json_encode($_REQUEST),
        ]);
        return $record->addNew();
    }

    /**
     * Return Error
     * 
     * @param string|array $data
     * @return array
     */
    public function returnError($data = null): array
    {
        $res = [
            'msg' => $this->error,
            'status' => AppConstant::NO,
            'url' => $this->getFailedUrl(),
        ];
        if (is_array($data)) {
            $res = array_merge($res, $data);
        } elseif (!empty($data)) {
            $res['msg'] = $data;
        }
        return $res;
    }

    /**
     * Return Success
     * 
     * @param string|array $data
     * @return array
     */
    public function returnSuccess($data = null): array
    {
        $res = [
            'msg' => Label::getLabel('LBL_ACTION_PERFORMED_SUCCESSFULLY'),
            'status' => AppConstant::YES,
            'url' => $this->getSuccessUrl(),
        ];
        if (is_array($data)) {
            $res = array_merge($res, $data);
        } elseif (!empty($data)) {
            $res['msg'] = $data;
        }
        return $res;
    }

    /**
     * Get Payment Process URL
     * 
     * @return string
     */
    public function getProcessUrl(): string
    {

        return MyUtility::makeFullUrl('Payment', 'process', [$this->order['order_id']]);
    }

    /**
     * Get Payment Success URL
     * 
     * @return string
     */
    public function getSuccessUrl(): string
    {
        return MyUtility::makeFullUrl('Payment', 'success', [$this->order['order_id']]);
    }

    /**
     * Get Payment Failed URL
     * 
     * @return string
     */
    public function getFailedUrl(): string
    {
        return MyUtility::makeFullUrl('Payment', 'failed', [$this->order['order_id']]);
    }

    /**
     * Get Payment Process Data
     * 
     * @return array
     */
    public function getProcessData(): array
    {
        return $this->process;
    }

    /**
     * Get Payment Success Data
     * 
     * @return array
     */
    public function getSuccessData(): array
    {
        return $this->success;
    }

    /**
     * Get Payment Failed Data
     * 
     * @return array
     */
    public function getFailedData(): array
    {
        return $this->failed;
    }

    /**
     * Set Payment Process Data
     * 
     * @return array
     */
    public function setProcessData(array $params): array
    {
        return $this->process = $params;
    }

    /**
     * Set Payment Success Data
     * 
     * @return array
     */
    public function setSuccessData(array $params): array
    {
        return $this->success = $params;
    }

    /**
     * Set Payment Failed Data
     * 
     * @return array
     */
    public function setFailedData(array $params): array
    {
        return $this->failed = $params;
    }

}
