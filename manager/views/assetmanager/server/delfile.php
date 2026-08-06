<?php

include_once(dirname(dirname(__FILE__)) . "/config.php");
$path_for_images = CONF_EDITOR_PATH;
$root = WEBSITEROOT_LOCALPATH . $path_for_images . '/';
$file = $root . $_POST["file"];
if (file_exists($file)) {
    unlink($file);
    FatUtility::dieJsonSuccess(Label::getLabel('LBL_IMAGE_DELETE_SUCCESSFULLY'));
} else {
    FatUtility::dieJsonError(Label::getLabel('LBL_IMAGE_NOT_DELETE_SUCCESSFULLY'));
}
