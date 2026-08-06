onmessage = function(e){
    var countDownDate = new Date(e.data['end']).getTime();
    var now = new Date(e.data['start']).getTime();
    var x = setInterval(function() {
        // Get today's date and time

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);        
        var html = ("0" + days).slice(-2)+ ":";
        html += ("0" + hours).slice(-2)+ ":";
        html += ("0" + minutes).slice(-2)+ ":";
        html += ("0" + seconds).slice(-2);
        
        // If the count down is finished, write some text
        if (distance < 0) {
            postMessage('');
            clearInterval(x);
        }else{
            // postMessage(html);
        }
        now+=1000;
    }, 1000);
};
