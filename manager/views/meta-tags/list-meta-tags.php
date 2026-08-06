<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="meta-tag-tbl">
    <?php if (!empty($frmSearch)) { ?>
        <?php if ($showFilters) { ?>
            <div class="card">
                <div class="card-head js--filter-trigger">
                    <h4> <?php echo Label::getLabel('LBL_SEARCH...'); ?></h4>
                </div>
                <div class="card-body js--filter-target" style="display:none;">
                    <?php
                    $frmSearch->addFormTagAttribute('class', 'form');
                    $frmSearch->addFormTagAttribute('onsubmit', 'searchMetaTag(this);return false;');
                    $frmSearch->setFormTagAttribute('id', 'frmSearch');
                    ($frmSearch->getField('keyword')) ? $frmSearch->getField('keyword')->addFieldtagAttribute('class', 'search-input') : NUll;
                    ($frmSearch->getField('hasTagsAssociated')) ? $frmSearch->getField('hasTagsAssociated')->addFieldtagAttribute('class', 'search-input') : NUll;
                    $submitBtn = $frmSearch->getField('btn_submit');
                    $clearbtn = $frmSearch->getField('btn_clear');
                    $submitBtn->attachField($clearbtn);
                    $clearbtn->addFieldtagAttribute('onclick', 'clearSearch();');
                    $submitBtn->developerTags['col'] = 6;
                    echo $frmSearch->getFormHtml();
                    ?>
                </div>
            </div>
        <?php
        } else {
            echo $frmSearch->getFormHtml();
        }
        ?>
    <?php } ?>
    <div class="card">
        <?php if ($canEdit && $canAdd) { ?>
            <div class="card-head">
                <div class="card-head-label">
                    <h3 class="card-head-title">
                        <?php echo Label::getLabel('LBL_OTHER_META_TAGS_LISTING'); ?>
                    </h3>
                </div>
                <a href="javascript:void(0);" onclick="addMetaTagForm(0, '<?php echo $metaType; ?>', 0)" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
            </div>
        <?php } ?>
        <div class="card-table">
            <div id="listing" class="table-responsive">
                <div class="table-processing loaderJs">
                    <div class="spinner spinner--sm spinner--brand"></div>
                </div>
            </div>
        </div>
    </div>
</div>