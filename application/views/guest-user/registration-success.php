<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<section class="section section--grey section--page">
    <div class="container container--fixed">
        <div class="page-panel -clearfix">
            <div class="page__panel-narrow">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-8 col-md-10">
                        <div class="box -padding-30 -skin">
                            <div class="message-display">
                                <div class="message-display__icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
                                        <path fill="#000" d="M105.823,52.206a4.4,4.4,0,0,0-4.4,4.393v4.425a43.258,43.258,0,0,1-43.3,43.188H58.092a43.213,43.213,0,1,1,.024-86.426h0.026a43.111,43.111,0,0,1,17.6,3.741A4.395,4.395,0,1,0,79.325,13.5,51.871,51.871,0,0,0,58.147,9H58.116a52,52,0,1,0-.029,104h0.031a52.054,52.054,0,0,0,52.108-51.973V56.6A4.4,4.4,0,0,0,105.823,52.206Z" transform="translate(-0.516 -1)"/>
                                        <path class="-color-fill" d="M113.706,15.075a4.409,4.409,0,0,0-6.226,0L58.117,64.335,46.918,53.16a4.4,4.4,0,0,0-6.226,6.213L55,73.655a4.409,4.409,0,0,0,6.226,0l52.476-52.367A4.386,4.386,0,0,0,113.706,15.075Z" transform="translate(-0.516 -1)"/>
                                    </svg>
                                </div>
                                <span class="-gap"></span>
                                <h1 class="-color-secondary"><?php echo Label::getLabel('MSG_Congratulations'); ?></h1>
                            </div>
                            <hr/>
                            <span class="-gap"></span>
                            <div class="-align-center">
                                <h6><?php echo $registrationMsg; ?> </h6>
                            </div>
                            <span class="-gap"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>