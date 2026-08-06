/* global fcom */
$(function () {
    $('form[name=frmRobots]').submit(function (event) {
        event.preventDefault();
        if (!$(this).validate()){
            return;
        }
        var data = fcom.frmData(this);
        fcom.updateWithAjax(fcom.makeUrl('Bots', 'setup'), data, function (res) {});
    });
});
