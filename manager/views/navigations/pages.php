<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if ($shortview != AppConstant::YES) { ?>
    <main class="main">
        <div class="container">
            <div class="breadcrumb-wrap">
                <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
                <div class="action-toolbar">
                    <a href="<?php echo MyUtility::makeUrl('Navigations'); ?>" class="btn btn-primary"><?php echo Label::getLabel('LBL_BACK'); ?></a>
                    <?php if ($canEdit) { ?>
                        <a href="javascript:void(0);" onclick="addNavigationLinkForm('<?php echo $nav_id; ?>', 0);" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
                    <?php } ?>
                </div>
            </div>
            <div class="card">
                <div class="card-table">
                    <div id="listing" class="table-responsive">
                    <?php } ?>
                    <?php
                    $arr_flds = [
                        'dragdrop' => '<i class="ion-arrow-move icon"></i>',
                        'listserial' => Label::getLabel('LBL_Sr._No'),
                        'nlink_identifier' => Label::getLabel('LBL_IDENTIFIER'),
                        'nlink_caption' => Label::getLabel('LBL_caption'),
                        'action' => Label::getLabel('LBL_Action'),
                    ];
                    if (!$canEdit) {
                        unset($arr_flds['dragdrop']);
                        unset($arr_flds['action']);
                    }
                    $tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'pageList']);
                    $th = $tbl->appendElement('thead')->appendElement('tr');
                    foreach ($arr_flds as $k => $val) {
                        $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
                        $e = $th->appendElement('th', $attr, $val, true);
                    }
                    $sr_no = 0;
                    foreach ($arrListing as $sn => $row) {
                        $sr_no++;
                        $tr = $tbl->appendElement('tr');
                        $tr->setAttribute("id", $row['nlink_id']);
                        foreach ($arr_flds as $key => $val) {
                            $td = $tr->appendElement('td');
                            switch ($key) {
                                case 'dragdrop':
                                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                                    $td->setAttribute("class", 'dragHandle');
                                    break;
                                case 'listserial':
                                    $td->appendElement('plaintext', [], $sr_no);
                                    break;
                                case 'nlink_identifier':
                                case 'nlink_caption':
                                    $td->appendElement('plaintext', [], $row[$key], true);
                                    break;
                                case 'action':
                                    if ($canEdit) {
                                        $action = new Action($row['nlink_nav_id']);
                                        $action->addEditBtn(Label::getLabel('LBL_Edit'),  'addNavigationLinkForm(' . $row['nlink_nav_id'] . ',' . $row['nlink_id'] . ')');
                                        $action->addRemoveBtn(Label::getLabel('LBL_Delete'),  'deleteNavigationLink(' . $row['nlink_nav_id'] . ',' . $row['nlink_id'] . ')');
                                        $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                                    }
                                    break;
                                default:
                                    $td->appendElement('plaintext', [], $row[$key], true);
                                    break;
                            }
                        }
                    }
                    /* } */
                    if (count($arrListing) == 0) {
                        $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
                    }
                    echo $tbl->getHtml();
                    ?>

                    <?php if ($shortview != AppConstant::YES) { ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php } ?>
<script>
    $(document).ready(function() {
        $('#pageList').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('Navigations', 'updateNlinkOrder'), order, function(res) {
                    reloadlist();
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>