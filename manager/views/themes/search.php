<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$arr_flds = array(
    'theme_title' => Label::getLabel('LBL_Theme_Color'),
    'theme_primary_color' => Label::getLabel('LBL_Primary_Color'),
    'theme_primary_inverse_color' => Label::getLabel('LBL_Primary_Inverse_Color'),
    'theme_secondary_color' => Label::getLabel('LBL_Secondary_Color'),
    'theme_secondary_inverse_color' => Label::getLabel('LBL_Secondary_Inverse_Color'),
    'theme_footer_color' => Label::getLabel('LBL_Footer_Color'),
    'theme_footer_inverse_color' => Label::getLabel('LBL_Footer_Inverse_Color'),
);
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table table--hovered'));
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', array(), $val);
}

$activeThemeId = FatApp::getConfig('CONF_ACTIVE_THEME');
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['theme_id']);

    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', array(), $sr_no);
                break;
            case 'theme_title':
                $activeString = ($activeThemeId == $row['theme_id']) ? ' <i class="icon ion-checkmark-circled is--active" title="' . Label::getLabel('LBL_ACTIVE_THEME') . '"></i>' : '';
                $td->appendElement('plaintext', array(), $row['theme_title'] . $activeString, true);
                break;
            case 'theme_primary_color':
            case 'theme_primary_inverse_color':
            case 'theme_secondary_color':
            case 'theme_secondary_inverse_color':
            case 'theme_footer_color':
            case 'theme_footer_inverse_color':
                $content = "<a href='javascript:void(0)' class = 'button small green theme-color-box' style='background-color:#" . $row[$key] . "; height: 11px;width: 11px;display: inline-block;'></a> ";
                $td->appendElement('plaintext', [], $content . '#' . $row[$key], true);
                break;
            case 'action':
                if ($canEdit) {
                    $action = new Action($row['theme_id']);
                    if ($row['theme_is_default'] == AppConstant::NO) {
                        $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'edit(' . $row['theme_id'] . ',"update")');
                        $action->addRemoveBtn(Label::getLabel('LBL_Delete'),  'deleteTheme(' . $row['theme_id'] . ')');
                    }
                    $action->addOtherBtn(Label::getLabel('LBL_Clone'),  'edit(' . $row['theme_id'] . ',"clone")', 'clone');
                    $url = MyUtility::makeUrl('Themes', 'preview', array($row['theme_id']));
                    $action->addOtherBtn(Label::getLabel('LBL_Preview'), 'javascript:void(0)', 'view', $url, '_blank');
                    if ($activeThemeId != $row['theme_id']) {
                        $action->addOtherBtn(Label::getLabel('LBL_Click_To_Activate'), 'activate(' . $row['theme_id'] . ')', 'active');
                    }
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
        }
    }
}
if (count($arr_listing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', array('colspan' => count($arr_flds)), Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, array(
    'name' => 'frmThemeSearchPaging'
));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount, 'pageSize' => $pageSize, 'adminLangId');
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>
