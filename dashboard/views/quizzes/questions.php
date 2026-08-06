<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$count = count($questions);
?>
<div class="page-layout">
    <div class="page-layout__small">
        <?php echo $this->includeTemplate('quizzes/navigation.php', [
            'quizId' => $quizId,
            'active' => 2,
            'next' => true,
            'count' => $count
        ]) ?>
    </div>
    <div class="page-layout__large">
        <div class="box-panel">
            <div class="box-panel__head border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4><?php echo Label::getLabel('LBL_SETUP_QUIZ'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="box-panel__body">
                <div class="box-panel__container">
                    <div class="page">
                        <div class="page__head pt-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <b><?php echo ($count > 0) ? Label::getLabel('LBL_TOTAL_QUESTIONS:') . ' ' . $count : ''; ?></b>
                                </div>
                                <div>
                                    <a href="javascript:void(0);" onclick="addQuestions('<?php echo $quizId ?>')" class="btn color-secondary btn--bordered addQuesJs">

                                        <svg class="icon icon--uploader icon--small me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-1-5h2v2h-2v-2zm2-1.645V14h-2v-1.5a1 1 0 0 1 1-1 1.5 1.5 0 1 0-1.471-1.794l-1.962-.393A3.501 3.501 0 1 1 13 13.355z" />
                                        </svg>
                                        <?php echo Label::getLabel('LBL_ADD_QUESTIONS'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="page__body">
                            <div class="table-scroll sortableWrapperJs">
                                <table class="table table--responsive table--bordered">
                                    <thead>
                                        <tr class="title-row">
                                            <th>
                                                <i class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move">
                                                    <svg class="svg-icon" viewBox="0 0 16 12.632">
                                                        <path d="M7.579 9.263v1.684H0V9.263zm1.684-4.211v1.684H0V5.053zM7.579.842v1.684H0V.842zM13.474 12.632l-2.527-3.789H16z"></path>
                                                        <path d="M12.632 2.105h1.684v7.579h-1.684z"></path>
                                                        <path d="M13.473 0L16 3.789h-5.053z"></path>
                                                    </svg>
                                                </i>
                                            </th>
                                            <th><?php echo $titleLbl = Label::getLabel('LBL_TITLE') ?></th>
                                            <th><?php echo $typeLbl = Label::getLabel('LBL_TYPE') ?></th>
                                            <th><?php echo $cateLbl = Label::getLabel('LBL_CATEGORY') ?></th>
                                            <th><?php echo $subcateLbl = Label::getLabel('LBL_SUB_CATEGORY') ?></th>
                                            <th><?php echo $actionLbl = Label::getLabel('LBL_ACTION') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody class="sortableJs">
                                        <?php if (count($questions) > 0) { ?>
                                            <?php foreach ($questions as $question) { ?>
                                                <tr data-id="<?php echo $question['quique_ques_id'] ?>">
                                                    <td>
                                                        <a href=" javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move sortHandlerJs">
                                                            <svg class="svg-icon" viewBox="0 0 16 12.632">
                                                                <path d="M7.579 9.263v1.684H0V9.263zm1.684-4.211v1.684H0V5.053zM7.579.842v1.684H0V.842zM13.474 12.632l-2.527-3.789H16z"></path>
                                                                <path d="M12.632 2.105h1.684v7.579h-1.684z"></path>
                                                                <path d="M13.473 0L16 3.789h-5.053z"></path>
                                                            </svg>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="flex-cell">
                                                            <div class="flex-cell__label">
                                                                <?php echo $titleLbl; ?>:
                                                            </div>
                                                            <div class="flex-cell__content">
                                                                <div style="max-width: 250px;">
                                                                    <p class="mb-1 bold-600 color-black">
                                                                        <?php echo $question['ques_title']; ?>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="flex-cell">
                                                            <div class="flex-cell__label">
                                                                <?php echo $typeLbl; ?>: </div>
                                                            <div class="flex-cell__content">
                                                                <div style="max-width: 250px;">
                                                                    <?php echo $types[$question['ques_type']]; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="flex-cell">
                                                            <div class="flex-cell__label">
                                                                <?php echo $cateLbl; ?>: </div>
                                                            <div class="flex-cell__content">
                                                                <div style="max-width: 250px;">
                                                                    <?php echo $question['cate_name']; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="flex-cell">
                                                            <div class="flex-cell__label">
                                                                <?php echo $subcateLbl; ?>: </div>
                                                            <div class="flex-cell__content">
                                                                <div style="max-width: 250px;">
                                                                    <?php echo $question['subcate_name']; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="flex-cell">
                                                            <div class="flex-cell__label">
                                                                <?php echo $actionLbl; ?>: </div>
                                                            <div class="flex-cell__content">
                                                                <div class="actions-group">
                                                                    <a href="javascript:void(0);" onclick="remove('<?php echo $question['quique_quiz_id'] ?>', '<?php echo $question['quique_ques_id'] ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                                                        <svg class="icon icon--issue icon--small">
                                                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>/images/sprite.svg#trash"></use>
                                                                        </svg>
                                                                        <div class="tooltip tooltip--top bg-black">
                                                                            <?php echo Label::getLabel('LBL_REMOVE'); ?>
                                                                        </div>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="6"><?php echo Label::getLabel('LBL_NO_QUESTIONS_FOUND') ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function() {
        $(".sortableJs").sortable({
            handle: ".sortHandlerJs",
            update: function(event, ui) {
                updateOrder('<?php echo $quizId ?>');
            },
            containment: ".sortableWrapperJs"
        });
    });
</script>