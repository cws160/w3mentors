/* global langLbl, fcom */

$(function () {
    copyCode = function () {
        try {
            let text = document.getElementById('referral_code').value;
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.className = "copiedCode";
            $("#referral_code").after(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.style.display = "none";
            $('.copiedCode').remove();
            fcom.success(langLbl.copied);
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    }

    goToSearchPage = function (pageno) {
        var frm = document.frmSearchPaging;
        $(frm.pageno).val(pageno);
        search(frm);
    };
    search = function (frm) {
        fcom.ajax(fcom.makeUrl('Refer', 'search'), fcom.frmData(frm), function (response) {
            $("#listing").html(response);
        });
    };
    clearSearch = function () {
        document.frmRewardPointSearch.reset();
        search(document.frmRewardPointSearch);
    };
    closeForm = function () {
        $.w3mentorsmodal.close()
    };
    sendMails = function (frm) {
        fcom.updateWithAjax(fcom.makeUrl('Refer', 'sendMails'), fcom.frmData(frm), function (response) {
            if (response.status) {
                $("input[name='emails']").tagit('removeAll');
            }

        });
    };
    redeemPoints = function (userID) {
        if (!confirm('Are you sure you want to redeem these points to wallet?')) {
            return false;
        }
        var data = "user_id=" + userID;
        fcom.updateWithAjax(fcom.makeUrl('Refer', 'redeemPoints'), data, function (response) {
            if (response.status) {
               location.reload();
            }

        });
    };
    search();
});

$(document).ready(function () {
    $('input[name="emails"]').tagit({
        caseSensitive: false,
        allowDuplicates: false,
        allowSpaces: true,
        singleFieldDelimiter: '||',
    });
    $('.ui-autocomplete-input').attr('name', 'tags');
    $('form input[name="emails"]').on('keypress', function (e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });
});