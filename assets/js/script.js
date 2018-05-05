;function timeCounter( timewrapper, element ) {

    // Set the date we're counting down to

    //var countDownDate = new Date("Jan 5, 2018 15:37:25").getTime();
    //var countDownDate = new Date(timewrapper.innerText).getTime();

// Update the count down every 1 second
    // Get todays date and time
    var now = new Date().getTime();

    // Find the distance between now an the count down date

    var distance;
    if( timewrapper ) {
        distance = timewrapper.innerText;

    } /*else {
        distance = countDownDate - now;
    }*/

    var x = setInterval(function() {

        // Time calculations for days, hours, minutes and seconds
        var days = Math.floor(distance / (24*60*60));

        var remaining_seconds = distance - ( days*( 24*60*60 ) );

        var hours = Math.floor( remaining_seconds / ( 60*60 ) );

        var remaining_seconds = remaining_seconds - hours*60*60;

        var minutes = Math.floor( remaining_seconds / 60 );

        var remaining_seconds = remaining_seconds - minutes*60;

        var seconds = remaining_seconds;
        --distance;

        // Output the result in an element with id="demo"
        /*console.log(days);
        console.log(hours);
        console.log(minutes);
        console.log(seconds);*/
        element.innerHTML = days + "d " + hours + "h "
            + minutes + "m " + seconds + "s ";

        // If the count down is over, write some text
        if (distance < 0) {
            clearInterval(x);
            document.getElementById("demo").innerHTML = "EXPIRED";
            element.innerText = "EXPIRED";
        }
    }, 1000);
};
(function ($) {
    $(document).ready(function () {
        timeCounter( document.getElementById('wauc_time_counter') , document.getElementById('wauc_time_counter') );
    });

}(jQuery));