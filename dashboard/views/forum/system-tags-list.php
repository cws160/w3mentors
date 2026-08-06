<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (0 < count($systemTags)) { ?>
    <div class="tags">
        <div class="tags__overflow">
            <?php foreach ($systemTags as $sn => $row) { ?>
                <a data-tag-id="<?php echo $row['ftag_id']; ?>" id="systemtag_<?php echo $row['ftag_id']; ?>" onclick="scubscribe(<?php echo $row['ftag_id']; ?>)" href="javascript:void(0);" class="tags__item badge badge--curve color-primary">
                    <?php echo $row['ftag_name']; ?>
                </a>
            <?php } ?>
        </div>
    </div>
    <?php
} else {
    $this->includeTemplate('_partial/no-record-found.php');
}
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'], $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>
<style>
    .tags .icon--alert {
        font-weight: bold;
        fill: var(--color-yellow);
    }
</style>
