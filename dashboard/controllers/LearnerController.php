<?php

/**
 * Learner Controller is used for handling Learners
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class LearnerController extends DashboardController
{

    /**
     * Initialize Learner
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        MyUtility::setUserType(User::LEARNER);
    }

    /**
     * Render Learner's Dashboard Homepage
     */
    public function index()
    {
        $lessStatsCount = (new Lesson(0, $this->siteUserId, $this->siteUserType))->getLessStatsCount();
        $schClassStats = (new OrderClass(0, $this->siteUserId, $this->siteUserType))->getSchedClassStats();
        $courseStats = (new OrderCourse(0, $this->siteUserId, $this->siteUserType, $this->siteLangId))->getCourseStats();
        $frmSrch = static::getSearchForm();
        $this->sets([
            'frmSrch' => $frmSrch,
            'schLessonCount' => $lessStatsCount['schLessonCount'],
            'totalLesson' => $lessStatsCount['totalLesson'],
            'totalClasses' => $schClassStats['totalClasses'],
            'totalCourses' => $courseStats['totalCourses'],
            'walletBalance' => User::getWalletBalance($this->siteUserId),
        ]);
        if (API_CALL) {
            $file = new Afile(Afile::TYPE_USER_PROFILE_IMAGE);
            $this->set('userImage', $file->getFile($this->siteUserId));
            $this->set('payoutMethods', PaymentMethod::getPayouts());
            $this->sets([
                'topRatedTeachers' => TeacherSearch::getTopRatedTeachers($this->siteLangId, 0),
                'upComingLessons' => (new Lesson(0, $this->siteUserId, User::LEARNER))->getUpComingLesson($this->siteLangId),
                'bookedClasses' => (new OrderClass(0, $this->siteUserId, User::LEARNER))->getUpComingClesss($this->siteLangId),
                'popularLanguages' => TeachLanguage::getPopularLangs($this->siteLangId),
                'slides' => Slide::getSlides(),
                'slideImages' => Slide::getSlideImages(array_keys(Slide::getSlides()), $this->siteLangId),
                'upComingClasses' => (new GroupClassSearch($this->siteLangId, $this->siteUserId, $this->siteUserType))->getUpcomingClasses(), 
            ]);
        } else {
            $this->_template->addJs([
                'issues/page-js/common.js',
                'lessons/page-js/common.js',
                'plans/page-js/common.js',
                'js/moment.min.js',
                'js/app.timer.js',
                'js/fullcalendar-luxon.min.js',
                'js/fullcalendar.min.js',
                'js/fullcalendar-luxon-global.min.js',
                'js/fateventcalendar.js',
                'attach-quizzes/page-js/index.js'
            ]);
        }
        $this->_template->render();
    }

    /**
     * Toggle Teacher Favorite
     */
    public function toggleTeacherFavorite($markedUnfav = 0)
    {

        $teacherId = FatApp::getPostedData('teacher_id', FatUtility::VAR_INT, 0);
        if ($teacherId == $this->siteUserId) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $db = FatApp::getDb();
        $srch = new TeacherSearch($this->siteLangId, $this->siteUserId, User::LEARNER);
        $srch->applyPrimaryConditions();
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addCondition('teacher.user_id', '=', $teacherId);
        $srch->addMultipleFields(['teacher.user_id', 'teacher.user_first_name', 'teacher.user_last_name']);
        $teacher = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($teacher)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $message = '';
        $action = 'N';
        $srch = new SearchBase(User::DB_TBL_TEACHER_FAVORITE, 'uft');
        $srch->addCondition('uft_user_id', '=', $this->siteUserId);
        $srch->addCondition('uft_teacher_id', '=', $teacherId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        if (!$db->fetch($srch->getResultSet())) {
            if($markedUnfav == AppConstant::YES) {
                MyUtility::dieJsonSuccess(Label::getLabel('LBL_REMOVED_FROM_FAVOURITES'));
            }
            $userObj = new User($this->siteUserId);
            if (!$userObj->setupFavoriteTeacher($teacherId)) {
                $message = Label::getLabel('LBL_PLEASE_CONTACT_SUPPORT');
            }
            $action = 'A';
            $message = Label::getLabel('LBL_ADDED_TO_FAVOURITES');
        } else {
            if (!$db->deleteRecords(User::DB_TBL_TEACHER_FAVORITE, [
                'smt' => 'uft_user_id = ? AND uft_teacher_id = ?',
                'vals' => [$this->siteUserId, $teacherId]
            ])) {
                $message = Label::getLabel('LBL_PLEASE_CONTACT_SUPPORT');
            }
            $action = 'R';
            $message = Label::getLabel('LBL_REMOVED_FROM_FAVOURITES');
        }
        $response = ['msg' => $message, 'action' => $action];
        if (API_CALL) {
            MyUtility::dieJsonSuccess($message);
        }
        MyUtility::dieJsonSuccess($response);
    }

    /**
     * Render Favorite page and Favorite Search Form
     */
    public function favourites()
    {
        $frmFavSrch = $this->getFavouriteSearchForm();
        $this->set('frmFavSrch', $frmFavSrch);
        $this->_template->render();
    }

    /**
     * Get Favorite Search Form
     * 
     * @return Form
     */
    private function getFavouriteSearchForm(): Form
    {
        $frm = new Form('frmFavSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_KEYWORD')]);
        $fld_submit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'), ['class' => 'btn btn--primary']);
        $fld_cancel = $frm->addResetButton('', "btn_clear", Label::getLabel('LBL_Clear'), ['onclick' => 'clearSearch();', 'class' => 'btn--clear']);
        $fld_submit->attachField($fld_cancel);
        $frm->addHiddenField('', 'page', 1);
        return $frm;
    }

    /**
     * Get Favorites
     */
    public function getFavourites()
    {
        $frm = $this->getFavouriteSearchForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $this->sets([
            'countriesArr' => Country::getNames($this->siteLangId, [], false),
            'favouritesData' => (new User($this->siteUserId))->getFavourites($post, $this->siteLangId),
            'postedData' => $post,
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    public static function getSearchForm(): Form
    {
        $frm = new Form('frmSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'ordles_status', Lesson::SCHEDULED);
        $frm->addHiddenField('', 'ordles_lesson_starttime', MyDate::formatDate(date('Y-m-d H:i:s'), 'Y-m-d'));
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE);
        $frm->addHiddenField('', 'pageno', 1);
        $frm->addHiddenField('', 'view', AppConstant::VIEW_DASHBOARD_LISTING);
        return $frm;
    }
}
