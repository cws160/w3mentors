/* global fcom, e, langLbl */
$(document).ready(function () {
    searchTestimonial(document.srchForm);
});
(function () {
    var active = 1;
    var inActive = 0;
    var dv = '#listing';
    goToSearchPage = function (page) {
        var frm = document.srchFormPaging;
        $(frm.page).val(page);
        searchTestimonial(frm);
    }
    reloadList = function () {
        searchTestimonial(document.srchFormPaging);
    };
    searchTestimonial = function (frm) {
        fcom.ajax(fcom.makeUrl('Testimonials', 'search'), fcom.frmData(frm), function (res) {
            $(dv).html(res);
        });
    };
    addTestimonialForm = function (id) {
        testimonialForm(id);
    };
    testimonialForm = function (id) {
        fcom.ajax(fcom.makeUrl('Testimonials', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    editTestimonialFormNew = function (testimonialId) {
        editTestimonialForm(testimonialId);
    };
    editTestimonialForm = function (testimonialId) {
        fcom.ajax(fcom.makeUrl('Testimonials', 'form', [testimonialId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupTestimonial = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Testimonials', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                editTestimonialLangForm(t.testimonialId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        });
    }
    editTestimonialLangForm = function (testimonialId, langId) {
        fcom.ajax(fcom.makeUrl('Testimonials', 'langForm', [testimonialId, langId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setupLangTestimonial = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Testimonials', 'langSetup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                if (langId == 'media') {
                    testimonialMediaForm(t.testimonialId);
                } else {
                    editTestimonialLangForm(t.testimonialId, langId);
                }
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        var data = 'testimonialId=' + id;
        fcom.updateWithAjax(fcom.makeUrl('Testimonials', 'deleteRecord'), data, function (res) {
            reloadList();
        });
    };
    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var testimonialId = parseInt(obj.id);
        var data = 'testimonialId=' + testimonialId + "&status=" + active;
        fcom.ajax(fcom.makeUrl('Testimonials', 'changeStatus'), data, function (res) {
            searchTestimonial(document.srchForm);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var testimonialId = parseInt(obj.id);
        var data = 'testimonialId=' + testimonialId + "&status=" + inActive;
        fcom.ajax(fcom.makeUrl('Testimonials', 'changeStatus'), data, function (res) {
            searchTestimonial(document.srchForm);
        });
    };
    clearSearch = function () {
        document.frmSearch.reset();
        searchTestimonial(document.frmSearch);
    };
    testimonialMediaForm = function (testimonialId) {
        fcom.ajax(fcom.makeUrl('Testimonials', 'media', [testimonialId]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    removeTestimonialImage = function (testimonialId, langId) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Testimonials', 'removeTestimonialImage', [testimonialId, langId]), '', function (t) {
            testimonialMediaForm(testimonialId);
        });
    }
})();
$(document).on('click', '.uploadFile-Js', function () {
    var node = this;
    $('#form-upload').remove();
    var testimonialId = $(node).attr('data-testimonial_id');
    var langId = 0;
    var frm = '<form enctype="multipart/form-data" id="form-upload" style="position:absolute; top:-100px;" >';
    frm = frm.concat('<input type="file" name="file" />');
    frm = frm.concat('<input type="hidden" name="testimonialId" value="' + testimonialId + '"/>');
    frm = frm.concat('<input type="hidden" name="lang_id" value="' + langId + '"/>');
    frm = frm.concat('</form>');
    $('body').prepend(frm);
    $('#form-upload input[name=\'file\']').trigger('click');
    if (typeof timer != 'undefined') {
        clearInterval(timer);
    }
    timer = setInterval(function () {
        if ($('#form-upload input[name=\'file\']').val() != '') {
            clearInterval(timer);
            $val = $(node).val();
            var data = new FormData($('#form-upload')[0]);
            fcom.ajaxMultipart(fcom.makeUrl('Testimonials', 'uploadTestimonialMedia'), data, function (res) {
                $(node).val($val);
                $('.text-danger').remove();
                $('#input-field').html(res.msg);
                testimonialMediaForm(res.testimonialId);
            }, { fOutMode: 'json' });
        }
    }, 500);
});