<?php

/**
 * AppToken Model
 */
class AppToken extends FatModel
{

    const DB_TBL = 'tbl_app_tokens';

    public function __construct()
    {
        parent::__construct();
    }

    public function getToken(int $userId)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('apptkn_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs) ?? [];
    }

    public function getData(string $token)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('apptkn_token', '=', $token);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetch($rs);
    }

    public function setData(array $data)
    {
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues($data);
        if (!$record->addNew($data)) {
            $this->error = Label::getLabel('API_CANNOT_SET_APP_USER');
            return false;
        }
        return true;
    }

    public static function generate(int $userId)
    {
        $authtoken = md5(microtime());
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues([
            'apptkn_user_id' => $userId,
            'apptkn_token' => $authtoken,
            'apptkn_created' => date('Y-m-d H:i:s'),
            'apptkn_device' => $_SERVER['DEVICE'] ?? ''
        ]);
        return $record->addNew() ? $authtoken : false;
    }

    public static function getUserId()
    {
        $headers = MyUtility::getApacheRequestHeaders();
        $token = !empty($headers['authorization']) ? $headers['authorization'] : ($_REQUEST['token'] ?? '');
        if (empty($token)) {
            return 0;
        }
        $token = str_replace("Bearer ", "", $token);
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('apptkn_token', '=', $token);
        $srch->addFld('apptkn_user_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        return $row['apptkn_user_id'] ?? 0;
    }

    public static function clearToken()
    {
        $headers = MyUtility::getApacheRequestHeaders();
        $token = str_replace("Bearer ", "", ($headers['authorization'] ?? ''));
        return FatApp::getDb()->deleteRecords(static::DB_TBL, ['smt' => 'apptkn_token = ?', 'vals' => [$token]]);
    }

}
