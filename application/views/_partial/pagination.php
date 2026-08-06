<?php
defined('SYSTEM_INIT') or die('Invalid Usage');
$pagination = '';
if ($pageCount <= 1) {
    return $pagination;
}
/* Number of links to display */
$linksToDisp = isset($linksToDisp) ? $linksToDisp : 3;
/* Current page number */
$pageNumber = $page;
/* arguments mixed(array/string(comma separated)) // function arguments */
$arguments = (isset($arguments)) ? $arguments : null;
$pageSize = (isset($pageSize)) ? $pageSize : AppConstant::PAGESIZE;
/* padArgListTo boolean(T/F) // where to pad argument list (left/right) */
$padArgToLeft = (isset($padArgToLeft)) ? $padArgToLeft : true;
/* On clicking page link which js function need to call */
$callBackJsFunc = isset($callBackJsFunc) ? $callBackJsFunc : 'goToSearchPage';
if (null != $arguments) {
    if (is_array($arguments)) {
        $args = implode(', ', $arguments);
    } elseif (is_string($arguments)) {
        $args = $arguments;
    }
    if ($padArgToLeft) {
        $callBackJsFunc = $callBackJsFunc . '(' . $args . ', xxpagexx);';
    } else {
        $callBackJsFunc = $callBackJsFunc . '(xxpagexx, ' . $args . ');';
    }
} else {
    $callBackJsFunc = $callBackJsFunc . '(xxpagexx);';
}
$pagination .= FatUtility::getPageString(
                '<li><button onclick="' . $callBackJsFunc . '">xxpagexx</button></li>',
                $pageCount,
                $pageNumber,
                '<li><button class="is-active">xxpagexx</button></li>',
                '',
                $linksToDisp,
                '<li><button class="is-backward"  onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Previous') . '"></button></li>',
                '<li><button class="is-forward" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Next') . '"></button></li>',
                '<li><button class="is-prev" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Previous') . '"></button></li>',
                '<li><button class="is-next" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Next_2') . '"></button></li>'
);
$ul = new HtmlElement('ul', [], $pagination, true);
?>
<div class="table-controls padding-6">
    <div class="pagination pagination--centered">
        <?php echo $ul->getHtml(); ?>
    </div>
</div>