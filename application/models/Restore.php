<?php

/**
 * This class is used to handle restoration process through cron
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Restore extends FatModel
{
    /* setup db names used for restoration */

    const DATABASE_BASE = 'w3mentors87h1y172_elearning_3';
    const DATABASE_FIRST = 'w3mentors87h1y172_elearning_1';
    const DATABASE_SECOND = 'w3mentors87h1y172_elearning_2';

    /* set restoration duration */
    const RESTORE_TIME_INTERVAL_HOURS = 4;
    const RESTORATION_SETUP_DATE = '2025-02-28 04:25:00';

    private $db;
    private $restoreDb;

    /**
     * Initialize
     */
    public function __construct()
    {
        /* Get the db to be restored */
        $this->restoreDb = (CONF_DB_NAME == static::DATABASE_FIRST) ? static::DATABASE_SECOND : static::DATABASE_FIRST;
        /* create a new connection */
        $this->db = new Database(CONF_DB_SERVER, CONF_DB_USER, CONF_DB_PASS, $this->restoreDb, true);
    }

    public function restoreDb()
    {
        if ($this->isRestoredSuccessfully()) {
            return true;
        }
        if (!$this->restoreDatabase()) {
            $this->error = "An error occurred while restoring database";
            return false;
        }
        if (!$this->executeUpdates()) {
            return false;
        }
        if (!$this->resetRestoreStatus()) {
            $this->error = "Unable to update restoration status";
            return false;
        }
        return true;
    }

    /**
     * Function to check if db restoration was successful or not
     *
     * @return boolean
     */
    private function isRestoredSuccessfully()
    {
        $query = $this->db->query("SELECT * FROM `tbl_configurations` WHERE `conf_name` = 'CONF_RESTORED_SUCCESSFULLY'");
        $row = $this->db->fetch($query);
        if (!$row) {
            return false;
        }
        if ($row['conf_val'] > 0) {
            return true;
        }
        return false;
    }

    /**
     * Function to update restoration successful status
     *
     * @return bool
     */
    private function resetRestoreStatus()
    {
        if (!$this->db->query("UPDATE `" . $this->restoreDb . "`.`tbl_configurations` SET `conf_val` = '1' WHERE `conf_name` = 'CONF_RESTORED_SUCCESSFULLY';")) {
            return false;
        }

        return true;
    }

    /**
     * Function to drop all the tables and restore from backup file
     *
     * @return bool
     */
    private function restoreDatabase()
    {
        $db = new Database(CONF_DB_SERVER, CONF_DB_USER, CONF_DB_PASS, $this->restoreDb, true);
        $rs = $db->query("SHOW TABLES FROM $this->restoreDb");
        while ($row = $rs->fetchArray()) {
            $tableName = $row[0];
            if (in_array($tableName, ['tbl_availability', 'tbl_user_preferences', 'tbl_user_settings'])) {
                continue;
            }
            $db->query('TRUNCATE ' . $this->restoreDb . '.' . $tableName);
            $db->query('INSERT INTO ' . $this->restoreDb . '.' . $tableName .
                    ' SELECT * FROM ' . static::DATABASE_BASE . '.' . $tableName);
        }
        return true;
    }

    private function executeUpdates()
    {
        $queries = $this->getQueries();
        $error = 0;
        foreach ($queries as $query) {
            if (!$this->db->query($query)) {
                $error = 1;
                $this->error = $this->db->getError();
                break;
            }
        }
        if ($error == 1) {
            return false;
        }
        return true;
    }

    private function getQueries()
    {
        $hours = round(abs(strtotime(date('Y-m-d H:i:s')) - strtotime(static::RESTORATION_SETUP_DATE)) / 3600, 1);
        return [
            "UPDATE tbl_admin_auth_token SET  admauth_expiry = DATE_ADD(admauth_expiry, INTERVAL " . $hours . " hour),  admauth_last_access = DATE_ADD(admauth_last_access, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_admin_commissions SET comm_created = DATE_ADD(comm_created, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_attached_files SET file_added = DATE_ADD(file_added, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_bank_transfers SET bnktras_datetime = DATE_ADD(bnktras_datetime, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_blog_post SET  post_added_on = DATE_ADD(post_added_on, INTERVAL " . $hours . " hour), post_published_on = DATE_ADD(post_published_on, INTERVAL " . $hours . " hour), post_updated_on = DATE_ADD(post_updated_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_coupons_history SET  couhis_created = DATE_ADD(couhis_created, INTERVAL " . $hours . " hour),  couhis_released = DATE_ADD(couhis_released, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_coupons SET  coupon_start_date = DATE_ADD(coupon_start_date, INTERVAL " . $hours . " hour),  coupon_end_date = DATE_ADD(coupon_end_date, INTERVAL " . $hours . " hour),  coupon_created = DATE_ADD(coupon_created, INTERVAL " . $hours . " hour),  coupon_updated = DATE_ADD(coupon_updated, INTERVAL " . $hours . " hour);",
            "UPDATE  tbl_courses SET  course_created = DATE_ADD(course_created, INTERVAL " . $hours . " hour),  course_updated = DATE_ADD(course_updated, INTERVAL " . $hours . " hour);",
            "UPDATE   tbl_course_approval_requests SET  coapre_created = DATE_ADD(coapre_created, INTERVAL " . $hours . " hour),  coapre_updated = DATE_ADD(coapre_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_course_progresses SET  crspro_started = DATE_ADD(crspro_started, INTERVAL " . $hours . " hour),  crspro_completed = DATE_ADD(crspro_completed, INTERVAL " . $hours . " hour);",
            "UPDATE  tbl_course_refund_requests SET  corere_created = DATE_ADD(corere_created, INTERVAL " . $hours . " hour),  corere_updated = DATE_ADD(corere_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_resources SET resrc_created = DATE_ADD(resrc_created, INTERVAL " . $hours . " hour),  resrc_deleted = DATE_ADD(resrc_deleted, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_faq SET faq_added_on = DATE_ADD(faq_added_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_orders SET order_addedon = DATE_ADD(order_addedon, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_order_classes SET  ordcls_starttime = DATE_ADD(ordcls_starttime, INTERVAL " . $hours . " hour),  ordcls_endtime = DATE_ADD(ordcls_endtime, INTERVAL " . $hours . " hour),  ordcls_updated = DATE_ADD(ordcls_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_order_courses SET  ordcrs_updated = DATE_ADD(ordcrs_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_order_giftcards SET  ordgift_expiry = DATE_ADD(ordgift_expiry, INTERVAL " . $hours . " hour),  ordgift_usedon = DATE_ADD(ordgift_usedon, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_order_payments SET ordpay_datetime = DATE_ADD(ordpay_datetime, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_order_subscriptions SET  ordsub_startdate = DATE_ADD(ordsub_startdate, INTERVAL " . $hours . " hour),  ordsub_enddate = DATE_ADD(ordsub_enddate, INTERVAL " . $hours . " hour),  ordsub_created = DATE_ADD(ordsub_created, INTERVAL " . $hours . " hour),  ordsub_updated = DATE_ADD(ordsub_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_rating_reviews SET ratrev_created = DATE_ADD(ratrev_created, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_reported_issues SET  repiss_reported_on = DATE_ADD(repiss_reported_on, INTERVAL " . $hours . " hour),  repiss_updated_on = DATE_ADD(repiss_updated_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_reported_issues_log SET reislo_added_on = DATE_ADD(reislo_added_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_themes SET theme_created = DATE_ADD(theme_created, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_users SET  user_created = DATE_ADD(user_created, INTERVAL " . $hours . " hour),  user_verified = DATE_ADD(user_verified, INTERVAL " . $hours . " hour),  user_deleted = DATE_ADD(user_deleted, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_user_transactions SET usrtxn_datetime = DATE_ADD(usrtxn_datetime, INTERVAL " . $hours . " hour);",
            "UPDATE `tbl_configurations` SET `conf_val` = '2020-01-01' WHERE `tbl_configurations`.`conf_name` = 'CONF_SALES_REPORT_GENERATED_DATE';",
            "UPDATE tbl_order_lessons SET  ordles_lesson_starttime = DATE_ADD(ordles_lesson_starttime, INTERVAL " . $hours . " hour),  ordles_lesson_endtime = DATE_ADD(ordles_lesson_endtime, INTERVAL " . $hours . " hour),  ordles_teacher_starttime = DATE_ADD(ordles_teacher_starttime, INTERVAL " . $hours . " hour),  ordles_teacher_endtime = DATE_ADD(ordles_teacher_endtime, INTERVAL " . $hours . " hour),  ordles_student_starttime = DATE_ADD(ordles_student_starttime, INTERVAL " . $hours . " hour),  ordles_student_endtime = DATE_ADD(ordles_student_endtime, INTERVAL " . $hours . " hour),  ordles_updated = DATE_ADD(ordles_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_group_classes SET  grpcls_start_datetime = DATE_ADD(grpcls_start_datetime, INTERVAL " . $hours . " hour),  grpcls_end_datetime = DATE_ADD(grpcls_end_datetime, INTERVAL " . $hours . " hour),  grpcls_teacher_starttime = DATE_ADD(grpcls_teacher_starttime, INTERVAL " . $hours . " hour),  grpcls_teacher_endtime = DATE_ADD(grpcls_teacher_endtime, INTERVAL " . $hours . " hour),  grpcls_added_on = DATE_ADD(grpcls_added_on, INTERVAL " . $hours . " hour);",
            "DELETE FROM `tbl_user_settings` WHERE `user_id` NOT IN (SELECT `user_id` FROM `tbl_users`);",
            "UPDATE tbl_affiliate_commissions SET afcomm_created = DATE_ADD(afcomm_created, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_affiliate_commission_history SET afcomhis_created = DATE_ADD(afcomhis_created, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_admin_transactions SET admtxn_datetime = DATE_ADD(admtxn_datetime, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_blog_contributions SET bcontributions_added_on = DATE_ADD(bcontributions_added_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_blog_post_comments SET bpcomment_added_on = DATE_ADD(bpcomment_added_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_categories SET cate_updated = DATE_ADD(cate_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_flashcards SET flashcard_addedon = DATE_ADD(flashcard_addedon, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_forum_questions SET fque_added_on = DATE_ADD(fque_added_on, INTERVAL " . $hours . " hour), fque_published_on = DATE_ADD(fque_published_on, INTERVAL " . $hours . " hour), fque_updated_on = DATE_ADD(fque_updated_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_forum_question_reported SET fquerep_added_on = DATE_ADD(fquerep_added_on, INTERVAL " . $hours . " hour), fquerep_updated_on = DATE_ADD(fquerep_updated_on, INTERVAL " . $hours . " hour);",
            "UPDATE  tbl_gdpr_requests SET gdpreq_added_on = DATE_ADD(gdpreq_added_on, INTERVAL " . $hours . " hour), gdpreq_updated_on  = DATE_ADD(gdpreq_updated_on, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_notifications SET notifi_read = DATE_ADD(notifi_read, INTERVAL " . $hours . " hour), notifi_added  = DATE_ADD(notifi_added, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_order_subscription_plans SET ordsplan_start_date = DATE_ADD(ordsplan_start_date, INTERVAL " . $hours . " hour), ordsplan_end_date  = DATE_ADD(ordsplan_end_date, INTERVAL " . $hours . " hour), ordsplan_created  = DATE_ADD(ordsplan_created, INTERVAL " . $hours . " hour), ordsplan_updated  = DATE_ADD(ordsplan_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_questions SET ques_created = DATE_ADD(ques_created, INTERVAL " . $hours . " hour), ques_updated  = DATE_ADD(ques_updated, INTERVAL " . $hours . " hour), ques_deleted  = DATE_ADD(ques_deleted, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_quizzes SET quiz_created = DATE_ADD(quiz_created, INTERVAL " . $hours . " hour), quiz_updated  = DATE_ADD(quiz_updated, INTERVAL " . $hours . " hour), quiz_deleted  = DATE_ADD(quiz_deleted, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_quiz_attempts SET quizat_started = DATE_ADD(quizat_started, INTERVAL " . $hours . " hour), quizat_created  = DATE_ADD(quizat_created, INTERVAL " . $hours . " hour), quizat_updated  = DATE_ADD(quizat_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_quiz_linked SET quilin_validity = DATE_ADD(quilin_validity, INTERVAL " . $hours . " hour), quilin_created  = DATE_ADD(quilin_created, INTERVAL " . $hours . " hour), quilin_deleted  = DATE_ADD(quilin_deleted, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_reward_points SET repnt_datetime = DATE_ADD(repnt_datetime, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_subscription_plans SET subplan_created = DATE_ADD(subplan_created, INTERVAL " . $hours . " hour), subplan_updated = DATE_ADD(subplan_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_teacher_requests SET tereq_date = DATE_ADD(tereq_date, INTERVAL " . $hours . " hour), tereq_status_updated = DATE_ADD(tereq_status_updated, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_user_withdrawal_requests SET withdrawal_request_date = DATE_ADD(withdrawal_request_date, INTERVAL " . $hours . " hour);",
            "UPDATE tbl_course_edit_requests SET coedre_created = DATE_ADD(coedre_created, INTERVAL " . $hours . " hour), coedre_updated = DATE_ADD(coedre_updated, INTERVAL " . $hours . " hour);",
            "UPDATE `tbl_sections` SET section_created = DATE_ADD(section_created, INTERVAL " . $hours . " hour), section_updated = DATE_ADD(section_updated, INTERVAL " . $hours . " hour), section_deleted = DATE_ADD(section_deleted, INTERVAL " . $hours . " hour);",
            "UPDATE `tbl_lectures` SET lecture_created = DATE_ADD(lecture_created, INTERVAL " . $hours . " hour), lecture_updated = DATE_ADD(lecture_updated, INTERVAL " . $hours . " hour), lecture_deleted = DATE_ADD(lecture_deleted, INTERVAL " . $hours . " hour);",
            "DELETE FROM `tbl_user_settings` WHERE `user_id` NOT IN (SELECT `user_id` FROM `tbl_users`);"
        ];
    }

}
