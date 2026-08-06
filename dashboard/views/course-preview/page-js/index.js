var tutor = false;
var notes = false;
var vdoPlayer = true;
var archived = 0;
$(function() {
    getLecture = function(next = 1, lectureCompleted = 0) {
        if (archived == 1) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('CoursePreview', 'getLecture', [next]), {
            'course_id': courseId,
            'lecture_id': currentLectureId,
        }, function(res) {
            if (lectureCompleted == 1) {
                var sectionId = $('.lecturesListJs input[type="checkbox"][value="' + currentLectureId + '"]').data('section');
                markComplete(sectionId, currentLectureId);
            }
            if (res.lecture_id == 0 && $('.quizListJs').length > 0) {
                $('.quizListJs').click();
                return;
            }
            loadLecture(res.lecture_id);
        });
    };

    removeEvent = function(player) {
        player.video.removeEventListener("ended", endedHandler);
    }

    addedEvent = function(player) {
        player.video.addEventListener("ended", endedHandler);
    }
    setupLayout = function (type = 'lecture') {
        $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
        $('.sidebarPanelJs').css({ 'display': '' });
        $('.tutorialTabsJs ul li').removeClass('is-active').show();
        $('.crsDetailTabJs').parent().addClass('is-active');
        $('.lectureDetailJs, .tabsPanelJs').show();
        $('.quizTitleJs, .lecTitleJs').hide();
        (type == 'quiz') ? $('.quizTitleJs').show() : $('.lecTitleJs').show();
    };
    loadLecture = function(lectureId) {
        archived = 0;
        if (lectureId > 0) {
            fcom.ajax(fcom.makeUrl('CoursePreview', 'getLectureData', [courseId, lectureId]), '', function(res) {
                $('.lectureDetailJs').html(res);
                getVideo(lectureId);
                if ($('.lecturesListJs input[type="checkbox"][value="' + lectureId + '"]').is(':checked') == true) {
                    $('#btnComplete' + lectureId).addClass('btn--disabled');
                }
            });
            currentLectureId = lectureId;
            $('.lecturesListJs .lecture, .sectionListJs').removeClass('is-active');
            $('#lectureJs' + lectureId).addClass('is-active');
            $('#lectureJs' + lectureId).parents('.sectionListJs').addClass('is-active');
            $('.lectureTitleJs').text($('#lectureJs' + lectureId + ' .lectureName').text());
            $('.sectionListJs .control-target-js').hide();
            $('#lectureJs' + lectureId).parents('.control-target-js').show();
            $('.quizListJs').removeClass('is-active');
        }
        setupLayout();
    };
    loadArchivedLecture = function (lectureId) {
        archived = 1;
        if (lectureId > 0) {
            fcom.ajax(fcom.makeUrl('CoursePreview', 'getArchivedLectureData', [lectureId]), '', function (res) {
                $('.lectureDetailJs').html(res);
                $('.lectureTitleJs').text($('#lectureJs' + lectureId + ' .lectureName').text());
                $('.lecturesListJs .lecture, .sectionListJs').removeClass('is-active');
                $('#lectureJs' + lectureId).addClass('is-active');
                $('#lectureJs' + lectureId).parents('.sectionListJs').addClass('is-active');
                getVideo(lectureId);
            });
        }
        setupLayout();
    };
    getVideo = function(lectureId) {
        fcom.updateWithAjax(fcom.makeUrl('CoursePreview', 'getVideo', [courseId, lectureId]), '', function(res) {      
            $('.directions-next.getNextJs').hide();
            $('.directions-prev.getPrevJs').hide();
            $('.videoContentJs div.course-video, .videoContentJs div.course-video-error, .videoContentJs div.course-quiz').hide();
            if (res.videoUrl || res.error != '') {
                if (res.error) {
                    $('.videoContentJs div.course-video-error span').html(res.error);
                    $('.videoContentJs div.course-video-error').css({ 'display': 'flex' });
                } else {
                    $('.videoContentJs div.course-video').show();
                    if($('mux-player').length > 0) {
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

    $('body').on('click', '.getNextJs', function() {
        if ($(this).attr('last-record') == 1) {
            return;
        }
        getLecture();
    });
    $('body').on('click', '.getPrevJs', function() {
        if ($(this).attr('last-record') == 1) {
            return;
        }
        getLecture(0);
    });
    $('.lecturesListJs input[type="checkbox"]').change(function() {
        setCompleteCount($(this).data('section'), $(this));
        if ($(this).is(':checked')) {
            $('#btnComplete' + $(this).val()).addClass('btn--disabled');
        } else {
            $('#btnComplete' + $(this).val()).removeClass('btn--disabled');
        }
        setProgress();
    });
    setCompleteCount = function(sectionId, obj) {
        $('.completedLecture' + sectionId).text($(obj).parents('.lecturesListJs').find('input[type="checkbox"]:checked').length);

        var totalLectures = parseInt($('.sidebarPanelJs').find('input[type = "checkbox"]').not(':disabled').length);
        var completedLectures = parseInt($('.sidebarPanelJs').find('input[type = "checkbox"]:checked').not(':disabled').length);
        var lbl = langLbl.courseProgressPercent;
        var percent = (completedLectures * 100) / totalLectures;
        lbl = lbl.replace("{percent}", percent.toFixed(2));
        $('.progressPercent').html(lbl);
    };
    markComplete = function(sectionId, lectureId) {
        var obj = $('.lecturesListJs input[type="checkbox"][value="' + lectureId + '"]');
        $(obj).prop('checked', true);
        setCompleteCount(sectionId, obj);
        $('#btnComplete' + lectureId).addClass('btn--disabled');
        setProgress();
    };
    setProgress = function() {
        var totalLectures = parseInt($('.sidebarPanelJs').find('input[type="checkbox"]').not(':disabled').length);
        var completedLectures = parseInt($('.sidebarPanelJs').find('input[type="checkbox"]:checked').not(':disabled').length);
        var percent = (completedLectures * 100) / totalLectures;
        $('#progressBarJs').prop('style', "--percent:" + percent.toFixed(2));
    };
    getTutorInfo = function() {
        if (tutor == false) {
            fcom.ajax(fcom.makeUrl('CoursePreview', 'getTeacherDetail'), { 'course_id': courseId }, function(res) {
                $('.tutorInfoJs').html(res);
                tutor = true;
                fcom.close();
            });
        }
        $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
        $('.sidebarPanelJs').css({ 'display': '' });
        $('.tutorInfoJs, .tabsPanelJs').show();
    };
    getReviews = function() {
        fcom.ajax(fcom.makeUrl('CoursePreview', 'getReviews'), { 'course_id': courseId }, function(res) {
            $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
            $('.sidebarPanelJs').css({ 'display': '' });
            $('.reviewsJs').html(res).show();
            $('.tabsPanelJs').show();
            searchReviews();
        });
    };
    searchReviews = function() {
        var data = fcom.frmData(document.reviewFrm);
        fcom.ajax(fcom.makeUrl('CoursePreview', 'searchReviews'), data, function(res) {
            $('.reviewSrchListJs').remove();
            $('.reviewsListJs').after(res);
            fcom.close();
        });
    };
    goToReviewsSearchPage = function(page) {
        fcom.process();
        var frm = document.reviewFrm;
        $(frm.pageno).val(page);
        searchReviews(frm);
    };
    getNotes = function() {
        fcom.process();
        if (notes == false) {
            fcom.ajax(fcom.makeUrl('LectureNotes', 'index'), { 'course_id': courseId, 'is_preview': 1 }, function(res) {
                $('.notesJs').html(res);
                notesSearch(document.frmNotesSearch);
                notes = true;
            });
        }
        $('.lectureDetailJs, .notesJs, .reviewsJs, .tutorInfoJs').hide();
        $('.sidebarPanelJs').css({ 'display': '' });
        $('.notesJs, .tabsPanelJs').show();
        fcom.close();
    };
    notesSearch = function(frm) {
        var data = fcom.frmData(frm);
        data += '&is_preview=1';
        fcom.ajax(fcom.makeUrl('LectureNotes', 'search'), data, function(res) {
            $('.notesListingJs').html(res);
            fcom.close();
        });
    };
    clearNotesSearch = function() {
        document.frmNotesSearch.reset();
        $('.notesHeadJs .form-search__action--reset').hide();
        notesSearch(document.frmNotesSearch);
    };
    goToNotesSearchPage = function(page) {
        var frm = document.frmNotesPaging;
        $(frm.page).val(page);
        notesSearch(frm);
    };
    $('body').on('input', '#notesKeywordJs', function() {
        var val = $(this).val();
        if (val != '') {
            $('.notesHeadJs .form-search__action--reset').show();
        } else {
            $('.notesHeadJs .form-search__action--reset').hide();
        }
    });
    openQuiz = function (id) {
        fcom.ajax(fcom.makeUrl('CoursePreview', 'getQuizDetail'), { id, courseId }, function (res) {
            $('.lectureDetailJs').html(res);
            $('.lectureTitleJs').text($('.quizListJs .lectureName').text());
            $('.lecturesListJs .lecture, .sectionListJs').removeClass('is-active');
            $('.quizListJs').addClass('is-active');
            $('.lecturesListJs').parent().hide();
            setupLayout('quiz');
            $('.crsNotesJs').hide();
        });
    };
    getQuiz = function () {
        fcom.ajax(fcom.makeUrl('CoursePreview', 'getQuiz'), { courseId }, function (res) {
            $('.videoContentJs div.course-video, .videoContentJs div.course-video-error, .videoContentJs div.course-quiz').hide();
            $('.directions-next.getNextJs, .directions-prev.getPrevJs').hide();
            $('.videoContentJs div.course-quiz').html(res).show();
        });
    };
    getLecture(1);
});