<?php

/**
 * Bank Transfer Pay
 * 
 * @author Fatbit Technologies
 */
class BankTransferPay extends Payment implements PaymentInterface
{

    public $order;
    public $pmethod;
    public $settings;

    const KEY = 'BankTransferPay';
    const DB_TBL = 'tbl_bank_transfers';
    const DB_TBL_PREFIX = 'bnktras_';
    /* statuses */
    const PENDING = 0;
    const APPROVED = 1;
    const DECLINED = 2;

    public function __construct(array $order)
    {
        $this->pmethod = [];
        $this->order = $order;
        parent::__construct();
    }

    /**
     * Initialize Payment Method
     * 
     * 1. Load Payment Method
     * 2. Format Payment Settings
     * 3. Validate Payment Settings
     * 
     * @return bool
     */
    public function initPayemtMethod(): bool
    {
        /* Load Payment Method */
        $this->pmethod = PaymentMethod::getByCode(static::KEY);
        if (empty($this->pmethod)) {
            $this->error = Label::getLabel("LBL_PAYEMNT_GATEWAY_NOT_FOUND");
            return false;
        }
        /* Format Payment Settings */
        $settings = json_decode($this->pmethod['pmethod_settings'], 1) ?? [];
        foreach ($settings as $row) {
            $this->settings[$row['key']] = $row['value'];
        }
        /* Validate Payment Settings */
        if (empty($this->settings['account_details'])) {
            $this->error = Label::getLabel("MSG_INCOMPLETE_PAYMENT_GATEWAY_SETUP");
            return false;
        }
        return true;
    }

    /**
     * Get Payment Data
     * 
     * @return bool
     */
    public function getChargeData()
    {
        /* Initialize Payment Method */
        if (!$this->initPayemtMethod()) {
            return false;
        }
        $frm = $this->getPaymentForm();
        $frm->fill(['bnktras_order_id' => $this->order['order_id']]);
        return [
            'frm' => $frm, 'order' => $this->order,
            'accountDetails' => $this->settings['account_details']
        ];
    }

    /**
     * Bank Transfer Callback
     * 
     * @param array $post
     * @return array
     */
    public function callbackHandler(array $post): array
    {
        return $this->returnSuccess();
    }

    /**
     * Bank Transfer Return
     * 
     * @param array $post
     * @return array
     */
    public function returnHandler(array $post): array
    {
        if ($this->order['order_payment_status'] == Order::ISPAID) {
            $this->error = Label::getLabel('LBL_ORDER_ALREADY_PAID');
            return $this->returnError();
        }
        $frm = $this->getPaymentForm();
        if (!$post = $frm->getFormDataFromArray($post)) {
            $this->error = current($frm->getValidationErrors());
            return $this->returnError();
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues([
            'bnktras_status' => static::PENDING,
            'bnktras_datetime' => date('Y-m-d H:i:s'),
            'bnktras_txn_id' => $post['bnktras_txn_id'],
            'bnktras_order_id' => $post['bnktras_order_id'],
            'bnktras_response' => $post['bnktras_response'],
            'bnktras_amount' => $this->order['order_net_amount'],
        ]);
        if (!$record->addNew()) {
            $this->error = $record->getError();
            $db->rollbackTransaction();
            return $this->returnError();
        }
        $recordId = $record->getId();
        $langId = MyUtility::getSystemLanguage()['language_id'] ?? 1;
        $mail = new FatMailer($langId, 'bank_transfer_payment_detail');
        if (!empty($_FILES['bnktras_receipt']['name'])) {
            $file = new Afile(Afile::TYPE_ORDER_PAY_RECEIPT);
            if (!$file->saveFile($_FILES['bnktras_receipt'], $recordId, true)) {
                $this->error = $file->getError();
                $db->rollbackTransaction();
                return $this->returnError();
            }
            $fileData = $file->getSavedFile();
            $mail->setAttachments([CONF_UPLOADS_PATH . $fileData['file_path']]);
        }
        $db->commitTransaction();
        $mail->setVariables([
            '{learner_name}' => $this->order['user_name'],
            '{order_id}' => Order::formatOrderId($this->order['order_id']),
            '{order_link}' => MyUtility::makeFullUrl('Orders', 'view', [$this->order['order_id']], CONF_WEBROOT_BACKEND),
        ]);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);
        return $this->returnSuccess();
    }

    /**
     * Get Payment Form
     * 
     * @return Form
     */
    private function getPaymentForm()
    {
        $frm = new Form('bankTransferPayFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'bnktras_order_id', '');
        $fld->requirements()->setRequired(true);
        $fld->requirements()->setIntPositive();
        $fld = $frm->addTextBox(Label::getLabel('LBL_TRANSACTION_ID'), 'bnktras_txn_id');
        $fld->requirements()->setRequired(true);
        $fld = $frm->addTextArea(Label::getLabel('LBL_TRANSACTION_DETAIL'), 'bnktras_response', '', ['style' => 'height:70px;']);
        $fld->requirements()->setRequired(true);
        $fld = $frm->addFileUpload(Label::getLabel('LBL_TRANSACTION_RECEIPT'), 'bnktras_receipt');
        
        $sizeLabel = Label::getLabel('LBL_FILE_SIZE_SHOULD_BE_LESS_THAN_{FILE-SIZE}_MB');
        $sizeLabel = str_replace('{file-size}',  MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_ORDER_PAY_RECEIPT)), $sizeLabel);
        $extLabel = Label::getLabel('LBL_SUPPORTED_FILE_FORMATS_ARE_{file-formats}');
        $extLabel = str_replace('{file-formats}', implode(", ", static::getAllowedExts()), $extLabel);
        
        $fld->htmlAfterField = '<small>' . $sizeLabel . ' & ' . $extLabel . '</small>';
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SUBMIT_DETAIL'));
        return $frm;
    }

    public static function getAllowedExts()
    {
        return array_values(Afile::getAllowedExts(Afile::TYPE_ORDER_PAY_RECEIPT));
    }

    public static function getPayments(int $orderId): array
    {
        $src = new SearchBase(static::DB_TBL, 'bnktras');
        $src->joinTable(Afile::DB_TBL, 'LEFT JOIN', 'file.file_record_id = bnktras.bnktras_id AND file.file_type=' . Afile::TYPE_ORDER_PAY_RECEIPT, 'file');
        $src->addCondition('bnktras_order_id', '=', $orderId);
        $src->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($src->getResultSet());
    }

    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::PENDING => Label::getLabel('LBL_PENDING'),
            static::APPROVED => Label::getLabel('LBL_APPROVED'),
            static::DECLINED => Label::getLabel('LBL_DECLINED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public static function getById(int $payId)
    {
        $srch = new SearchBase(static::DB_TBL, 'bnktras');
        $srch->addCondition('bnktras_id', '=', $payId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function getBookBeforeHours()
    {
        if (!$this->initPayemtMethod()) {
            return 0;
        }
        return FatUtility::int($this->settings['book_before_hours'] ?? 0);
    }

}
