// Create global variables to hold appointments lists.
let dayview_lessons_list = [];
let dayview_services_list = [];
let dayview_group_lessons_list = [];
let multiple_update_status_route =
  window.location.origin + "/amt_dayview/update-multiple-status";

/**
 * This function is called whenever new list of
 * appointments are retrieved from the backend.
 * The function is resetting the global variables
 * that holds the appointments list and update them
 * based on the newsly lists that arrive.
 * @param {*} response
 */
function update_appointments_list_global_variables(response) {
  // Empty the global appointments lists.
  dayview_lessons_list = [];
  dayview_services_list = [];
  dayview_group_lessons_list = [];

  // Run through each record.
  jQuery.each(response, function (index, val) {
    if (val.hasOwnProperty('appointmentType')) {
      // Extract and saved appropriate time format for display.
      val.renderedTime = extract_time_format_for_multiple_status_update_modal(
        val
      );

      // Add the record to the related global variable.
      switch (val.appointmentType) {
        case "services":
          dayview_services_list.push(val);
          break;
        case "group_lesson":
          dayview_group_lessons_list.push(val);
          break;
        default:
          dayview_lessons_list.push(val);
          break;
      }
    }
  });
}

/**
 * When one of the three buttons of Update Lesson,
 * Update Group Lesson, or Update Services is clicked
 * in dayview page. This piece of code is going to
 * take care of displaying the right list of the events
 * in the modal so that the user can go ahead and change
 * their statuses in bulk mode and save them.
 */
jQuery(document).on("click", ".update_multiple_status_item", function () {
  // Defining a simple variable.
  let appointments;
  let bundle = jQuery(this).data("bundle");

  // Determine which button is clicked.
  switch (bundle) {
    case "dayview_services":
      appointments = dayview_services_list;
      // Add the title to the modal.
      jQuery("#appointment_type_identifier").text("Services");
      // Add a data attribute to the submit button.
      jQuery("#update_multiple_status_submit_btn").data("type", "services");
      // Hide unrelated status select box.
      jQuery("#lessons_status_selectbox").hide();
      // Show the related status select box.
      jQuery(
        "#services_status_selectbox, #multiple_status_update_status_column"
      ).show();
      break;

    case "dayview_group_lessons":
      appointments = dayview_group_lessons_list;
      // Add the title to the modal.
      jQuery("#appointment_type_identifier").text("Group Lessons");
      // Add a data attribute to the submit button.
      jQuery("#update_multiple_status_submit_btn").data("type", "group_lesson");
      // Hide unrelated status select box.
      jQuery(
        "#services_status_selectbox, #multiple_status_update_status_column"
      ).hide();
      // Show the related status select box.
      jQuery("#lessons_status_selectbox").show();
      break;

    default:
      appointments = dayview_lessons_list;
      // Add the title to the modal.
      jQuery("#appointment_type_identifier").text("Lessons");
      // Add a data attribute to the submit button.
      jQuery("#update_multiple_status_submit_btn").data("type", "lesson");
      // Hide unrelated status select box.
      jQuery("#services_status_selectbox").hide();
      // Show the related status select box.
      jQuery(
        "#lessons_status_selectbox, #multiple_status_update_status_column"
      ).show();
      break;
  }

  // Populate the modal with related list of events.
  put_related_appointments_on_multiple_status_update_modal(
    appointments,
    bundle
  );

  // Show the modal.
  jQuery("#update_multiple_status").modal();
});

/**
 * This piece of code is triggered when the user
 * tries to submit the list of the events for
 * their status change in bulk mode. The code
 * will attempt to get all the selected events
 * and the ID of the status based on the selected
 * status in the select box, put them in apppropriate
 * format and send an ajax request to update the events'
 * statuses.
 */
