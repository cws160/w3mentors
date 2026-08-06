/* global fcom, langLbl, google */
var chart;
var graphMediaW = $('.graph-media').width();
function upcomingLesson(data) {
    fcom.ajax(fcom.makeUrl('Lessons', 'upcoming'), data, function (t) {
        $('#listItemsLessons').html(t);
    }, {process: false});
}
getStatisticalData = function (duration) {
    fcom.updateWithAjax(fcom.makeUrl('TeacherReports', 'getStatisticalData'), {duration: duration}, function (res) {
        $('.earing-amount-js').html(res.earning);
        $('.session-sold-count-js').html(res.sessionCount);
        google.load("visualization", "1", {packages: ["corechart", 'table']});
        if (chart != undefined || chart != null) {
            chart.clearChart();
        }
        var options = {
            height: 380,
            width: graphMediaW,
            colors: ["#f4d18c", "#3bc0c0"],
            legend: 'none',
            hAxis: {title: res.graphData.column.durationType, },
            animation: {duration: 1000, easing: 'out', }
        };
        google.setOnLoadCallback(function () {
            column = res.graphData.column;
            rowData = res.graphData.rowData;
            data = new google.visualization.DataTable();
            data.addColumn('string', column.durationType);
            data.addColumn('number', column.earningLabel);
            data.addColumn({"type": 'string', "role": 'tooltip'});
            data.addColumn('number', column.sessionSoldLabel);
            data.addColumn({"type": 'string', "role": 'tooltip'});
            data.addRows(rowData);
            drawChart(data, options);
        });
    }, {process: false});
};
function drawChart(graphArray, options) {
    containerDiv = document.getElementById("chart_div");
    chart = new google.visualization.AreaChart(containerDiv);
    chart.draw(graphArray, options);
}
