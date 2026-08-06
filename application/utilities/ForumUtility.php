<?php

/**
 * This class is used for Forum Utility
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumUtility
{
    /**
     * Initialize ForumUtility Class
     *
     */
    public function __construct()
    {
    }

    public static function sendEmailsAllowed() : bool
    {
        if (1 == FatApp::getConfig('CONF_FORUM_SEND_EMAILS', FatUtility::VAR_INT, 0)) {
            return true;
        }
        return false;
    }

    public static function sendSystemNotificationsAllowed() : bool
    {
        if (1 == FatApp::getConfig('FORUM_SEND_SYSTEM_NOTIFICATIONS', FatUtility::VAR_INT, 0)) {
            return true;
        }
        return false;
    }

    /**
     * Send emails/System Notifications allowed
     * @type string (allowed values 'EM' = Emails, 'SN' = System Notification and empty string to check EM and SN both)
     *
     */
    public static function canSendNotifications(string $type = ''): bool
    {
        if ('SN' == $type) {
            if (true === static::sendSystemNotificationsAllowed()) {
                return true;
            }
            return false;
        } elseif ('EM' == $type) {
            if (true === static::sendEmailsAllowed()) {
                return true;
            }
            return false;
        } else {

            if (true === static::sendEmailsAllowed() || true === static::sendSystemNotificationsAllowed()) {
                return true;
            }
        }
        return false;
    }
}