jQuery(document).on("click", "#update_multiple_status_submit_btn", function () {
  // Get the list of selected event.
  data = collect_data_from_multiple_update_status_modal();

  if (data.length > 0) {
    // Variables for holding status ID and appointment type.
    let status_id;
    let type;

    // If modal contains are services.
    if (jQuery(this).data("type") == "services") {
      status_id = jQuery("#services_status_selectbox option:selected").val();
      type = jQuery(this).data("type");
    }
    // If modal contains all lessons or group lessons.
    else {
      status_id = jQuery("#lessons_status_selectbox option:selected").val();
      type = jQuery(this).data("type");
    }

    // Send the ajax request.
    jQuery.ajax({
      url: multiple_update_status_route,
      type: "POST",
      data: {
        data: data,
        status_id: status_id,
        type: type,
        dateTime: moment().format("YYYY-MM-DDTHH:mm:ss"),
      },
      // If the call was not successfull.
      error: function () {
        alert("status not updated!");
      },
      // If the call was successfull.
      success: function (response) {
        display_update_status_attempt_fails(response, true);
      },
    });
  } else {
    alert("Please Select at least one appointment!");
  }
});

/**
 * This piece of code is used to select or deselect all
 * the events in the update multiple status modal. It's very
 * handy for selecting all the records and changing their
 * statuses.
 */
jQuery(document).on(
  "change",
  "#multiple_status_update_select_all_checkbox",
  function () {
    jQuery(".update_multiple_status_checkbox:checkbox").prop(
      "checked",
      jQuery(this).prop("checked")
    );
  }
);

/**
 * This function is called to extract and produce
 * a simple time format for the starting and ending
 * time of each event, suitable for display in the
 * multiple update status modal. (The time format is
 * a simple 24 hours format with hours:minutes).
 * @param {*} val
 */
function extract_time_format_for_multiple_status_update_modal(val) {
  let times = new Array();
  times.push(new Date(Date.parse(val.start)).getHours());
  times.push(new Date(Date.parse(val.start)).getMinutes());
  times.push(new Date(Date.parse(val.end)).getHours());
  times.push(new Date(Date.parse(val.end)).getMinutes());

  jQuery.each(times, function (new_index, value) {
    if (value == 0) {
      times[new_index] = "00";
    }
  });

  return times;
}

/**
 * This function is used to populate the
 * multiple status update modal with the appropriate
 * and related list of the appointments.
 * @param {*} appointments
 */
function put_related_appointments_on_multiple_status_update_modal(appointments, bundle) {
  // Empty the modal in case it is not.
  jQuery("#update_multiple_status_table_body").empty();

  // If appointments are loaded in dayview.
  if (appointments.length > 0 && appointments[0].hasOwnProperty('appointmentType')) {
    // Run through each event.
    jQuery.each(appointments, function (index, val) {
      if (val.appointmentType != "schedules") {
        // Define a column to hold status data.
        let statusColumn = "";

        // If data is related to services or lessons.
        // Group lesson's status are excluded from display.
        if (bundle == "dayview_services" || bundle == "dayview_lessons") {
          if (val.status != null) {
            statusColumn =
              '<td><span class="badge badge-secondary">' +
              val.status +
              "</span></td>";
          } else {
            statusColumn = "<td> </td>";
          }
        }

        jQuery("#update_multiple_status_table_body").append(`
          <tr>
            <td>
              <div class="checkbox">
                <label>
                  <input type="checkbox" data-entity-id="${val.entityId}" class="update_multiple_status_checkbox">
                </label>
              </div>
            </td>
            <td class="update_multiple_status_event_title">${val.dataToShow}</td>
            <td>${val.instructorName}</td>
            <td>
              ${val.renderedTime[0]}:${val.renderedTime[1]}
              </br>
              ${val.renderedTime[2]}:${val.renderedTime[3]}
            </td>
            ${statusColumn}
          </tr>`
        );
      }
    });
  }

  // If no event is loaded in dayview page.
  else {
    jQuery("#update_multiple_status_table_body").append(`
      No Appointments loaded in Dayview page! 
    `);
  }
}

/**
 * This function is used to wrap the selected events
 * and put them in an array to make them ready to be
 * sent to the backend for a status update in bulk mode.
 */
