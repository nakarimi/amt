(function ($) {

  Drupal.behaviors.amt_general = {
    attach: function(context, settings) {
      // This js hides the admin toolbar for instructor and studio manger roles only.
      // Select admin toolbar based on ID and remove it.
      $('#toolbar-administration').remove();
    }
  };
}(jQuery));
