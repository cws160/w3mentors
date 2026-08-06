var tutor = false;
var notes = false;
var lecture = false;
var reviews = false;
var archived = 0;
$(function () {
    setupLayout = function (type = 'lecture') {
        $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
        $('.sidebarPanelJs').css({ 'display': '' });
        $('.tutorialTabsJs ul li').removeClass('is-active').show();
        $('.crsDetailTabJs').parent().addClass('is-active');
        $('.lectureDetailJs, .tabsPanelJs').show();
        $('.quizTitleJs, .lecTitleJs').hide();
        (type == 'quiz') ? $('.quizTitleJs').show() : $('.lecTitleJs').show();
    };
    loadLecture = function (lectureId) {
        archived = 0;
        if (lecture == false || lectureId > 0) {
            var progressId = $('#progressId').val();
            fcom.ajax(fcom.makeUrl('Tutorials', 'getLectureData', [lectureId, progressId]), '', function (res) {
                $('.lectureDetailJs').html(res);
                setCurrentLecture(lectureId);
                getVideo(lectureId);
                lecture = true;
            });
        }
        setupLayout();
    };
    loadArchivedLecture = function (lectureId) {
        archived = 1;
        if (lecture == false || lectureId > 0) {
            fcom.ajax(fcom.makeUrl('Tutorials', 'getArchivedLectureData', [lectureId]), '', function (res) {
                $('.lectureDetailJs').html(res);
                $('.lectureTitleJs').text($('#lectureJs' + lectureId + ' .lectureName').text());
                $('.lecturesListJs .lecture, .sectionListJs').removeClass('is-active');
                $('#lectureJs' + lectureId).addClass('is-active');
                $('#lectureJs' + lectureId).parents('.sectionListJs').addClass('is-active');
                getVideo(lectureId);
                lecture = true;
            });
        }
        setupLayout();
    };
    getVideo = function (lectureId) {
        var progressId = $('#progressId').val();
        fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'getVideo', [lectureId, progressId]), '', function (res) {
            $('.directions-next.getNextJs').hide();
            $('.directions-prev.getPrevJs').hide();
            $('.videoContentJs div.course-video, .videoContentJs div.course-video-error, .videoContentJs div.course-quiz').hide();
            if (res.videoUrl || res.error != '') {
                if (res.error) {
                    $('.videoContentJs div.course-video-error span').html(res.error);
                    $('.videoContentJs div.course-video-error').css({ 'display': 'flex' });
                } else {
                    $('.videoContentJs div.course-video').show();
                    if ($('mux-player').length > 0) {
                        $('mux-player').attr('playback-id', res.videoUrl);
                    } else {
                        $('iframe[data-id="vdocipher"]').attr('src', res.videoUrl);
                    }
                }
                if (res.nextLecture) {
                    $('.directions-next.getNextJs .directionTitleJs').html(res.nextLecture);
                    $('.directions-next.getNextJs').show();
                }
                if (res.previousLecture) {
                    $('.directions-prev.getPrevJs .directionTitleJs').html(res.previousLecture);
                    $('.directions-prev.getPrevJs').show();
                }
            }
        });
    };
    setCurrentLecture = function (lectureId) {
        $('.lecturesListJs .lecture, .sectionListJs').removeClass('is-active');
        $('.sectionListJs .control-target-js').hide();
        $('#lectureJs' + lectureId).addClass('is-active');
        $('#lectureJs' + lectureId).parents('.sectionListJs').addClass('is-active');
        $('#lectureJs' + lectureId).parents('.control-target-js').show();
        $('.lectureTitleJs').text($('#lectureJs' + lectureId + ' .lectureName').text());
        $('.quizListJs').removeClass('is-active');
        currentLectureId = lectureId;
    };
    getLecture = function (lectureCompleted = 0, next = 1) {
        if (archived == 1) {
            return;
        }
        var progressId = $('#progressId').val();
        fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'getLecture', [next]), {
            'progress_id': progressId,
        }, function (res) {
            
            lecture = false;
            if (lectureCompleted == 1) {
                markComplete(res.previous_lecture_id, 1);
            }

            if (res.next_lecture_id == 0 && $('.quizListJs').length > 0) {
                $('.quizListJs').click();
                return;
            }
            loadLecture(res.next_lecture_id);
            setProgress();
        });
    };
    markComplete = function (lectureId, status) {
        fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'markComplete'), {
            'status': status,
            'lecture_id': lectureId,
            'progress_id': $('#progressId').val()
        }, function (res) {
            var obj = $('#lectureJs' + lectureId).find('input[type="checkbox"]');
            if (!res.status) {
                if (status == 1) {
                    $(obj).prop('checked', false);
                } else {
                    $(obj).prop('checked', true);
                }
                return false;
            }

            if (status == 1) {
                $(obj).prop('checked', true);
                $('#btnComplete' + lectureId).addClass('btn--disabled');
            } else {
                $('#btnComplete' + lectureId).removeClass('btn--disabled');
            }
            var sectionId = $(obj).data('section');
            $('.completedLecture' + sectionId).text($(obj).parents('.lecturesListJs').find('input[type="checkbox"]:checked').length);
            setProgress();
        }, { 'process': false, 'failed': true });
    };
    $('.lecturesListJs input[type="checkbox"]').change(function () {
        var _obj = $(this);
        var checked = ($(_obj).is(":checked")) ? 1 : 0;
        markComplete($(_obj).val(), checked);
    });
    $('body').on('click', '.getNextJs', function () {
        if ($(this).attr('last-record') == 1) {
            return;
        }
        getLecture();
    });
    $('body').on('click', '.getPrevJs', function () {
        if ($(this).attr('last-record') == 1) {
            return;
        }
        getLecture(0, 0);
    });
    setProgress = function () {
        var progressId = $('#progressId').val();
        fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'setProgress'), {
            'progress_id': progressId
        }, function (res) {
            var lbl = langLbl.courseProgressPercent;
            lbl = lbl.replace("{percent}", res.progress);
            $('.progressPercent').html(lbl);
            $('#progressBarJs').prop('style', "--percent:" + parseInt(res.progress));
            if (res.is_completed == true && $('.quizListJs').length == 0) {
                window.location = fcom.makeUrl('Tutorials', 'completed', [progressId]);
            }
        }, { 'process': false });
    };
    if (currentLectureId > 0) {
        loadLecture(currentLectureId);
    } else {
        getLecture();
    }
    getTutorInfo = function () {
        if (tutor == false) {
            fcom.ajax(fcom.makeUrl('Tutorials', 'getTeacherDetail'), { 'course_id': courseId }, function (res) {
                $('.tutorInfoJs').html(res);
                tutor = true;
            });
        }
        $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
        $('.sidebarPanelJs').css({ 'display': '' });
        $('.tutorInfoJs, .tabsPanelJs').show();
    };
    getReviews = function () {
        var progressId = $('#progressId').val();
        fcom.ajax(fcom.makeUrl('Tutorials', 'getReviews'), { 'course_id': courseId, 'progress_id': progressId }, function (res) {
            $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
            $('.sidebarPanelJs').css({ 'display': '' });
            $('.reviewsJs').html(res).show();
            $('.tabsPanelJs').show();
            searchReviews();
        });
    };
    searchReviews = function () {
        var data = fcom.frmData(document.reviewFrm);
        fcom.ajax(fcom.makeUrl('Tutorials', 'searchReviews'), data, function (res) {
            $('.reviewSrchListJs').remove();
            $('.reviewsListJs').after(res);
        });
    };
    goToReviewsSearchPage = function (page) {
        var frm = document.reviewFrm;
        $(frm.pageno).val(page);
        searchReviews(frm);
    };
    feedbackForm = function (ordcrsId) {
        fcom.ajax(fcom.makeUrl('Tutorials', 'feedbackForm'), { 'ordcrs_id': ordcrsId }, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    feedbackSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Tutorials', 'feedbackSetup'), fcom.frmData(frm), function (res) {
            $.w3mentorsmodal.close();
            $('.reviewFrmJs').removeAttr('onclick').addClass('btn--disabled');
        });
    };
    getNotes = function (id) {
        if (notes == false) {
            fcom.ajax(fcom.makeUrl('LectureNotes', 'index'), { 'course_id': courseId, 'ordcrs_id': id }, function (res) {
                $('.notesJs').html(res);
                notesSearch(document.frmNotesSearch);
                notes = true;
            });
        }
        $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
        $('.sidebarPanelJs').css({ 'display': '' });
        $('.notesJs, .tabsPanelJs').show();
    };
    notesSearch = function (frm, process = true) {
        var data = fcom.frmData(frm);
        fcom.ajax(fcom.makeUrl('LectureNotes', 'search'), data, function (res) {
            $('.notesListingJs').html(res);
        }, { process: process });
    };
    clearNotesSearch = function () {
        document.frmNotesSearch.reset();
        $('.notesHeadJs .form-search__action--reset').hide();
        notesSearch(document.frmNotesSearch);
    };
    goToNotesSearchPage = function (page) {
        var frm = document.frmNotesPaging;
        $(frm.page).val(page);
        notesSearch(frm);
    };
    notesForm = function (id, ordcrsId) {
        var mod = '';
        if ($('.quizListJs').hasClass('is-active')) {
            mod = 'quiz';
        } else if (archived == 1) {
            mod = 'archived';
        }
        fcom.ajax(fcom.makeUrl('LectureNotes', 'form', [id]), {
            'lecnote_lecture_id': currentLectureId,
            'lecnote_course_id': courseId,
            'lecnote_ordcrs_id': ordcrsId,
            'mod': mod
        }, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    $('body').on('input', '#notesKeywordJs', function () {
        var val = $(this).val();
        if (val != '') {
            $('.notesHeadJs .form-search__action--reset').show();
        } else {
            $('.notesHeadJs .form-search__action--reset').hide();
        }
    });
    setupNotes = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('LectureNotes', 'setup'), data, function (res) {
            clearNotesSearch();
            notesSearch(document.frmNotesSearch, false);
            $.w3mentorsmodal.close();
        });
    };
    removeNotes = function (id, ordcrsId) {
        if (confirm(langLbl.confirmRemove)) {
            fcom.updateWithAjax(fcom.makeUrl('LectureNotes', 'delete'), { 'lecnote_id': id, 'lecnote_ordcrs_id': ordcrsId }, function (res) {
                notesSearch(document.frmNotesSearch);
            });
        }
    };
    goToPendingLecture = function () {
        var lectureId = 0;
        $('.sectionListJs').each(function () {
            if (lectureId < 1) {
                if ($(this).find('input[type="checkbox"]:not(:checked)').length > 0) {
                    lectureId = $(this).find('input[type="checkbox"]:not(:checked):first').val();
                }
            }
        });
        loadLecture(lectureId);
    };
    openQuiz = function (id) {
        fcom.ajax(fcom.makeUrl('Tutorials', 'getQuizDetail'), { id }, function (res) {
            $('.lectureDetailJs').html(res);
            $('.lectureTitleJs').text($('.quizListJs .lectureName').text());
            $('.lecturesListJs .lecture, .sectionListJs').removeClass('is-active');
            $('.quizListJs').addClass('is-active');
            $('.lecturesListJs').parent().hide();
            setupLayout('quiz');
            $('.crsNotesJs').hide();
        });
    };
    getQuiz = function (id) {
        fcom.ajax(fcom.makeUrl('Tutorials', 'getQuiz'), { id }, function (res) {
            $('.videoContentJs div.course-video, .videoContentJs div.course-video-error, .videoContentJs div.course-quiz').hide();
            $('.directions-next.getNextJs, .directions-prev.getPrevJs').hide();
            $('.videoContentJs div.course-quiz').html(res).show();
        });
    };
    finishQuiz = function () {
        var progressId = $('#progressId').val();
        window.location = fcom.makeUrl('Tutorials', 'completed', [progressId]);
    }
});