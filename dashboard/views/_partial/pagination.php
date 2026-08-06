<?php
defined('SYSTEM_INIT') or die('Invalid Usage');
$pagination = '';
if ($pageCount <= 1) {
    return $pagination;
}
$pageNumber = $page;
/* Number of links to display */
$linksToDisp = isset($linksToDisp) ? $linksToDisp : 3;
/* Current page number */
/* arguments mixed(array/string(comma separated)) // function arguments */
$arguments = (isset($arguments)) ? $arguments : null;
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
    '<li><button class="is-backward"  onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_First') . '"></button></li>',
    '<li><button class="is-forward" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Last') . '"></button></li>',
    '<li><button class="is-prev" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Previous') . '"></button></li>',
    '<li><button class="is-next" onclick="' . $callBackJsFunc . '" title="' . Label::getLabel('LBL_Next') . '"></button></li>'
);
$ul = new HtmlElement('ul', [], $pagination, true);

$showform = ($pageNumber - 1) * $pageSize + 1;
$showtill = ($recordCount < $showform + $pageSize - 1) ? $recordCount : $showform + $pageSize - 1;
$showtotal = ($recordCount == SEARCH_MAX_COUNT) ? $recordCount . '+' : $recordCount;
$showpaging = str_replace(
    ['{showform}', '{showtill}', '{showtotal}'],
    [$showform, $showtill, $showtotal],
    Label::getLabel('LBL_SHOWING_{showform}_to_{showtill}_of_{showtotal}_Entries')
);
?>

<div class="paging-controls">
    <p class="mb-0"><?php echo $showpaging; ?></p> &nbsp;&nbsp;
    <div class="pagination">
        <?php echo $ul->getHtml(); ?>
    </div>

</div>