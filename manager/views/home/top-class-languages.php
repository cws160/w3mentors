<?php
if (empty($statsInfo)) {
    echo "<div class='no-record-found h-100 d-flex align-items-center justify-content-center'>" . Label::getLabel('LBL_NO_RECORD_FOUND') . "</div>";
    exit;
}
?>
<div class="scrollbar">
    <ul class="list list--table">
        <?php
        foreach ($statsInfo as $row) {
            echo '<li>' . $row['language'] . ' <span class="count">' . $row['totalsold'] . " " . Label::getLabel('LBL_SOLD'). '</span></li>';
        } ?>
    </ul>
</div>