<?php
defined('SYSTEM_INIT') or die('Invalid Usage');
$pagination = '';
if ($pageCount <= 1) {
    return $pagination;
}
$linksToDisp = isset($linksToDisp) ? $linksToDisp : 2;
/* Current page number */
$pagesize = isset($pagesize) ? $pagesize : AppConstant::PAGESIZE;
/* padArgListTo boolean(T/F) // where to pad argument list (left/right) */
$padArgToLeft = (isset($padArgToLeft)) ? $padArgToLeft : true;
/* On clicking page link which js function need to call */
$callBackJsFunc = isset($callBackJsFunc) ? $callBackJsFunc : 'goToSearchPage';
$callBackJsFunc = $callBackJsFunc . '(xxpagexx);';
$prevSvg = '<span class="svg-icon"><svg class="icon icon--messaging"><use xlink:href="' . CONF_WEBROOT_URL . 'images/sprite.svg#prev"></use></svg></span>';
$nextSvg = '<span class="svg-icon"><svg class="icon icon--messaging"><use xlink:href="' . CONF_WEBROOT_URL . 'images/sprite.svg#next"></use></svg></span>';
$prevBtnHtml = '<a class="is-prev" href="javascript:void(0);" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Previous') . '">' . $prevSvg . '</a></li>';
$nextBtnHtml = '<a class="is-next"  href="javascript:void(0);" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Next') . '">' . $nextSvg . '</a></li>';
$pagination .= FatUtility::getPageString('', $pageCount, $pageno, '', '', $linksToDisp, '', '', '<li>' . $prevBtnHtml . '</li>', '<li>' . $nextBtnHtml . '</li>');
$ul = new HtmlElement('ul', array('class' => 'controls m-0',), $pagination, true);
$startIdx = (($pageno - 1) * $pagesize) + 1;
$to = ($startIdx + $pagesize) - 1;
$to = ($to > $recordCount) ? $recordCount : $to;
if ($pageno == 1 && $recordCount > $pagesize) {
    $ul->prependElement('li', ['class' => 'is-disabled'], $prevBtnHtml, true);
}
if ($to == $recordCount) {
    $ul->appendElement('li', ['class' => 'is-disabled'], $nextBtnHtml, true);
}
?>
<aside class="col-md-auto col-sm-5">
    <div class="d-flex align-items-center gap-3">
        <span><?php echo $startIdx . ' ' . Label::getLabel('Lbl_to') . ' ' . $to . ' ' . Label::getLabel('LBL_of') . ' ' . $recordCount; ?></span>
        <?php echo $ul->getHtml(); ?>
    </div>
</aside>