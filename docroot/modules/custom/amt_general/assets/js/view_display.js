(function ($) {

    Drupal.behaviors.amt_general = {
      attach: function(context, settings) {
        if(jQuery('.field--name-field-package-label').length) {
            jQuery('.field--name-name').html(jQuery('.field--name-field-package-label .field--item').html());
            jQuery('.field--name-field-package-label').hide();
        }
      }
    };
}(jQuery));
  