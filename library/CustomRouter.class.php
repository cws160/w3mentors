<?php

class CustomRouter
{

    static function setRoute(&$controller, &$action, &$params)
    {
        if (strtolower($controller) === 'api') {
            define('API_CALL', true);
            define('CONF_THEME_PATH', CONF_APPLICATION_PATH . 'apiviews/');
            $controller = ($action == 'index') ? 'home' : $action;
            $action = $params[0] ?? 'index';
            $params = array_slice($params, 1);
            return;
        }
        define('API_CALL', false);
        define('CONF_THEME_PATH', CONF_APPLICATION_PATH . 'views/');
        if (
                in_array($controller, SeoUrl::staticControllers()) ||
                !defined('SYSTEM_FRONT') || FatUtility::isAjaxCall()
        ) {
            return;
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI']);
        $uriQuery = urldecode($requestUri['query'] ?? '');
        $uriPath = urldecode($requestUri['path'] ?? '');
        $uriPath = array_values(array_filter(explode("/", $uriPath)));
        if (($uriPath[0] ?? '') == trim(CONF_WEBROOT_FRONTEND, '/')) {
            array_shift($uriPath);
        }
        $langCodes = Language::getCodes();
        $langCode = strtolower($uriPath[0] ?? '');
        $langId = $_COOKIE['CONF_SITE_LANGUAGE'] ?? CONF_DEFAULT_LANG;
        $urlLangId = array_search($langCode, $langCodes);

        if (CONF_LANGCODE_URL == AppConstant::YES) {
            if ($urlLangId !== false) {
                $langId = $urlLangId;
                array_shift($uriPath);
                if (CONF_DEFAULT_LANG == $langId) {
                    MyUtility::setSiteLanguage(Language::getData($langId), true);
                    $redirect = implode('/', $uriPath);
                    $redirect = empty($uriQuery) ? $redirect : $redirect . '?' . $uriQuery;
                    header("Location:/" . $redirect, true, 301);
                    header("Connection: close");
                    exit;
                }
            } elseif (CONF_DEFAULT_LANG != $langId) {
                $params = array_slice($uriPath, 2);
                $controller = $uriPath[0] ?? '';
                $action = $uriPath[1] ?? '';
                if (strtolower($controller) == 'dashboard') {
                    array_shift($uriPath);
                    $params = array_slice($uriPath, 2);
                    $controller = $uriPath[0] ?? '';
                    $action = $uriPath[1] ?? '';
                }
                $langCode = '/' . $langCodes[$langId];
                $redirect = $langCode . FatUtility::generateUrl($controller, $action, $params);
                $redirect = empty($uriQuery) ? $redirect : $redirect . '?' . $uriQuery;
                header("Location:" . $redirect, true, 301);
                header("Connection: close");
                exit;
            }
        } else {
            if ($urlLangId !== false) {
                array_shift($uriPath);
                $redirect = implode('/', $uriPath);
                $redirect = empty($uriQuery) ? $redirect : $redirect . '?' . $uriQuery;
                header("Location:/" . $redirect, true, 301);
                header("Connection: close");
                exit;
            }
        }
        $url = SeoUrl::getCustomUrl($langId, implode("/", $uriPath));
        if (!empty($url)) {
            $langCode = CONF_LANGCODE_URL ? '/' . $langCodes[$langId] : '';
            $uriPath = explode("/", $url['seourl_custom']);
            $params = array_slice($uriPath, 2);
            $controller = $uriPath[0] ?? '';
            $action = $uriPath[1] ?? '';
            if (strtolower($controller) == 'dashboard') {
                array_shift($uriPath);
                $params = array_slice($uriPath, 2);
                $controller = $uriPath[0] ?? '';
                $action = $uriPath[1] ?? '';
            }
            $redirect = $langCode . FatUtility::generateUrl($controller, $action, $params);
            $redirect = empty($uriQuery) ? $redirect : $redirect . '?' . $uriQuery;
            $redirect = explode("/", trim($redirect, "/"));
            foreach ($redirect as $key => $urlPath) {
                $redirect[$key] = urlencode($urlPath);
            }
            $customUrl = rtrim(CONF_WEBROOT_FRONTEND, '/') . $langCode . '/' . $url['seourl_custom'];
            header("Location:" . $customUrl, true, intval($url['seourl_httpcode']));
            header("Connection: close");
            exit;
        }

        $url = SeoUrl::getOriginalUrl(implode("/", $uriPath), FatUtility::int($urlLangId));
        if (!empty($url['seourl_original'])) {
            if ($url['totalRecord'] == 1) {
                define('CONF_SITE_LANGUAGE', $url['seourl_lang_id']);
            } else {
                define('CONF_SITE_LANGUAGE', $langId);
            }
            $uriPath = explode("/", $url['seourl_original']);
            $uriPath = array_values(array_filter($uriPath));
            $params = array_slice($uriPath, 2);
            $controller = $uriPath[0] ?? 'Home';
            $action = $uriPath[1] ?? 'index';
            if (strtolower($controller) == 'dashboard') {
                array_shift($uriPath);
                $params = array_slice($uriPath, 2);
                $controller = $uriPath[0] ?? 'Home';
                $action = $uriPath[1] ?? 'index';
            }
            return;
        }

        if (CONF_LANGCODE_URL == AppConstant::YES) {
            $langId = ($urlLangId !== false) ? $urlLangId : $langId;
            define('CONF_SITE_LANGUAGE', $langId);
            $params = array_slice($uriPath, 2);
            $controller = $uriPath[0] ?? 'Home';
            $action = $uriPath[1] ?? 'index';
            if (strtolower($controller) == 'dashboard') {
                array_shift($uriPath);
                $params = array_slice($uriPath, 2);
                $controller = $uriPath[0] ?? 'Home';
                $action = $uriPath[1] ?? 'index';
            }
        } else {
            define('CONF_SITE_LANGUAGE', $langId);
            $controller = empty($controller) ? 'Home' : $controller;
            $action = empty($action) ? 'index' : $action;
            if (strtolower($controller) == 'dashboard') {
                $controller = empty($action) ? 'Home' : $action;
                $action = $params[0] ?? 'index';
            }
        }
    }
}
