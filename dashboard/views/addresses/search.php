<div class="content-panel__head">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_MANAGE_ADDRESSES'); ?></h5>
        </div>
        <div><a href="javascript:void(0);" onclick="addressForm(0);" class="btn btn--small btn--bordered color-secondary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a></div>
    </div>
</div>
<div class="content-panel__body">
    <div class="form">
        <div class="form__body p-0">
            <div class="table-scroll">
                <table class="table table--bordered table--responsive">
                    <tr class="title-row">
                        <th><?php echo $lblAddress = Label::getLabel('LBL_Address'); ?></th>
                        <th><?php echo $lblAction = Label::getLabel('LBL_ACTIONS'); ?></th>
                    </tr>
                    <?php foreach ($records as $address) { ?>
                        <tr id="address-<?php echo $address['usradd_id']; ?>">

                            <td>
                                <div class="address-group">
                                    <div class="address-group__icon">
                                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 20.8995L16.9497 15.9497C19.6834 13.2161 19.6834 8.78392 16.9497 6.05025C14.2161 3.31658 9.78392 3.31658 7.05025 6.05025C4.31658 8.78392 4.31658 13.2161 7.05025 15.9497L12 20.8995ZM12 23.7279L5.63604 17.364C2.12132 13.8492 2.12132 8.15076 5.63604 4.63604C9.15076 1.12132 14.8492 1.12132 18.364 4.63604C21.8787 8.15076 21.8787 13.8492 18.364 17.364L12 23.7279ZM12 13C13.1046 13 14 12.1046 14 11C14 9.89543 13.1046 9 12 9C10.8954 9 10 9.89543 10 11C10 12.1046 10.8954 13 12 13ZM12 15C9.79086 15 8 13.2091 8 11C8 8.79086 9.79086 7 12 7C14.2091 7 16 8.79086 16 11C16 13.2091 14.2091 15 12 15Z"></path>
                                        </svg>
                                    </div>
                                    <div class="address-group__content">
                                        <div class="d-flex mb-1">
                                            <h6 class="me-2"><?php echo UserAddresses::getAddressTypes($address['usradd_type']) ?></h6>
                                            <?php if ($address['usradd_default'] == AppConstant::YES) { ?>
                                                <span class="badge badge--round badge--small m-0 bg-dark"><?php echo Label::getLabel('LBL_DEFAULT'); ?></span>
                                                <br>
                                            <?php } ?>
                                        </div>
                                        <?php echo UserAddresses::format($address); ?><br>
                                        <?php echo $address['usradd_phone']; ?>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="flex-cell">
                                    <div class="flex-cell__content">
                                        <div class="actions-group actions-group--address">
                                            <a href="javascript:void(0);" onclick="addressForm('<?php echo $address['usradd_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                                <svg class="icon icon--issue icon--small">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#edit'; ?>"></use>
                                                </svg>
                                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_EDIT'); ?></div>
                                            </a>
                                            <a href="javascript:void(0);" onclick="removeAddress('<?php echo $address['usradd_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                                <svg class="icon icon--issue icon--small">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#trash'; ?>"></use>
                                                </svg>
                                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_DELETE'); ?></div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (count($records) < 1) { ?>
                        <tr>
                            <td colspan="8"><?php echo Label::getLabel('LBL_NO_RECORD_FOUND') ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        <div class="form__actions">

        </div>
    </div>
</div>