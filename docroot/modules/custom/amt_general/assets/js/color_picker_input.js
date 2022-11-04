(function ($) {

  Drupal.behaviors.amt_general = {
    attach: function(context, settings) {
      // This js hides the admin toolbar for instructor and studio manger roles only.
      // Select admin toolbar based on ID and remove it.
      $('#edit-field-color-0-color').attr('autocomplete', 'off');
      $('#edit-field-color-0-color').minicolors({
        control: 'hue',
        defaultValue: '',
        format: 'hex',
        keywords: '',
        inline: $(this).attr('data-inline') === 'true',
        letterCase: 'lowercase',
        opacity: $(this).attr('data-opacity'),
        position: 'bottom',
        swatches: $(this).attr('data-swatches') ? $(this).attr('data-swatches').split('|') : [],
        change: function (value, opacity) {
          if (!value) return;
          if (opacity) value += ', ' + opacity;
          if (typeof console === 'object') {
            console.log(value);
          }
        },
        theme: 'bootstrap'
      });
    }
  };
}(jQuery));
