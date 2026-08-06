/* global langLbl, pkghours, fcom, labels */

(function () {
    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl("Packages", "search"), fcom.frmData(frm), function (response) {
            $("#listing").html(response);
        });
    };
    clearSearch = function () {
        document.frmPackageSearch.reset();
        search(document.frmPackageSearch);
    };
    form = function (packageId) {
        fcom.ajax(fcom.makeUrl("Packages", "form"), {packageId: packageId}, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    setup = function (form, goToLangForm) {
        if (!$(form).validate()) {
            return;
        }
        var data = new FormData(form);
        fcom.ajaxMultipart(fcom.makeUrl('Packages', 'setup'), data, function (res) {
            search(document.frmPackageSearch);
            if (goToLangForm && $('.lang-li').length > 0) {
                langId = $('.lang-li').first().attr('data-id');
                langForm(res.packageId, langId);
                return;
            }
            $.w3mentorsmodal.close();
        }, {fOutMode: 'json'});
    };
    showAddresses = function (isOffline) {
        if (isOffline == 1) {
            $('select[name="grpcls_address_id"]').attr({ 'disabled': false, 'class': '' });
        } else {
            $('select[name="grpcls_address_id"]').val('').attr({ 'disabled': true, 'class': 'selection-disabled' });
        }
    };
    langForm = function (packageId, langId) {
        fcom.ajax(fcom.makeUrl("Packages", "langForm"), {packageId: packageId, langId: langId}, function (res) {
            $.w3mentorsmodal(res, { 'size': 'modal-lg' });
        });
    };
    langSetup = function (form, goToNext) {
        if (!$(form).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl("Packages", "setupLang"), fcom.frmData(form), function (res) {
            search(document.frmPackageSearch);
            if (goToNext && $('.lang-list .is-active').next('li').length > 0) {
                $('.lang-list .is-active').next('li').find('a').trigger('click');
                return;
            }
            $.w3mentorsmodal.close();
        });
    };
    cancelSetup = function (packageId) {
        if (!confirm(langLbl.confirmCancel)) {
            return false;
        }
        fcom.updateWithAjax(fcom.makeUrl('Packages', 'cancelSetup'), {packageId: packageId}, function (res) {
            if (res.status == 1) {
                search(document.frmPackageSearch);
                $.w3mentorsmodal.close();
            }
        });
    };
    formatSlug = function (fld) {
        fcom.updateWithAjax(fcom.makeUrl('Home', 'slug'), {slug: $(fld).val()}, function (res) {
            $(fld).val(res.slug);
            if (res.slug != '') {
                checkUnique($(fld), "tbl_group_classes", "grpcls_slug", "grpcls_id", $("#grpcls_id"), []);
            }
        });
    };
    addClassRow = function () {
        $(".more-container-js").append(getClassRow(counter + 1));
        bindDatetimePicker('.datetime');
        counter = 1 + counter;
    };
    removeClassRow = function (no) {
        $(".class-row-" + no).remove();
    };
    getClassRow = function (no) {
        return `<div class="row class-row-${no}">
                <div class="col-md-8">
                    <div class="field-set">
                        <div class="caption-wraper"> <label class="field_label"> ${labels.CLASS_TITLE}-${no} <span class="spn_must_field">*</span> <a href="javascript:removeClassRow(${no})" class="color-secondary"> ${labels.REMOVE_CLASS}</a></label> </div>
                        <div class="field-wraper"> <div class="field_cover"> <input data-field-caption="${labels.CLASS_TITLE}-${no}" data-fatreq="{&quot;required&quot;:true,&quot;lengthrange&quot;:[10,100]}" type="text" name="title[]" value=""> </div> </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="field-set">
                        <div class="caption-wraper"> <label class="field_label"> ${labels.START_TIME} <span class="spn_must_field">*</span> </label> </div>
                        <div class="field-wraper"> <div class="field_cover"> <input class="datetime" autocomplete="off" readonly="readonly" data-field-caption="${labels.START_TIME}" data-fatreq="{&quot;required&quot;:true}" type="text" name="starttime[]" value=""> </div> </div>
                    </div>
                </div>
            </div>`;
    };

})();