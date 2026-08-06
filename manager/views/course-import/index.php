<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <h5><?php echo Label::getLabel('LBL_Course_Import'); ?></h5>
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="card">
            <div class="card-body">
                <div id="courseImportFormBlock">
                    <?php echo Label::getLabel('LBL_Loading..'); ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        importForms();
    });

    (function() {
        var dv = '#courseImportFormBlock';
        importForms = function() {
            fcom.ajax(fcom.makeUrl('CourseImport', 'courseImportForm'), '', function(t) {
                $(dv).html(t);
            });
        };

        submitImportUploadedCourse = function() {
            var data = new FormData();
            $inputs = $('#courseImportForm input[type=text],#courseImportForm select,#courseImportForm input[type=hidden]');
            $inputs.each(function() {
                data.append(this.name, $(this).val());
            });
            $.each($('#import_file')[0].files, function(i, file) {
                data.append('import_file', file);
                fcom.ajaxMultipart(fcom.makeUrl('CourseImport', 'submitImportUploadedCourse'), data, function(res) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }, {
                    fOutMode: 'json',
                    timeout: 0
                });
            });
        };

        submitImportUploadedReviews = function() {
            var data = new FormData();
            $inputs = $('#reviewImportForm input[type=text],#reviewImportForm select,#reviewImportForm input[type=hidden]');
            $inputs.each(function() {
                data.append(this.name, $(this).val());
            });
            $.each($('#import_review_file')[0].files, function(i, file) {
                data.append('import_review_file', file);
                fcom.ajaxMultipart(fcom.makeUrl('CourseImport', 'submitImportUploadedReviews'), data, function(res) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }, {
                    fOutMode: 'json',
                    timeout: 0
                });
            });
        };

    })();
</script>