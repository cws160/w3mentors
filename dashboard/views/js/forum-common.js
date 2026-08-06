var forum = {
    removeMultiSpaces: function (str, trimSpacesFromRight)
    {
        trimSpacesFromRight = trimSpacesFromRight || false;
        let txt = str.replace(/\s\s+/g, ' ');
        txt = txt.trimStart();
        if (true == trimSpacesFromRight) {
            txt = txt.trimEnd();
        }
        return txt;
    },
    validateTxtLimit: function (thsElm, trimSpacesFromRight)
    {
        trimSpacesFromRight = trimSpacesFromRight || false;
        let txt = forum.removeMultiSpaces($(thsElm).val(), trimSpacesFromRight);
        $(thsElm).val(txt);
        var ele = $(thsElm).parent();
        if ($(ele).hasClass('field-count')) {
            var max = parseInt($(ele).data('length'));
            var strLen = parseInt(txt.length);
            var limit = max - strLen;
            if (limit < 0) {
                $(thsElm).val(txt.substring(0, max));
                $(ele).attr('field-count', 0);
                return;
            }
            $(ele).attr('field-count', limit);
        }
        return true;
    }
};