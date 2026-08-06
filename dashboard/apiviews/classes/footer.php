</main>
<!-- ] -->
</div>
</body>
<!-- Custom Loader -->
<div id="app-alert" class="alert-position alert-position--top-right fadeInDown animated"></div>
<script>
<?php if (Message::getMessageCount() > 0) { ?>
        fcom.success('<?php echo Message::getData()['msgs'][0]; ?>');
<?php } if (Message::getDialogCount() > 0) { ?>
        fcom.warning('<?php echo Message::getData()['dialog'][0]; ?>');
<?php } if (Message::getErrorCount() > 0) { ?>
        fcom.error('<?php echo Message::getData()['errs'][0]; ?>');
<?php } ?>
</script>
</html>