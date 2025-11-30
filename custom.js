
(function($){
  $(document).ready(function() {
    //GRAVITY FORMS - MATERIAL DSIGN - ADD FOCUS TO LABEL
    $('form input, form textarea, form select').focusin(function(){
      $(this).parent().siblings('label').addClass('focused');
    });

    $('form input, form textarea, form select').focusout(function(){
      var input = $(this);
      if( input.val().length === 0 ){
        input.parent().siblings('label').removeClass('focused');
      }
    });
    //on ajax submission
    $('#gform_ajax_frame_1, #gform_ajax_frame_2, #gform_ajax_frame_3').on( 'load', function(){
      $('form input, form textarea, form select').focusin(function(){
        $(this).parent().siblings('label').addClass('focused');
      });
      $('form input, form textarea, form select').focusout(function(){
        var input = $(this);
        if( input.val().length === 0 ){
          input.parent().siblings('label').removeClass('focused');
        }
      });
      $('form input, form textarea, form select').each(function(){
        if( $(this).val().length != 0 ){
          $(this).parent().siblings('label').addClass('focused');
        }
      });
    });
  });
})(jQuery);