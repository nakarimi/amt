
// This helper add the buttons on day-ivew popup.
function add_event_pupup_buttons(event) {
  jQuery("#modal-footer > .popup-close")
    .addClass("btn-danger")
    .wrap("<div class='col-sm-4 popup-close-wrap'></div>");

  if (event.appointmentType != "schedules") {
    jQuery("#modal-footer .popup-close-wrap")
      .before(`<div class="col-sm-4 fresh"><button type="button" 
    class="btn btn-success c-btn add-lesson-student btn-loading" data-loading-text="Loading..."  event-id="${event.entityId}">Add Lesson</button></div>`);

    jQuery("#modal-footer .popup-close-wrap")
      .before(`<div class="col-sm-4 fresh"><button type="button" 
    class="btn btn-success c-btn add-service-student btn-loading" data-loading-text="Loading..."  event-id="${event.entityId}">Add Service</button></div>`);

    jQuery("#modal-footer .popup-close-wrap").before(
      `<div class="col-sm-4 fresh"><button type="button" id="setshowstatus" disabled 
    class="btn btn-warning c-btn">Post Lesson</button></div>`
    );
  }

  var el = jQuery("#studentDashboardsDropdownButton")
    .addClass("col-sm-4")
    .clone();
  jQuery("#studentDashboardsDropdownButton").remove();

  jQuery("#modal-footer .popup-close-wrap").before(el);

  if (event.appointmentType != "schedules") {
    jQuery("#modal-footer .popup-close-wrap").before(
      `<div class="col-sm-4 fresh"><button type="button" id="add-btn-enrollment" 
    class="btn btn-info c-btn enrollment add-btn-enrollment" entity-id="${event.entityId}">Enrollment</button></div>`
    );

    jQuery("#modal-footer .popup-close-wrap").before(
      `<div class="col-sm-4 fresh"><button type="button" class="btn btn-primary c-btn add-payment-student btn-loading" data-loading-text="Loading..."  event-id="${event.entityId}">Payment</button></div>`
    );
  }

  jQuery("#modal-footer .popup-close-wrap").before(
    `<div class="col-sm-4 fresh"><a type="button" id="edit_button" 
  class="btn btn-gray c-btn" href="/events/` +
    event.entityId +
    `/edit?source=` + (window.location.href.indexOf("week-view") > -1 ? 'week-view' : 'day-view') + `">Edit Lesson</a></div>`
  );

  if (event.appointmentType != "schedules") {
    jQuery("#modal-footer .popup-close-wrap").before(
      `<div class="col-sm-4 fresh"><button type="button" id="crs_button" 
      class="btn btn-gray-dark c-btn" data-toggle="modal" data-target="#crs_modal" data-entity-id="${event.entityId}">Cancel/RS</button></div>`
    );
  }
  
  // Disable delete if event was a parent event.
  let attribute = (event.isParent) ? 'title="Parent Events can\'t be deleted!" disabled' : '';

  jQuery("#modal-footer .popup-close-wrap").before(
    `<div class="col-sm-4 fresh">
      <button id="delete_button" type="button" class="btn btn-danger" ` + attribute + ` data-delete="false" data-id="` + event.entityId + `" >Delete</button>
    </div>
  `);
}

