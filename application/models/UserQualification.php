<?php

/**
 * This class is used to handle User Qualification
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserQualification extends MyAppModel
{

    const DB_TBL = 'tbl_user_qualifications';
    const DB_TBL_PREFIX = 'uqualification_';
    const EXPERIENCE_EDUCATION = 1;
    const EXPERIENCE_CERTIFICATION = 2;
    const EXPERIENCE_WORK = 3;

    private $userId = 0;

    /**
     * Initialize User Qualification
     * 
     * @param int  $id
     * @param int $userId
     */
    public function __construct(int $id = 0, int $userId = 0)
    {
        parent::__construct(static::DB_TBL, 'uqualification_id', $id);
        $this->userId = $userId;
    }

    /**
     * Get Experience Types
     * 
     * @return array
     */
    public static function getExperienceTypeArr(): array
    {
        return [
            static::EXPERIENCE_EDUCATION => Label::getLabel('LBL_Education'),
            static::EXPERIENCE_CERTIFICATION => Label::getLabel('LBL_Certification'),
            static::EXPERIENCE_WORK => Label::getLabel('LBL_Work_Experience'),
        ];
    }

    /**
     * Get Qualification to Update
     * 
     * @return bool|array
     */
    public function getQualiForUpdate()
    {
        $srch = new SearchBase(UserQualification::DB_TBL, 'uq');
        $srch->doNotCalculateRecords();
        $srch->addCondition('uqualification_user_id', '=', $this->userId);
        $srch->addCondition('uqualification_id', '=', $this->mainTableRecordId);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        return $row;
    }

    /**
     * Get Qualification
     * 
     * @param bool $active
     * @param bool $joinFile
     * @return array
     */
    public function getUQualification(bool $active = true, bool $joinFile = false): array
    {
        $srch = new SearchBase(UserQualification::DB_TBL, 'uqualification');
        if ($joinFile) {
            $srch->joinTable(Afile::DB_TBL, 'LEFT JOIN', 'file.file_record_id = uqualification.uqualification_id and file.file_type = ' . Afile::TYPE_USER_QUALIFICATION_FILE, 'file');
            $srch->addFld(['file.file_id', 'file.file_name']);
        }
        $srch->addCondition('uqualification_user_id', '=', $this->userId);
        if ($active) {
            $srch->addCondition('uqualification_active', '=', AppConstant::ACTIVE);
        }
        $srch->addMultipleFields(['uqualification.*']);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    public static function getForm(): Form
    {
        $frm = new Form('frmQualification');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addHiddenField('', 'uqualification_id', 0);
        $fld->requirements()->setInt();
        $fld = $frm->addSelectBox(Label::getLabel('LBL_Experience_Type'), 'uqualification_experience_type', UserQualification::getExperienceTypeArr(), '', [], Label::getLabel('LBL_Select'));
        $fld->requirements()->setRequired();
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Title'), 'uqualification_title', '', ['placeholder' => Label::getLabel('LBL_Eg:_B.A._English')]);
        $fld->requirements()->setLength(1, 100);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Institution'), 'uqualification_institute_name', '', ['placeholder' => Label::getLabel('LBL_Eg:_Oxford_University')]);
        $fld->requirements()->setLength(1, 100);
        $fld = $frm->addRequiredField(Label::getLabel('LBL_Location'), 'uqualification_institute_address', '', ['placeholder' => Label::getLabel('LBL_Eg:_London')]);
        $fld->requirements()->setLength(1, 100);
        $fld = $frm->addTextArea(Label::getLabel('LBL_Description'), 'uqualification_description', '', ['placeholder' => Label::getLabel('LBL_Eg._Focus_in_Humanist_Literature')]);
        $fld->requirements()->setLength(1, 500);
        $yearArr = range(date('Y'), 1970);
        $fld1 = $frm->addSelectBox(Label::getLabel('LBL_Start_Year'), 'uqualification_start_year', array_combine($yearArr, $yearArr), '', [], '');
        $fld1->requirements()->setRequired();
        $toYearArr = range(date('Y', strtotime("+10 years")), 1970);
        $fld2 = $frm->addSelectBox(Label::getLabel('LBL_End_Year'), 'uqualification_end_year', array_combine($toYearArr, $toYearArr), date('Y'), [], '');
        $fld2->requirements()->setRequired();
        $fld2->requirements()->setCompareWith('uqualification_start_year', 'ge');
        $fld = $frm->addFileUpload(Label::getLabel('LBL_Upload_Certificate'), 'certificate');
        $fld->requirements()->setRequired(false);
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Save_Changes'));
        return $frm;
    }

}
