
  // Labor Day -  Store:  Review Cart Update Reservations - Design/Dev
  function startTimer(duration, display) {
      var timer = duration, minutes, seconds;
      var interval = setInterval(function () {
          minutes = parseInt(timer / 60, 10)
          seconds = parseInt(timer % 60, 10);

          minutes = minutes < 10 ? "0" + minutes : minutes;
          seconds = seconds < 10 ? "0" + seconds : seconds;

          display.textContent = minutes + ":" + seconds;

          if (--timer < 0) {
              //timer = duration;
              display = document.querySelector('.reservation_countdown_cart');
              display.textContent = "Your reservation is about to expire !!!";
              clearInterval(interval);
          }
      }, 1000);
  }

  window.onload = function () {
      var fiveMinutes = 10 * 5,
      display = document.querySelector('.reservation_countdown_cart .time');
      startTimer(fiveMinutes, display);
  };

    
