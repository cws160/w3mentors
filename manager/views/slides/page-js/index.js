/* global fcom, langLbl, SITE_ROOT_URL */
$(document).ready(function () {
    searchSlides(document.frmSlideSearch);
});
(function () {
    var active = 1;
    var inActive = 0;
    reloadList = function () {
        var frm = document.frmSlideSearch;
        searchSlides(frm);
    }
    searchSlides = function (form) {
        var dv = '#listing';
        fcom.ajax(fcom.makeUrl('Slides', 'search'), fcom.frmData(form), function (res) {
            $(dv).html(res);
        });
    };
    addSlideForm = function (id) {
        slideForm(id);
    };
    slideForm = function (id) {
        fcom.ajax(fcom.makeUrl('Slides', 'form', [id]), '', function (t) {
            fcom.updatePopupContent(t);
        });
    };
    setup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Slides', 'setup'), fcom.frmData(frm), function (t) {
            reloadList();
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                slideMediaForm(t.slideId, langId);
                return;
            }
        });
    };
    slideMediaForm = function (slideId, langId) {
        fcom.ajax(fcom.makeUrl('Slides', 'mediaForm'), { langId: langId, slideId: slideId }, function (t) {
            fcom.updatePopupContent(t);
        });
    };
    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Slides', 'deleteRecord'), { id: id }, function (res) {
            reloadList();
        });
    };

    activeStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var slideId = parseInt(obj.id);
        var data = 'slideId=' + slideId + '&status=' + active;
        fcom.ajax(fcom.makeUrl('Slides', 'changeStatus'), data, function (res) {
            searchSlides(document.frmSlideSearch);
        });
    };
    inactiveStatus = function (obj) {
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var slideId = parseInt(obj.id);
        var data = 'slideId=' + slideId + '&status=' + inActive;
        fcom.ajax(fcom.makeUrl('Slides', 'changeStatus'), data, function (res) {
            searchSlides(document.frmSlideSearch);
        });
    };
    setupMedia = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        var data = new FormData(frm);
        fcom.ajaxMultipart(fcom.makeUrl('Slides', 'setupMedia'), data, function (response) {
            let element = $('.tabs-nav a.active').parent().next('li');
            if (element.length > 0) {
                let langId = element.find('a').attr('data-id');
                slideMediaForm(response.slideId, langId);
                return;
            } else {
                $.w3mentorsmodal.close();
            }
        }, { fOutMode: 'json' });
    };
    
    $(document).on('click', '.homepageSlide-Js', function () {
        var fld = $(this).data('fld');
        $('input[name="'+fld+'"]').trigger('click');
    });

    $(document).on('change', '.slideImages', function () {
        var input = this;
        var url = $(this).val();
        let fldName = $(this).attr('name');
        var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
        if (input.files && input.files[0]&& (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) 
         {
            var reader = new FileReader();
            reader.onload = function (e) {
               $('#'+fldName).attr('src', e.target.result);
            }
           reader.readAsDataURL(input.files[0]);
        }
      });
})();