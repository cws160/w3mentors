<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php $this->includeTemplate('teacher-request/_partial/leftPanel.php', ['step' => 5]); ?>
<div class="page-block__right">
    <div class="page-block__head">
        <div class="head__title">
            <h4><?php echo Label::getLabel('LBL_Tutor_registration'); ?></h4>
        </div>
    </div>
    <div class="page-block__body">
        <div class="block-content">
            <?php $msg = ''; ?>
            <?php if ($request['tereq_status'] == TeacherRequest::STATUS_PENDING) { 
                $msg =  Label::getLabel('LBL_Thank_You_For_Submitting_Your_Application'); ?>
                <div class="block-content__head d-flex justify-content-center">
                    <h5><?php echo Label::getLabel('LBL_APPLICATION_AWAITING_APPROVAL'); ?></h5>
                </div>
            <?php } elseif ($requestCount >= $allowedCount && $request['tereq_status'] == TeacherRequest::STATUS_CANCELLED) { 
                 $msg =  Label::getLabel('LBL_SORRY_YOUR_APPLICATION_IS_REJECTED'); ?>
                <div class="block-content__head d-flex justify-content-center">
                    <h5><?php echo Label::getLabel('LBL_YOU_HAVE_REACH_MAX_ATTEMPTS_TO_SUBMIT_REQUEST'); ?></h5>
                </div>
                <div class="d-flex justify-content-center">
                    <p><?php echo nl2br($request['tereq_comments']); ?></p>
                </div>
                <div class="-gap-10"></div>
            <?php } elseif ($request['tereq_status'] == TeacherRequest::STATUS_CANCELLED) {
                $msg =  Label::getLabel('LBL_YOU_CAN_RESUBMIT_APPLICATION'); ?> 
                <div class="d-flex justify-content-center">
                    <h5><?php echo Label::getLabel('LBL_APPLICATION_HAS_BEEN_REJECTED'); ?></h5>
                </div>
                <div class="-gap-10"></div>
                <div class="d-flex justify-content-center">
                    <p><?php echo nl2br($request['tereq_comments']); ?></p>
                </div>
                
                
            <?php } elseif ($request['tereq_status'] == TeacherRequest::STATUS_APPROVED) { 
                 $msg =  Label::getLabel('LBL_CONGRATULATIONS_YOUR_APPLICATION_IS_APPROVED'); ?>
                <div class="block-content__head d-flex justify-content-center">
                    <h5><?php echo Label::getLabel('LBL_APPLICATION_HAS_BEEN_APPROVED'); ?></h5>
                </div>
            <?php } ?>
            <div class="block-content__body">
                <div class="message-display message--resume message--confirmetion m-4">
                    <div class="message-display__icon">
                    <?php if ($request['tereq_status'] == TeacherRequest::STATUS_CANCELLED) { ?>
                        <svg id="a" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200.8 121.6"><path d="M140.37,114.38l-51.49-30.27L129.65,12.2l51.49,30.27-40.78,71.91Z" fill="#fff"/><path d="M130.02,13.58l-39.79,70.17,49.77,29.26,39.79-70.17-49.77-29.26M129.28,10.82l53.22,31.28-41.76,73.65-53.22-31.28L129.28,10.82Z" fill="#ccd0d9"/><path d="M136.78,39.32l-10.68-6.28,6.17-10.89,10.68,6.28-6.17,10.89Z" fill="#ccd0d9"/><path d="M132.64,23.53l-5.19,9.15,8.95,5.26,5.19-9.15-8.95-5.26M131.9,20.78l12.4,7.29-7.16,12.63-12.4-7.29,7.16-12.63Z" fill="#ccd0d9"/><path d="M148.36,31.86l22.73,13.36-1.79,3.16-22.74-13.37,1.79-3.16Z" fill="#ccd0d9"/><path d="M145.37,37.12l22.73,13.36-.89,1.58-22.73-13.36.89-1.58Z" fill="#ccd0d9"/><path d="M143.29,40.8l17.57,10.33-.89,1.58-17.57-10.33.89-1.58Z" fill="#ccd0d9"/><path d="M118.48,44.45l41.33,24.3-.89,1.58-41.33-24.3.89-1.58Z" fill="#ccd0d9"/><path d="M116.09,48.66l41.33,24.3-.89,1.58-41.33-24.3.89-1.58Z" fill="#ccd0d9"/><path d="M110.73,58.13l41.33,24.3-.89,1.58-41.33-24.3.89-1.58Z" fill="#ccd0d9"/><path d="M108.34,62.34l41.33,24.3-.89,1.58-41.33-24.3.89-1.58Z" fill="#ccd0d9"/><path d="M102.97,71.81l41.33,24.3-.89,1.58-41.33-24.3.89-1.58Z" fill="#ccd0d9"/><path d="M100.58,76.02l41.33,24.3-.89,1.58-41.33-24.3.89-1.58Z" fill="#ccd0d9"/><path d="M65.77,114.38L25,42.47l51.49-30.27,40.78,71.91-51.49,30.27Z" fill="#fff"/><path d="M76.12,13.58l-49.77,29.26,39.79,70.17,49.77-29.26L76.12,13.58M76.86,10.82l41.76,73.65-53.22,31.28L23.64,42.11l53.22-31.28Z" fill="#ccd0d9"/><path d="M40.95,56.02l-6.17-10.89,10.68-6.28,6.17,10.89-10.68,6.28Z" fill="#ccd0d9"/><path d="M45.09,40.24l-8.95,5.26,5.19,9.15,8.95-5.26-5.19-9.15M45.82,37.48l7.16,12.63-12.4,7.29-7.16-12.63,12.4-7.29Z" fill="#ccd0d9"/><path d="M51.07,35.8l22.73-13.36,1.79,3.16-22.73,13.36-1.79-3.16Z" fill="#ccd0d9"/><path d="M54.05,41.06l22.73-13.36.89,1.58-22.73,13.36-.89-1.58Z" fill="#ccd0d9"/><path d="M56.14,44.75l17.57-10.33.89,1.58-17.57,10.33-.89-1.58Z" fill="#ccd0d9"/><path d="M46.85,68.45l41.33-24.3.89,1.58-41.33,24.3-.89-1.58Z" fill="#ccd0d9"/><path d="M49.23,72.66l41.33-24.3.89,1.58-41.33,24.3-.89-1.58Z" fill="#ccd0d9"/><path d="M54.6,82.12l41.33-24.3.89,1.58-41.33,24.3-.89-1.58Z" fill="#ccd0d9"/><path d="M56.99,86.33l41.33-24.3.89,1.58-41.33,24.3-.89-1.58Z" fill="#ccd0d9"/><path d="M62.36,95.8l41.33-24.3.89,1.58-41.33,24.3-.89-1.58Z" fill="#ccd0d9"/><path d="M64.75,100.01l41.33-24.3.89,1.58-41.33,24.3-.89-1.58Z" fill="#ccd0d9"/><rect x="62.51" y="3.2" width="80.93" height="112" fill="#fff"/><rect x="63.51" y="4.2" width="78.93" height="110" fill="none" stroke="#ccd0d9" stroke-width="2"/><rect x="71.94" y="12.8" width="18.86" height="19.2" fill="#ccd0d9"/><rect x="72.94" y="13.8" width="16.86" height="17.2" fill="none" stroke="#ccd0d9" stroke-width="2"/><rect x="97.87" y="14.4" width="34.57" height="4.8" fill="#ccd0d9"/><rect x="97.87" y="22.4" width="34.57" height="2.4" fill="#ccd0d9"/><rect x="97.87" y="28" width="26.71" height="2.4" fill="#ccd0d9"/><rect x="71.94" y="48.8" width="62.86" height="2.4" fill="#ccd0d9"/><rect x="71.94" y="55.2" width="62.86" height="2.4" fill="#ccd0d9"/><rect x="71.94" y="69.6" width="62.86" height="2.4" fill="#ccd0d9"/><rect x="71.94" y="76" width="62.86" height="2.4" fill="#ccd0d9"/><rect x="71.94" y="90.4" width="62.86" height="2.4" fill="#ccd0d9"/><rect x="71.94" y="96.8" width="62.86" height="2.4" fill="#ccd0d9"/><circle cx="161.8" cy="13.51" r="5" fill="none"/><circle cx="161.8" cy="13.51" r="4" fill="none" stroke="#ccd0d9" stroke-width="2"/><circle cx="194.51" cy="73.49" r="6.29" fill="none"/><circle cx="194.51" cy="73.49" r="5.29" fill="none" stroke="#ccd0d9" stroke-width="2"/><circle cx="23.23" cy="75.14" r="3.14" fill="none"/><circle cx="23.23" cy="75.14" r="2.14" fill="none" stroke="#ccd0d9" stroke-width="2"/><rect x="31.87" width="2.36" height="16" fill="#ccd0d9"/><rect x="25.58" y="7.2" width="14.93" height="2.4" fill="#ccd0d9"/><rect x="164.66" y="102.4" width="2.36" height="10.4" fill="#ccd0d9"/><rect x="160.73" y="106.4" width="10.21" height="2.4" fill="#ccd0d9"/><rect x="1.97" y="34.19" width="9.51" height="9.51" transform="translate(-25.57 16.16) rotate(-45)" fill="none"/><rect x="2.97" y="35.19" width="7.51" height="7.51" transform="translate(-25.57 16.16) rotate(-45)" fill="none" stroke="#ccd0d9" stroke-miterlimit="4" stroke-width="2"/><rect x="187.08" y="19.19" width="7.93" height="7.93" transform="translate(39.59 141.87) rotate(-45)" fill="none"/><rect x="188.08" y="20.19" width="5.93" height="5.93" transform="translate(39.59 141.87) rotate(-45)" fill="none" stroke="#ccd0d9" stroke-miterlimit="4" stroke-width="2"/><circle cx="23.23" cy="105.49" r="6.29" fill="none"/><circle cx="23.23" cy="105.49" r="5.29" fill="none" stroke="#ccd0d9" stroke-width="2"/><circle cx="147.92" cy="91.6" r="30" fill="#c9252d"/><polygon points="159.82 99.74 156.56 103 147.92 94.36 139.28 103 136.02 99.74 144.66 91.1 137.02 83.46 140.28 80.2 147.92 87.84 155.56 80.2 158.82 83.46 151.18 91.1 159.82 99.74" fill="#fff"/></svg>
                    <?php } else { ?>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 160">
                                <defs>
                                <style>
                                            .a, .g { fill: none; }
                                            .b, .e, .i { fill: #fff; }
                                            .c, .d, .f { fill: #ccd0d9; }
                                            .e, .f, .g { stroke: #ccd0d9; stroke-width: 2px; }
                                            .h { fill: #1dce70; }
                                            .j, .k { stroke: none; }
                                            .k { fill: #ccd0d9; }
                                </style>
                                </defs>
                                <g transform="translate(-836 -294)">
                                <rect class="a" transform="translate(836 294)" />
                                <g transform="translate(839.143 320.4)">
                                <g transform="translate(129.223 10.823) rotate(30)">
                                <g class="b" transform="translate(0 0)">
                                <path class="j" d="M 61.37833786010742 84.13814544677734 L 1.649896621704102 83.67002868652344 L 1.007855653762817 1.007922053337097 L 60.73629379272461 1.476034283638 L 61.37833786010742 84.13814544677734 Z" />
                                <path class="k" d="M 2.015712738037109 2.015853881835938 L 2.642215728759766 82.67776489257813 L 60.37047958374023 83.13019561767578 L 59.74396514892578 2.468284606933594 L 2.015712738037109 2.015853881835938 M -3.814697265625e-06 -7.62939453125e-06 L 61.72860717773438 0.4837799072265625 L 62.38619613647461 85.14605712890625 L 0.6575698852539063 84.66226959228516 L -3.814697265625e-06 -7.62939453125e-06 Z" />
                                </g>
                                <g class="c" transform="translate(7.248 7.313)">
                                <path class="j" d="M 13.48823738098145 13.61832904815674 L 1.105050206184387 13.52127552032471 L 1.007856011390686 1.007930517196655 L 13.3910436630249 1.104984045028687 L 13.48823738098145 13.61832904815674 Z" />
                                <path class="k" d="M 2.015715599060059 2.015860557556152 L 2.097373008728027 12.52902221679688 L 12.48037910461426 12.61039924621582 L 12.39872074127197 2.097237586975098 L 2.015715599060059 2.015860557556152 M -2.86102294921875e-06 0 L 14.38336658477783 0.1127300262451172 L 14.49609661102295 14.62625980377197 L 0.112727165222168 14.51352977752686 L -2.86102294921875e-06 0 Z" />
                                </g>
                                <path class="d" d="M0,0,26.37.207,26.4,3.835.028,3.628Z" transform="translate(27.035 8.678)" />
                                <path class="d" d="M0,0,26.37.207l.014,1.814L.014,1.814Z" transform="translate(27.082 14.725)" />
                                <path class="d" d="M0,0,20.376.16l.014,1.814L.014,1.814Z" transform="translate(27.114 18.958)" />
                                <path class="d" d="M0,0,47.945.376l.014,1.814L.014,1.814Z" transform="translate(7.459 34.526)" />
                                <path class="d" d="M0,0,47.945.376l.014,1.814L.014,1.814Z" transform="translate(7.497 39.364)" />
                                <path class="d" d="M0,0,47.945.376l.014,1.814L.014,1.814Z" transform="translate(7.582 50.249)" />
                                <path class="d" d="M0,0,47.945.376l.014,1.814L.014,1.814Z" transform="translate(7.619 55.087)" />
                                <path class="d" d="M0,0,47.945.376l.014,1.814L.014,1.814Z" transform="translate(7.704 65.972)" />
                                <path class="d" d="M0,0,47.945.376l.014,1.814L.014,1.814Z" transform="translate(7.741 70.81)" />
                                </g>
                                <g transform="translate(23.584 42.106) rotate(-30)">
                                <g class="b" transform="translate(0 0)">
                                <path class="j" d="M 0.3502781391143799 83.65435791015625 L 0.9923191666603088 0.9922467470169067 L 60.72076034545898 0.5241345763206482 L 60.07871627807617 83.18624114990234 L 0.3502781391143799 83.65435791015625 Z" />
                                <path class="k" d="M 59.7129020690918 1.532066345214844 L 1.984638214111328 1.9844970703125 L 1.358135223388672 82.64640808105469 L 59.08638763427734 82.19397735595703 L 59.7129020690918 1.532066345214844 M 61.72861862182617 -0.483795166015625 L 61.07102966308594 84.17848205566406 L -0.6575813293457031 84.66226959228516 L -7.62939453125e-06 -7.62939453125e-06 L 61.72861862182617 -0.483795166015625 Z" />
                                </g>
                                <g class="c" transform="translate(7.135 7.2)">
                                <path class="j" d="M 0.8951289057731628 13.50560188293457 L 0.9923229813575745 0.9922568202018738 L 13.37551021575928 0.8952032923698425 L 13.27831649780273 13.40854835510254 L 0.8951289057731628 13.50560188293457 Z" />
                                <path class="k" d="M 12.36765098571777 1.903133392333984 L 1.984645843505859 1.98451042175293 L 1.902988433837891 12.49767208099365 L 12.2859935760498 12.41629505157471 L 12.36765098571777 1.903133392333984 M 14.38336944580078 -0.112727165222168 L 14.27063941955566 14.40080261230469 L -0.1127300262451172 14.5135326385498 L 0 2.86102294921875e-06 L 14.38336944580078 -0.112727165222168 Z" />
                                </g>
                                <path class="d" d="M0,0,26.37-.207l-.028,3.628-26.37.207Z" transform="translate(26.903 8.255)" />
                                <path class="d" d="M0,0,26.37-.207l-.014,1.814-26.37.207Z" transform="translate(26.856 14.302)" />
                                <path class="d" d="M0,0,20.376-.16l-.014,1.814-20.376.16Z" transform="translate(26.823 18.535)" />
                                <path class="d" d="M0,0,47.945-.376,47.93,1.438-.014,1.814Z" transform="translate(6.924 34.413)" />
                                <path class="d" d="M0,0,47.945-.376,47.93,1.438-.014,1.814Z" transform="translate(6.886 39.251)" />
                                <path class="d" d="M0,0,47.945-.376,47.93,1.438-.014,1.814Z" transform="translate(6.802 50.136)" />
                                <path class="d" d="M0,0,47.945-.376,47.93,1.438-.014,1.814Z" transform="translate(6.764 54.974)" />
                                <path class="d" d="M0,0,47.945-.376,47.93,1.438-.014,1.814Z" transform="translate(6.68 65.859)" />
                                <path class="d" d="M0,0,47.945-.376,47.93,1.438-.014,1.814Z" transform="translate(6.642 70.697)" />
                                </g>
                                <g transform="translate(62.453 3.2)">
                                <g class="e">
                                <rect class="j" width="80.929" height="112" />
                                <rect class="a" x="1" y="1" width="78.929" height="110" />
                                </g>
                                <g class="f" transform="translate(9.429 9.6)">
                                <rect class="j" width="18.857" height="19.2" />
                                <rect class="a" x="1" y="1" width="16.857" height="17.2" />
                                </g>
                                <rect class="d" width="34.571" height="4.8" transform="translate(35.357 11.2)" />
                                <rect class="d" width="34.571" height="2.4" transform="translate(35.357 19.2)" />
                                <rect class="d" width="26.714" height="2.4" transform="translate(35.357 24.8)" />
                                <rect class="d" width="62.857" height="2.4" transform="translate(9.429 45.6)" />
                                <rect class="d" width="62.857" height="2.4" transform="translate(9.429 52)" />
                                <rect class="d" width="62.857" height="2.4" transform="translate(9.429 66.4)" />
                                <rect class="d" width="62.857" height="2.4" transform="translate(9.429 72.8)" />
                                <rect class="d" width="62.857" height="2.4" transform="translate(9.429 87.2)" />
                                <rect class="d" width="62.857" height="2.4" transform="translate(9.429 93.6)" />
                                </g>
                                <g class="g" transform="translate(156.738 8.509)">
                                <circle class="j" cx="5" cy="5" r="5" />
                                <circle class="a" cx="5" cy="5" r="4" />
                                </g>
                                <g class="g" transform="translate(188.167 67.2)">
                                <circle class="j" cx="6.286" cy="6.286" r="6.286" />
                                <circle class="a" cx="6.286" cy="6.286" r="5.286" />
                                </g>
                                <g class="g" transform="translate(20.024 72)">
                                <circle class="j" cx="3.143" cy="3.143" r="3.143" />
                                <circle class="a" cx="3.143" cy="3.143" r="2.143" />
                                </g>
                                <g transform="translate(25.524 0)">
                                <rect class="d" width="2.357" height="16" transform="translate(6.286)" />
                                <rect class="d" width="2.4" height="14.929" transform="translate(14.929 7.2) rotate(90)" />
                                </g>
                                <g transform="translate(160.667 102.4)">
                                <rect class="d" width="2.357" height="10.4" transform="translate(3.929)" />
                                <rect class="d" width="2.4" height="10.214" transform="translate(10.214 4) rotate(90)" />
                                </g>
                                <g class="g" transform="translate(6.667 32.218) rotate(45)">
                                <rect class="j" width="9.514" height="9.514" />
                                <rect class="a" x="1" y="1" width="7.514" height="7.514" />
                                </g>
                                <g class="g" transform="translate(190.985 17.543) rotate(45)">
                                <rect class="j" width="7.929" height="7.929" />
                                <rect class="a" x="1" y="1" width="5.929" height="5.929" />
                                </g>
                                <g class="g" transform="translate(16.881 99.2)">
                                <circle class="j" cx="6.286" cy="6.286" r="6.286" />
                                <circle class="a" cx="6.286" cy="6.286" r="5.286" />
                                </g>
                                </g>
                                <g transform="translate(9 -15)">
                                <circle class="h" cx="30" cy="30" r="30" transform="translate(948 397)" />
                                <g transform="translate(-6.086 816.401) rotate(-45)">
                                <rect class="i" width="4" height="10.538" transform="translate(960 412)" />
                                <rect class="i" width="4" height="24" transform="translate(983.908 421.396) rotate(90)" />
                                </g>
                                </g>
                                </g>
                            </svg>
                        <?php } ?>
                    </div>
                    
                        <h5><?php echo Label::getLabel('LBL_Hello'); ?> <?php echo implode(" ", [$request['tereq_first_name'], $request['tereq_last_name']]); ?></h5>
                        <p><?php echo $msg; ?></p>
                        <div class="application-no">
                            <?php echo Label::getLabel('LBL_Application_Reference') ?>: <span id="reg-no"><?php echo $request['tereq_reference']; ?></span>
                        </div>
                        <div class="d-flex justify-content-center">
                            <?php if ($requestCount < $allowedCount && $request['tereq_status'] == TeacherRequest::STATUS_CANCELLED) { ?>
                                <a href="javascript:void(0)" onclick="resubmit();" class="btn btn--bordered btn--small color-secondary mr-2"><?php echo Label::getLabel('LBL_Resubmit'); ?></a>
                            <?php } ?>
                            <?php if (!empty($siteUserId)) { ?>
                                <a href="<?php echo MyUtility::makeUrl('learner', '', [], CONF_WEBROOT_DASHBOARD) ?>" class="btn btn--bordered btn--small color-secondary"><?php echo Label::getLabel('LBL_Visit_My_Account'); ?></a>
                            <?php } ?>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>