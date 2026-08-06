<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

?>
<div class="container container--fixed">
    <div class="page__head">
        <a href="<?php echo MyUtility::makeUrl('Quizzes') ?>" class="page-back">
            <svg class="icon icon--back me-2">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#arrow-back"></use>
            </svg>
            <?php echo Label::getLabel('LBL_BACK_TO_QUIZZES'); ?>
        </a>
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-8">
                <h1 id="mainHeadingJs">
                    <?php echo Label::getLabel('LBL_MANAGE_QUIZZES'); ?>
                </h1>
                <p class="m-0">&nbsp;</p>
            </div>
            <div class="col-sm-auto"></div>
        </div>
    </div>
    <div class="page__body" id="pageContentJs">
    </div>
    <script>
        var siteLangId = "<?php echo $siteLangId ?>";
        $(document).ready(function() {
            form("<?php echo $quizId ?>");
        });
        var TYPE_SINGLE = <?php echo Question::TYPE_SINGLE; ?>;
        var TYPE_MULTIPLE = <?php echo Question::TYPE_MULTIPLE; ?>;
        var TYPE_TEXT = <?php echo Question::TYPE_TEXT; ?>;
    </script>