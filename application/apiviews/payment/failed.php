<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="payment-page">
    <div class="cc-payment">
        <div class="logo-payment">
            <?php echo MyUtility::getLogo(); ?>
        </div>
        <div class="reff row">
            <div class="col-lg-6 col-md-6 col-sm-12">
                <p><?php echo Label::getLabel('LBL_PAYABLE_AMOUNT'); ?> : <strong><?php echo MyUtility::formatMoney($order['order_net_amount']) ?></strong> </p>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12">
                <p><?php echo Label::getLabel('LBL_ORDER_INVOICE'); ?>: <strong><?php echo Order::formatOrderId($order["order_id"]); ?></strong></p>
            </div>
        </div>
        <div class="payment-from">
            <div class="payable-form__body">
                <div class="message-display message-display--small">
                    <div class="message-display__icon">
                        <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 200 200">
                            <path d="M150,87.869V34.9L115.088,0H0V200H150v-0.366A56.228,56.228,0,0,0,150,87.869ZM112.488,15.082L134.912,37.5H112.488V15.082ZM12.5,187.5V12.488H100V49.994h37.5V87.869A56,56,0,0,0,108.423,100H25v12.5H96.982A55.964,55.964,0,0,0,90.768,125H25v12.5H87.869a55.839,55.839,0,0,0,20.566,50H12.5Zm131.25-.732a43.024,43.024,0,1,1,43.018-43.018A43.111,43.111,0,0,1,143.75,186.768ZM25,75H125V87.5H25V75Z"></path>
                            <path fill="#fd4444" d="M156.25,118.75l-12.5,12.5-12.5-12.5-12.5,12.5,12.5,12.5-12.5,12.5,12.5,12.5,12.5-12.5,12.5,12.5,12.5-12.5-12.5-12.5,12.5-12.5Z"></path>
                        </svg>
                    </div>
                    <h1><?php echo Label::getLabel('LBL_PAYMENT_FAILED'); ?></h1>
                    <?php $msgs = array_merge($messageData['msgs'], $messageData['errs'], $messageData['info'], $messageData['dialog']); ?>
                    <?php if (count($msgs) > 0) { ?>
                        <p><?php echo current($msgs); ?></p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>