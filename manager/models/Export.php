<?php

/**
 * This class is used to handle Exports
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
abstract class Export extends MyAppModel
{

    const DB_TBL = 'tbl_export_logs';
    const DB_TBL_PREFIX = 'export_';
    /* Status */
    const SCHEDULED = 1;
    const INPROCESS = 2;
    const COMPLETED = 3;
    const FAILED = 4;
    /* Types */
    const USERS = 'Users';
    const TEACHER_REQUESTS = 'TeacherRequests';
    const WITHDRAW_REQUESTS = 'WithdrawRequests';
    const RATING_REVIEWS = 'RatingReviews';
    const GDPR_REQUESTS = 'GdprRequests';
    const ADMIN_USERS = 'AdminUsers';
    const GROUP_CLASSES = 'GroupClasses';
    const PACKAGE_CLASSES = 'PackageClasses';
    const ORDERS = 'Orders';
    const LESSONS = 'Lessons';
    const SUBSCRIPTIONS = 'Subscriptions';
    const CLASSES = 'Classes';
    const PACKAGES = 'Packages';
    const GIFTCARDS = 'Giftcards';
    const WALLET = 'Wallet';
    const REPORTED_ISSUES = 'ReportedIssues';
    const PREFERENCES = 'Preferences';
    const SPEAK_LANGUAGE = 'SpeakLanguage';
    const SPEAK_LANGUAGE_LEVELS = 'SpeakLanguageLevels';
    const TEACH_LANGUAGE = 'TeachLanguage';
    const COUNTRIES = 'Countries';
    const VIDEO_CONTENT = 'VideoContent';
    const TESTIMONIALS = 'Testimonials';
    const FAQ_CATEGORIES = 'FaqCategories';
    const FAQS = 'Faq';
    const EMAIL_TEMPLATES = 'EmailTemplates';
    const BLOG_CATEGORIES = 'BlogPostCategories';
    const BLOG_POSTS = 'BlogPosts';
    const BLOG_COMMENTS = 'BlogComments';
    const BLOG_CONTRIBUTIONS = 'BlogContributions';
    const META_TAGS = 'MetaTags';
    const URL_REWRITING = 'UrlRewriting';
    const LESSON_LANGUAGES = 'LessonLanguages';
    const CLASS_LANGUAGES = 'ClassLanguages';
    const TEACHER_PERFORMANCE = 'TeacherPerformance';
    const WALLET_BALANCE = 'WalletBalanceReport';
    const TEACHER_PAYOUTS_REPORT = 'TeacherPayoutsReport';
    const HOURS_TAUGHT_REPORT = 'HoursTaughtReport';
    const LESSON_STATS = 'LessonStats';
    const SALES_REPORT = 'SalesReport';
    const SETTLEMENTS = 'Settlements';
    const EXPORTS = 'Exports';
    const FORUM = 'Forum';
    const FORUM_REPORTED_QUESTIONS = 'ForumReportedQuestions';
    const FORUM_TAGS = 'ForumTags';
    const FORUM_TAGS_REQUESTS = 'ForumTagRequests';
    const ADMIN_EARNINGS = 'AdminEarnings';
    const COURSE_LANGUAGES = 'CourseLanguages';
    const CATEGORIES = 'Categories';
    const COURSES = 'Courses';
    const COURSE_ORDERS = 'CourseOrders';
    const COURSE_REQUESTS = 'CourseRequests';
    const COURSE_EDIT_REQUESTS = 'CourseEditRequests';
    const COURSE_REFUND_REQUESTS = 'CourseRefundRequests';
    const STATES = 'States';
    const AFFILIATE_REPORT = 'AffiliateReport';
    const ORDER_SUBSCRIPTION_PLANS = 'OrderSubscriptionPlans';
    const QUESTIONS = 'Questions';
    const QUIZZES = 'Quizzes';

    protected $sql;
    protected $type;
    protected $langId;
    protected $filters;
    protected $headers;

    public function __construct(int $langId, int $id = 0)
    {
        $this->langId = $langId;
        parent::__construct(static::DB_TBL, 'export_id', $id);
    }

    public static function getTypes(string $key = null)
    {
        $arr = [
            static::USERS => Label::getLabel('EXP_USERS'),
            static::ADMIN_USERS => Label::getLabel('EXP_ADMIN_USERS'),
            static::GDPR_REQUESTS => Label::getLabel('EXP_GDPR_REQUESTS'),
            static::RATING_REVIEWS => Label::getLabel('EXP_RATING_REVIEWS'),
            static::TEACHER_REQUESTS => Label::getLabel('EXP_TEACHER_REQUESTS'),
            static::WITHDRAW_REQUESTS => Label::getLabel('EXP_WITHDRAW_REQUESTS'),
            static::GROUP_CLASSES => Label::getLabel('EXP_GROUP_CLASSES'),
            static::PACKAGE_CLASSES => Label::getLabel('EXP_PACKAGE_CLASSES'),
            static::ORDERS => Label::getLabel('EXP_ORDERS'),
            static::LESSONS => Label::getLabel('EXP_LESSONS_ORDERS'),
            static::SUBSCRIPTIONS => Label::getLabel('EXP_RECURRING_LESSON_ORDERS'),
            static::CLASSES => Label::getLabel('EXP_CLASSES_ORDERS'),
            static::PACKAGES => Label::getLabel('EXP_PACKAGES_ORDERS'),
            static::GIFTCARDS => Label::getLabel('EXP_GIFTCARDS_ORDERS'),
            static::WALLET => Label::getLabel('EXP_WALLET_ORDERS'),
            static::REPORTED_ISSUES => Label::getLabel('EXP_REPORTED_ISSUES'),
            static::PREFERENCES => Label::getLabel('EXP_TEACHER_PREFERENCES'),
            static::SPEAK_LANGUAGE => Label::getLabel('EXP_SPEAK_LANGUAGE'),
            static::SPEAK_LANGUAGE_LEVELS => Label::getLabel('EXP_SPEAK_LANGUAGE_LEVELS'),
            static::TEACH_LANGUAGE => Label::getLabel('EXP_TEACH_LANGUAGE'),
            static::COUNTRIES => Label::getLabel('EXP_COUNTRIES'),
            static::VIDEO_CONTENT => Label::getLabel('EXP_VIDEO_CONTENT'),
            static::TESTIMONIALS => Label::getLabel('EXP_TESTIMONIALS'),
            static::FAQ_CATEGORIES => Label::getLabel('EXP_FAQ_CATEGORIES'),
            static::FAQS => Label::getLabel('EXP_FAQS'),
            static::EMAIL_TEMPLATES => Label::getLabel('EXP_EMAIL_TEMPLATES'),
            static::BLOG_CATEGORIES => Label::getLabel('EXP_BLOG_CATEGORIES'),
            static::BLOG_POSTS => Label::getLabel('EXP_BLOG_POSTS'),
            static::BLOG_COMMENTS => Label::getLabel('EXP_BLOG_COMMENTS'),
            static::BLOG_CONTRIBUTIONS => Label::getLabel('EXP_BLOG_CONTRIBUTIONS'),
            static::META_TAGS => Label::getLabel('EXP_META_TAGS'),
            static::URL_REWRITING => Label::getLabel('EXP_URL_REWRITING'),
            static::LESSON_LANGUAGES => Label::getLabel('EXP_LESSON_LANGUAGES'),
            static::CLASS_LANGUAGES => Label::getLabel('EXP_CLASS_LANGUAGES'),
            static::TEACHER_PERFORMANCE => Label::getLabel('EXP_TEACHER_PERFORMANCE'),
            static::WALLET_BALANCE => Label::getLabel('EXP_WALLET_BALANCE_REPORT'),
            static::TEACHER_PAYOUTS_REPORT => Label::getLabel('EXP_TEACHER_PAYOUTS_REPORT'),
            static::HOURS_TAUGHT_REPORT => Label::getLabel('EXP_HOURS_TAUGHT_REPORT'),
            static::LESSON_STATS => Label::getLabel('EXP_LESSON_STATS'),
            static::SALES_REPORT => Label::getLabel('EXP_SALES_REPORT'),
            static::SETTLEMENTS => Label::getLabel('EXP_SETTLEMENTS'),
            static::EXPORTS => Label::getLabel('EXP_EXPORT_HISTORY'),
            static::FORUM => Label::getLabel('EXP_FORUM_QUESTIONS'),
            static::FORUM_REPORTED_QUESTIONS => Label::getLabel('EXP_FORUM_REPORTED_QUESTIONS'),
            static::FORUM_TAGS => Label::getLabel('EXP_FORUM_TAGS'),
            static::FORUM_TAGS_REQUESTS => Label::getLabel('EXP_FORUM_TAGS_REQUESTS'),
            static::ADMIN_EARNINGS => Label::getLabel('EXP_ADMIN_EARNINGS'),
            static::COURSE_LANGUAGES => Label::getLabel('EXP_COURSE_LANGUAGES'),
            static::CATEGORIES => Label::getLabel('EXP_CATEGORIES'),
            static::COURSES => Label::getLabel('EXP_COURSES'),
            static::COURSE_ORDERS => Label::getLabel('EXP_COURSE_ORDERS'),
            static::COURSE_REQUESTS => Label::getLabel('EXP_COURSE_REQUESTS'),
            static::COURSE_EDIT_REQUESTS => Label::getLabel('EXP_COURSE_EDIT_REQUESTS'),
            static::COURSE_REFUND_REQUESTS => Label::getLabel('EXP_COURSE_REFUND_REQUESTS'),
            static::STATES => Label::getLabel('EXP_STATES'),
            static::AFFILIATE_REPORT => Label::getLabel('EXP_AFFILIATE_REPORT'),
            static::ORDER_SUBSCRIPTION_PLANS => Label::getLabel('EXP_ORDER_SUBSCRIPTION_PLANS'),
            static::QUESTIONS => Label::getLabel('EXP_QUESTIONS'),
            static::QUIZZES => Label::getLabel('EXP_QUIZZES'),
            
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::SCHEDULED => Label::getLabel('EXP_SCHEDULED'),
            static::INPROCESS => Label::getLabel('EXP_INPROCESS'),
            static::COMPLETED => Label::getLabel('EXP_COMPLETED'),
            static::FAILED => Label::getLabel('EXP_FAILED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public function setSearchObject(array $array)
    {
        $srch = $array['srch'];
        $srch->removeFields();
        $srch->doNotLimitRecords();
        $srch->doNotCalculateRecords();
        $this->formatPostData($array['post']);
        $srch->addMultipleFields($this->getFields());
        $this->sql = $srch->getQuery();
    }

    public function setup(): bool
    {
        $this->assignValues([
            'export_sql' => $this->sql,
            'export_type' => $this->type,
            'export_file' => self::getTypes($this->type) . '_' . uniqid() . '.csv',
            'export_status' => static::SCHEDULED,
            'export_filters' => json_encode($this->filters),
            'export_headers' => json_encode($this->headers),
        ]);
        return $this->saveData();
    }

    public function start(): bool
    {
        $this->setFldValue('export_status', static::INPROCESS);
        $this->setFldValue('export_started', date('Y-m-d H:i:s'));
        return $this->saveData();
    }

    public function create()
    {
        $id = $this->getMainTableRecordId();
        $export = static::getAttributesById($id);
        if (empty($export)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $rs = FatApp::getDb()->query($export['export_sql']);
        $file = CONF_UPLOADS_PATH . $export['export_file'];
        if (!$fh = fopen($file, 'w')) {
            $this->error = Label::getLabel('LBL_CANNOT_OPEN_FILE');
            return false;
        }
        return $this->writeData($fh, $rs);
    }

    public function complete(int $rows): bool
    {
        $this->setFldValue('export_records', $rows);
        $this->setFldValue('export_status', static::COMPLETED);
        $this->setFldValue('export_finished', date('Y-m-d H:i:s'));
        return $this->saveData();
    }

    public function failed(): bool
    {
        $this->setFldValue('export_status', static::FAILED);
        $this->setFldValue('export_finished', date('Y-m-d H:i:s'));
        return $this->saveData();
    }

    public function download()
    {
        $id = $this->getMainTableRecordId();
        $export = static::getAttributesById($id);
        $file = CONF_UPLOADS_PATH . $export['export_file'];
        if (!file_exists($file)) {
            $this->error = Label::getLabel('LBL_FILE_NOT_FOUND');
            return false;
        }
        if (!mime_content_type($file)) {
            $this->error = Label::getLabel('LBL_INVALID_FILE_CONTENT');
            return false;
        }
        $this->setFldValue('export_download', date('Y-m-d H:i:s'));
        if (!$this->saveData()) {
            return false;
        }
        ob_end_clean();
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        header("Content-Transfer-Encoding: Binary");
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $export['export_file'] . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        echo iconv(mb_detect_encoding(file_get_contents($file), mb_detect_order(), true), "UTF-8",file_get_contents($file));
        unlink($file);
    }

    public function saveData(): bool
    {
        if ($this->getMainTableRecordId() > 0) {
            $this->setFldValue('export_updated', date('Y-m-d H:i:s'));
        } else {
            $this->setFldValue('export_created', date('Y-m-d H:i:s'));
        }
        return $this->save();
    }

    private function formatPostData(array $post)
    {
        unset($post['page'], $post['pageno'], $post['pagesize'], $post['pageSize']);
        $this->filters = [];
        foreach ($post as $key => $value) {
            if ($key == 'teacher_id') {
                continue;
            }
            if ($value != '') {
                $this->filters[$key] = $value;
            }
        }
    }

    abstract public function getFields(): array;

    abstract public function writeData($fh, $rs): int;
}