// This helper add button action for event popup.
function add_event_delete_button_confirm() {
  jQuery(document).on("click", "#delete_button", function () {
    event_id = jQuery("#delete_button").attr("data-id");
    data_delete = jQuery("#delete_button").attr("data-delete");
    jQuery("#statusListTab").removeClass("active");
    jQuery("#editStatus").removeClass("active");
    jQuery("#appointmentDetailsTab").removeClass("active");
    jQuery("#appointmentDetails").removeClass("active");
    jQuery("#deleteStatus").addClass("active");

    if (data_delete == "true") {
      jQuery.ajax({
        cache: false,
        url: popupCallback,
        type: "GET",
        dataType: "json",
        data: { type: "delete_event", id: event_id },
        error: function () {
          console.log("Oops! Try again.");
        },
        success: function (response) {
          jQuery(".popup-close").click();
          updateCalendarData();
        },
      });
    }
    jQuery("#delete_button").attr("data-delete", "true");
  });
}
// Add status to popup.
function add_status_to_pupup(response, event) {
  jQuery("#studentStatusList").empty();
  // Display the modal and set the values to the event values.
  jQuery("#dayview-popup").html(response[0]);
  jQuery(".calendar.modal")
    .css("display", "block")
    .animate({ opacity: 1 }, 400);
  // creating links for student dashboard pages.
  if (response[1].length > 0) {
    // Hide the dropdown if there were no student for that event.
    jQuery("#studentDashboardsDropdownButton").css(
      "display",
      "inline-block"
    );
    // Hide the dropdown if there were no student for that event.
    jQuery("#studentStatusButton").css("display", "inline-block");
    jQuery.each(response[1], function (key, value) {
      // Push the attendees ids to that global array.
      attendeesIds.push(value.attendees_id);
      if (response[1].length > 1) {
        jQuery(".single-stu").hide();
        jQuery(".couple-stu").show();
        jQuery("#dahboardsDropMenu").append(
          '<li><a href="/student-dashboard?id=' +
          value.id +
          '" target="_blank">' +
          value.name +
          "</a></li>"
        );
      } else {
        jQuery(".single-stu").show();
        jQuery(".couple-stu").hide();
        jQuery(".single-stu.link").attr(
          "href",
          "/student-dashboard?id=" + value.id
        );
      }

      var appointmentTime = moment(event.start._i).format("HH:mm:ss");

      if (
        value.status == "Pending Status" &&
        event.appointmentType == "lesson" && 
        !is_appointment_in_future(appointmentTime)
      ) {
        jQuery("#setshowstatus").attr("disabled", false);
      }
      else if(
        value.status == "Pending Status" &&
        event.appointmentType == "lesson" && 
        is_appointment_in_future(appointmentTime)
        ) {
        jQuery("#setshowstatus").attr("title", "Future lesson can not be posted!");
      }
    });

    jQuery.each(response[2], function (key, value) {
      var disAttrib = (value.status == "Pending Status") ? '' : 'disabled';

      // the disAttrib is only for event with type of lesson, not any other type.
      if (event.appointmentType != "lesson") {
        disAttrib = '';
      }

      // Append the status for each student as a dropdown.
      if (event.appointmentType == "services") {
        jQuery("#studentStatusList")
          .append(
            "<li>" +
            '<div class="row"><label class="col-sm-5 col-form-label">' +
            value.name +
            ":</label>" +
            '<div class="col-md-5"><select name="attendeesStatusvalues" class="form-control attendessStatusValues" id="attendeesStatus"  style="display: inline-block; float: right">' +
            '<option  value="110"  ' +
            (value.status == "Cancelled"
              ? " selected "
              : disAttrib) +
            " >Cancelled</option>" +
            '<option  value="112"  ' +
            (value.status == "Incomplete"
              ? " selected "
              : disAttrib) +
            " >Incomplete</option>" +
            '<option  value="107"  ' +
            (value.status == "No Sale"
              ? " selected "
              : disAttrib) +
            " >No Sale</option>" +
            '<option  value="109"  ' +
            (value.status == "No Showed"
              ? " selected "
              : disAttrib) +
            " >No Showed</option>" +
            '<option  value="113"  ' +
            (value.status == "Pending Status"
              ? " selected "
              : '') +
            " >Pending Status</option>" +
            '<option  value="111"  ' +
            (value.status == "Rescheduled"
              ? " selected "
              : disAttrib) +
            " >Rescheduled</option>" +
            '<option  value="106"  ' +
            (value.status == "Sale" ? " selected " : disAttrib) +
            " >Sale</option>" +
            '<option  value="108"  ' +
            (value.status == "Think it Over"
              ? " selected "
              : disAttrib) +
            " >Think it Over</option>" +
            "</select>" +
            '</div></div></li><br><input type="hidden" value="' +
            value.entity_id +
            '" id="entityIdofAttendees">'
          );
      } else {

        var postStatus = ''; // Disable future lesson posting.
        var appointmentTime = moment(event.start._i).format("HH:mm:ss");
        if(disAttrib != "disabled" && is_appointment_in_future(appointmentTime)) {
          postStatus = 'disabled';
        }

        jQuery("#studentStatusList")
          .append(
            "<li>" +
            '<div class="row"><label class="col-sm-5 col-form-label">' +
            value.name +
            ":</label>" +
            '<div class="col-md-5"><select name="attendeesStatusvalues" class="form-control attendessStatusValues" id="attendeesStatus"  style="display: inline-block; float: right">' +
            '<option  value="63"  ' +
            (value.status == "Cancelled"
              ? " selected "
              : disAttrib) +
            " >Cancelled</option>" +
            '<option  value="61"  ' +
            (value.status == "No Showed Not Charged"
              ? " selected "
              : disAttrib) +
            " >No Showed Not Charged</option>" +
            '<option  value="60"  ' +
            (value.status == "No Showed, Charged"
              ? " selected "
              : (disAttrib + postStatus)) +
            " >No Showed, Charged</option>" +
            '<option  value="64"  ' +
            (value.status == "Pending Status"
              ? " selected "
              : '') +
            " >Pending Status</option>" +
            '<option  value="62"  ' +
            (value.status == "Rescheduled"
              ? " selected "
              : disAttrib) +
            " >Rescheduled</option>" +
            '<option  value="59"  ' +
            (value.status == "Showed" ? " selected " : (disAttrib + postStatus)) +
            " >Showed</option>" +
            '</select></div></div></li><br><input type="hidden" value="' +
            value.attendees_id +
            '" id="entityIdofAttendees"><input type="hidden" value="' +
            value.entity_id +
            '" id="entityIdofEvents">'
          );
      }
    });
    if (event.appointmentType == "services") {
      jQuery("#studentStatusList").append('<button type="button" id="saveServiceStatusButton" class="btn btn-primary" style="">Save</button>');
    } else {
      jQuery("#studentStatusList").append('<button type="button" id="saveStatusButton" class="btn btn-primary" style="">Save</button>');
    }
  }
  // Hide the dropdown if there were no student for that event.
  else {
    jQuery("#studentDashboardsDropdownButton").css(
      "display",
      "none"
    );
    jQuery("#studentStatusButton").css("display", "none");
  }
}