function collect_data_from_multiple_update_status_modal() {
  let selectedEvents = [];

  // Run through the list of the events in the modal.
  jQuery(".update_multiple_status_checkbox").each(function () {
    // Select the ones that are checked.
    if (jQuery(this).is(":checked")) {
      // Push it to a new array.
      selectedEvents.push(jQuery(this).data("entity-id"));
    }
  });

  return selectedEvents;
}

jQuery("#appointmentDetails").on("click", "#cancelFutureAppts", function () {
  var eventID = jQuery(this).attr("eventID");
  var parentID = jQuery(this).attr("parentID");

  var r = confirm(
    "Do you want to remove all future instances of this appointment?"
  );
  if (r == true) {
    jQuery.ajax({
      url: window.location.origin + "/amt_dayview/cancelFutureStandingAppts",
      type: "POST",
      dataType: "json",
      data: {
        eventID: eventID,
        parentID: parentID,
      },
      error: function () {
        alert(
          "Oops! Could not remove future appointments. Please contact the support team."
        );
      },
      success: function (response) {
        window.location.reload();
      },
    });
  }
});


function display_update_status_attempt_fails(response, multiple = false) {
  // Closing the modal.
  jQuery("#update_multiple_status").modal("hide");

  if (response == "success") {
    // If update is attempted using the multiple events status upate feature.
    if (multiple) {
      alert("All statuses updated.");
      jQuery("#spinner-amt").hide();
    }
    // If a single event's status is attempted to update.
    else {
      alert("Status updated for the selected event");
      jQuery("#spinner-amt").hide();
    }
  }
  else if(response == "Failed to update for unknown reason") {
    alert("Failed to update for unknown reason. Check the logs after reload.");
    window.location.reload();
  } else {
    // Empty the result display modal.
    jQuery("#update_multiple_status_result_body").empty();

    // Loop throught the failed list of statuses.
    for (const key in response) {
      if (Object.hasOwnProperty.call(response, key)) {
        // Add one by one to the result modal.
        jQuery("#update_multiple_status_result_body").append(`
            <tr>
                <td>${key}</td>
                <td>${response[key]}</td>
            </tr>
        `);
      }
    }

    // Dispaly the result modal.
    jQuery("#update_multiple_status_result_modal").modal();

    // Add remove tooltips function if appears but not appear.
    var tooltipInterval = setInterval(tooltipMouseLeave, 500);
    function tooltipMouseLeave() {
      jQuery(".path-day-view .popover").removeClass('in');

      if (jQuery('.path-day-view .popover').length > 0) {
        jQuery(".path-day-view .popover").mouseleave(function () {
          jQuery(".path-day-view .popover").removeClass('in');
        });
        clearInterval(tooltipInterval);
      }
    }
  }
  // Refresh in the dayview calendar.
  jQuery("#filter_form_submited").click();

}

// This helper do a click on related entity (target) to load it's form in popup using ajax.
function load_entity_pupup_on_button_click() {
  var interval = null;
  jQuery(".add-lesson-student").click(function () {
    formModalOpen();
    jQuery("#autofill-lesson").click();
  });
  jQuery(".add-service-student").click(function () {
    formModalOpen();
    jQuery("#autofill-service").click();
  });
  jQuery("#add-btn-enrollment").click(function () {
    formModalOpen();
    jQuery("#autofill-enrollment").click();
  });
  jQuery(".add-payment-student").click(function () {
    formModalOpen();
    jQuery("#autofill-payment").click();
  });

  // Do paralel actions on appointment details when form modal opening.
  function formModalActions() {
    if (jQuery("#drupal-modal").length > 0) {
      clearInterval(interval);
      jQuery('.spin').hide();
      jQuery('.add-lesson-student, .add-service-student, .add-payment-student, #add-btn-enrollment').removeClass('disabled').attr('disabled', false);
    }
  }

  // Doing paralel action till form opening.
  function formModalOpen(){
    interval = setInterval(formModalActions, 200);
  }

}
