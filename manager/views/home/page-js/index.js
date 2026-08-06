/* global fcom, dv, google, google, layoutDirection, ans */
var chartData = {};
var position = (layoutDirection != 'rtl') ? 'start' : 'end';
var chartEventData = null;
var chartTrafficData = null;
var eventChart = null;
var trafficChart = null;
var canView = '';
(function () {
    getTopClassLanguage = function (interval, intervalText) {
        $('.topClassLanguage').html(fcom.getLoader());
        fcom.ajax(fcom.makeUrl('Home', 'topClassLanguages'), { interval: interval }, function (response) {
            $('.topClassLanguage').html(response);
            $('.languageDurationType-js').text(intervalText);
        }, { process: false });
    };
    getTopLessonLanguage = function (interval, intervalText) {
        $('.topLessonLanguage').html(fcom.getLoader());
        fcom.ajax(fcom.makeUrl('Home', 'topLessonLanguages'), { interval: interval }, function (response) {
            $('.topLessonLanguage').html(response);
            $('.languageDurationType-js2').text(intervalText);
        }, { process: false });
    };
    getEventMeasurements = function (interval, intervalText) {
        $('.analytics-event-measurements').html(fcom.getLoader());
        $('.analytic-event-chart').html('').hide();
        chartEventData = null;
        if (eventChart) {
            eventChart.off();
            eventChart.detach();
            eventChart = null;
        }
        $('.eventDurationType-js').text(intervalText);
        fcom.ajax(fcom.makeUrl('Home', 'googleAnalyticsEvents'), { interval: interval }, function (response) {
            $('.analytics-event-measurements').html(response);
            $('.analytic-event-chart').html('').hide();
            if (chartEventData) {
                $('.analytic-event-chart').show();
                var dataTraficSrc = google.visualization.arrayToDataTable(chartEventData);
                var optionsTraficSrc = { title: '', width: $('#analytic-event-chart').width(), height: 360, pieHole: 0.4, pieStartAngle: 100, legend: { position: 'bottom', textStyle: { fontSize: 12, alignment: 'center' } } };
                var trafic = new google.visualization.PieChart(document.getElementById('analytic-event-chart'));
                trafic.draw(dataTraficSrc, optionsTraficSrc);
            }
        }, { process: false });
    };
    getTrafficAcquitions = function (interval, intervalText) {
        $('.analytics-traffic--acquitions').html(fcom.getLoader());
        $('.analytic-traffic-chart').html('').hide();
        chartTrafficData = null;
        if (trafficChart) {
            trafficChart.detach();
            trafficChart = null;
        }
        $('.trafficDurationType-js').text(intervalText);
        fcom.ajax(fcom.makeUrl('Home', 'googleAnalyticsTrafficAcquitions'), { interval: interval }, function (response) {
            $('.analytics-traffic--acquitions').html(response);
            $('.analytic-traffic-chart').html('').hide();
            if (chartTrafficData) {
                $('.analytic-traffic-chart').show();
                var dataTraficSrc = google.visualization.arrayToDataTable(chartTrafficData);
                var optionsTraficSrc = { title: '', width: $('#analytic-traffic-chart').width(), height: 360, pieHole: 0.4, pieStartAngle: 100, legend: { position: 'bottom', textStyle: { fontSize: 12, alignment: 'center' } } };
                var trafic = new google.visualization.PieChart(document.getElementById('analytic-traffic-chart'));
                trafic.draw(dataTraficSrc, optionsTraficSrc);
            }
        }, { process: false });
    };
    getTopCourseCategories = function (interval, intervalText) {
        $('.topCourseCategories').html( fcom.getLoader() );
        fcom.ajax(fcom.makeUrl('Home', 'topCourseCategories'), { interval: interval }, function (response) {
            $('.topCourseCategories').html(response);
            $('.crsCatgDurationType-js2').text(intervalText);
        }, { process: false });
    };
    getStatisticsData = function () {
        $("#lessonEarning--js").html(fcom.getLoader());
        fcom.updateWithAjax(fcom.makeUrl('Home', 'dashboardStatChart'), '', function (response) {
            chartData = response;
            $("#lessonEarning--js").html('');
            callChart('lessonEarning--js', Object.keys(chartData.lessonData), Object.values(chartData.lessonData), position);
        }, { process: false });
    };
    regenerateStat = function () {
        fcom.updateWithAjax(fcom.makeUrl('salesReport', 'regenerate'), '', function (t) {
            setTimeout(() => {
                window.location.reload()
            }, 1000);
        });
    };
})();
$(document).ready(function () {
    $position = (layoutDirection != 'rtl') ? 'start' : 'end';
});
$(document).ready(function () {
    if (canView == '1') {
        getStatisticsData();
        getTopClassLanguage();
        getTopLessonLanguage();
        getEventMeasurements();
        getTrafficAcquitions();
        getTopCourseCategories();
        $('.carousel--oneforth-js').slick(getSlickSliderSettings(4));
        $('.statistics-nav-js li a').click(function () {
            $('.statistics-tab-js .tabs_panel').hide();
            $('.statistics-nav-js li a').removeClass('active');
            var activeTab = $(this).attr('rel');
            $(this).addClass('active');
            $("#" + activeTab).show();
            if ($(this).attr('data-chart')) {
                if (activeTab == 'tabs_1') {
                    callChart('lessonEarning--js', Object.keys(chartData.lessonData), Object.values(chartData.lessonData), position);
                } else if (activeTab == 'tabs_2') {
                    callChart('classEarning--js', Object.keys(chartData.classData), Object.values(chartData.classData), position);
                } else if (activeTab == 'tabs_3') {
                    callChart('courseEarning--js', Object.keys(chartData.courseData), Object.values(chartData.courseData), position);
                } else if (activeTab == 'tabs_4') {
                    callChart('userSignups--js', Object.keys(chartData.userData), Object.values(chartData.userData), position);
                }
            }
        });
    }
});