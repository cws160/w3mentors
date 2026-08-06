<?php

use ReCaptcha\ReCaptcha;

/**
 * A Common Utility Class
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CommonHelper
{

    public static function renderHtml($content = '', $stripJs = true)
    {
        $str = html_entity_decode($content, ENT_QUOTES);
        return $stripJs ? static::stripJavascript($str) : $str;
    }

    public static function convertToCsv($inputArray, $outputFile, $delimiter)
    {
        $tempMemory = fopen('php://memory', 'w');
        foreach ($inputArray as $key => $line) {
            fputcsv($tempMemory, $line, $delimiter);
        }
        fseek($tempMemory, 0);
        header('Content-Encoding: UTF-8');
        header('Content-Description: File Transfer');
        header('Content-type: application/csv; charset=UTF-8; encoding=UTF-8');
        header('Content-Disposition: attachement; filename="' . $outputFile . '";');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        fpassthru($tempMemory);
    }

    public static function verifyCaptcha(string $value)
    {
        if (API_CALL) {
            return true;
        }
        $siteKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY');
        $secretKey = FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY');
        if (empty($siteKey) || empty($secretKey)) {
            return true;
        }
        $recaptcha = new ReCaptcha($secretKey);
        $resp = $recaptcha->verify($value, $_SERVER['REMOTE_ADDR']);
        return $resp->isSuccess();
    }

    public static function stripJavascript($content = '')
    {
        $javascript = '/<script[^>]*?>.*?<\/script>/si';
        return preg_replace($javascript, '', $content);
    }

    public static function getCurrentUrl()
    {
        return self::getUrlScheme() . $_SERVER['REQUEST_URI'];
    }

    public static function getnavigationUrl($type, $nav_url = '', $pageId = 0, $categoryId = 0)
    {
        if (NavigationLinks::NAVLINK_TYPE_CMS == $type) {
            $url = MyUtility::makeUrl('cms', 'view', [$pageId], CONF_WEBROOT_FRONTEND);
        } elseif (NavigationLinks::NAVLINK_TYPE_EXTERNAL_PAGE == $type) {
            if (strpos(strtolower($nav_url), '{domain}') !== false) {
                $url = str_replace('{DOMAIN}', '', $nav_url);
                $url = str_replace('{domain}', '', $url);
                $url = MyUtility::makeUrl($url, CONF_WEBROOT_FRONTEND);
            } else {
                $url = CommonHelper::processURLString($nav_url);
            }
        } elseif (NavigationLinks::NAVLINK_TYPE_CATEGORY_PAGE == $type) {
            $url = MyUtility::makeUrl('category', 'view', [$categoryId], CONF_WEBROOT_FRONTEND);
        }
        return $url;
    }

    public static function getUrlScheme()
    {
        $pageURL = 'http';
        if (isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) {
            $pageURL .= 's';
        }
        $pageURL .= '://';
        if ('80' != $_SERVER['SERVER_PORT']) {
            $pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
        } else {
            $pageURL .= $_SERVER['SERVER_NAME'];
        }
        return $pageURL;
    }

    public static function redirectUserReferer($returnUrl = false)
    {
        if (!defined('REFERER')) {
            if (self::getCurrentUrl() == $_SERVER['HTTP_REFERER'] || empty($_SERVER['HTTP_REFERER'])) {
                define('REFERER', MyUtility::makeUrl('/'));
            } else {
                define('REFERER', $_SERVER['HTTP_REFERER']);
            }
        }
        if ($returnUrl) {
            return REFERER;
        }
        FatApp::redirectUser(REFERER);
    }

    public static function crop($data, $src)
    {
        if (empty($data)) {
            return;
        }
        $size = getimagesize($src);
        $size_w = $size[0]; // natural width
        $size_h = $size[1]; // natural height
        $src_img_w = $size_w;
        $src_img_h = $size_h;
        $degrees = $data->rotate;
        switch ($size['mime']) {
            case 'image/gif':
                $src_img = imagecreatefromgif($src);
                break;
            case 'image/jpeg':
                $src_img = imagecreatefromjpeg($src);
                break;
            case 'image/png':
                $src_img = imagecreatefrompng($src);
                break;
        }
        // Rotate the source image
        if (is_numeric($degrees) && 0 != $degrees) {
            // PHP's degrees is opposite to CSS's degrees
            $new_img = imagerotate($src_img, -$degrees, imagecolorallocatealpha($src_img, 0, 0, 0, 127));
            imagedestroy($src_img);
            $src_img = $new_img;
            $deg = abs($degrees) % 180;
            $arc = ($deg > 90 ? (180 - $deg) : $deg) * M_PI / 180;
            $src_img_w = $size_w * cos($arc) + $size_h * sin($arc);
            $src_img_h = $size_w * sin($arc) + $size_h * cos($arc);
            // Fix rotated image miss 1px issue when degrees < 0
            --$src_img_w;
            --$src_img_h;
        }
        $tmp_img_w = $data->width;
        $tmp_img_h = $data->height;
        $dst_img_w = 320;
        $dst_img_h = 320;
        $src_x = $data->x;
        $src_y = $data->y;
        if ($src_x <= -$tmp_img_w || $src_x > $src_img_w) {
            $src_x = $src_w = $dst_x = $dst_w = 0;
        } elseif ($src_x <= 0) {
            $dst_x = -$src_x;
            $src_x = 0;
            $src_w = $dst_w = min($src_img_w, $tmp_img_w + $src_x);
        } elseif ($src_x <= $src_img_w) {
            $dst_x = 0;
            $src_w = $dst_w = min($tmp_img_w, $src_img_w - $src_x);
        }
        if ($src_w <= 0 || $src_y <= -$tmp_img_h || $src_y > $src_img_h) {
            $src_y = $src_h = $dst_y = $dst_h = 0;
        } elseif ($src_y <= 0) {
            $dst_y = -$src_y;
            $src_y = 0;
            $src_h = $dst_h = min($src_img_h, $tmp_img_h + $src_y);
        } elseif ($src_y <= $src_img_h) {
            $dst_y = 0;
            $src_h = $dst_h = min($tmp_img_h, $src_img_h - $src_y);
        }
        // Scale to destination position and size
        $ratio = $tmp_img_w / $dst_img_w;
        $dst_x /= $ratio;
        $dst_y /= $ratio;
        $dst_w /= $ratio;
        $dst_h /= $ratio;
        $dst_img = imagecreatetruecolor($dst_img_w, $dst_img_h);
        // Add transparent background to destination image
        imagefill($dst_img, 0, 0, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
        imagesavealpha($dst_img, true);
        $result = imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        if ($result) {
            if (!imagepng($dst_img, $src)) {
                return Label::getLabel('MSG_Failed_to_save_cropped_file');
            }
        } else {
            return Label::getLabel('MSG_Failed_to_crop_file');
        }
        imagedestroy($src_img);
        imagedestroy($dst_img);
    }

    public static function processURLString($urlString)
    {
        $strtestpos = strpos(' ' . $urlString, '.');
        if (!$strtestpos) {
            return $urlString;
        }
        $urlString = trim($urlString);
        if ($urlString) {
            $my_bool = false;
            if ('https' == substr($urlString, 0, 5)) {
                $my_bool = true;
            }
            $urlString = preg_replace('/https?:\/\//', '', $urlString);
            $urlString = trim($urlString);
            $pre_str = 'http://';
            if ($my_bool) {
                $pre_str = 'https://';
            }
            $urlString = $pre_str . $urlString;
        }
        return $urlString;
    }

    public static function truncateCharacters($string, $limit, $break = ' ', $pad = '...', $nl2br = false)
    {
        if (empty($string)) {
            return '';
        }
        if (strlen($string) <= $limit) {
            return ($nl2br) ? nl2br($string) : $string;
        }
        $tempString = str_replace('\n', '^', $string);
        $tempString = mb_substr($tempString, 0, $limit, 'utf-8');
        if ('^' == substr($tempString, -1)) {
            $limit = $limit - 1;
        }
        $string = mb_substr($string, 0, $limit, 'utf-8');
        if (false !== ($breakpoint = strrpos($string, $break))) {
            $string = mb_substr($string, 0, $breakpoint, 'utf-8');
        }
        return (($nl2br) ? nl2br($string) : $string) . $pad;
    }

    public static function getFirstChar($string, $capitalize = false)
    {
        $string = mb_substr(html_entity_decode($string), 0, 1, 'utf8');
        if (!empty($string)) {
            if (true == $capitalize) {
                return strtoupper($string);
            }
            return $string;
        }
    }

    public static function seoUrl($string, $replace = "/[\s,<>\/\"&#%+?$@=]/")
    {
        $string = trim($string);
        $string = preg_replace($replace, "-", $string);
        $string = preg_replace('/[\\s-]+/', '-', $string);
        $string = preg_replace("/[\-]+/", "-", $string);
        if (file_exists(CONF_INSTALLATION_PATH . 'application/controllers/' . strtolower($string) . 'Controller' . '.php')) {
            return $string . '-' . rand(1, 100);
        }
        return trim($string, '-');
    }

    public static function recursiveDelete($str)
    {
        if (is_file($str)) {
            return @unlink($str);
        }
        if (is_dir($str)) {
            $scan = glob(rtrim($str, '/') . '/*');
            foreach ($scan as $index => $path) {
                static::recursiveDelete($path);
            }
            return @rmdir($str);
        }
    }

    public static function isCsvValidMimes()
    {
        return [
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/octet-stream',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
            'application/excel',
            'application/vnd.msexcel',
            'text/plain',
        ];
    }

    public static function getVideoDetail($url)
    {
        $data = [];
        $data['video_id'] = '';
        $data['video_thumb'] = '';
        $data['video_type'] = '';
        if (false !== strpos($url, 'youtube')) {
            $pattern = '%^# Match any youtube URL
                        (?:https?://)?  # Optional scheme. Either http or https
                        (?:www\.)?      # Optional www subdomain
                        (?:             # Group host alternatives
                          youtu\.be/    # Either youtu.be,
                        | youtube\.com  # or youtube.com
                          (?:           # Group path alternatives
                                /embed/     # Either /embed/
                          | /v/         # or /v/
                          | .*v=        # or /watch\?v=
                          )             # End path alternatives.
                        )               # End host alternatives.
                        ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
                        ($|&).*         # if additional parameters are also in query string after video id.
                        $%x';
            $result = preg_match($pattern, $url, $matches);
            if (false !== $result && isset($matches[1])) {
                $data['video_type'] = 1;
                $data['video_id'] = $matches[1];
                $data['video_thumb'] = 'http://img.youtube.com/vi/' . $data['video_id'] . '/1.jpg';
            }
        }
        return $data;
    }

    public static function setSeesionCookieParams()
    {
        $secure = FatApp::getConfig('CONF_USE_SSL', FatUtility::VAR_BOOLEAN, false);
        $params = ['httponly' => true, 'secure' => $secure, 'path' => CONF_WEBROOT_FRONT_URL,];
        if ($secure) {
            $params['samesite'] = 'none';
        }
        session_set_cookie_params($params);
    }

    public static function replaceStringData($str, $replacements = [], $replaceTags = false)
    {
        foreach ($replacements as $key => $val) {
            if ($replaceTags) {
                $val = strip_tags($val);
            }
            $str = str_replace($key, $val, $str);
            $str = str_replace(strtolower($key), $val, $str);
            $str = str_replace(strtoupper($key), $val, $str);
        }
        return $str;
    }

    public static function htmlEntitiesDecode($var, $stripJs = true)
    {
        if (is_array($var)) {
            foreach ($var as $key => $val) {
                $var[$key] = self::htmlEntitiesDecode($val, $stripJs);
            }
        } elseif (is_string($var) || is_numeric($var)) {
            $var = html_entity_decode($var, ENT_QUOTES, 'UTF-8');
            $var = $stripJs ? static::stripJavascript($var) : $var;
        }
        return $var;
    }

    public static function validateMaxUploadSize($uloadedFileSize)
    {
        $uloadedFileSize = FatUtility::int($uloadedFileSize);
        $maxUploadSizeAllowed = static::getMaximumFileUploadSize(true);

        if ((0 >= $uloadedFileSize) || ($uloadedFileSize > $maxUploadSizeAllowed)) {
            return false;
        }

        return true;
    }

    public static function getMaximumFileUploadSize($returnInBytes = false)
    {
        if (true === $returnInBytes) {
            return min(
                static::convertPHPSizeToBytes(ini_get('post_max_size')),
                static::convertPHPSizeToBytes(ini_get('upload_max_filesize'))
            );
        }

        /* Assuming upload_max_filesize and post_max_size having same units (in Normal conditions it would be same).  */
        $unit = strtoupper(substr(ini_get('upload_max_filesize'), -1));

        return min(substr(ini_get('upload_max_filesize'), 0, -1), substr(ini_get('post_max_size'), 0, -1)) . $unit;
    }

    public static function convertPHPSizeToBytes($sSize)
    {
        $unit = strtoupper(substr($sSize, -1));
        if (!in_array($unit, array('G', 'M', 'K'))) {
            return (int) $sSize;
        }

        $size = substr($sSize, 0, -1);
        switch ($unit) {
            case 'M':
                $size = FatUtility::int($size) * 1048576;
                break;
            case 'K':
                $size = FatUtility::int($size) * 1024;
                break;
            case 'G':
                $size = FatUtility::int($size) * 1073741824;
                break;
            default:
                $size = FatUtility::int($size);
                break;
        }
        return FatUtility::int($size);
    }

    public static function convertDuration($duration, $seconds = false, $format = true)
    {
        $formattedTime = [];
        $time = [];
        $hrs = floor($duration / 3600);
        if ($hrs > 0) {
            $formattedTime[] = $hrs . strtolower(Label::getLabel('LBL_H'));
        }
        $time[] = $hrs;

        $min = gmdate("i", $duration);
        if ($min > 0) {
            $formattedTime[] = $min . strtolower(Label::getLabel('LBL_M'));
        }
        $time[] = $min;

        if ($seconds) {
            $sec = gmdate("s", $duration);
            if ($sec > 0) {
                $formattedTime[] = $sec . strtolower(Label::getLabel('LBL_S'));
            }
            $time[] = $sec;
        }
        if ($format == true) {
            if($duration > 0) {
                return (count($formattedTime) > 0) ? implode(' ', $formattedTime) : '';
            } else {
                return '00' . strtolower(Label::getLabel('LBL_M'));
            }
        } else {
            return (count($time) > 0 && array_sum($time) > 0) ? implode(':', $time) : '';
        }
    }

    public static function removeSpecialChars($string)
    {
        $string = preg_replace('/[^A-Za-z0-9\s\x{0600}-\x{06FF}]+/u', '', $string);
        return CommonHelper::htmlEntitiesDecode($string);
    }

    public static function checkOfflineSessionsEnabled($offlineSessions = true, $type = null): bool
    {
        if (!FatApp::getConfig('CONF_ENABLE_OFFLINE_SESSIONS')) {
            return false;
        }
        if (!$offlineSessions) {
            return false;
        }
        return true;
    }

    public static function sanitizeInput($fields = array())
    {
        $status = true;

        $openScriptTagRegex = '/<\s*script\b[^>]*>/i';
        // Regex to match standalone </script> tags
        $closeScriptTagRegex = '/<\s*\/\s*script\s*>/i';

        foreach ($fields as $key => $value) {
            // Check for standalone opening or closing script tags
            $containsOpenScriptTag = preg_match($openScriptTagRegex, $value);
            $containsCloseScriptTag = preg_match($closeScriptTagRegex, $value);
            if ($containsOpenScriptTag || $containsCloseScriptTag) {
                $status = false;
                break;
            }
        }
        return $status;
    }

    public static function getCaptionsForFields($frm, $fields = array())
    {
        $captions = array();
        foreach ($fields as $key => $value) {
            $fld = $frm->getField($value);
            $captions[] = $fld->getCaption();
        }
        return $captions;
    }

    public static function setFormProperties($frm)
    {
        $frm->setValidationLangFile(CONF_INSTALLATION_PATH . 'public/validation/validation_labels.php');
        return $frm;
    }
    
    /**
     * Set referer url for redirection to the same page after signup
     *
     * @return string
     */
    public static function setRefererPageUrl()
    {
        $_SESSION['REFERER_PAGE_URL'] = static::getCurrentUrl();
    }
    
    /**
     * Get the referer page url
     *
     * @return string
     */
    public static function getRefererPageUrl()
    {
        return $_SESSION['REFERER_PAGE_URL'] ?? MyUtility::makeUrl('Home');
    }
}
