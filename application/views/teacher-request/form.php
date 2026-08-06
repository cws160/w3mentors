<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
?>
<?php $this->includeTemplate('teacher-request/_partial/header.php', ['siteLangId' => $siteLangId]); ?>
<div class="main-body">
    <section class="page-block section bg-gradiant section--registration">
        <div class="container container--narrow">
            <div class="page-block__cover" id="main-container"></div>
        </div>
    </section>
    <?php $this->includeTemplate('teacher-request/_partial/footer.php', ['siteLangId' => $siteLangId]); ?>
</div>
<script>
    $(document).ready(function() {
        getform(<?php echo $step; ?>);
    });
</script>