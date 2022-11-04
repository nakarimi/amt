(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.amt_dashboard = {
    loaded: false,
    attach: function (context, settings) {
      if (Drupal.behaviors.amt_dashboard.loaded) {
        return;
      }
      Drupal.behaviors.amt_dashboard.loaded = true;
      init(context, settings);
    },
  };

  function init(context, settings) {
    // Add Loader on ajax calls
    $("body")
      .once("body")
      .prepend(
        "<div id='spinner-amt'><img src='/modules/custom/amt_dayview/assets/js/spinner.gif'></div>"
      );
    $(document).ajaxSend(function () {
      $("#spinner-amt").show();
    });
    $(document).ajaxComplete(function () {
      $("#spinner-amt").hide();
    });
    $(".btn-tobe-checkbox").each(function (index) {
      $(this).replaceWith(
        `<input type="checkbox" class="hide_enrollment" value="${$(this).attr(
          "href"
        )}">`
      );
    });
    jQuery(".views-field-nothing-1").each(function (index) {
      $(this).click(function (e) {
        var hideEnrollment =
          window.location.origin + "/amt_dashboard/hideEnrollment";
        $.ajax({
          url: hideEnrollment,
          type: "POST",
          data: {
            enrollment_id: jQuery(this).children().val(),
          },
          // If the call was not successfull.
          error: function () {
            alert("Enrollment could not be updated, an issue happened.");
          },
          // If the call was successfull.
          success: function (response) {
            console.log("Enrollment hide/show status updated");
            $("#filterEnrollmentBtn").click();
          },
        });
      });
    });

    // Add class for css styling.
    $('table#LessonsListTable tr td:contains("Enrollment")').each(function (
      index
    ) {
      $(this).parent().addClass("enrollment");
    });

    // Apply the myCustomBehaviour effect to the elements only once.
    $("#student_dashboard_container", context)
      .once("student_dashboard_container")
      .each(function () {
        // Some selectors used in below codes.
        var tableId = "#LessonsListTable";
        var service = [2];
        var teacher = [4];
        var dateColumn = [0];
        var ServiceFilter = "#ServiceFilter";
        var TeacherFilter = "#TeacherFilter";
        var datePickerForLessonList = "#datePickerForLessonList";

        // Introduce date format for datatables to
        // correctly sort the dates. This features need
        // moment.js file that is imported to this page
        // via amt_sort_date library package.

        initialize_enrollment_table_datatable();
        function initialize_enrollment_table_datatable() {
          $.fn.dataTable.moment("MM-DD-YYYY");
          // Calling datatable for the lessons list table.
          $("#EnrollmentsTable").DataTable({
            lengthMenu: [
              [10, 25, 50, -1],
              [10, 25, 50, "All"],
            ],
            pageLength: 50,
            info: false,
            stateSave: true,
            order: [[5, "DESC"]],
            searching: false,
            columnDefs: [
              { orderable: false, targets: [8] },
            ],
          });
        }

        // Print Student Future Appointments.
        function printStudentFutureAppointments() {
          $('.amt-print-student-future-appointments').on('click', function() {
            $('.amt-student-future-lessons-list table').print({
              prepend: '<div class="print-heading"> Future Appointments for ' + $('#studentsLessonsDataInCirlcles > h3 > b').html() + '</div>',
              stylesheet: '../js/student_events_print.css',
            });
          });
        }
        
        printStudentFutureAppointments();
        $(document).ajaxComplete(function(event, xhr, settings) {
          let params = Object.fromEntries(new URLSearchParams(settings.data));
          if (params.hasOwnProperty('view_name') && params.view_name == 'student_future_lessons') {
            printStudentFutureAppointments();
          }
        });

        // Calling datatable for the lessons list table.
        var LessonsListDataTable = $(tableId).DataTable({
          order: [[0, "desc"]],
          lengthMenu: [
            [10, 25, 50, -1],
            [10, 25, 50, "All"],
          ],
          searching: true,
          bFilter: false,
          pageLength: 50,
          sDom: "ltipr",
        });

        // Generating the filters input with the valid data for optioning
        // the filter on the table.
        searchTeacherAndService(ServiceFilter, service);
        searchTeacherAndService(TeacherFilter, teacher);

        /*
        This function is called to populate the desirced input field namely
        the Teacher filters select box and the services filter select box
        with the data that are corrently inside the table. When this data are
        store as options in the filters input fields, table will be ready to
        to be searched and filtered using those filters.
        */
        function searchTeacherAndService(selector, column) {
          // Getting the unique data from the corresponding column.
          var newarray = LessonsListDataTable.columns(column)
            .data()
            .eq(0)
            .unique();
          for (var i = 0; i < newarray.length; i++) {
            $(selector).append(
              '<option value"' +
              newarray[i] +
              '">' +
              newarray[i] +
              "</option>"
            );
          }
        }

        // Applying filters on the lessons list table.
        filter_teacher_service_date_in_lessons(ServiceFilter, service);
        filter_teacher_service_date_in_lessons(TeacherFilter, teacher);
        filter_teacher_service_date_in_lessons(
          datePickerForLessonList,
          dateColumn
        );

        /*
      This function is called when the value of one of the inputs fields of
      Services, Teachers or DatePicker is change to filter the table based
      on that newly changed value.
      */
        function filter_teacher_service_date_in_lessons(selector, column) {
          $(selector).on("change", function () {
            var val = $.fn.dataTable.util.escapeRegex($(this).val());
            LessonsListDataTable.column(column)
              .search(val ? "^" + val + "$" : "", true, false)
              .draw(); // note columns(0) here
          });
        }

        // From this part, codes are related to enrollment filter and autocompletion.
        var filterEnrollmentUrl =
          window.location.origin + "/amt_dashboard/filterenrollments";
        // Defining the route for payments load via ajax call.
        $("#filterEnrollmentBtn").on("click", function () {
          $.ajax({
            url: filterEnrollmentUrl,
            type: "POST",
            data: {
              instructor: $("#instrcutorFilter").val(),
              category: $("#categoryFilter").val(),
              totalPaid: $("#totalPaidFilter").val(),
              totalPrice: $("#totalPriceFilter").val(),
              studentId: $("#studentId").val(),
            },
            // If the call was not successfull.
            error: function () {
              alert("Filters could not be loaded");
            },
            // If the call was successfull.
            success: function (response) {
              // Deleting the current data in table body.
              $("#EnrollmentsTable tbody").empty();
              // Repopulating the table body with new data.
              $.each(response, function (i, item) {
                $("#EnrollmentsTable tbody").append(
                  "<tr> <td>" +
                  $.trim(item.instructor) +
                  "</td> <td>" +
                  $.trim(item.category) +
                  "</td> <td>" +
                  $.trim(item.packageName) +
                  "</td> <td>" +
                  $.trim(item.totalPaid) +
                  "</td> <td>" +
                  $.trim(item.totalPrice) +
                  "</td> <td>" +
                  $.trim(item.last_payment) +
                  "</td> <td>" +
                  $.trim(item.saleDate) +
                  "</td> <td>" +
                  $.trim(item.lessonAvailable) +
                  "</td> <td>" +
                  $.trim(item.documentId) +
                  "</td> <td>" +
                  item.display +
                  "</td> <td>" +
                  $.trim(item.settings) +
                  "</td> </tr>"
                );
              });

              // Re-rendering the enrollment datatable.
              $("#EnrollmentsTable").dataTable().fnDestroy();
              initialize_enrollment_table_datatable();
            },
          });
        });
        // Logics and function for loading the autocomplete data..
        // This autocomplete is used in student dasboard page.
        // Selecing the instructor filter.
        var selector = '[name="instructor"]';
        autoCompleteFilter(selector, "user", "name", "instructor");
        // Selecing the category filter.
        selector = '[name="category"]';
        autoCompleteFilter(
          selector,
          "taxonomy_term",
          "name",
          "enrollment_type"
        );
        // custom function to load items on cutom filter inputs.
        function autoCompleteFilter(selector, eckType, field, bundle) {
          $(selector).autocomplete({
            source: function (request, response) {
              jQuery.ajax({
                url: window.location.origin + "/amt_dashboard/autocomplete",
                type: "POST",
                charset: "UTF-8",
                data: {
                  eckType: eckType,
                  term: request.term,
                  bundle: bundle,
                  field: field,
                },
                success: function (resp) {
                  response(resp);
                },
              });
            },
            select: function (event, ui) {
              event.preventDefault();
              $(this).val(ui.item.label + " (" + ui.item.value + ")");
            },
          });
        }

        // Set student id into the modal input.
        $("#paidAheadBalanceBTN").on("click", function () {
          // Get student id from an input field in the twig file.
          let student_id = $("#studentId").val();

          var element = "#paidAheadBalance form input#id";
          if (student_id < 1) {
            // Error if not student id was there.
            $(element).get(0).type = "text";
            $(element).attr("placeholder", "Could not find Student ID").css({
              "background-color": "red",
              display: "block",
            });
          } else {
            // set the id.
            $(element).val(student_id);
          }
        });
        // Hide\Show of enrollment checkbox on studnet dashboard.
        $(document).on("change", ".hide_enrollment", function () {
          var hideEnrollment =
            window.location.origin + "/amt_dashboard/hideEnrollment";

          // let label = ($(this).next().text() == 'Show') ? 'Hide' : 'Show';

          // // Class is used to change this element label, as mark , since this is not accessible inside success.
          // $(this).next().addClass('changed_checkbox')

          $.ajax({
            url: hideEnrollment,
            type: "POST",
            data: {
              enrollment_id: $(this).val(),
            },
            // If the call was not successfull.
            error: function () {
              alert("Enrollment could not be updated, an issue happened.");
            },
            // If the call was successfull.
            success: function (response) {
              $("#filterEnrollmentBtn").click();
            },
          });
        });
      });

    // Update Multiple Status.
    // Create global variables to hold appointments lists.
    let dayview_lessons_list = [];
    let dayview_services_list = [];
    let dayview_group_lessons_list = [];
    let multiple_update_status_route =
      window.location.origin + "/amt_dayview/update-multiple-status";
    let active_event_type = '';
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
          active_event_type = 'services';
          // Add the title to the modal.
          jQuery("#appointment_type_identifier").text("Services");
          // Add a data attribute to the submit button.
          jQuery("#update_multiple_status_submit_btn").data("type", "services");
          // Hide unrelated status select box.
          jQuery("#lessons_status_selectbox, tbody#lessons, tbody#glessons").hide();
          // Show the related status select box.
          jQuery(
            "#services_status_selectbox, #multiple_status_update_status_column, tbody#services"
          ).show();
          break;

        case "dayview_group_lessons":
          appointments = dayview_group_lessons_list;
          active_event_type = 'group_lesson';
          // Add the title to the modal.
          jQuery("#appointment_type_identifier").text("Group Lessons");
          // Add a data attribute to the submit button.
          jQuery("#update_multiple_status_submit_btn").data("type", "group_lesson");
          // Hide unrelated status select box.
          jQuery(
            "#services_status_selectbox, #multiple_status_update_status_column, tbody#lessons, tbody#services"
          ).hide();
          // Show the related status select box.
          jQuery("#lessons_status_selectbox, tbody#glessons").show();
          break;

        default:
          appointments = dayview_lessons_list;
          active_event_type = 'lesson';
          // Add the title to the modal.
          jQuery("#appointment_type_identifier").text("Lessons");
          // Add a data attribute to the submit button.
          jQuery("#update_multiple_status_submit_btn").data("type", "lesson");
          // Hide unrelated status select box.
          jQuery("#services_status_selectbox, tbody#services, tbody#glessons").hide();
          // Show the related status select box.
          jQuery(
            "#lessons_status_selectbox, #multiple_status_update_status_column, tbody#lessons"
          ).show();
          break;
      }

      // Uncheck all checkbox
      jQuery("#multiple_status_update_select_all_checkbox:checkbox").prop(
        "checked", false);
      jQuery(".update_multiple_status_checkbox_" + active_event_type + ":checkbox").prop(
        "checked", false);
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
        if (jQuery(this).data("type") == "lesson") {
          status_id = jQuery("#lessons_status_selectbox option:selected").val();
          type = jQuery(this).data("type");
        }
        // If modal contains group lessons.
        else {
          status_id = jQuery("#glessons_status_selectbox option:selected").val();
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
        jQuery(".update_multiple_status_checkbox_" + active_event_type + ":checkbox").prop(
          "checked",
          jQuery(this).prop("checked")
        );
      }
    );


    /**
     * This function is used to wrap the selected events
     * and put them in an array to make them ready to be
     * sent to the backend for a status update in bulk mode.
     */
    function collect_data_from_multiple_update_status_modal() {
      let selectedEvents = [];

      // Run through the list of the events in the modal.
      jQuery(".update_multiple_status_checkbox_" + active_event_type).each(function () {
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
      else if (response == "Failed to update for unknown reason") {
        alert("Failed to update for uknwon reason, Please reload!");
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
      }
      // Refresh the page.
      location.reload();
    }
  }
})(jQuery, Drupal);
