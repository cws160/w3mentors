<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$teachs = $srchFrm->getField('teachs');
$pageno = $srchFrm->getField('pageno');
$jslabels = json_encode([
    'allLanguages' => Label::getLabel('LBL_ALL_TEACH_LANGUAGES'),
    'selectTiming' => Label::getLabel('LBL_SELECT_TIMING'),
    'allPrices' => Label::getLabel('LBL_ALL_PRICES'),
]);
?>
<script>
    LABELS = <?php echo $jslabels; ?>;
</script>

<section class="section bg-gradiant section--page-header text-center">
    <div class="container container--md">
        <h2><?php echo str_replace('{teachlang}', ($sLanguage['tlang_name']), Label::getLabel('LBL_{teachlang}_LANGUAGE_PAGE_HEADING')); ?></h2>
        <?php echo $srchFrm->getFormTag(); ?>
        <div class="main-search">
            <div class="main-search__field main-search__trigger-js">
                <input type="text" class="" autocomplete="off" name="teach_language" onkeyup="onkeyupLanguage();" placeholder="<?php echo Label::getLabel('LBL_SEARCH_LANGUAGE'); ?>">
            </div>
            <div class="main-search__target main-search__target-js" style="display: none;">
                <div class="filter-dropdown">
                    <div class="filter-dropdown__body">
                        <div class="select-list select-list--vertical select-list--scroll">
                            <?php $this->includeTemplate('_partial/teach-languages.php', ['teachLanguages' => $teachs->options, 'values' => $teachs->value, 'langPage' => true, 'class' => 'list-vertical']); ?>
                            <ul class="list-vertical no-record-js" style="display:none">
                                <li><?php echo Label::getLabel('LBL_NO_RECORD_FOUND') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="main-search__action">
                <a class="main-search__submit" onclick="searchKeyword();" title="Search">
                    <svg class="icon icon--search">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                    </svg>
                </a>
                <div class="main-search__reset" onclick="clearKeyword();" style="display: none;" title="Reset">
                    <span class="close"></span>
                </div>
            </div>
            <input type="hidden" name="pageno" value="<?php echo $pageno->value; ?>" />
        </div>
        </form>
    </div>
</section>
<section class="section">
    <div class="container container--lg">
        <div class="page">
            <div class="page-header mb-xl-5 mb-4">
                <div class="row g-3 justify-content-between align-items-center">
                    <div class="col-auto">
                        <div class="search-result">
                            <h3 class="record-count-header">
                                <?php echo str_replace('{recordcount}', $recordCount ?? 0, Label::getLabel('LBL_FOUND_THE_BEST_{recordcount}_TEACHERS_FOR_YOU')); ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="page-panel">
                    <div class="page-panel__large">
                        <div class="page-listing" id="listing"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($sLanguage['tlang_description'])) { ?>
        <div class="container container--lg">
            <?php
            $desclbl = Label::getLabel('LBL_{language}_DESCRIPTION');
            $desclbl = str_replace('{language}', $sLanguage['tlang_name'], $desclbl);
            ?>
            <h4><?php echo $desclbl ?></h4>
            <p><?php echo htmlspecialchars_decode($sLanguage['tlang_description'] ?? ''); ?></p>
        </div>
    <?php  } ?>
</section>
<script>
    $(document).ready(function (){
        setTimeout(function () {
            if (window.__sharethis__) {
                window.__sharethis__.initialize();
            }
        }, 1000);
    });
</script>