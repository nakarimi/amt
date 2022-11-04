(function ($) {

Drupal.behaviors.amt_general = {
attach: function(context, settings) {
$('.events-form, .student-accounts-form', context).once('events-form, student-accounts-form').each(function () {
  // Select all the field from event form.
  const durationSelector = $('[data-drupal-selector="edit-field-duration"]');
  const dateSelector = $('[data-drupal-selector="edit-field-date-and-time-0-value-date"]');
  const timeSelector = $('[data-drupal-selector="edit-field-date-and-time-0-value-time"], [data-drupal-selector="edit-lesson-date-time"]');
  const showMessageSelector = $('#date-and-time-error');
  var instructorSelector = $('[data-drupal-selector="edit-field-instructor"]');
  // Set time picker for time field of event.
  timeSelector.timepicker({
    'timeFormat': 'h:i A',
    'step': 15,
    'scrollDefault': 'now',
    'forceRoundTime': true,
  });
  // After rebuild of the instructor field add the new on change event on that.
  $(document).ajaxComplete(function (e, resp) {
    instructorSelector = $('[data-drupal-selector="edit-field-instructor"]');
    if (resp.responseJSON != undefined && resp.responseJSON.length > 0) {
      if (resp.responseJSON[0].command == 'update_build_id') {
        instructorSelector.change( function() {
          instructor = $(this).val();
          duration = durationSelector.val();
          date = dateSelector.val();
          // Check the field for data validations.
          if (instructor != "_none" && duration != "" && date != "") {
            // sending an Ajax request for getting the time slot.
            sendingAjax(instructor, duration, date);
          }
        });
      }
    }
  });
  // on edit page of the event, set configuration for time slot.
  instructor = instructorSelector.val();
  duration = durationSelector.val();
  date = dateSelector.val();
  // If all the three field has value send the Ajax.
  if (instructor != "_none" && duration != "" && date != "") {
    sendingAjax(instructor, duration, date);
  }
  // After change of duration update the time slot.
  durationSelector.change( function() {
    instructor = instructorSelector.val();
    duration = durationSelector.val();
    date = dateSelector.val();
    if (instructor != "_none" && duration != "" && date != "") {
      sendingAjax(instructor, duration, date);
    }
  });
  // After changing the date update the time slot.
  dateSelector.change( function() {
    instructor = instructorSelector.val();
    duration = durationSelector.val();
    date = dateSelector.val();
    if (instructor != "_none" && duration != "" && date != "") {
      sendingAjax(instructor, duration, date);
    }
  });

  // The time input field changed an ajax
  // request and check the instructor available time.
  timeSelector.change( function() {
    instructor = instructorSelector.val();
    duration = durationSelector.val();
    date = dateSelector.val();
    time = timeSelector.val();
    if (instructor != "_none" && duration != "" && date != "" && time != "") {
      // Sending Ajax request for checking instructor available time.
      sendingAjax(instructor, duration, date, time);
    }
  });

  function sendingAjax(instructor, duration, date, time='time') {
    $('.available_time_warning').remove();
    // If instructor was not provided.
    if (typeof instructor == "undefined") {
      return;
    }

    $.ajax({
      url: window.location.origin + '/amt_validations/timeslots',
      type: 'POST',
      charset: 'UTF-8',
      data: {
        'time' : time,
        'instructor' : instructor,
        'duration' : duration,
        'date' : date,
      },
      success: function(resp) {
        // if the response was not null it shows it has the conflict time with some other events.
        if (resp != 'null' && time == 'time') {
          timeSelector.timepicker('option',{
            'disableTimeRanges': JSON.parse(resp),
          });
        }
        // If the response was null set all time picker enable.
        else if(time == 'time'){
          timeSelector.timepicker('option',{
            'disableTimeRanges': [],
          });
          timeSelector.val('12:00 PM');
        }
        else{
          // Show message for instructor time available
          // if the event was out of that time.
          showMessageSelector.html(resp);
        }
      }
    });
  }
});
}
};
}(jQuery));
