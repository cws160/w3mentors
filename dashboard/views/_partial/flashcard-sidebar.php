<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$flashcardSrchFrm->addFormTagAttribute('onsubmit', 'searchFlashcards(this); return false;');
?>
<!-- [ Flashcard-search ========= -->
<div class="fcard-search">
    <div class="fcard-search__head">
        <h6><?php echo Label::getLabel('LBL_FLASHCARDS'); ?><span></span></h6>
        <a href="javascript:void(0);" onclick="flashcardForm(0);" class="color-secondary underline pt-3 pb-3 flash-card-add-js"><?php echo Label::getLabel('LBL_Add'); ?></a>
    </div>
    <div class="fcard-search__body">
        <div id="flashcardSearchForm">
            <?php echo $flashcardSrchFrm->getFormTag(); ?>
            <?php echo $flashcardSrchFrm->getFieldHtml('keyword'); ?>
            <span class="form__action-wrap">
                <span class="svg-icon"><svg>
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                    </svg>
                </span>
            </span>
            </form>
        </div>
    </div>
</div>
<!-- ] -->
<div id="flashcard">
</div>