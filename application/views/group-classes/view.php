<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$isPackage = ($class['grpcls_type'] == GroupClass::TYPE_PACKAGE);
$bookedSeats = $class['grpcls_booked_seats'] + $class['grpcls_unpaid_seats'];
$teacherName = $class['user_full_name'];
?>
<title><?php echo $class['grpcls_title']; ?></title>
<!-- [ MAIN BODY ========= -->
<section class="section bg-gradiant section--page-header">
    <div class="container container--narrow">
        <div class="breadcrumbs mb-4 p-sm-0 px-2">
            <ul>
                <li><a href="<?php echo MyUtility::makeUrl(); ?>"><?php echo Label::getLabel('LBL_Home'); ?></a></li>
                <li><a href="<?php echo MyUtility::makeUrl('GroupClasses'); ?>"><?php echo Label::getLabel('LBL_Group_Classes'); ?></a></li>
                <li><?php echo $class['grpcls_title']; ?></li>
            </ul>
        </div>
        <div class="details-view p-sm-0 px-2">
            <div class="details-view__media">
                <div class="course-preview">
                    <div class="course-preview__media ratio ratio--16by9">
                        <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_GROUP_CLASS_BANNER, $class['grpcls_id'], Afile::SIZE_LARGE]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $class['grpcls_title']; ?>" />
                    </div>
                </div>
            </div>
            <div class="details-view__content">
                <a href="<?php echo MyUtility::makeUrl('Teachers', 'view', [$class['user_username']]) ?>" class="profile-meta d-flex align-items-center gap-3 mb-4">
                    <div class="profile-meta__media">
                        <span class="avtar avtar--medium avtar--round" data-title="D">
                            <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $class['grpcls_teacher_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $class['user_first_name'] . ' ' . $class['user_last_name']; ?>" />
                        </span>
                    </div>
                    <div class="profile-meta__details">
                        <h4><?php echo $class['user_full_name']; ?></h4>
                        <div class="rating mt-2">
                            <svg class="rating__media">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating'; ?>"></use>
                            </svg>
                            <span class="rating__value"><?php echo $class['testat_ratings']; ?></span>
                            <span class="rating__count">(<?php echo $class['testat_reviewes']; ?>)</span>
                        </div>
                    </div>
                </a>
                <hgroup>
                    <h1 class="page-heading"><?php echo $class['grpcls_title']; ?></h1>
                </hgroup>

            </div>
        </div>
    </div>
</section>
<section class="section">
    <div class="container container--narrow">
        <div class="page-flex">
            <!-- ] -->
            <!-- [ PANEL SMALL ========= -->
            <div class="page-flex__small">
                <div class="page-flex__sticky scrolling" id="STICKY">
                    <div class="page-box">
                        <div class="page-box__head">
                            <h5><?php echo Label::getLabel('LBL_This_Group_Classes_Includes:'); ?></h5>
                        </div>
                        <div class="page-box__body">
                            <div class="course-options">
                                <ul>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--language" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <path d="M5.388,5.562a6.4,6.4,0,0,0,7.054,10.355,2.765,2.765,0,0,0-.192-1.378,9.843,9.843,0,0,0-1.8-2.275c-.27-.284-.253-.5-.156-1.15l.01-.073c.066-.443.176-.706,1.668-.942a1.024,1.024,0,0,1,1.234.6l.093.138a1.465,1.465,0,0,0,.75.6c.132.06.3.136.516.26.522.3.522.635.522,1.373v.084a4.2,4.2,0,0,1-.078.827,6.4,6.4,0,0,0-2.484-9.873A6.3,6.3,0,0,0,11.26,5.128c-.108.148-.262.906-.76.968a2.934,2.934,0,0,1-.49-.007c-.5-.032-1.178-.076-1.4.515A2.343,2.343,0,0,0,8.9,8.524a.467.467,0,0,1,.037.415.811.811,0,0,1-.234.4,2.307,2.307,0,0,1-.335-.344A2.194,2.194,0,0,0,7.4,8.226c-.147-.041-.309-.074-.466-.108-.439-.092-.936-.2-1.052-.443A1.731,1.731,0,0,1,5.8,6.982a2.547,2.547,0,0,0-.163-1.076,1.021,1.021,0,0,0-.245-.344ZM10,18a8,8,0,1,1,8-8A8,8,0,0,1,10,18Z" transform="translate(2 2)"></path>
                                            </svg>
                                        </span>
                                        <?php
                                        $langNames = [];
                                        if ($class['grpcls_tlang_name']) {
                                            foreach ($class['grpcls_tlang_name'] as $clsname) {
                                                $langNames[] = $clsname['name'];
                                            }
                                        }
                                        ?>
                                        <span class="course-options__item-label"><?php echo Label::getLabel('LBL_Subject') . ' : ' . implode(' / ', $langNames); ?> </span>
                                    </li>
                                    <?php if ($class['grpcls_offline'] == AppConstant::YES) { ?>
                                        <li class="course-options__item">
                                            <span class="course-options__item-media">
                                                <svg class="icon icon--language" height="24" id="icon" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                                                    <g id="grid_system" />
                                                    <g id="_icons">
                                                        <g>
                                                            <circle cx="12" cy="17.5" r="1.5" />
                                                            <path d="M19.9,18.5l0.8-0.8c0.4-0.4,0.4-1,0-1.4s-1-0.4-1.4,0l-0.8,0.8l-0.8-0.8c-0.4-0.4-1-0.4-1.4,0s-0.4,1,0,1.4l0.8,0.8    l-0.8,0.8c-0.4,0.4-0.4,1,0,1.4c0.2,0.2,0.5,0.3,0.7,0.3s0.5-0.1,0.7-0.3l0.8-0.8l0.8,0.8c0.2,0.2,0.5,0.3,0.7,0.3    s0.5-0.1,0.7-0.3c0.4-0.4,0.4-1,0-1.4L19.9,18.5z" />
                                                            <path d="M12,12.2c-1.4,0-2.7,0.6-3.6,1.6c-0.4,0.4-0.4,1,0,1.4c0.4,0.4,1,0.4,1.4,0c1.2-1.3,3.2-1.3,4.3,0    c0.2,0.2,0.5,0.3,0.7,0.3c0.2,0,0.5-0.1,0.7-0.3c0.4-0.4,0.4-1,0-1.4C14.7,12.8,13.4,12.2,12,12.2z" />
                                                            <path d="M16.8,12.2c0.2,0.2,0.4,0.3,0.7,0.3c0.3,0,0.5-0.1,0.7-0.3c0.4-0.4,0.4-1,0-1.4c-1.7-1.6-3.9-2.5-6.1-2.5    s-4.5,0.9-6.1,2.5c-0.4,0.4-0.4,1,0,1.4c0.4,0.4,1,0.4,1.4,0c1.3-1.3,3-2,4.8-2S15.4,10.9,16.8,12.2z" />
                                                            <path d="M19.3,9.2c0.2,0.2,0.4,0.3,0.7,0.3c0.3,0,0.5-0.1,0.7-0.3c0.4-0.4,0.3-1-0.1-1.4c-2.4-2.2-5.5-3.4-8.7-3.4    S5.8,5.5,3.3,7.7c-0.4,0.4-0.4,1-0.1,1.4c0.4,0.4,1,0.4,1.4,0.1C6.8,7.3,9.3,6.3,12,6.3S17.2,7.3,19.3,9.2z" />
                                                        </g>
                                                    </g>
                                                </svg>
                                            </span>
                                            <span class="course-options__item-label"><?php echo Label::getLabel('LBL_OFFLINE'); ?></span>
                                        </li>
                                    <?php } ?>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--calendar" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <path d="M12.5,2.4h2.8a.7.7,0,0,1,.7.7V14.3a.7.7,0,0,1-.7.7H2.7a.7.7,0,0,1-.7-.7V3.1a.7.7,0,0,1,.7-.7H5.5V1H6.9V2.4h4.2V1h1.4ZM11.1,3.8H6.9V5.2H5.5V3.8H3.4V6.6H14.6V3.8H12.5V5.2H11.1ZM14.6,8H3.4v5.6H14.6Z" transform="translate(3 4)"></path>
                                            </svg>
                                        </span>
                                        <span class="course-options__item-label"><?php echo MyDate::showDate($class['grpcls_start_datetime']); ?></span>
                                    </li>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--time" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <path d="M10,18a8,8,0,1,1,8-8A8,8,0,0,1,10,18Zm0-1.6A6.4,6.4,0,1,0,3.6,10,6.4,6.4,0,0,0,10,16.4Zm.8-6.4H14v1.6H9.2V6h1.6Z" transform="translate(2 2)"></path>
                                            </svg>
                                        </span>
                                        <span class="course-options__item-label">
                                            <?php
                                            echo MyDate::showTime($class['grpcls_start_datetime']);
                                            $str = Label::getLabel('LBL_{minutes}_Minutes');
                                            $str = str_replace('{minutes}', $class['grpcls_duration'], $str);
                                            echo ($class['grpcls_type'] == GroupClass::TYPE_REGULAR) ? ' (' . $str . ')' : ' ' . Label::getLabel('LBL_ONWARDS');
                                            ?>
                                        </span>
                                    </li>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--seats" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <path d="M15.375,7.375H14.7V3.664A3.668,3.668,0,0,0,11.039,0H4.961A3.668,3.668,0,0,0,1.3,3.664V7.375H.625A.625.625,0,0,0,0,8v4.875a.625.625,0,0,0,.625.625H3.394L2.57,15.087a.625.625,0,0,0,1.11.576L4.8,13.5h6.4l1.122,2.163a.625.625,0,0,0,1.109-.576L12.606,13.5h2.769A.625.625,0,0,0,16,12.875V8A.625.625,0,0,0,15.375,7.375ZM2.547,3.664A2.416,2.416,0,0,1,4.961,1.25h6.078a2.416,2.416,0,0,1,2.414,2.414V7.375h-.578A.625.625,0,0,0,12.25,8V9.75H3.75V8a.625.625,0,0,0-.625-.625H2.547Zm12.2,8.586H1.25V8.625H2.5v1.75A.625.625,0,0,0,3.125,11h9.75a.625.625,0,0,0,.625-.625V8.625h1.25Z" transform="translate(4 4)"></path>
                                            </svg>
                                        </span>
                                        <span class="course-options__item-label"><strong><?php echo $class['grpcls_total_seats']; ?></strong> <?php echo Label::getLabel('LBL_SEATS'); ?> </span></span>
                                    </li>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--notes" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <g transform="translate(-2)">
                                                    <path d="M16.222,17.556H3.778A.778.778,0,0,1,3,16.778v-14A.778.778,0,0,1,3.778,2H16.222A.778.778,0,0,1,17,2.778v14A.778.778,0,0,1,16.222,17.556ZM15.444,16V3.556H4.556V16ZM6.889,5.889h6.222V7.444H6.889ZM6.889,9h6.222v1.556H6.889Zm0,3.111h6.222v1.556H6.889Z" transform="translate(4 2)"></path>
                                                </g>
                                            </svg>
                                        </span>
                                        <span class="course-options__item-label"><strong><?php echo (count($pkgclses) < 1) ? '1' : count($pkgclses); ?></strong> <?php echo Label::getLabel('LBL_SESSIONS'); ?> </span>
                                    </li>
                                    <?php if ($class['grpcls_offline'] == AppConstant::YES) { ?>
                                        <li class="course-options__item">
                                            <span class="course-options__item-media">
                                                <svg class="icon icon--address" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512">
                                                    <g>
                                                        <path d="M341.476,338.285c54.483-85.493,47.634-74.827,49.204-77.056C410.516,233.251,421,200.322,421,166
                                                        C421,74.98,347.139,0,256,0C165.158,0,91,74.832,91,166c0,34.3,10.704,68.091,31.19,96.446l48.332,75.84
                                                        C118.847,346.227,31,369.892,31,422c0,18.995,12.398,46.065,71.462,67.159C143.704,503.888,198.231,512,256,512
                                                        c108.025,0,225-30.472,225-90C481,369.883,393.256,346.243,341.476,338.285z M147.249,245.945
                                                        c-0.165-0.258-0.337-0.51-0.517-0.758C129.685,221.735,121,193.941,121,166c0-75.018,60.406-136,135-136
                                                        c74.439,0,135,61.009,135,136c0,27.986-8.521,54.837-24.646,77.671c-1.445,1.906,6.094-9.806-110.354,172.918L147.249,245.945z
                                                        M256,482c-117.994,0-195-34.683-195-60c0-17.016,39.568-44.995,127.248-55.901l55.102,86.463
                                                        c2.754,4.322,7.524,6.938,12.649,6.938s9.896-2.617,12.649-6.938l55.101-86.463C411.431,377.005,451,404.984,451,422
                                                        C451,447.102,374.687,482,256,482z" />
                                                    </g>
                                                    <g>
                                                        <path d="M256,91c-41.355,0-75,33.645-75,75s33.645,75,75,75c41.355,0,75-33.645,75-75S297.355,91,256,91z M256,211
                                                            c-24.813,0-45-20.187-45-45s20.187-45,45-45s45,20.187,45,45S280.813,211,256,211z" />
                                                    </g>
                                                </svg>
                                            </span>
                                            <span class="course-options__item-label">
                                                <a href="javascript:void(0);" class="underline color-secondary" onclick="viewAddress('<?php echo $class['grpcls_address_id']; ?>');">
                                                    <?php
                                                    $address = $class['grpcls_address'];
                                                    echo UserAddresses::format($address);
                                                    ?>
                                                </a>
                                            </span>
                                        </li>
                                    <?php } ?>
                                    <?php if (!empty($class['class_offer']) || !empty($class['package_offer'])) { ?>
                                        <li class="course-options__item color-primary">
                                            <span class="course-options__item-media">
                                                <svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12.5192 22H12.13C11.6059 21.9154 11.2069 21.6204 10.8474 21.2481C10.5542 20.9443 10.2415 20.6601 9.94093 20.3637C9.7758 20.2008 9.58339 20.1377 9.35591 20.1714C8.86247 20.2438 8.36805 20.3098 7.8751 20.3871C6.81514 20.5525 5.98998 19.9865 5.77809 18.9298C5.67969 18.4392 5.59347 17.9456 5.51943 17.4505C5.4751 17.1536 5.33628 16.953 5.06739 16.8185C4.62704 16.5984 4.194 16.3626 3.76145 16.1273C2.83789 15.6244 2.52857 14.6705 2.98012 13.718C3.19737 13.2601 3.41804 12.8042 3.64649 12.3522C3.76827 12.1115 3.76729 11.8855 3.64552 11.6453C3.4195 11.1992 3.20273 10.7486 2.98694 10.2971C2.5237 9.3285 2.83643 8.37067 3.78142 7.86191C4.22079 7.62514 4.65919 7.38641 5.10392 7.15992C5.34651 7.03664 5.47169 6.84782 5.51358 6.58463C5.59055 6.10327 5.67336 5.62288 5.75958 5.14299C5.95296 4.06677 6.72454 3.48268 7.803 3.60644C8.2984 3.66319 8.79184 3.73852 9.28382 3.81924C9.55563 3.86375 9.77629 3.80456 9.97357 3.60595C10.3306 3.2464 10.6974 2.89663 11.0632 2.54588C11.8222 1.81845 12.8271 1.81796 13.586 2.5449C13.9611 2.90445 14.3342 3.26645 14.7044 3.63139C14.8754 3.79967 15.0731 3.86229 15.3074 3.82707C15.8009 3.75369 16.2948 3.68471 16.7878 3.61035C17.8404 3.45186 18.6583 4.01638 18.8706 5.06521C18.9715 5.56173 19.0543 6.06267 19.1332 6.5636C19.177 6.84292 19.3061 7.03909 19.5628 7.16824C19.9968 7.38641 20.4221 7.62269 20.8507 7.85212C21.8264 8.37458 22.1338 9.32997 21.6486 10.3304C21.4392 10.7628 21.2336 11.1977 21.0144 11.6253C20.8844 11.8782 20.8819 12.114 21.0115 12.3678C21.2385 12.8135 21.4552 13.264 21.6696 13.7161C22.1221 14.6705 21.8147 15.6224 20.8912 16.1258C20.4474 16.368 20.0022 16.6077 19.5516 16.8366C19.31 16.9594 19.1804 17.1453 19.1381 17.4094C19.0606 17.8908 18.9773 18.3712 18.8921 18.8511C18.6997 19.9346 17.9149 20.5246 16.8335 20.392C16.3386 20.3314 15.8447 20.2585 15.3527 20.1787C15.0877 20.1362 14.8715 20.1964 14.6805 20.3896C14.3926 20.6807 14.0862 20.9531 13.8027 21.2476C13.4433 21.6204 13.0443 21.9149 12.5202 21.9995L12.5192 22ZM16.7308 8.22391C16.6879 7.87071 16.4302 7.61731 16.1053 7.61927C15.9071 7.62074 15.7668 7.7264 15.6353 7.85897C13.1568 10.349 10.6774 12.8384 8.19854 15.3279C8.15275 15.3739 8.10501 15.4199 8.06897 15.4727C7.94086 15.6596 7.92283 15.8616 8.02513 16.0632C8.12742 16.2642 8.29693 16.3694 8.5249 16.3777C8.748 16.3856 8.89657 16.2569 9.04221 16.1106C11.2274 13.9147 13.4135 11.7202 15.5992 9.52516C15.8924 9.23066 16.1935 8.94351 16.475 8.63825C16.5832 8.52134 16.647 8.36284 16.7308 8.22342V8.22391ZM14.6162 12.9656C13.4467 12.9695 12.5129 13.9186 12.5187 15.0965C12.5246 16.2408 13.4749 17.1883 14.6191 17.1908C15.779 17.1932 16.7303 16.2324 16.7249 15.0643C16.7191 13.8975 15.779 12.9612 14.6162 12.9651V12.9656ZM10.034 11.0338C11.1943 11.0289 12.1324 10.0833 12.1315 8.92101C12.1305 7.75624 11.1685 6.79743 10.0111 6.80721C8.85711 6.817 7.91942 7.77337 7.92527 8.93421C7.93111 10.0995 8.87465 11.0392 10.0345 11.0338H10.034Z" />
                                                    <path d="M14.6284 16.0299C14.0999 16.0329 13.6771 15.618 13.6678 15.0863C13.6586 14.5589 14.0999 14.1108 14.6255 14.1143C15.1452 14.1177 15.5778 14.5526 15.5787 15.0736C15.5797 15.5994 15.155 16.0265 14.6284 16.0299Z" />
                                                    <path d="M10.9814 8.91563C10.9872 9.43564 10.56 9.87542 10.0393 9.88472C9.51812 9.89401 9.06852 9.4464 9.07046 8.92101C9.07241 8.39415 9.49815 7.96953 10.0242 7.96807C10.5518 7.9666 10.9751 8.38584 10.9814 8.91563Z" />
                                                </svg>
                                            </span>
                                            <?php if ($class['grpcls_type'] == GroupClass::TYPE_REGULAR && !empty($class['class_offer'])) { ?>
                                                <span class="course-options__item-label"><?php echo number_format(round($class['class_offer'], 2), 2, '.', '') . '% ' . Label::getLabel('LBL_OFF'); ?></span>
                                            <?php } elseif ($class['grpcls_type'] == GroupClass::TYPE_PACKAGE && !empty($class['package_offer'])) { ?>
                                                <span class="course-options__item-label"><?php echo number_format(round($class['package_offer'], 2), 2, '.', '') . '% ' . Label::getLabel('LBL_OFF'); ?></span>
                                            <?php } ?>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                        <div class="page-box__footer">
                            <div class="course-pricing mb-3">
                                <div class="course-pricing__head text-center mb-3">
                                    <span class="course-pricing__price"><?php echo MyUtility::formatMoney($class['grpcls_entry_fee']); ?></span>
                                </div>
                                <div class="course-pricing__body">
                                    <?php if ($class['grpcls_already_booked']) { ?>
                                        <button title="<?php echo Label::getLabel('LBL_ALREADY_BOOKED') ?>" class="btn btn--block btn--primary btn--large btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($class['grpcls_booked_seats'] >= $class['grpcls_total_seats']) { ?>
                                        <button title="<?php echo Label::getLabel('LBL_CLASS_FULL') ?>" class="btn btn--block btn--primary btn--large btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($class['grpcls_start_datetime'] < date('Y-m-d H:i:s', strtotime('+' . $bookingBefore . ' minutes', $class['grpcls_currenttime_unix']))) { ?>
                                        <button title="<?php echo Label::getLabel('LBL_BOOKING_CLOSED') ?>" class="btn btn--block btn--primary btn--large btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($siteUserId == $class['grpcls_teacher_id']) { ?>
                                        <button title="<?php echo Label::getLabel('LBL_CANNOT_BOOK_OWN_CLASS'); ?>" class="btn btn--block btn--primary btn--large btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($class['grpcls_status'] != GroupClass::SCHEDULED) { ?>
                                        <button title="<?php echo Label::getLabel('LBL_CLASS_NOT_ACTIVE') ?>" class="btn btn--block btn--primary btn--large btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($bookedSeats >= $class['grpcls_total_seats']) { ?>
                                        <button title="<?php echo Label::getLabel('LBL_PROCESSING_CLASS_ORDER_TEXT') ?>" class="btn btn--block btn--primary btn--large btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($bookedSeats >= $class['grpcls_total_seats']) { ?>
                                        <button title="<?php echo Label::getLabel('LBL_CLASS_HOLD_INFO'); ?>" class="btn btn--block btn--primary btn--large btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($isPackage) { ?>
                                        <button onclick="cart.addPackage(<?php echo $class['grpcls_id']; ?>)" class="btn btn--block btn--primary btn--large"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } else { ?>
                                        <button onclick="cart.addClass(<?php echo $class['grpcls_id']; ?>)" class="btn btn--block btn--primary btn--large"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="sharing-view align-center mt-4 p-0 m-0">
                                <h6><?php echo Label::getLabel('LBL_SHARE_THIS_CLASS'); ?></h6>
                                <ul class="social--share pt-3">
                                    <li class="social--fb">
                                        <a class="st-custom-button" data-network="facebook" displaytext="Facebook" title="Facebook" st_processed="yes">
                                            <img alt="Facebook" src="<?php echo CONF_WEBROOT_URL; ?>images/social_01.svg">
                                        </a>
                                    </li>
                                    <li class="social--tw">
                                        <a class="st-custom-button" data-network="twitter" displaytext="Twitter" title="Twitter" st_processed="yes">
                                            <img alt="Twitter" src="<?php echo CONF_WEBROOT_URL; ?>images/social_02.svg">
                                        </a>
                                    </li>
                                    <li class="social--pt">
                                        <a class="st-custom-button" data-network="pinterest" displaytext="Pinterest" title="Pinterest" st_processed="yes">
                                            <img alt="Pinterest" src="<?php echo CONF_WEBROOT_URL; ?>images/social_05.svg">
                                        </a>
                                    </li>
                                    <li class="social--mail">
                                        <a class="st-custom-button" data-network="email" displaytext="Email" title="Email" st_processed="yes">
                                            <img alt="Email" src="<?php echo CONF_WEBROOT_URL; ?>images/social_06.svg">
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] -->
            <!-- [ PANEL LARGE 2 ========= -->
            <div class="page-flex__large">
                <div class="panels-container">
                    <div class="content-group">
                        <h5 class="mb-4"><?php echo Label::getLabel('LBL_CLASS_DESCRIPTION'); ?></h5>
                        <p><?php echo nl2br($class['grpcls_description'] ?? ''); ?></p>
                    </div>
                    <div class="content-group">
                        <?php if (count($pkgclses)) { ?>
                            <h5 class="mb-4"><?php echo Label::getLabel('LBL_CLASSES') . ' (' . count($pkgclses) . ')'; ?></h5>
                            <div class="class-list">
                                <?php foreach ($pkgclses as $pkgcls) { ?>
                                    <div class="class-list__item">
                                        <div class="class-card">
                                            <h5><?php echo $pkgcls['grpcls_title']; ?></h5>
                                            <p>
                                                <?php echo MyDate::showDate($pkgcls['grpcls_start_datetime'], true); ?> (<?php echo str_replace('{minutes}', $class['grpcls_duration'], Label::getLabel('LBL_{minutes}_Minutes', $siteLangId)); ?>)
                                            </p>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <!-- ] -->
        </div>
    </div>
</section>
<?php if (count($moreClasses) > 0) { ?>
    <section class="section section--cardslider" data-aos="fade-up" data-aos-duration="1000">
        <div class="container container--xxl">
            <div class="section__header mb-xl-3">
                <h2>
                    <?php echo Label::getLabel('LBL_MORE_GROUP_CLASSES_FROM') . ' ' . $teacherName; ?>
                </h2>
            </div>
            <div class="section__body">
                <div class="slider slider-oneforth slider-oneforth-js">
                    <?php
                    foreach ($moreClasses as $class) { ?>
                        <div class="slider__item">
                            <?php
                            $classData = ['class' => $class, 'siteUserId' => $siteUserId, 'bookingBefore' => $bookingBefore, 'cardClass' => 'card-cover'];
                            $this->includeTemplate('group-classes/card.php', $classData, false);
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
<?php } ?>
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>
<script src="//maps.googleapis.com/maps/api/js?key=<?php echo FatApp::getConfig('CONF_GOOGLE_API_KEY', FatUtility::VAR_STRING, '') ?>&libraries=places&v=weekly" defer></script>