var forum = {
    
    getQueUserId: function (elemId) {
        return $('#' + elemId).data('owner_id');
    },

    getloggedUserId: function (elemId) {
        let loggedUserId = $('#' + elemId).data('luser_id');
        if (1 > loggedUserId) {
            signinForm();
            return;
        }
        return loggedUserId;
    },

    scrollToElem: function (mainelem, elm, offset, timer)
    {
        if (elm) {
            $(mainelem).animate({
                scrollTop: offset
            }, timer);
        }
    },

    addNewQuestion: function (main_elm)
    {
        let lgUserId = this.getloggedUserId(main_elm);
        if (0 < lgUserId) {
            window.location = fcom.makeUrl('Forum', 'Form', [], confWebDashUrl);
        }
    },

    readMoreLink: function (elm, adjustheight, readMoreText) {
        var adjustheight = adjustheight || 6; /* units rem */
        var elm = elm || '.article-more';
        var ellipsestext = "";


        $(elm).each(function (i, item) {
            let elmHt = $('.article-more__content', $(item)).height() / parseFloat($("html").css("font-size")); /* em is relative to the size of font used. This is to get height in em */
            if (elmHt > adjustheight) {
                $(".article-more__content", $(item)).css('height', adjustheight + 'rem').css('overflow', 'hidden');
                $(item).append('<span class="article-more__action">' + ellipsestext + '<a href=" ' + $(".article-more__content", $(item)).data('link') + ' " class="adjust ">' + readMoreText + '</a></span>');
            }
        });
    }
};

(function ($) {
    upVote = function (ths, recordId, reactType) {
        fcom.updateWithAjax(fcom.makeUrl('Forum', 'upVote'), {recordId: recordId, reactType: reactType}, function (res) {
            handleCounters(ths, res.voteType, reactType);
        });
    };

    downVote = function (ths, recordId, reactType) {
        fcom.updateWithAjax(fcom.makeUrl('Forum', 'downVote'), {recordId: recordId, reactType: reactType}, function (res) {
            handleCounters(ths, res.voteType, reactType);
        });
    };

    handleCounters = function (ths, voteType, reactType)
    {
        var elmId = $(ths).data('record_id');
        var totalReactionCount = $('#tot_counts' + reactType + '_' + elmId).data('count');
        var totalUpCount = $('#up' + reactType + '_' + elmId).data('count');
        var totalDownCount = $('#down' + reactType + '_' + elmId).data('count');

        if ('1' == voteType) {
            totalUpCount = totalUpCount + 1;
            if (1 == $('#down' + reactType + '_' + elmId).data('downvoted')) {
                totalDownCount = totalDownCount - 1;
                $('#down' + reactType + '_' + elmId).data('downvoted', '0')
            }
            $('#up' + reactType + '_' + elmId).data('upvoted', '1')
            $('#up' + reactType + '_' + elmId).addClass('color-success');
            $('#down' + reactType + '_' + elmId).removeClass('color-danger');
        }
        if ('0' == voteType) {
            if ($(ths).attr('id') == 'up' + reactType + '_' + elmId) {
                if (1 == $('#up' + reactType + '_' + elmId).data('upvoted')) {
                    totalUpCount = totalUpCount - 1;
                    $('#up' + reactType + '_' + elmId).data('upvoted', '0')
                }
            } else {
                if (1 == $('#down' + reactType + '_' + elmId).data('downvoted')) {
                    totalDownCount = totalDownCount - 1;
                    $('#down' + reactType + '_' + elmId).data('downvoted', '0')
                }
            }
            $('#up' + reactType + '_' + elmId).removeClass('color-success');
            $('#down' + reactType + '_' + elmId).removeClass('color-danger');
        }
        if ('0' > voteType) {
            totalDownCount = totalDownCount + 1;
            if (1 == $('#up' + reactType + '_' + elmId).data('upvoted')) {
                totalUpCount = totalUpCount - 1;
                $('#up' + reactType + '_' + elmId).data('upvoted', '0')
            }
            $('#down' + reactType + '_' + elmId).data('downvoted', '1')
            $('#up' + reactType + '_' + elmId).removeClass('color-success');
            $('#down' + reactType + '_' + elmId).addClass('color-danger');
        }
        totalReactionCount = Math.abs(totalUpCount - totalDownCount);
        $('#tot_counts' + reactType + '_' + elmId).data('count', totalReactionCount);
        $('#up' + reactType + '_' + elmId).data('count', totalUpCount);
        $('#down' + reactType + '_' + elmId).data('count', totalDownCount);

        $('#tot_counts' + reactType + '_' + elmId).removeClass('color-success color-danger');

        if (totalUpCount < totalDownCount) {
            $('#tot_counts' + reactType + '_' + elmId).addClass('color-danger');
        } else if (totalUpCount > totalDownCount) {
            $('#tot_counts' + reactType + '_' + elmId).addClass('color-success');
        }

        $('#tot_counts' + reactType + '_' + elmId).text(totalReactionCount);
        $('#tot_counts' + reactType + '_' + elmId).data('count', totalReactionCount);
        $('#totupcounts' + reactType + '_' + elmId).text(totalUpCount);
        $('#totdowncounts' + reactType + '_' + elmId).text(totalDownCount);

        if (0 < totalUpCount || 0 < totalDownCount || 0 < totalReactionCount) {
            $('#empty_count' + reactType + '_' + elmId).addClass('hide');
            $('#nonempty_count' + reactType + '_' + elmId).addClass('show');
            $('#empty_count' + reactType + '_' + elmId).hide();
            $('#nonempty_count' + reactType + '_' + elmId).show();
        } else {
            $('#empty_count' + reactType + '_' + elmId).addClass('hide');
            $('#nonempty_count' + reactType + '_' + elmId).addClass('show');
            $('#empty_count' + reactType + '_' + elmId).show();
            $('#nonempty_count' + reactType + '_' + elmId).hide();
        }
    };

    reportQuestion = function (recordId) {
        fcom.ajax(fcom.makeUrl('Forum', 'reportForm'), {recordId: recordId}, function (res) {
            $.w3mentorsmodal(res,{'size':'modal-md'});
        });
    };

    reportSetup = function (frm) {
        if (!$(frm).validate()) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('Forum', 'setupReportQuestion'), fcom.frmData(frm), function (res) {
            if (1 == res.status) {
                $('.spam-lnk-js').remove();
                $.w3mentorsmodal.close();
            }
        });
    };

    /* read more functionality [ */
    $(document).delegate('.readMore', 'click', function () {
        var $this = $(this);
        var $moreText = $this.siblings('.moreText');
        var $lessText = $this.siblings('.lessText');

        if ($this.hasClass('expanded')) {
            $lessText.show();
            $moreText.hide();
            $this.text($linkMoreText);
        } else {
            $moreText.slideDown(1000);
            $lessText.hide();
            $this.text($linkLessText);
        }
        $this.toggleClass('expanded');
    });

}(jQuery));