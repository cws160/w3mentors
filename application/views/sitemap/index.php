<section class="section section--page">
    <div class="container container--fixed">
        <?php //pr($urls); 
        foreach ($urls as $language => $urlsList) {; ?>
            <?php if (!empty($language)) { ?>
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12">
                        <h2><?php echo str_replace('{language}', $language, Label::getLabel('LBL_{language}_URLS')); ?> </h2>
                    </div>
                </div>
            <?php } ?>
            <div class="row">
            <?php foreach ($urlsList as $title => $urlData) { ?>
                    <div class="col-xl-3 col-lg-3 col-md-3">
                        <h5 style="font-size:1.6em;"><?php echo $title; ?></h5>
                        <ol style="margin:0 0 30px 0; padding:0; list-style:inside decimal;">
                            <?php foreach ($urlData as $url) { ?>
                                <li><a href="<?php echo $url['url'] ?>"><?php echo $url['value'];  ?></a></li>
                            <?php } ?>
                        </ol>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</section>