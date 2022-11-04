(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.amt_validations = {
    loaded: false,
    attach: function (context, settings) {
      if (Drupal.behaviors.amt_validations.loaded) {
        return;
      }
      Drupal.behaviors.amt_validations.loaded = true;
      init();
    },
  };
  function init() {
    $(document).ready(function () {
      // Select all the field from event form.
      const dateSelector = $('[data-drupal-selector*="edit-lesson-date-date"]');
      const timeSelector = $('[data-drupal-selector*="edit-lesson-date-time"]');
      const showMessageSelector = $("#date-and-time-error");
      var instructorSelector = $(
        '[data-drupal-selector*="edit-field-teacher"]'
      );
      // Set time picker for time field of event.
      jQuery('[data-drupal-selector*="edit-lesson-date-time"]').timepicker({
        'timeFormat': 'h:i A',
        'step': 15,
        'scrollDefault': 'now',
        'forceRoundTime': true,
      });
      // After changing the date update the time slot.
      dateSelector.change(function () {
        instructor = instructorSelector.find(":selected").val();
        duration = "00:15";
        date = dateSelector.val();
        if (instructor != "_none" && duration != "" && date != "") {
          sendingAjax(instructor, duration, date);
        }
      });

      // The time input field changed an ajax
      // request and check the instructor available time.
      timeSelector.change(function () {
        instructor = instructorSelector.find(":selected").val();
        duration = "00:15";
        date = dateSelector.val();
        time = timeSelector.val();
        if (
          instructor != "_none" &&
          duration != "" &&
          date != "" &&
          time != ""
        ) {
          // Sending Ajax request for checking instructor available time.
          sendingAjax(instructor, duration, date, time);
        }
      });

      function sendingAjax(instructor, duration, date, time = "time") {
        $('.available_time_warning').remove();
        $.ajax({
          url: window.location.origin + "/amt_validations/timeslots",
          type: "POST",
          charset: "UTF-8",
          data: {
            time: time,
            instructor: instructor,
            duration: duration,
            date: date,
          },
          success: function (resp) {
            // if the response was not null it shows it has the conflict time with some other events.
            if (resp != "null" && time == "time") {
              timeSelector.timepicker("option", {
                disableTimeRanges: JSON.parse(resp),
              });
            }
            // If the response was null set all time picker enable.
            else if (time == "time") {
              timeSelector.timepicker("option", {
                disableTimeRanges: [],
              });
              timeSelector.val("12:00 PM");
            } else {
              // Show message for instructor time available
              // if the event was out of that time.
              $('[id="edit-lesson-date"], [id*="edit-lesson-date--"]').after(resp);
            }
          },
        });
      }
    });
  }
})(jQuery, Drupal);
