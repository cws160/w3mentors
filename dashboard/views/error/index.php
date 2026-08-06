<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="container container--fixed">
    <div class="page__head"></div>
    <div class="page__body page__body-flex">



        <div class="error">
            <div class="row">
                <div class="col-md-5 col-xl-4">
                    <div class="error__media">
                        <img src="<?php echo CONF_WEBROOT_FRONTEND; ?>images/404.png">
                    </div>
                </div>
                <div class="col-md-7 col-xl-8">
                    <div class="error__content align-left mb-4">
                        <h3><?php echo Label::getLabel('LBL_SORRY_THE_PAGE_CANNOT_BE_FOUND'); ?></h3>
                        <p>The page you are looking for might have been removed, had its name changed, or is temporarily unavailable. Please try the following::</p>
                        <ul class="list-group list-group--line">
                            <li class="list-group--item">Make sure that the web address displayed is spelled and formatted correctly</li>
                            <li class="list-group--item">If you reached here by clicking a link, let us know that the link is incorrect</li>
                            <li class="list-group--item">Whoops! Forget that this ever happened, and go find a tutor..</li>
                        </ul>
                        <a href="<?php echo MyUtility::makeUrl('Teachers', '', [], CONF_WEBROOT_FRONTEND); ?>" class="btn btn--primary"><?php echo Label::getLabel('LBL_FIND_A_TEACHER'); ?></a>
                    </div>
                </div>
            </div>
        </div>


    </div>