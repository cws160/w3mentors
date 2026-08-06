<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Change_Password'); ?></h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $pwdFrm->addFormTagAttribute('class', 'form');
                        $pwdFrm->setFormTagAttribute('autocomplete', 'off');
                        echo $pwdFrm->getFormtag();
                        ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"><?php echo Label::getLabel('LBL_CURRENT_PASSWORD'); ?><span class="spn_must_field">*</span></label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php
                                            $curPwd = $pwdFrm->getField('current_password');
                                            $curPwd->setFieldTagAttribute('autocomplete', 'off');
                                            echo $pwdFrm->getFieldHTML('current_password');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"><?php echo Label::getLabel('LBL_NEW_PASSWORD'); ?><span class="spn_must_field">*</span></label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $pwdFrm->getFieldHTML('new_password'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"><?php echo Label::getLabel('LBL_CONFIRM_NEW_PASSWORD'); ?><span class="spn_must_field">*</span></label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $pwdFrm->getFieldHTML('conf_new_password'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label"></label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $pwdFrm->getFieldHTML('btn_submit'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </form>
                        <?php echo $pwdFrm->getExternalJS(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>