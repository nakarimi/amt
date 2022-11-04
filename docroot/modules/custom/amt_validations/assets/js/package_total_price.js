(function ($) {

  Drupal.behaviors.amt_validations = {
    attach: function(context, settings) {
      /*
      * Get the lesson count and lesson price and multiply them.
      */
      function getLessonPriceAndCount() {
        // Get the value of Lesson count.
        var lessonCount = $('#edit-field-lesson-count-0-value').val();
        // Get the value of Lesson price.
        var lessonPrice = $('#edit-field-lesson-price-0-value').val();
        return lessonCount * lessonPrice;
      }

      // On change of lesson count set total price value.
      $('#edit-field-lesson-count-0-value').change(function() {
        $('#edit-field-total-price-0-value').val(getLessonPriceAndCount());
      });
      // On change of lesson price set total price value.
      $('#edit-field-lesson-price-0-value').change(function() {
        $('#edit-field-total-price-0-value').val(getLessonPriceAndCount());
      });
    }
  };
}(jQuery));
