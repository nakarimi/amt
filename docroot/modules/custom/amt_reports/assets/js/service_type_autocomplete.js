(function ($) {

  Drupal.behaviors.amt_dashboard = {
    attach: function(context, settings) {

      // Selecing the instructor filter.
      var selector = '[name="service_type"]';
      autoCompleteFilter(selector, 'taxonomy_term', 'name', 'service_type');
      //  autoCompleteFilter(selector, 'contacts','field_email', 'student_record');
      selector = '[name="student_name"]';
      autoCompleteFilter(selector, 'student_accounts','title', 'student_account');
      selector = '[name="enrollment_title"]';
      autoCompleteFilter(selector, 'packages','title', 'enrollment');
      // custom function to load items on custom filter inputs.
      function autoCompleteFilter(selector, eckType, field, bundle) {
        $(selector).autocomplete({
          source: function(request, response) {
            jQuery.ajax({
              url: window.location.origin + '/amt_reports/autocomplete',
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
            // When one student is selected, write only the name of the student.
            // Not the studen last name.
            // This applies only for the Name input field, because we only want
            // to have the first name of the student because the list that comes
            // from the data and needed to be filtered has only first name of the student.
            if (selector == '[name="name"]' || selector == '[name="email"]' || selector == '[name="instructor"]') {
              $(this).val(ui.item.label);
            }
            else {
              $(this).val(ui.item.label + ' (' + ui.item.value + ')' );
            }
          }
        });
      }
      $('#multiselect').multiselect({
        buttonWidth : '100%',
        maxHeight: 250,
        includeSelectAllOption : true,
        nonSelectedText: 'Select Instructor'
      });

      function getSelectedValues() {
        var selectedVal = $("#multiselect").val();
        for(var i=0; i < selectedVal.length; i++){
          function innerFunc(i) {
            setTimeout(function() {
              location.href = selectedVal[i];
            }, i*2000);
      }
          }
          innerFunc(i);
        }
    }


  };

}(jQuery));
