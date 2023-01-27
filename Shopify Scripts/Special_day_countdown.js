
  
$( document ).ready(function() {
  
  /*-------------------------*/  
  /*----COUNTDOWN TOP BAR ---*/    
  /*-------------------------*/    
  $(".top-bar-left").append('<h4 class="countdown_special_day" style="display:none;" > LABOR DAY SALE ENDS IN <span class="countdown"></span> </h4>');
  var change_count = 0;

    // Set the date we're counting down to
    //var countDownDate = new Date("Aug 30, 2018 14:59:59").getTime();
    var countDownDate = new Date("Sep 3, 2018 23:59:59").getTime();

    // Update the count down every 1 second
    var x = setInterval(function() {

      // Get todays date and time
      var now = new Date().getTime();

      // Find the distance between now and the count down date
      var distance = countDownDate - now;

      // Time calculations for days, hours, minutes and seconds
      var days = Math.floor(distance / (1000 * 60 * 60 * 24));
      var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      var seconds = Math.floor((distance % (1000 * 60)) / 1000);

      // Display the result in the element with id="demo"
      var time = '';
      if(days > 0){ time += days + "d "; }
      if(days >  0 || (days == 0 && hours > 0)){ time += hours + "h "; }
      time += minutes + "m "; 
      time += seconds + "s ";
      
      if(change_count == 7){
          $(".top-bar-left h4").each(function( index ) {
               if($(this).is(":hidden")){$(this).delay(200).fadeIn(300);}
               else{$(this).fadeOut(300);}
      });
          change_count = 0;
      }

      

      $(".countdown").html(time);
      //document.querySelector(".countdown_special_day .countdown").innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";

      // If the count down is finished, write some text 
      if (distance < 0) {
        clearInterval(x);
        document.querySelector(".countdown_special_day_top_bar .countdown").innerHTML = "Labor Day Weekend EXPIRED !!!";
        document.querySelector(".countdown_special_day_product_page .countdown").innerHTML = "Labor Day Weekend EXPIRED !!!";        
      }
      
      change_count += 1;

    }, 1000);
  
  
  
  
});