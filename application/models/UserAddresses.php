<?php

use Google\Service\Analytics\UserRef;

/**
 * This class is used to handle User Addresses
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserAddresses extends MyAppModel
{

    const DB_TBL = 'tbl_user_addresses';
    const DB_TBL_PREFIX = 'usradd_';
    const TYPE_HOME = 1;
    const TYPE_OFFICE = 2;
    const TYPE_OTHER = 3;

    private $userId = 0;

    /**
     * Initialize User addresses
     * 
     * @param int  $id
     * @param int $userId
     */
    public function __construct(int $userId, int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'usradd_id', $id);
        $this->userId = $userId;
    }


    public static function getAddressTypes(int $key = null, int $langId = 0)
    {
        $arr = [
            static::TYPE_HOME => Label::getLabel('LBL_HOME', $langId),
            static::TYPE_OFFICE => Label::getLabel('LBL_OFFICE', $langId),
            static::TYPE_OTHER => Label::getLabel('LBL_OTHER', $langId),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public function saveRecord($post)
    {
        if ($this->getMainTableRecordId()) {
            $post['usradd_updated'] = date('Y-m-d H:i:s');
        } else {
            $post['usradd_created'] = date('Y-m-d H:i:s');
        }
        if ($post['usradd_default']) {
            $this->clearDefault();
        }
        if (!$post['usradd_default'] && !$this->validateDefault()) {
            $post['usradd_default'] = 1;
        }
        $post['usradd_user_id'] = $this->userId;
        $this->assignValues($post);
        if (!$this->save()) {
            $this->getError();
            return false;
        }
        return true;
    }


    /**
     * Get addresses
     * @return array
     */
    public function getAll(int $langId, $addresses = [], $deleted = false): array
    {
        $srch = new SearchBase(UserAddresses::DB_TBL, 'usradd');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'usr.user_id = usradd.usradd_user_id', 'usr');
        $srch->joinTable(Country::DB_TBL, 'INNER JOIN', 'c.country_id = usradd.usradd_country_id', 'c');
        $on = 'clang.countrylang_country_id = c.country_id AND clang.countrylang_lang_id = ' . $langId;
        $srch->joinTable(Country::DB_TBL_LANG, 'LEFT JOIN', $on, 'clang');
        $srch->joinTable(State::DB_TBL, 'INNER JOIN', 'st.state_id = usradd.usradd_state_id', 'st');
        $srch->joinTable(State::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = st.state_id AND stlang.stlang_lang_id = ' . $langId, 'stlang');
        $srch->addCondition('usradd_user_id', '=', $this->userId);
        if (count($addresses) > 0) {
            $srch->addDirectCondition('usradd.usradd_id IN (' . implode(',', $addresses) . ')');
        }
        if ($deleted) {
            $srch->addDirectCondition('usradd_deleted IS NULL');
        }
        $srch->addMultipleFields(['usradd.*', 'IFNULL(stlang.state_name, st.state_identifier) AS state_name', 'IFNULL(clang.country_name, c.country_identifier) AS country_name']);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get addresses
     * @return array
     */
    public function getOptions(int $langId): array
    {
        $srch = new SearchBase(UserAddresses::DB_TBL, 'usradd');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'usr.user_id = usradd.usradd_user_id', 'usr');
        $srch->joinTable(Country::DB_TBL, 'INNER JOIN', 'c.country_id = usradd.usradd_country_id', 'c');
        $on = 'clang.countrylang_country_id = c.country_id AND clang.countrylang_lang_id = ' . $langId;
        $srch->joinTable(Country::DB_TBL_LANG, 'LEFT JOIN', $on, 'clang');
        $srch->joinTable(State::DB_TBL, 'INNER JOIN', 'st.state_id = usradd.usradd_state_id', 'st');
        $srch->joinTable(State::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = st.state_id AND stlang.stlang_lang_id = ' . $langId, 'stlang');
        $srch->addCondition('usradd_user_id', '=', $this->userId);
        $srch->addDirectCondition('usradd_deleted IS NULL');
        $srch->addMultipleFields(['usradd.usradd_id', 'usradd.usradd_address', 'usradd.usradd_city', 'IFNULL(stlang.state_name, st.state_identifier) as state_name', 'usradd.usradd_zipcode', 'usradd.usradd_zipcode', 'IFNULL(clang.country_name, c.country_identifier) country_name']);
        $srch->doNotCalculateRecords();
        $addresses = FatApp::getDb()->fetchAll($srch->getResultSet());
        if (empty($addresses)) {
            return [];
        }
        $data = [];
        foreach ($addresses as $address) {
            $data[$address['usradd_id']] = self::format($address);
        }
        return $data;
    }

    public  function clearDefault()
    {
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('usradd_default', 0);
        $where = ['smt' => 'usradd_user_id = ?', 'vals' => [$this->userId]];
        if (!$record->update($where)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public function validateDefault()
    {
        $srch = new SearchBase(static::DB_TBL, 'usradd');
        $srch->addCondition('usradd_user_id', '=', $this->userId);
        $srch->addDirectCondition('usradd_deleted IS NULL');
        $srch->addCondition('usradd_default', '=', AppConstant::YES);
        if ($this->getMainTableRecordId() > 0) {
            $srch->addCondition('usradd_id', '!=', $this->getMainTableRecordId());
        }
        $srch->getResultSet();
        return $srch->recordCount() > 0 ? AppConstant::YES : AppConstant::NO;
    }

    public static function getDefault($userId, $langId)
    {
        $srch = new SearchBase(static::DB_TBL, 'usradd');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'usr.user_id = usradd.usradd_user_id', 'usr');
        $srch->joinTable(Country::DB_TBL, 'INNER JOIN', 'c.country_id = usradd.usradd_country_id', 'c');
        $on = 'clang.countrylang_country_id = c.country_id AND clang.countrylang_lang_id = ' . $langId;
        $srch->joinTable(Country::DB_TBL_LANG, 'LEFT JOIN', $on, 'clang');
        $srch->joinTable(State::DB_TBL, 'INNER JOIN', 'st.state_id = usradd.usradd_state_id', 'st');
        $srch->joinTable(State::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = st.state_id AND stlang.stlang_lang_id = ' . $langId, 'stlang');
        $srch->addCondition('usradd_user_id', '=', $userId);
        $srch->addCondition('usradd_default', '=', AppConstant::YES);
        $srch->addMultipleFields(['usradd.*', 'IFNULL(stlang.state_name, st.state_identifier) AS state_name', 'IFNULL(clang.country_name, c.country_identifier) AS country_name']);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get formatted address
     *
     * @param int $langId
     * @return string
     */
    public function getFormattedAddress(int $langId)
    {
        $address = $this->getAddressById($langId);
        if (empty($address)) {
            return '';
        }
        return self::format($address);
    }

    public function getAddressById(int $langId)
    {
        $srch = new SearchBase(UserAddresses::DB_TBL, 'usradd');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'usr.user_id = usradd.usradd_user_id', 'usr');
        $srch->joinTable(Country::DB_TBL, 'INNER JOIN', 'c.country_id = usradd.usradd_country_id', 'c');
        $on = 'clang.countrylang_country_id = c.country_id AND clang.countrylang_lang_id = ' . $langId;
        $srch->joinTable(Country::DB_TBL_LANG, 'LEFT JOIN', $on, 'clang');
        $srch->joinTable(State::DB_TBL, 'INNER JOIN', 'st.state_id = usradd.usradd_state_id', 'st');
        $srch->joinTable(State::DB_TBL_LANG, 'LEFT JOIN', 'stlang.stlang_state_id = st.state_id AND stlang.stlang_lang_id = ' . $langId, 'stlang');
        $srch->addCondition('usradd.usradd_id', '=', $this->getMainTableRecordId());
        if ($this->userId) {
            $srch->addCondition('usradd.usradd_user_id', '=', $this->userId);
        }
        $srch->addDirectCondition('usradd.usradd_deleted IS NULL');
        $srch->addMultipleFields(['usradd.*', 'IFNULL(stlang.state_name, st.state_identifier) AS state_name', 'IFNULL(clang.country_name, c.country_identifier) AS country_name']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function remove()
    {
        $addressId = $this->getMainTableRecordId();
        $address = $this->getAddressById(0);
        if (empty($address)) {
            $this->error = Label::getLabel('LBL_ADDRESS_NOT_FOUND');
            return false;
        }
        if ($address['usradd_user_id'] != $this->userId) {
            $this->error = Label::getLabel('LBL_UNAUTHORIZED_ACTION');
            return false;
        }
        /* check default address */
        if ($address['usradd_default'] == AppConstant::YES) {
            $this->error = Label::getLabel('LBL_CANNOT_DELETE_DEFAULT_ADDRESS');
            return false;
        }
        $db = FatApp::getDb();
        /* check if address is used */
        $srch = new SearchBase(GroupClass::DB_TBL);
        $srch->addCondition('grpcls_address_id', '=', $addressId);
        $srch->addCondition('grpcls_start_datetime', '>', date('Y-m-d H:i:s'));
        $srch->addCondition('grpcls_status', '!=', GroupClass::CANCELLED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if ($db->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_CANNOT_DELETE_THIS_ADDRESS._IT_IS_ASSOCIATED_WITH_CLASSES');
            return false;
        }

        $this->setFldValue('usradd_deleted', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    public static function format(array $address)
    {
        return $address['usradd_address'] . ', ' . $address['usradd_city'] . ', ' . $address['state_name'] . ', ' . $address['usradd_zipcode'] . ', ' . $address['country_name'];
    }
}