// Update an event with ajax request.
function event_update(data) {

  let type = 'lesson';
  if (data['action'] && data['action'] == "serviceStatusUpdate") {
    type = 'service';
  }

  jQuery.ajax({
    cache: false,
    url: window.location.origin + "/amt_general/event_update",
    type: "POST",
    data: data,
    error: function () {
      event_update_callback(-1, type);
    },
    success: function (response) {
      event_update_callback(response, type);
    },
  });
}

// Callback executes after event update.
function event_update_callback(response, type) {

  if (type != "service") {
    let errorsDisplay = jQuery('#rs_errors');

    // Clear old error messages.
    errorsDisplay.text('');

    if (response == -1) {
      errorsDisplay.text('Failed to set new schedule date, please reload the page and try again.');
      return;
    }
    if (response == 'event_not_supported') {
      errorsDisplay.text('This event can not be rescheduled!');
    }
    else if (response == 'incomplete_values') {
      errorsDisplay.text('Some of the values are not set correctly, please reload the page and try again.');
    }
    else if (response == '1' || response == '2') {
      // If the reschedule date was set successfully, update the event status to Rescheduled.
      // #attendeesStatus is a select input that is located
      // in Edit Status tab of the lesson modal.
      // Here we just select the Rescheduled option of that and click
      // #saveStatusButton to set the lesson status to Rescheduled.
      jQuery('#attendeesStatus option:contains("Rescheduled")').attr('selected', true);
      setTimeout(() => {
        jQuery('#saveStatusButton').click();
      }, 200);
      jQuery('#crs_modal .close').click();
    }
    else {
      errorsDisplay.text('Unexpected error occured! please reload the page and try again.');
    }

    // Clear the error message after short time.
    setTimeout(function () {
      errorsDisplay.text('');
      jQuery('#rs_date').val('');
      jQuery('#rs_time').val('');
    }, 4000);
  }
  else if (type == "service") {
    alert("Status updated for the selected event");
    jQuery('#filter_form_submited').click();
  }
}

