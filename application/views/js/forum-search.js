/* global fcom */
var forumSearch = {
    searchArr: [],
    searchFrm: document.srchQuestionForm,
    baseUrl: '',
    firstLoad: 0,
    goToSearchPage: function (pageno) {
        $(searchFrm.pageno).val(pageno);
        forumSearch.search();
    },

    search: function ()
    {
        let srchUrl = forumSearch.makeSearchQueryUrl(true);
        fcom.ajax(srchUrl, {}, function (response) {
            $('#listing').html(response);
            setTimeout(resetIframes, 500);
        });
    },

    removeTag: function (id)
    {
        alert('Called Remove Tag from serach forum js');
        $('#quesTags' + id).remove();
    },

    resetForumSearch: function (ths)
    {
        $('#pageno').val(1);
        $('#tag_id').val(0);
        $('#keyword').val('');
        $(ths).hide();
        forumSearch.removeFilter('tag');
        forumSearch.removeFilter('keyword');
        forumSearch.addFilter('pageno', 1);
        forumSearch.processQueryString();
    },

    addFilter: function (key, value)
    {
        forumSearch.searchArr[key] = value;
    },

    removeFilter: function (key)
    {
        delete forumSearch.searchArr[key];
    },

    searchByKeyWord()
    {
        let tagId = $('#tag_id').val();
        forumSearch.removeFilter('keyword');
        if (0 < tagId) {
            forumSearch.processQueryString();
            return;
        }
        let kwrd = $('#keyword').val();
        if (0 < kwrd.length) {
            forumSearch.addFilter('keyword', kwrd);
        }
        forumSearch.removeFilter('tag');
        forumSearch.addFilter('pageno', 1);
        forumSearch.processQueryString();
    },

    setSearchByType: function (ths)
    {
        $('.srch_type').removeClass('is-active');
        $(ths).parent('li').addClass('is-active');
        var srchType = parseInt($(ths).data('search_type'));
        $('input[name="search_type"]').val(srchType);
        let tagId = $('#tag_id').val();
        if (0 < tagId) {
            $('#tag-reset').show();
        }
        forumSearch.addFilter('search_type', srchType);
        forumSearch.addFilter('pageno', 1);
        forumSearch.processQueryString();
    },

    processQueryString: function ()
    {
        let qStr = window.location.search;
        if (0 == forumSearch.firstLoad) {
            qStr.substring(1).split("&").forEach(function (pair) {
                if (pair === "")
                    return;
                var parts = pair.split("=");
                forumSearch.searchArr[parts[0]] = parts[1] &&
                        decodeURIComponent(parts[1].replace(/\+/g, " "));
            });
        }
        forumSearch.search();
        if (0 < forumSearch.firstLoad) {
            let srchUrl = forumSearch.makeSearchQueryUrl(false);
            window.history.pushState("", "", srchUrl);
        }
        forumSearch.firstLoad = 1;
    },

    makeSearchQueryUrl: function (includeBaseUrl)
    {
        var includeBaseUrl = includeBaseUrl || false;
        let valueSeperator = '=';
        let url = '';
        if (true == includeBaseUrl) {
            url = forumSearch.removeLastSpace(forumSearch.baseUrl) + fcom.makeUrl('Forum', 'search');
        }
        for (var key in forumSearch.searchArr) {
            url = url + forumSearch.getQueryParamSeperator(url) + key + valueSeperator + forumSearch.searchArr[key];
        }
        return encodeURI(url);
    },

    searchPaging: function (pageNo)
    {
        var pageNo = pageNo || 1;
        forumSearch.addFilter('pageno', pageNo);
        forumSearch.processQueryString();
        $('body,html').animate({
            scrollTop: 0
        }, 500);
        return false;
    },

    autoCompleteTags: function ()
    {
        $('input[name=\'keyword\']').autocomplete({
            'classes': {"ui-autocomplete": "custom-ui-autocomplete"},
            'source': function (request, response) {
                fcom.updateWithAjax(fcom.makeUrl('Forum', 'autoCompleteTags'),
                        {keyword: request.term},
                        function (result) {
                            response($.map(result.data, function (item) {
                                return {label: item['ftag_name'], value: item['ftag_name'], id: item['ftag_id']};
                            }));
                        });
            },
            select: function (event, ui) {
                $('#keyword').val(ui.item.label);
                $('#tag_id').val(ui.item.id);
                $('#tag-reset').show();
                $('#pageno').val(1);
                forumSearch.addFilter('tag', ui.item.label + '-' + ui.item.id);
                forumSearch.addFilter('pageno', 1);
                forumSearch.removeFilter('keyword');
                forumSearch.processQueryString();
            }
        });
    },

    getQueryParamSeperator: function (url)
    {
        if (url.indexOf("?") > -1) {
            return '&';
        }
        return '?';
    },

    removeLastSpace: function (str) {
        return str.replace(/\/*$/, "");
    },
};