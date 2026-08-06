<?php

use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\Channel;

/**
 * A Common Google Calendar Utility  
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GoogleCalendar extends Google
{

    private $redirect = null;

    /**
     * Initialize Google Calendar
     * 
     * @param int $userId
     */
    public function __construct(int $userId = 0)
    {
        $this->userId = $userId;
        parent::__construct();
    }

    /**
     * Authorize
     * 
     * @param string $code
     * @return bool
     */
    public function authorize(string|null $code = null, bool $isAdmin = false): bool
    {
        $authRedirectUri = MyUtility::makeFullUrl('Configurations', 'googleAuthorize', [], CONF_WEBROOT_BACKEND);
        $redirectUrl = MyUtility::makeUrl('Configurations', '', [], CONF_WEBROOT_BACKEND);
        if ($isAdmin == false) {
            $authRedirectUri = MyUtility::makeFullUrl('Account', 'GoogleCalendarAuthorize', [], CONF_WEBROOT_DASHBOARD);
            $redirectUrl = MyUtility::makeUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD);
        }
        try {
            if (!$this->getClient()) {
                return false;
            }
            $this->client->setApplicationName(FatApp::getConfig('CONF_WEBSITE_NAME_' . MyUtility::getSiteLangId()));
            $this->client->setScopes([Calendar::CALENDAR, Calendar::CALENDAR_EVENTS]);
            $this->client->setAccessType("offline");
            $this->client->setPrompt('consent');
            $this->client->setRedirectUri($authRedirectUri);
            if (empty($code)) {
                if (API_CALL) {
                    $this->error = Label::getLabel('LBL_AUTHENTICATION_CODE_MISSING');
                    return false;
                }
                $this->redirect = $this->client->createAuthUrl();
                return true;
            }
            if (API_CALL) {
                $accessToken = $code;
            } else {
                $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
                if (array_key_exists('error', $accessToken)) {
                    $this->error = Label::getLabel('LBL_SOMETHING_WENT_WRONG_PLEASE_TRY_AGAIN_LATER');
                    $this->redirect = $redirectUrl;
                    return false;
                }
                if ($isAdmin == true) {
                    (new Configurations())->updateConf('GOOGLE_AUTH_TOKEN', json_encode($accessToken));
                    return true;
                }
            }
            $this->client->setAccessToken($accessToken);
        } catch (Exception $exc) {
            $this->error = $exc->getMessage();
            return false;
        } 
        $data = [
            'user_google_token' => json_encode($this->client->getAccessToken()),
        ];
        $refreshToken = $this->client->getRefreshToken();
        if (!empty($refreshToken)) {
            $data['user_google_refresh_token'] = $refreshToken;
        }
        $userSetting = new UserSetting($this->userId);
        if (!$userSetting->saveData($data)) {
            $this->error = $userSetting->getError();
            return false;
        }
        if (!$this->setupGoogleCalendarSyncAndWatch($this->userId)) {
            $this->error = $userSetting->getError();
            return false;
        }
        $this->redirect = $redirectUrl;
        return true;
    }

    /**
     * Get Redirect URL
     * 
     * @return type
     */
    public function getRedirectUrl()
    {
        return $this->redirect;
    }

    /**
     * Add Event
     * 
     * @param array $data
     * @return bool
     */
    public function addEvent(array $data)
    {
        if (!$token = $this->getUserToken($data['google_token'] ?? '')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        unset($data['google_token']);
        try {
            if (!$client = $this->getClient()) {
                return false;
            }
            $client->refreshToken($token);
            $service = new Calendar($client);
            $event = $service->events->insert('primary', new Event($data));
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
        return $event->id;
    }

    /**
     * Update Event
     * 
     * @param string $eventId
     * @param array $data
     * @return bool
     */
    public function updateEvent(string $eventId, array $data): bool
    {
        if (!$token = $this->getUserToken($data['google_token'] ?? '')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        unset($data['google_token']);
        try {
            if (!$client = $this->getClient()) {
                return false;
            }
            $client->refreshToken($token);
            $service = new Calendar($client);
            $event = $service->events->update('primary', $eventId, new Event($data));
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Delete Event
     * 
     * @param string $eventId
     * @param string $token
     * @return bool
     */
    public function deleteEvent(string $eventId, string $token): bool
    {
        if (!$token = $this->getUserToken($token ?? '')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        try {
            if (!$client = $this->getClient()) {
                return false;
            }
            $client->refreshToken($token);
            $service = new Calendar($client);
            $event = $service->events->delete('primary', $eventId);
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
        return true;
    }

    /**
     * Add Google Calendar Events
     * 
     * @param string $token
     * @param $startDate
     * @param int $syncDays
     * @return bool
     */
    public function addGoogleCalendarEvents(string $token, string|null $startDate = NULL): bool
    {
        if (!$token = $this->getUserToken($token ?? '')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $syncDays = FatApp::getConfig('CONF_GOOGLE_CALENDAR_SYNC_DURATION', FatUtility::VAR_INT, 10);
        try {
            if (!$client = $this->getClient()) {
                return false;
            }
            $client->refreshToken($token);
            $service = new Calendar($client);
            $startDate = (empty($startDate)) ? date('c', strtotime('today midnight')) : date('c', strtotime($startDate . ' +' . $syncDays . ' days'));
            $endDate = strtotime(date('Y-m-d') . ' +' . $syncDays . ' days');
            $endDate = date('c', $endDate);
            $optParams = [
                'singleEvents' => true, 'showDeleted' => false,
                'timeZone' => MyUtility::getSystemTimezone(), 'timeMin' => $startDate, 'timeMax' => $endDate,
                'eventTypes' => ['default', 'outOfOffice', 'workingLocation'],
            ];
            $nextPageToken = null;
            do {
                if (!empty($nextPageToken)) {
                    $optParams['pageToken'] = $nextPageToken;
                }
                $events = $service->events->listEvents('primary', $optParams);
                foreach ($events->getItems() as $event) {
                    if ($event->status == "confirmed") {
                        $start = $event->getStart();
                        $end = $event->getEnd();
                        if (!empty($start->dateTime) && !empty($end->dateTime)) {
                            $googleCalendar = new GoogleCalendarEvent($this->userId, 0, 0);
                            if (!$googleCalendar->addGoogleCalEvent($event->id, $start->dateTime, $end->dateTime)) {
                                $this->error = $googleCalendar->getError();
                                return false;
                            }
                        }
                    }
                }
                $nextPageToken = $events->getNextPageToken();
            } while (!empty($nextPageToken));
            $settingData = ['user_google_event_sync_date' => date('Y-m-d H:i:s')];
            $nextSyncToken = $events->getNextSyncToken();
            if (!empty($nextSyncToken)) {
                $settingData['user_google_event_sync_token'] = $nextSyncToken;
            }

            $usrStngObj = new UserSetting($this->userId);
            if (!$usrStngObj->saveData($settingData)) {
                $this->error = $usrStngObj->getError();
                return false;
            }
            return true;
        } catch (Exception $e) {
            $errorCode = $e->getCode();
            if (in_array($errorCode, [400, 401])) {
                $userSetting = new UserSetting($this->userId);
                $userSetting->removeGoogleCalendarData();
            }
            return false;
        }
    }

    /**
     * Watch Changes In Google Calendar Events
     * 
     * @param string $token
     * @return bool
     */
    public function addGoogleWatch(string $token): bool
    {
        if (!$token = $this->getUserToken($token ?? '')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }

        try {
            if (!$client = $this->getClient()) {
                return false;
            }
            $client->refreshToken($token);
            $service = new Calendar($client);
            $channel =  new Channel($client);
            $channel->setId(uniqid('user_' . $this->userId . '_'));
            $channel->setType('web_hook');
            $channel->setToken(json_encode(['user_id' => $this->userId]));
            $channel->setAddress(MyUtility::generateFullUrl('Teachers', 'googleEventWatch', [$this->userId], ''));
            $watch = $service->events->watch('primary', $channel);
            if (!empty($watch)) {
                $usrStngObj = new UserSetting($this->userId);
                if (!$usrStngObj->saveData([
                    'user_google_event_watch_id' => $watch->id,
                    'user_google_event_watch_resource_id' => $watch->resourceId,
                    'user_google_event_watch_expiration' => date('Y-m-d H:i:s', ($watch->expiration / 1000))
                ])) {
                    $this->error = $usrStngObj->getError();
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            $errorCode = $e->getCode();
            if (in_array($errorCode, [400, 401])) {
                $userSetting = new UserSetting($this->userId);
                $userSetting->removeGoogleCalendarData();
            }
            return false;
        }
    }

    /**
     * Remove watch
     * 
     * @param string $token
     * @param string $channelId
     * @param string $resourceId
     * @return bool
     */
    public function removeWatch(string $token, string $channelId, string $resourceId): bool
    {
        if (!$token = $this->getUserToken($token ?? '')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        try {
            if (!$client = $this->getClient()) {
                return false;
            }
            $client->refreshToken($token);
            $service = new Calendar($client);
            $channel =  new Channel($service);
            $channel->setId($channelId);
            $channel->setResourceId($resourceId);
            $service->channels->stop($channel);
            return true;
        } catch (Exception $e) {
            $errorCode = $e->getCode();
            if (in_array($errorCode, [400, 401])) {
                $userSetting = new UserSetting($this->userId);
                $userSetting->removeGoogleCalendarData();
            }
            return false;
        }
    }

    /**
     * Incremental Synchronization
     * 
     * @param string $token
     * @param string $syncToken
     * @return bool
     */
    public function incrementalSync(string $token, string $syncToken): bool
    {
        if (!$token = $this->getUserToken($token ?? '')) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }

        try {
            if (!$client = $this->getClient()) {
                return false;
            }
            $client->refreshToken($token);
            $service = new Calendar($client);
            $optParams = [
                'syncToken' => $syncToken,
                'timeZone' => MyUtility::getSystemTimezone(),
                'eventTypes' => ['default', 'outOfOffice', 'workingLocation'],
            ];
            $nextPageToken = null;
            do {
                if (!empty($nextPageToken)) {
                    $optParams['pageToken'] = $nextPageToken;
                }
                $events = $service->events->listEvents('primary', $optParams);
                foreach ($events->getItems() as $event) {
                    $googleCalendar = new GoogleCalendarEvent($this->userId, 0, 0);
                    if ($event->status == "cancelled") {
                        if (!$googleCalendar->deletGoogleCalEvent($event->id)) {
                            $this->error = $googleCalendar->getError();
                            return false;
                        }
                        continue;
                    }
                    if ($event->status == "confirmed") {
                        $start = $event->getStart();
                        $end = $event->getEnd();
                        if (!empty($event->recurrence)) {
                            /**
                             * For recurring events, google only send one event details
                             * So, calling syncing with current date to fetch all events.
                             */
                            $userToken = UserSetting::getSettings($this->userId, ['user_google_token']);
                            $googleCalendar = new GoogleCalendarEvent($this->userId, 0, 0);
                            if (!$googleCalendar->addEventsList($userToken['user_google_token'])) {
                                $this->error = $googleCalendar->getError();
                                return false;
                            }
                            continue;
                        }
                        /* create single event */
                        if (!empty($start->dateTime) && !empty($end->dateTime)) {
                            $googleCalendar = new GoogleCalendarEvent($this->userId, 0, 0);
                            if (!$googleCalendar->addGoogleCalEvent($event->id, $start->dateTime, $end->dateTime)) {
                                $this->error = $googleCalendar->getError();
                                return false;
                            }
                        }
                    }
                }
                $nextPageToken = $events->getNextPageToken();
            } while (!empty($nextPageToken));
            $nextSyncToken = $events->getNextSyncToken();
            if (!empty($nextSyncToken) && $nextSyncToken != $syncToken) {
                $usrStngObj = new UserSetting($this->userId);
                if (!$usrStngObj->saveData(['user_google_event_sync_token' => $nextSyncToken])) {
                    $this->error = $usrStngObj->getError();
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            $errorCode = $e->getCode();
            if (in_array($errorCode, [400, 401])) {
                $userSetting = new UserSetting($this->userId);
                $userSetting->removeGoogleCalendarData();
            }
            if ($errorCode == 410) {
                $googleCalendar = new GoogleCalendar($this->userId);
                $syncDays = FatApp::getConfig('CONF_GOOGLE_CALENDAR_SYNC_DURATION', FatUtility::VAR_INT, 10);
                if (!$googleCalendar->addGoogleCalendarEvents($token, null, $syncDays)) {
                    return false;
                }
            }
            return false;
        }
    }

    private function setupGoogleCalendarSyncAndWatch(int $userId)
    {
        $userSettings = UserSetting::getSettings($userId);
        $googleCalendarEvent = new GoogleCalendarEvent($userId, 0, 0);
        if (!$googleCalendarEvent->addEventsList($userSettings['user_google_token'], $userSettings['user_google_event_sync_date'])) {
            $this->error = $googleCalendarEvent->getError();
            return false;
        }
        return true;
    }
}
