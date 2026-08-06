<?php

/**
 * This class is used to handle GiftCard
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Giftcard extends MyAppModel
{

    const DB_TBL = 'tbl_order_giftcards';
    const DB_TBL_PREFIX = 'ordgift_';
    const STATUS_UNUSED = 0;
    const STATUS_USED = 1;
    const STATUS_CANCELLED = 2;
    const RECEIVED = 2;
    const PURCHASED = 1;

    /**
     * Initialize GiftCard
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'ordgift_id', $id);
    }

    /**
     * Get Types
     * 
     * @param int $key
     * @return string|array
     */
    public static function getTypes(int $key = null)
    {
        $arr = [
            static::PURCHASED => Label::getLabel('LBL_PURCHASED'),
            static::RECEIVED => Label::getLabel('LBL_RECEIVED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get StFatuses
     * 
     * @param int $key
     * @return string|array
     */
    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::STATUS_USED => Label::getLabel('LBL_USED'),
            static::STATUS_UNUSED => Label::getLabel('LBL_UNUSED'),
            static::STATUS_CANCELLED => Label::getLabel('LBL_CANCELLED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Send Mail To Admin And Recipient
     * 
     * @param int $orderId
     * @return bool
     */
    public function sendMailToAdminAndRecipient(int $orderId): bool
    {
        $srch = new SearchBase(Giftcard::DB_TBL, 'ordgift');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'ordgift.ordgift_order_id = orders.order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = orders.order_user_id', 'user');
        $srch->addCondition('ordgift_order_id', '=', $orderId);
        $srch->addMultipleFields([
            'ordgift_receiver_email',
            'ordgift_receiver_name',
            'ordgift_code',
            'order_net_amount',
            'user.user_first_name',
            'user.user_last_name',
            'user.user_lang_id'
        ]);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $giftcard = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($giftcard)) {
            $this->sendEmailToAdmin($giftcard);
            $this->sendEmailToRecipient($giftcard);
        }
        return true;
    }

    /**
     * Send Email To Admin
     * 
     * @param array $giftcard
     * @return bool
     */
    public function sendEmailToAdmin(array $giftcard): bool
    {
        $currencyData = MyUtility::getSystemCurrency();
        $langId = MyUtility::getSystemLanguage()['language_id'];
        $vars = [
            '{sender_name}' => $giftcard['user_first_name'] . ' ' . $giftcard['user_last_name'],
            '{recipient_name}' => $giftcard['ordgift_receiver_name'],
            '{recipient_email}' => $giftcard['ordgift_receiver_email'],
            '{giftcard_code}' => $giftcard['ordgift_code'],
            '{giftcard_amount}' => MyUtility::formatMoney($giftcard['order_net_amount'])
        ];
        $mail = new FatMailer($langId, 'giftcard_admin');
        $mail->setVariables($vars);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Send Email To Recipient
     * 
     * @param array $giftcard
     * @return type
     */
    public function sendEmailToRecipient(array $giftcard)
    {
        $receiver = User::getByEmail($giftcard['ordgift_receiver_email']);
        $giftcard['user_lang_id'] = FatApp::getConfig('CONF_DEFAULT_LANG');
        if (!empty($receiver['user_email'])) {
            $giftcard['user_lang_id'] = $receiver['user_lang_id'];
        }
        $vars = [
            '{sender_name}' => $giftcard['user_first_name'] . ' ' . $giftcard['user_last_name'],
            '{recipient_name}' => $giftcard['ordgift_receiver_name'],
            '{giftcard_code}' => $giftcard['ordgift_code'],
            '{contact_us_email}' => FatApp::getConfig('CONF_CONTACT_EMAIL')
        ];
        $mail = new FatMailer($giftcard['user_lang_id'], 'giftcard_recipient');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$giftcard['ordgift_receiver_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Redeem GiftCard
     * 
     * @param string $code
     * @param int $userId
     * @return bool
     */
    public function redeem(string $code, int $userId): bool
    {
        if (!$card = $this->getCardForRedeem($code, $userId)) {
            $this->error = Label::getLabel('LBL_INVALID_OR_EXPIRED_GIFTCARD');
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues([
            "ordgift_id" => $card['ordgift_id'],
            "ordgift_status" => static::STATUS_USED,
            "ordgift_usedon" => date("Y-m-d H:i:s")
        ]);
        if (!$record->addNew([], $record->getFlds())) {
            $db->rollbackTransaction();
            $this->error = $record->getError();
            return false;
        }
        $user = User::getAttributesById($userId, ['user_lang_id']);
        $comment = Label::getLabel('LBL_GIFTCARD_REDEEM_TO_WALLET_{amount}_BY_GIFT_CODE_{code}', $user['user_lang_id']);
        $comment = str_replace(['{amount}', '{code}'], [MyUtility::formatMoney($card['order_net_amount']), $card['ordgift_code']], $comment);
        $transObj = new Transaction($userId, Transaction::TYPE_GFCARD_REDEEM);
        if (!$transObj->credit($card['order_net_amount'], $comment)) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($transObj->getError());
        }
        $transObj->sendEmail();
        $db->commitTransaction();
        $this->sendRedeemNotification($card, $userId);
        $notifiVar = ['{amount}' => MyUtility::formatMoney(abs($card['order_net_amount'])), '{reason}' => Label::getLabel('LBL_WALLET_ORDER_PAYMENT', $card['receiver_lang_id'])];
            $notifi = new Notification($userId, Notification::TYPE_WALLET_CREDIT);
            $notifi->sendNotification($notifiVar);
        return true;
    }

    /**
     * Send Redeem Notification
     * 
     * @param array $card
     */
    private function sendRedeemNotification(array $card)
    {
        $notifi = new Notification($card['sender_id'], Notification::TYPE_REDEEM_GIFTCARD);
        $notifi->sendNotification([]);
        $vars = [
            '{sender_name}' => $card['sender_first_name'] . ' ' . $card['sender_last_name'],
            '{receiver_name}' => $card['receiver_first_name'] . ' ' . $card['receiver_last_name'],
            '{giftcard_code}' => $card['ordgift_code']
        ];
        $mail = new FatMailer($card['sender_lang_id'], "giftcard_redeem");
        $mail->setVariables($vars);
        $mail->sendMail([$card['sender_email']]);
    }

    /**
     * Get Card For Redeem
     * 
     * @param string $code
     * @param int $userId
     * @return null|array
     */
    public function getCardForRedeem(string $code, int $userId)
    {
        $srch = new SearchBase(static::DB_TBL, 'giftcard');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'giftcard.ordgift_order_id = orders.order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'sender.user_id = orders.order_user_id', 'sender');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'receiver.user_id = giftcard.ordgift_receiver_id', 'receiver');
        $srch->addMultipleFields([
            'sender.user_first_name as sender_first_name',
            'sender.user_last_name as sender_last_name',
            'sender.user_lang_id as sender_lang_id',
            'sender.user_email as sender_email',
            'sender.user_id as sender_id',
            'receiver.user_first_name as receiver_first_name',
            'receiver.user_last_name as receiver_last_name',
            'receiver.user_lang_id as receiver_lang_id',
            'ordgift_id', 'ordgift_code', 'order_net_amount'
        ]);
        $srch->addCondition('order_user_id', '!=', $userId);
        $srch->addCondition('ordgift_receiver_id', '=', $userId);
        $srch->addCondition('ordgift_code', '=', $code);
        $srch->addCondition('ordgift_status', '=', static::STATUS_UNUSED);
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('order_status', '=', Order::STATUS_COMPLETED);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Generate Giftcard Code
     * 
     * @return type
     */
    private function generateGiftcardCode()
    {
        $cardCode = strtoupper(FatUtility::getRandomString(6));
        if ($this->checkUniqueGiftcardCode($cardCode)) {
            return $cardCode;
        } else {
            $this->generateGiftcardCode();
        }
    }

    /**
     * Check Unique Giftcard Code
     * 
     * @param string $cardCode
     * @return boolean
     */
    private function checkUniqueGiftcardCode(string $cardCode)
    {
        $srch = new SearchBase(Giftcard::DB_TBL);
        $srch->addCondition('ordgift_code', '=', $cardCode);
        $srch->addMultipleFields(['ordgift_code']);
        $rs = $srch->getResultSet();
        $row = FatApp::getDb()->fetch($rs);
        if (empty($row)) {
            return true;
        }
        return false;
    }
}
