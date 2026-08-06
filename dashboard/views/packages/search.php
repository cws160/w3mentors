<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($packages) == 0) {
    $link = MyUtility::makeFullUrl('Teachers', '', [], CONF_WEBROOT_FRONTEND);
    $variables = [
        'msgHeading' => Label::getLabel('LBL_NO_CLASS_PACKAGE_FOUND'),
        'btn' => '<a href="' . MyUtility::makeFullUrl('GroupClasses', '', [], CONF_WEBROOT_FRONTEND) . '" class="btn btn--primary">' . Label::getLabel('LBL_FIND_CLASSES') . '</a>'
    ];
    if ($siteUserType == User::TEACHER) {
        $variables['btn'] = '<a href="javascript:void(0)" onclick="form(0);" class="btn btn--primary">' . Label::getLabel('LBL_ADD_PACKAGE') . '</a>';
    }
    $this->includeTemplate('_partial/no-record-found.php', $variables, false);
    return;
}
?>
<div class="results">
    <div class="-float-right">
        <div class="list-inline-item">
            <span class="box-hint badge--round bg-info  m-0 -no-border">&nbsp;</span>
            <?php echo Label::getLabel('LBL_ONLINE_SESSION'); ?>
        </div>
        <div class="list-inline-item">
            <span class="box-hint badge--round bg-yellow m-0 -no-border">&nbsp;</span>
            <?php echo Label::getLabel('LBL_IN-PERSON_SESSION'); ?>
        </div>
    </div>
    <?php foreach ($packages as $package) { ?>
        <div class="lessons-group mt-5">
            <date class="date uppercase small bold-600">&nbsp;</date>
            <!-- [ LESSON CARD ========= -->
            <div class="card-landscape">
                <div class="card-landscape__colum card-landscape__colum--first">
                    <div class="card-landscape__head">
                        <time class="card-landscape__time"><?php echo MyDate::showTime($package['grpcls_start_datetime']); ?></time>
                        <date class="card-landscape__date"><?php echo MyDate::showDate($package['grpcls_start_datetime']); ?></date>
                    </div>
                </div>
                <div class="card-landscape__colum card-landscape__colum--second">
                    <div class="gcard-landscape__body">
                        <div class="detal-list">
                            <div class="detal-list__item">
                                <?php
                                $tooltip = Label::getLabel('LBL_ONLINE_SESSION');
                                $classLbl = 'bg-info';
                                if ($package['grpcls_offline'] == AppConstant::YES) {
                                    $tooltip = Label::getLabel('LBL_IN-PERSON_SESSION');
                                    $classLbl = 'bg-yellow';
                                }
                                ?>
                                <span class="card-landscape__title">
                                    <span class="badge--round box-hint list-inline-item m-0 -no-border <?php echo $classLbl; ?>" title="<?php echo $tooltip; ?>">&nbsp;</span>
                                    <?php echo $package['grpcls_title']; ?>
                                </span>
                            </div>
                            <div class="detal-list__item">
                                <?php if ($siteUserType == User::LEARNER) { ?>
                                    <span class="card-landscape__status badge color-secondary badge--curve badge--small ms-0"><?php echo OrderPackage::getStatuses($package['ordpkg_status']); ?></span>
                                <?php } elseif ($siteUserType == User::TEACHER) { ?>
                                    <span class="card-landscape__status badge color-secondary badge--curve badge--small ms-0"><?php echo GroupClass::getStatuses($package['grpcls_status']); ?></span>
                                    <span class="card-landscape__status badge color-primary badge--curve badge--small ms-0"><?php echo Label::getLabel('LBL_ENTRY_FEE') . ' : ' . MyUtility::formatMoney($package['grpcls_entry_fee']); ?></span>
                                    <span class="card-landscape__status badge color-primary badge--curve badge--small ms-0"><?php echo Label::getLabel('LBL_BOOKED_SEATS') . ' : ' . $package['grpcls_booked_seats'] . '/' . $package['grpcls_total_seats']; ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-landscape__colum card-landscape__colum--third">
                    <div class="card-landscape__actions">
                        <div class="profile-meta">
                            <?php if ($siteUserType == User::LEARNER) { ?>
                                <div class="profile-meta__media">
                                    <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($package['teacher_full_name']); ?>">
                                        <?php echo '<img src="' . MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $package['grpcls_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL) . '?t=' . time() . '" alt="' . $package['teacher_full_name'] . '" />'; ?>
                                    </span>
                                </div>
                                <div class="profile-meta__details">
                                    <p class="bold-600 color-black"><?php echo $package['teacher_full_name']; ?></p>
                                    <p class="small"> <?php echo $package['teacher_country']; ?> </p>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="actions-group">
                            <?php
                            $queryString = '?package_id=' . $package['grpcls_id'];
                            if ($siteUserType == User::LEARNER) {
                                $queryString .= '&order_id=' . $package['ordpkg_order_id'];
                            }
                            ?>
                            <a href="<?php echo MyUtility::makeUrl('Classes', 'index') . $queryString; ?>" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--enter icon--18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#view'; ?>"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_VIEW_CLASSES'); ?></div>
                            </a>
                            <?php if ($package['canEdit']) { ?>
                                <a href="javascript:void(0);" onclick="form('<?php echo $package['grpcls_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--edit icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#edit'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_EDIT'); ?></div>
                                </a>
                            <?php } ?>
                            <?php if ($package['canCancel']) { ?>
                                <a href="javascript:void(0);" onclick="cancelSetup('<?php echo $package['grpcls_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--cancel icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#cancel'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_CANCEL'); ?></div>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] ========= -->
        </div>
    <?php } ?>
</div>
<?php
$pagingArr = [
    'page' => $post['pageno'],
    'pageSize' => $post['pagesize'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>