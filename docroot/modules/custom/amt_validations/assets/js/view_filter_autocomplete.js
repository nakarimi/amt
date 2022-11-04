(function ($) {

  Drupal.behaviors.amt_validations = {
    attach: function(context, settings) {
      // Apply the myCustomBehaviour effect to the elements only once.
      $('.views-exposed-form', context).once('views-exposed-form').each(function () {

        // Each attribute should be sit on main template of filter twig.
        // Instructor filter auto-complete in enrollment list and lessons list view page.
        var selector = '[data-drupal-selector="edit-instructor"]';
        autoCompleteFilter(selector, 'user', 'filterBasedOnNameAndLastName', 'instructor', true);

        // Inquiry Taker field filter auto-complete for inquiry view page.
        selector = '[data-drupal-selector="edit-inquiry-taker"]';
        autoCompleteFilter(selector, 'user', 'filterBasedOnNameAndLastName', '');

        // Teacher field filter auto-complete for inquiry view page.
        selector = '[data-drupal-selector="edit-teacher"]';
        autoCompleteFilter(selector, 'user', 'filterBasedOnNameAndLastName', 'instructor');

        /**
         * Custom function to load items on cutom filter inputs.
         * @param  {[string]} selector
         *   The selector name that autocomplete will apply on.
         * @param  {[string]} eckType
         *   This is the entity type that data should fetch from.
         * @param  {[string]} field
         *   The field that data should fetch.
         * @param  {[string]} bundle
         *   Entity bundle type to make search faster and more effective.
         *
         */
        function autoCompleteFilter(selector, eckType, field, bundle, excludeTeachers = false) {
          $(selector).autocomplete({
            source: function(request, response) {
              jQuery.ajax({
                url: window.location.origin + '/amt_dayview/autocomplete',
                type: 'POST',
                charset: 'UTF-8',
                data: {
                  'eckType': eckType,
                  'term': request.term,
                  'bundle': bundle,
                  'field': field,
                  'excludeTeachers': excludeTeachers,
                },
                success: function(resp) {
                  response(resp);
                }
              });
            },
            select: function(event, ui) {
              event.preventDefault();
              $(this).val(ui.item.label + ' (' + ui.item.value + ')');
            }
          });
        }
      });
    }
  };
}(jQuery));
