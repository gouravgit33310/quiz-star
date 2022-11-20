(function ($, Drupal) {
  Drupal.behaviors.quizers = {
      attach: function (context, settings) {
          const urlParams = new URLSearchParams(window.location.search);
  const param_x = urlParams.get('timer');
      
          var timer2 = param_x;
          
          var interval = setInterval(function() {
          
          
            var timer = timer2.split(':');
            //by parsing integer, I avoid all extra string processing
            var minutes = parseInt(timer[0], 10);
            var seconds = parseInt(timer[1], 10);
            --seconds;
            minutes = (seconds < 0) ? --minutes : minutes;
            if (minutes < 0) clearInterval(interval);
            
            seconds = (seconds < 0) ? 59 : seconds;
            seconds = (seconds < 10) ? '0' + seconds : seconds;
            //minutes = (minutes < 10) ?  minutes : minutes;
            $('.countdown').html(minutes + ':' + seconds);
            timer2 = minutes + ':' + seconds;
            if (timer2 == '-1:59') {

                $('.fieldgroup ').hide();
                $('.countdown').html('Time up please submit your quiz');
            }
          }, 1000);
          $('.add-ans-fix').closest('.form-textarea-wrapper').addClass('des-textarea');
          $('.des-textarea').closest('.form-type-textarea').closest('.text-format-wrapper').addClass('right-ans-text');
          
      }
    };
  })(jQuery, Drupal);