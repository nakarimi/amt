/*jshint esversion: 6 */
(function ($) {

  Drupal.behaviors.amt_dayview_script = {
    attach: function(context, settings) {
      // Load  the script only once.
      $('body', context).once('amt_dayview_script').each(function () {
        let searchParams = new URLSearchParams(window.location.search);
        // opening the schedule lesson popup if schedule_lesson was set
        if (searchParams.has('action') && searchParams.get('action') == 'schedule_lesson') {
          $(".use-ajax.hidden#lesson").click();
        }
        if (searchParams.has('action') && searchParams.get('action') == 'schedule_event') {

          // If schedule was clicked then open service form automatically.
          $('#add_service').click();

          // Add class to close button to be able to bind the event.
          $(document).ajaxComplete(function(){
            $('.events-services-form').parent().parent().parent().addClass('service_schedule_form');
            $(".service_schedule_form .events-services-form [name=field_instructor] option[value='_none']").remove();
          });
        }

        // If user click close button then unset sessions with redirect.
        $(document).on("click", '.service_schedule_form .ui-dialog-titlebar-close', function () {
          redirect_to_unset_sessions();
        });

        $(document).on("click", '.delete_event_modal', function () {
          $.ajax({
            cache: false,
            url: window.location.origin + "/amt_dayview/delete/event",
            type: "POST",
            dataType: "json",
            data: {id : jQuery('#delete_button').attr('data-id')},
            error: function () {
              console.log("Oops! Try again.");
            },
            success: function (response) {
              location.reload(true);
            },
            complete: function (response) {
              location.reload(true);
            },
      
          });
        });

        $(document).keyup(function(e) {
             if (e.key === "Escape") { // escape key maps to keycode `27`
                redirect_to_unset_sessions();
            }
        });
        
        // Unset session of dayview, inquiry schedule event popup close.
        function redirect_to_unset_sessions(){
          window.location.href = window.location.origin + '/day-view-unset-session';
        }

      });
    },
  };

  jQuery(document).on('change', 'input[name="lesson_date[date]"]', function(){
    let date = jQuery(this).val();
    jQuery('input[name="field_showed[0][value][date]"]').val(date);
  })

}(jQuery));
