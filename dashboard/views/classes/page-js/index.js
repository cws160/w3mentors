/* global monthNames, langLbl, fcom, VIEW_CALENDAR, VIEW_LISTING, VIEW_LISTING */
var dayShortNames = weekDayNames.shortName.slice(0);
var lastValue = dayShortNames[6];
dayShortNames.pop();
dayShortNames.unshift(lastValue);
defaultsValue = {
    monthNames: monthNames.longName,
    monthNamesShort: monthNames.shortName,
    dayNamesMin: dayShortNames,
    dayNamesShort: dayShortNames,
    currentText: langLbl.today,
    closeText: langLbl.done,
    prevText: langLbl.prev,
    nextText: langLbl.next,
    isRTL: (layoutDirection == 'rtl')
};
$.datepicker.regional[''] = $.extend(true, {}, defaultsValue);
$.datepicker.setDefaults($.datepicker.regional['']);
(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    searchListing = function (frm) {
        fcom.ajax(fcom.makeUrl('Classes', 'search'), fcom.frmData(frm), function (response) {
            $("#listing").html(response);
        });
    };
    search = function (form) {
        var view = (form && form.view.value) ? parseInt(form.view.value) : VIEW_LISTING;
        switch (view) {
            case VIEW_CALENDAR:
                getCalendarView();
                break;
            case VIEW_LISTING:
            default:
                searchListing(form);
                break;
        }
    };
    getCalendarView = function () {
        fcom.ajax(fcom.makeUrl('Classes', 'calendarView'), '', function (response) {
            $("#listing").html(response);
        });
    };
    clearSearch = function () {
        document.frmClassSearch.reset();
        search(document.frmClassSearch);
    };
    addForm = function (classId) {
        fcom.ajax(fcom.makeUrl('Classes', 'addForm'), {classId: classId}, function (response) {
            $.w3mentorsmodal(response, { 'size': 'modal-lg' });
            bindDatetimePicker("#grpcls_start_datetime");
        });
    };
    showAddresses = function (isOffline) {
        if (isOffline == 1) {
            $('select[name="grpcls_address_id"]').attr({'disabled': false, 'class': ''});
        } else {
            $('select[name="grpcls_address_id"]').val('').attr({'disabled': true, 'class': 'selection-disabled'});
        }
    };
    setupClass = function (form, goToLangForm) {
        if (!$(form).validate()) {
            return;
        }
        var data = new FormData(form);
        fcom.ajaxMultipart(fcom.makeUrl('Classes', 'setupClass'), data, function (res) {
            search(document.frmClassSearch);
            if (goToLangForm && $('.lang-li').length > 0) {
                langId = $('.lang-li').first().attr('data-id');
                langForm(res.classId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        }, {fOutMode: 'json'});
    };
    langForm = function (classId, langId) {
        fcom.ajax(fcom.makeUrl('Classes', 'langForm'), {classId: classId, langId: langId}, function (response) {
            $.w3mentorsmodal(response, { 'size':'modal-lg'});
        });
    };
    setupLangData = function (form, goToNext) {
        if (!$(form).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Classes', 'setupLang'), fcom.frmData(form), function (res) {
            search(document.frmClassSearch);
            if (goToNext && $('.lang-list .is-active').next('li').length > 0) {
                $('.lang-list .is-active').next('li').find('a').trigger('click');
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    formatSlug = function (fld) {
        fcom.updateWithAjax(fcom.makeUrl('Home', 'slug'), {slug: $(fld).val()}, function (res) {
            $(fld).val(res.slug);
            if (res.slug != '') {
                checkUnique($(fld), 'tbl_group_classes', 'grpcls_slug', 'grpcls_id', $('#grpcls_id'), []);
            }
        });
    };
})();