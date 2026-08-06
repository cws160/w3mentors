(function () {
    viewDetail = function (lessonId) {
        $.w3mentorsmodal(function () {
            fcom.ajax(fcom.makeUrl('PurchasedLessons', 'viewDetail', [lessonId]), '', function (t) {
                $.w3mentorsmodal(t);
            });
        });
    };
    updateScheduleStatus = function (id, value) {
        if (!confirm("Do you really want to update status?")) {
            return;
        }
        if (id === null) {
            fcom.error('Invalid Request!');
            return false;
        }
        fcom.ajax(fcom.makeUrl('PurchasedLessons', 'updateStatusSetup'), {"slesson_id": id, "slesson_status": value}, function (json) {
            res = $.parseJSON(json);
        });
    };
})();	