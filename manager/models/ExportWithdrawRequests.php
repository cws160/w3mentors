<?php

class ExportWithdrawRequests extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::WITHDRAW_REQUESTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $currencySymbol = MyUtility::getSiteCurrency()['currency_code'];
        $this->headers = [
            'withdrawal_id' => Label::getLabel('LBL_ID'),
            'user_name' => Label::getLabel('LBL_USER'),
            'user_email' => Label::getLabel('LBL_EMAIL'),
            'withdrawal_transaction_fee' => Label::getLabel('LBL_TXN_FEE') . '[' . $currencySymbol . ']',
            'withdrawal_amount' => Label::getLabel('LBL_AMOUNT') . '[' . $currencySymbol . ']',
            'account_details' => Label::getLabel('LBL_ACCOUNT'),
            'withdrawal_comments' => Label::getLabel('LBL_COMMENTS'),
            'withdrawal_request_date' => Label::getLabel('LBL_DATE'),
            'withdrawal_status' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'user_first_name', 'user_last_name', 'user_email', 'withdrawal_id',
            'withdrawal_transaction_fee', 'withdrawal_amount', 'withdrawal_comments',
            'withdrawal_request_date', 'withdrawal_status', 'withdrawal_paypal_email_id',
            'withdrawal_bank', 'withdrawal_account_holder_name', 'withdrawal_account_number',
            'withdrawal_ifc_swift_code', 'withdrawal_bank_address', 'pmethod_code'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $labelBankName = Label::getLabel('LBL_BANK_NAME');
        $labelAcName = Label::getLabel('LBL_AC_NAME');
        $labelAcNumber = Label::getLabel('LBL_AC_NUMBER');
        $labelIfsc = Label::getLabel('LBL_IFSC/SWIFT_CODE');
        $labelBankAddress = Label::getLabel('LBL_BANK_ADDRESS');
        $labelPaypalEmail = Label::getLabel('LBL_PAYPAL_EMAIL');
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $txt = '';
            switch ($row["pmethod_code"]) {
                case BankPayout::KEY:
                    $txt .= $labelBankName . ': ' . $row["withdrawal_bank"] . ' | ';
                    $txt .= $labelAcName . ': ' . $row["withdrawal_account_holder_name"] . ' | ';
                    $txt .= $labelAcNumber . ': ' . $row["withdrawal_account_number"] . ' | ';
                    $txt .= $labelIfsc . ': ' . $row["withdrawal_ifc_swift_code"] . ' | ';
                    if (!empty($row["withdrawal_bank_address"])) {
                        $txt .= $labelBankAddress . ': ' . nl2br($row["withdrawal_bank_address"]);
                    }
                    break;
                case PaypalPayout::KEY:
                    $txt .= $labelPaypalEmail . ': ' . $row["withdrawal_paypal_email_id"];
                    break;
            }
            fputcsv($fh, [
                'withdrawal_id' => WithdrawRequest::formatRequestNumber($row["withdrawal_id"]),
                'user_name' => $row["user_first_name"] . ' ' . $row["user_last_name"],
                'user_email' => $row["user_email"],
                'withdrawal_transaction_fee' => MyUtility::formatMoney($row['withdrawal_transaction_fee'], false),
                'withdrawal_amount' => MyUtility::formatMoney($row['withdrawal_amount'], false),
                'account_details' => $txt,
                'withdrawal_comments' => $row['withdrawal_comments'],
                'withdrawal_request_date' => MyDate::formatDate($row['withdrawal_request_date']),
                'withdrawal_status' => WithdrawRequest::getStatuses($row['withdrawal_status']),
            ]);
            $count++;
        }
        return $count;
    }
}
