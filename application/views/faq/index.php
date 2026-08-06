<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<section class="section bg-gradiant section--page-header text-center">
    <div class="container container--xl">
        <h6 class="small-title"><?php echo strtoupper(Label::getLabel('LBL_FAQ')); ?></h6>
        <h2><?php echo Label::getLabel('LBL_faq_title_second'); ?></h2>
        <?php if (!empty($faqs)) { ?>
            <div class="main-search">
                <div class="main-search__field">
                    <input type="text" name="faq_search" placeholder="<?php echo Label::getLabel('LBL_FAQ_SEARCH_PLACEHOLDER_TXT'); ?>">
                </div>
                <div class="main-search__action">
                    <a class="main-search__submit" title="Search">
                        <svg class="icon icon--search">
                            <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/sprite.svg#search"></use>
                        </svg>
                    </a>
                    <div class="main-search__reset" onclick="clearKeyword();" style="display: none;" title="Reset">
                        <span class="close"></span>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</section>
<section class="section section--faq" id="faq-area" data-aos="fade-up" data-aos-duration="1000">
    <div class="container container--narrow">
        <nav class="tabs-wrapper text-center mb-3">
            <ul class="nav nav-pills justify-content-md-center gap-3" id="nav-tab" role="tablist">
                <?php
                $firstCatId = array_key_first($typeArr);
                foreach ($typeArr as $catId => $catName) {
                ?>
                    <li class="nav-item">
                        <button class="nav-link <?php echo ($firstCatId == $catId) ? 'active' : '' ?>" id="nav-tab<?php echo '__' . $catId ?>" data-bs-toggle="tab" data-bs-target="#nav__<?php echo '' . $catId ?>" type="button" role="tab" aria-controls="nav<?php echo '__' . $catId ?>" aria-selected="true"><?php echo $catName; ?></button>
                    <?php } ?>
                    </li>
            </ul>
        </nav>
        <div class="section__body">
            <div class="faq-container border-0">
                <?php if (empty($faqs)) { ?>
                    <div class="page-listing__body no-record-js mt-5" style="display: block;">
                        <div class="box -padding-30" style="margin-bottom: 30px;">
                            <div class="message-display">
                                <div class="message-display__icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 408">
                                        <path d="M488.468,408H23.532A23.565,23.565,0,0,1,0,384.455v-16.04a15.537,15.537,0,0,1,15.517-15.524h8.532V31.566A31.592,31.592,0,0,1,55.6,0H456.4a31.592,31.592,0,0,1,31.548,31.565V352.89h8.532A15.539,15.539,0,0,1,512,368.415v16.04A23.565,23.565,0,0,1,488.468,408ZM472.952,31.566A16.571,16.571,0,0,0,456.4,15.008H55.6A16.571,16.571,0,0,0,39.049,31.566V352.891h433.9V31.566ZM497,368.415a0.517,0.517,0,0,0-.517-0.517H287.524c0.012,0.172.026,0.343,0.026,0.517a7.5,7.5,0,0,1-7.5,7.5h-48.1a7.5,7.5,0,0,1-7.5-7.5c0-.175.014-0.346,0.026-0.517H15.517a0.517,0.517,0,0,0-.517.517v16.04a8.543,8.543,0,0,0,8.532,8.537H488.468A8.543,8.543,0,0,0,497,384.455h0v-16.04ZM63.613,32.081H448.387a7.5,7.5,0,0,1,0,15.008H63.613A7.5,7.5,0,0,1,63.613,32.081ZM305.938,216.138l43.334,43.331a16.121,16.121,0,0,1-22.8,22.8l-43.335-43.318a16.186,16.186,0,0,1-4.359-8.086,76.3,76.3,0,1,1,19.079-19.071A16,16,0,0,1,305.938,216.138Zm-30.4-88.16a56.971,56.971,0,1,0,0,80.565A57.044,57.044,0,0,0,275.535,127.978ZM63.613,320.81H448.387a7.5,7.5,0,0,1,0,15.007H63.613A7.5,7.5,0,0,1,63.613,320.81Z"></path>
                                    </svg>
                                </div>
                                <h5><?php echo Label::getLabel('LBL_NO_RESULT_FOUND!'); ?></h5>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php foreach ($faqs as $catId => $faqDetails) { ?>
                    <div id="<?php echo 'nav__' . $catId ?>" <?php echo ($firstCatId != $catId) ? '' : ''; ?> class="tab-pane <?php echo ($firstCatId == $catId) ? 'active' : '' ?>">
                        <?php foreach ($faqDetails as $ques) {
                            if (empty($ques['faq_title']) || empty($ques['faq_description'])) {
                                continue;
                            }
                        ?>
                            <div class="faq-row faq-group-js">
                                <button data-bs-toggle="collapse" data-bs-target="#description<?php echo $ques['faq_id']; ?>" class="faq-title faq__trigger collapsed">
                                    <h5><?php echo $ques['faq_title']; ?></h5>
                                </button>
                                <div class="faq__target faq__target-js collapse" data-bs-parent="#<?php echo 'nav__' . $catId ?>" id="description<?php echo $ques['faq_id']; ?>">
                                    <div class="faq-answer">
                                        <div class="editor-content"><?php echo nl2br($ques['faq_description']); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="page-listing__body no-record-js mt-5" style="display: none;">
            <div class="box -padding-30" style="margin-bottom: 30px;">
                <div class="message-display">
                    <div class="message-display__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 408">
                            <path d="M488.468,408H23.532A23.565,23.565,0,0,1,0,384.455v-16.04a15.537,15.537,0,0,1,15.517-15.524h8.532V31.566A31.592,31.592,0,0,1,55.6,0H456.4a31.592,31.592,0,0,1,31.548,31.565V352.89h8.532A15.539,15.539,0,0,1,512,368.415v16.04A23.565,23.565,0,0,1,488.468,408ZM472.952,31.566A16.571,16.571,0,0,0,456.4,15.008H55.6A16.571,16.571,0,0,0,39.049,31.566V352.891h433.9V31.566ZM497,368.415a0.517,0.517,0,0,0-.517-0.517H287.524c0.012,0.172.026,0.343,0.026,0.517a7.5,7.5,0,0,1-7.5,7.5h-48.1a7.5,7.5,0,0,1-7.5-7.5c0-.175.014-0.346,0.026-0.517H15.517a0.517,0.517,0,0,0-.517.517v16.04a8.543,8.543,0,0,0,8.532,8.537H488.468A8.543,8.543,0,0,0,497,384.455h0v-16.04ZM63.613,32.081H448.387a7.5,7.5,0,0,1,0,15.008H63.613A7.5,7.5,0,0,1,63.613,32.081ZM305.938,216.138l43.334,43.331a16.121,16.121,0,0,1-22.8,22.8l-43.335-43.318a16.186,16.186,0,0,1-4.359-8.086,76.3,76.3,0,1,1,19.079-19.071A16,16,0,0,1,305.938,216.138Zm-30.4-88.16a56.971,56.971,0,1,0,0,80.565A57.044,57.044,0,0,0,275.535,127.978ZM63.613,320.81H448.387a7.5,7.5,0,0,1,0,15.007H63.613A7.5,7.5,0,0,1,63.613,320.81Z"></path>
                        </svg>
                    </div>
                    <h5><?php echo Label::getLabel('LBL_NO_RESULT_FOUND!'); ?></h5>
                </div>
            </div>
        </div>
    </div>
</section>
<?php 
$contactUsBanner = ExtraPage::getBlockContent(ExtraPage::BLOCK_APPLY_TO_TEACH_FAQ_CONTACT, $siteLangId);
if(!empty($contactUsBanner)) {
    $this->includeTemplate('_partial/contact-us-section.php', ['contactUsBanner' => $contactUsBanner]);
}
?>
<script>
    $(document).ready(function() {
        $('input[name="faq_search"]').keyup(function() {
            filter = $(this).val().toUpperCase();
            filter = $.trim(filter);
            if (filter == '') {
                $('.main-search__reset').hide();
                $('.main-search__submit').show();
                $('.faq-row').show();
                $('.no-record-js').hide();
                return;
            }
            $('.main-search__reset').show();
            $('.main-search__submit').hide();
            $('.faq-row').hide();
            $('.faq-row .faq-title h5').each(function() {
                txtValue = $(this).text();
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    $(this).parents('.faq-row').show();
                }
            });
            let activeTabId = $('.nav-link.active').attr('data-bs-target');
            if ($(activeTabId).find('.faq-row:visible').length == 0) {
                $('.no-record-js').show();
            } else {
                $('.no-record-js').hide();
            }
        });
        $('.nav-link').click(function(){
            let targetId = $(this).attr('data-bs-target');
            if ($(targetId).find('.faq-row:visible').length == 0) {
                $('.no-record-js').show();
            } else {
                $('.no-record-js').hide();
            }
        });
    });
    function clearKeyword()
    {
        $('input[name="faq_search"]').val('');
        $('.main-search__reset').hide();
        $('.main-search__submit').show();
        $('.faq-row').show();
        $('.no-record-js').hide();
    }
</script>