// This function send ajax request to update statuses from popup.
function updateStatus(selectedStatus, eventId) {
  let message =
    "Sorry it seems we faced an issue, and could not update status for this event.\n possible reason might be there is lesson avialable.";

  jQuery.ajax({
    cache: false,
    url: window.location.origin + "/amt_dayview/updateStatus",
    type: "POST",
    data: {
      selectedStatus: selectedStatus,
      eventId: eventId,
      dateTime: moment().format("YYYY-MM-DDTHH:mm:ss"),
    },
    // If the call was not successfull.
    error: function () {
      alert(message);
    },
    // If the call was successfull.
    success: function (response) {
      display_update_status_attempt_fails(response);

      // empty the global array.
      attendeesIds.length = 0;
    },
  });
}

/**
 * Callback for the Cancel/RS popup's save button.
 * rs = ReSchedule
 */
jQuery('#crs_save_btn').on('click', function (e) {
  e.preventDefault();
  let errorsDisplay = jQuery('#rs_errors');
  let rsDate = jQuery('#rs_date').val();
  let rsTime = jQuery('#rs_time').val();
  let eventId = jQuery('#crs_button').attr('data-entity-id');

  // Clear old error messages.
  errorsDisplay.text('');

  // If the reschedule date and time was set, it means that the user wants to reschedule the event,
  // so first we set the new schedule date for the lesson via an ajax request then if the request
  // was successful, we update the status to Rescheduled as well.
  if (rsDate && rsTime) {
    data = {
      rsDate: rsDate,
      rsTime: rsTime,
      eventId: eventId,
    };
    event_update(data);
  }
  else if (rsDate || rsTime) {
    errorsDisplay.text('Please select/unselect both Date and Time!');
  }
  else {
    // #attendeesStatus is a select input that is located
    // in Edit Status tab of the lesson modal.
    // Here we just select the cancelled option of that and click
    // #saveStatusButton to set the lesson status to cancelled.
    jQuery('#attendeesStatus option:contains("Cancelled")').attr('selected', true);
    setTimeout(() => {
      jQuery('#saveStatusButton').click();
    }, 200);

    jQuery('#crs_modal .close').click();
  }
});

// Onclick of saving status ids and attendees ids..
jQuery("#studentStatusList").on("click", "#saveServiceStatusButton", function () {

  let data = {
    action: 'serviceStatusUpdate',
    eventStatus: jQuery("#attendeesStatus").val(),
    eventId: jQuery("#entityIdofAttendees").val(),
  };

  // Call Update event.
  event_update(data);

  // Hide modal.
  removeCookie('ajax-modal-data-event');
  jQuery(".modal.calendar,.modal.calendar-cell-click").css({
    display: "none",
    opacity: "0",
  });
});

function postButtonClick() {
  jQuery("#setshowstatus").click(function (e) {
    e.preventDefault();
    jQuery(
      '#attendeesStatus option:contains("Showed"):not(:contains(" Showed")):not(:contains("Showed "))'
    ).attr("selected", true);
    setTimeout(() => {
      jQuery("#saveStatusButton").click();
    }, 200);
  });
}

// Onclick of saving status ids and attendees ids..
jQuery("#studentStatusList").on("click", "#saveStatusButton", function () {

  // get attendees ids from global array of attendeesIds.
  var attendees = jQuery.unique(attendeesIds);
  var selectedStatus = [];

  // Get event id. For events with type lesson.
  var eventId = jQuery("#entityIdofEvents").val();

  jQuery(".attendessStatusValues :selected").each(function () {
    // Push select status id selectedStatus array.
    selectedStatus.push(jQuery(this).val());
  });
  // join the two arrays based on the key.
  var result = selectedStatus.reduce(function (result, field, index) {
    result[attendees[index]] = field;
    return result;
  }, {});

  // Call Update status.
  updateStatus(result, eventId);

  // Hide modal.
  removeCookie('ajax-modal-data-event');
  jQuery(".modal.calendar,.modal.calendar-cell-click").css({
    display: "none",
    opacity: "0",
  });
});

// Remove cookie by its name.
function removeCookie(name) {
  document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

// Check if lesson is in fture.
function is_appointment_in_future(appointmentTime) {

  // Get current time with moment.
  const now = moment().format("HH:mm:ss");

  return (moment(now, "HH:mm:ss").isBefore(moment(appointmentTime, "HH:mm:ss")));
}
