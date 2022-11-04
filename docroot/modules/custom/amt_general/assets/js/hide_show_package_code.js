(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.mybehavior = {
    attach: function (context, settings) {

      // For currently selected SPS code in edit package form.
      var currentSpsCode = jQuery('select#edit-field-sps-code option').filter(':selected').html();
      filterPackageCodes(currentSpsCode);

      // When changing the SPS Code.
      jQuery("#edit-field-sps-code").change(function(event , element) {
        var selection = jQuery('select#edit-field-sps-code option').filter(':selected').html();
        filterPackageCodes(selection);
      });      
    }
  };

  /**
   * Hide other package codes and keep only the codes that are related to the selected SPS code.
   * @param {string} selectedSpsCode The selected SPS code to filter Package codes based on.
   */
  function filterPackageCodes(selectedSpsCode) {
    // Hide all options and then show the specific options depending on the selected sps code.
    jQuery('#edit-field-package-code option').hide();

    // SUNDRY 
    if (selectedSpsCode == "SUNDRY"){
      jQuery("#edit-field-package-code option:contains('SUN_')").show()
    }
    // MISC 
    else if (selectedSpsCode == "MISC" ) {
      jQuery("#edit-field-package-code option:contains('MISC_')").show()
      jQuery("#edit-field-package-code option:contains('Showcase (Individual Studio)')").show();
      jQuery("#edit-field-package-code option:contains('Cruise/Trip')").show();
    }
    // Non Uni 
    else if (selectedSpsCode == "Non Unit" ) {
      jQuery("#edit-field-package-code option:contains('Non Unit')").show()
      jQuery("#edit-field-package-code option:contains('Private Lesson')").show()
      jQuery("#edit-field-package-code option:contains('Master Class/Dance Camp')").show()
    }
  }
})(jQuery, Drupal, drupalSettings);