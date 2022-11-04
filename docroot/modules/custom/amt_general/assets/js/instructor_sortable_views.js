/*jshint esversion: 6 */
(function ($) {

  Drupal.behaviors.amt_general = {
    attach: function(context, settings) {

      allIDs = getIDs();
      localStorage.setItem("ids", JSON.stringify(allIDs));

      $(checkForChanges);

      function checkForChanges() {
        if ($('form table tbody tr').hasClass('drag-previous')) {
          var type = ($(location).attr('pathname') == '/instructor-reorder') ? 'primari' : 'secondary';

          allIDs = getIDs();
          // Check if order really changed.
          var oldIDs = JSON.parse(localStorage.getItem("ids"));
          if (!(allIDs == oldIDs)) {
            jQuery.ajax({
              url: window.location.origin + '/amt_general/insturctor_sort_update',
              type: 'POST',
              charset: 'UTF-8',
              data: {
                'ids': allIDs,
                'queuetype': type, 
              },
              success: function(resp) {
                if (typeof resp == 'object') {
                  if (!$('div#success-alert').length) {
                    $('#main-content').before('<div class="alert alert-success" id="success-alert"><strong>Order Updated! </strong></div>');
                  }
                  
                }

                setTimeout(function(){
                  $("div#success-alert").slideUp(1000, function() {
                    $(this).remove();
                  });
                }, 1500);
              }
            });

            localStorage.setItem("ids", JSON.stringify(allIDs));
          }          
        }
        else {
          setTimeout(checkForChanges, 1000);
        }
      }

      function getIDs() {
        var ids = [];
        $('form table tbody tr td:nth-child(2).views-field-uid').each( function(){
         //add ids to array
         ids.push($(this).text().trim());       
        });

        return ids;
      }
    },
  };

  // Onclick create cookie for date we want create events.
  // jQuery('#add-new-event-dropdown-toggle').click(function () {
  //   // Added for updating the date field filter.
  //   var timeInfo = $('#calendar').fullCalendar('getView');
  //   // Set a cookie for edit event and come back in day view.
  //   setCookie('ajax-modal-data', timeInfo.start.format(), 0.1);
  // });

}(jQuery));
