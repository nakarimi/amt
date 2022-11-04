(function ($) {

  Drupal.behaviors.amt_staff = {
    attach: function(context, settings) {
      // Selecting the instructor filter.
      var selector = '[name="field_student_target_id"]';
      autoCompleteFilter(selector, 'student_accounts', 'title', 'student_account');
      selector = '[name="field_enrollment_package_name_target_id"]';
      autoCompleteFilter(selector, 'packages', 'title', 'package');

      // custom function to load items on custom filter inputs.
      function autoCompleteFilter(selector, eckType, field, bundle) {
        $(selector).autocomplete({
          source: function(request, response) {
            jQuery.ajax({
              url: window.location.origin + '/amt_royalty/autocomplete',
              type: 'POST',
              charset: 'UTF-8',
              data: {
                'eckType': eckType,
                'term': request.term,
                'bundle': bundle,
                'field': field,
              },
              success: function(resp) {
                response(resp);
              },
              error: function() {
                alert('Error Occured');
              }
            });
          },
          select: function(event, ui) {
            event.preventDefault();
            $(this).val(ui.item.label + ' (' + ui.item.value + ')' );
          }
        });
      }
    }
  };

}(jQuery));
