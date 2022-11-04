(function ($, Drupal) {
  var initialized;
  function init() {
    if (!initialized) {
      initialized = true;
      var EnrollmentListURL = window.location.origin + "/enrollments/";
      var enrollmentElement = 'input[data-drupal-selector*="field-enrollment-name"]';
      var studentAccountElement = 'input[data-drupal-selector*="field-student-name"]';
      var wrapper_first = 'div[id*="field-enrollment-name"]';

      $(document).ready(function () {

        if ($(wrapper_first).length) {
          var newElementID = $(wrapper_first).attr('id') + '-newEnrollmentListID';
          if ($('#' + newElementID).length == 0) {
            CreateElement(newElementID, EnrollmentListURL, enrollmentElement, studentAccountElement);
          }

          $(document).on('click', 'ul#ui-id-1 li', function () {
            LoadEnrollmentContent(EnrollmentListURL, $(studentAccountElement).val(), newElementID, false);
          });

          $(document).on('change', '#' + newElementID, function () {
            var enrollmentValue = $(this).val();
            var text = document.getElementById(newElementID).options[document.getElementById(newElementID).selectedIndex].innerHTML;
            $(enrollmentElement).val(text + ' (' + enrollmentValue + ')');
          });
        }
      });

      $(document).ajaxComplete(function () {
        var wrapper = 'div[id*="field-enrollment-name"]';
        if ($(wrapper).length) {

          var newElementID = $(wrapper).attr('id') + '-newEnrollmentListID';
          // check if it's already been created because this code runs eveytime an ajax trigger completes
          if ($('#' + newElementID).length == 0) {
            CreateElement(newElementID, EnrollmentListURL, enrollmentElement, studentAccountElement);
          }

          $(document).on('change', studentAccountElement, function () {
            // This cause too many duplicate request.
            // LoadEnrollmentContent(EnrollmentListURL, $(this).val(), newElementID, false);
          });
          $(document).on('change', '#' + newElementID, function () {
            var enrollmentValue = $(this).val();
            var text = document.getElementById(newElementID).options[document.getElementById(newElementID).selectedIndex].innerHTML;
            $(enrollmentElement).val(text + ' (' + enrollmentValue + ')');
          });
        }
      });

      // Update enrollment list with changing gross tuition.
      jQuery('input[id*="field-gross-tuition"]').attr('title', 'Use negative amount to refund, i.e. -100');
      $('input[id*="field-gross-tuition"]').change(function (e) { 
        e.preventDefault();
        
        var valueofAccount = $(studentAccountElement).val();
        if (valueofAccount) {
          var wrapper = 'div[id*="field-enrollment-name"]';
          var amount = $(this).val();
          var isRefund = amount < 0;  // Check if this payment is actually a refund. If not refund then do nothing.
          if ($(wrapper).length && isRefund) {  
            var enrollmentSelected = $(enrollmentElement).val();
            $(enrollmentElement).val('');
            var newElementID = $(wrapper).attr('id') + '-newEnrollmentListID';
            LoadEnrollmentContent(EnrollmentListURL, valueofAccount, newElementID, enrollmentElement, enrollmentSelected);
          }
        }
      });
    }
  }
  Drupal.behaviors.amt_validations = {
    attach: function () {
      init();
    }
  };


  function CreateElement(newElementID, EnrollmentListURL, enrollmentElement, studentAccountElement) {


    var newSelectElement = $('<select>').appendTo('div[id^="edit-field-enrollment-name-wrapper"] > div');
    newSelectElement.append($("<option>").attr('value', '').text('Please Select Student Account'));
    newSelectElement.addClass('form-select').addClass('form-control');
    newSelectElement.attr("id", newElementID);
    newSelectElement.prop("disabled", true);
    $('[data-drupal-selector="edit-field-enrollment-name-0-target-id"]').addClass('hidden');
    $('div[id^="edit-field-enrollment-name-wrapper"] > div > div > span').addClass('hidden');

    // In edit page if the inputs have value add values.
    var valueofAccount = $(studentAccountElement).val();
    if (valueofAccount) {
      LoadEnrollmentContent(EnrollmentListURL, valueofAccount, newElementID, enrollmentElement);
    }

  }

  function LoadEnrollmentContent(EnrollmentListURL, valueofAccount, createID, enrollmentfield, enrollmentSelected='') {

    valueofAccount = valueofAccount.split(" ").pop().replace(/\D/g, '');
    var newSelectElement = $('#' + createID);
    // Add a temperory Loading Option to select.
    newSelectElement.html($("<option>").text('loading...'));
    // Disable the Element while it's loading the JSON.
    newSelectElement.prop("disabled", true);

    // Start request for enrollment list data.
    // The request patern is like this: /enrollments/1212/1.
    // Avoid empty requests.
    if (!valueofAccount) {
      newSelectElement.html(
        $("<option>").text("No Enrollment Attached to This Account...")
      );
    } else if (valueofAccount.length > 0) {
      let indexOfPayment = window.location.pathname.indexOf("payment");
      let refund = '?refund=' + (jQuery('input[id*="field-gross-tuition"]').val() < 0);
      $.getJSON(EnrollmentListURL + valueofAccount + '/' + window.location.pathname.indexOf("events") + '/' + indexOfPayment + refund, function (data) {

        if (data === undefined || data.length == 0) {
          newSelectElement.html($("<option>").text('No Enrollment Attached to This Account...'));
        } else {
          // Add Empty Option.
          newSelectElement.html($("<option selected='selected'>").text('Select Enrollment'));
          // Load Item From json file to select element.
          $.each(data, function (key, val) {
            // Add any other enrollments that fetched.
            newSelectElement.append($("<option>").attr('value', key).text(val).prop('selected', val == enrollmentSelected));
          });

          // Enable the Select Element after it's filled up.
          newSelectElement.prop("disabled", false);
          // Set Value to New Element if Enrollment has value.
          if (enrollmentfield) {
            var valueofEnrollment = $(enrollmentfield).val().split(" ").pop().replace(/\D/g, '');
            newSelectElement.val(valueofEnrollment);
          }
        }
      });
    }
  }

}(jQuery, Drupal));