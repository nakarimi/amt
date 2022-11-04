/*jshint esversion: 6 */
(function ($) {

  Drupal.behaviors.amt_validations = {
    attach: function(context, settings) {
      // Following js codes are for payment enrollment form.
      // it is using for calculating next payment date when enrollment is created.
      function nextPaymentDate(days) {
        // Get Sale Date field value.
        var saleDateField = document.getElementById('edit-field-sale-date-0-value-date').value;
        var saleDate = new Date(saleDateField);
        // Increment Date based on the given day.
        saleDate.setDate(saleDate.getDate() + days);
        // Get the days.
        var dd = saleDate.getDate();
        // Get the month.
        var mm = saleDate.getMonth() + 1;
        // Get the year.
        var y = saleDate.getFullYear();
        var nextPaymentDate;
        // concatenate zero if days or months are one digit.
        if (mm < 10 && dd < 10) {
          nextPaymentDate = y + '-' + '0' + mm + '-' + '0' + dd;
        } else if (mm < 10 && dd > 9) {
          nextPaymentDate = y + '-' + '0' + mm + '-' + dd;
        } else if (mm > 9 && dd < 10) {
          nextPaymentDate = y + '-' + mm + '-' + '0' + dd;
        } else {
          nextPaymentDate = y + '-' + mm + '-' + dd;
        }
        // Attach value to next-scheduled_payment field.
        document.getElementById('edit-field-next-scheduled-payment-0-value-date').value = nextPaymentDate;
      }

      // it is for interval unit field.
      $('#edit-field-interval-unit').change(function() {
        // Get the value of Payment Interval.
        var paymentInterval = document.getElementById('edit-field-payment-interval-0-value').value;
        // Get the value of Interval Unit.
        var intervalUnit = document.getElementById('edit-field-interval-unit').value;

        // Check which interval unit has been assigned to payment interval.
        if (intervalUnit == "Days") {
          // if unit was days then next payment will increment based on the payment interval days.
          nextPaymentDate(paymentInterval * 1);
        }
        else if (intervalUnit == "Months") {
          // if unit was months then payment interval will multiply to 30 to change it to days. .
          nextPaymentDate(paymentInterval * 30);
        }
        else if (intervalUnit == "Weeks") {
          // if unit was weeks then payment interval will multiply to 7 to change it to days.
          nextPaymentDate(paymentInterval * 7);
        }
        else {
          // if not unit was select then no value will be assigned to next payment date. 
          document.getElementById('edit-field-next-scheduled-payment-0-value-date').value = '';
        }
      });

      // Disabling comma separated values in student and pachage names.
      let lessonTypeInput = '.field--name-field-lesson-types input';
      $('#user-form ' + lessonTypeInput + ', #packages-enrollment-form .field--name-field-enrollment-package-name input, #packages-enrollment-form .field--name-field-student input, #user-instructor-form ' + lessonTypeInput + ', #user-executive-form ' + lessonTypeInput).on('keyup keydown', function(e) {
        let inputVal = $(this).val();
        $(this).val(inputVal.replace(/,/g, ''));
      });
    }
  };
}(jQuery));
