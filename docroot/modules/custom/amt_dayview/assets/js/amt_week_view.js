(function ($) {
  /*
    Adding Event Operation ID and Type to localStorage.
    in case of Move and Copy.
  */
  function addEventOperation(event, Operation) {
    var instructor = $('[name="instructor"]').val();
    if (instructor != "") {
      instructor = instructor.split(/\(([^)]+)\)/.exec(instructor)[0])[0];
    }
    var value = JSON.stringify({
      id: event.miscProps.entityId,
      instructor: event.resourceIds[0],
      operation: Operation,
      instructorName: instructor,
      title: event.title,
    });
    return localStorage.setItem("opEvent", value);
  }

  /*
    remove Event Operation ID and Type to localStorage.
    in case of Cancel and (after paste)
  */
  function removeEventOperation() {
    $("#copy-or-paste").css("display", "none");
    return localStorage.removeItem("opEvent");
  }

  /*
    Retriveing Operation object.
    in case of paste (to fetch the ID and Type)
  */
  getEventOperation();
  function getEventOperation() {
    var EventOperation = localStorage.getItem("opEvent");
    var parsedEvent = JSON.parse(EventOperation);
    if (!parsedEvent) {
      return null;
    }
    $("#copy-or-paste #content").html(
      `
    <p class="title" style=" text-align: center;"><strong>` +
        parsedEvent.title +
        `</strong></p>
    <div>ID: ` +
        parsedEvent.id +
        `</div>
    <div>Instructor: ` +
        parsedEvent.instructorName +
        `</div>
      <div id="operation">Operation: ` +
        parsedEvent.operation +
        `</div>
      `
    );
    $("#copy-or-paste").css("display", "block");
    return parsedEvent;
  }

  // Function to cancel the copy/paste operation.
  $("#cancel-operation").click(function () {
    removeEventOperation();
  });

  // Creating Cookies with JS.
  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
  }

  Drupal.behaviors.amt_dayview = {
    attach: function (context, settings) {
      // Save array globaly.
      window.attendeesIds = [];
      // Apply the myCustomBehaviour effect to the elements only once.
      $("#calendar", context)
        .once("calendar")
        .each(function () {
          if (Drupal.behaviors.amt_dayview.init) {
            Drupal.behaviors.amt_dayview.init();
            Drupal.behaviors.amt_dayview.init = undefined;
          }

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
          // Reload the page if user has been logged out.
          $(document).on("ajaxError", function (event, xhr) {
            // Error 403 is forbidden error.
            if (xhr.status === 403) {
              // Reload the page.
              window.location.reload();
            } else {
              // alert('Oops! Try again.');
            }
          });

          // Modal in dayview has an issue with .modal() function.
          // Then I do it just by changing the css property.
          $('[aria-hidden="true"],[data-dismiss="modal"], .close').click(function (event) {
            /* Act on the event */
            $(".calendar.modal").css({
              display: "none",
              opacity: "0",
            });
            removeCookie('ajax-modal-data-event');
          });

          // Get the dayview load to use in ajax calling.
          var eventsCallback = window.location.origin + "/amt_dayview/load";
          var popupCallback = window.location.origin + "/amt_dayview/popup";
          var copyPaste = window.location.origin + "/amt_copypaste";

          // Get the url data with name.
          $.urlParam = function (name) {
            var results = new RegExp("[?&]" + name + "=([^&#]*)").exec(
              window.location.href
            );
            if (results != null) {
              return results[1];
            }
            return 0;
          };

          // DayView functionality.
          // Ajax request for getting the day view configuration.
          $.ajax({
            url: window.location.origin + "/amt_dayview/main-configuration",
            type: "GET",
            dataType: "json",
            data: "",
            error: function () {
              // alert('Oops! Try again.');
            },
            success: function (response) {
              response = JSON.parse(response);
              setConfiguration(response);

              // After edit of event and come back to day view and set the date.
              if ($.urlParam("date")) {
                $("#calendar").fullCalendar("gotoDate", $.urlParam("date"));
              }
            },
          });
          /**
           * Callendar configuration for dayview.
           * @param {[array]} calendarConfig
           *   Configuration fetched from server that user set on dayview configuration page.
           */
          function setConfiguration(calendarConfig) {
            // Main configuration on callendar options.
            $("#calendar").fullCalendar({
              // Set the start day of week.
              firstDay: 0,

              // Set the default view that calendar will loaded on.
              defaultView: "agendaWeek",

              // Set the height auto to prevent from empty spaces bellow the calendar.
              height: "auto",

              // Active the ajax reload the resources(instractors).
              refetchResourcesOnNavigate: calendarConfig.resource_ajax,

              // To make the time slot devided in 15mis.
              slotDuration: calendarConfig.duration,
              displayEventTime: calendarConfig.event_time,

              // This define each time slot can get how many part
              // of the rows, for example if we set it to "00:01:00"
              // then it will devide each row by 15 mins but just show
              // the one between one like: 00:15:00 , 00:45:00 , 01:15:00.
              slotLabelInterval: calendarConfig.slot_interval,
              // Set the Slot time format to 12 hours format.
              slotLabelFormat: "hh:mm A",
              groupByResource: calendarConfig.resource,

              // To trun of the all day row at the top of calendar.
              allDaySlot: calendarConfig.day_slot,

              groupByDateAndResource: calendarConfig.date_resource,

              // Settings for manage the callendar header options.
              header: {
                left: calendarConfig.left,
                center: calendarConfig.center,
                right: calendarConfig.right,
              },

              viewRender: function (view, element) {
                // Updating the date field of filter.

                // Get selected date.
                let selectedDate = $("#calendar_date").val();
                var date = (year = week = ""); //view.start;

                // Check if any date is selected already.
                if (selectedDate.length == 0) {
                  // If date was not selected, get everything based on current date.
                  date = moment();
                  year = date.format("Y");
                  week = date.format("W");
                } else {
                  // If date was selected, then split it only.
                  year = selectedDate.split("-W")[0];
                  week = selectedDate.split("-W")[1];
                }

                // Get date range from week and year.
                let newDate = moment()
                  .day("Monday")
                  .year(year)
                  .week(week)
                  .add(1, "weeks")
                  .toDate();

                // update calender.
                if (newDate) {
                  $("#calendar").fullCalendar("gotoDate", newDate);
                }

                // Set the input.
                $("#calendar_date").val(year + "-W" + week);

                // Set the table title (date and week), week +1 is because week number is according to AMI week number which is different than normal week number of the year.
                $(".fc-center h2").text(
                  view.title + " - Week # " + (parseInt(week) + 1)
                );
              },
              eventRender: function (event, element) {
                // Render the Main content of the events with more details
                // and with html tags to be more user frendlly.
                element.find(".fc-title").html(event.dataToShow);

                element.popover({
                  // title: event.title,
                  content: event.tooltip,
                  trigger: calendarConfig.tooltip_trigger,
                  placement: calendarConfig.tooltip_placement,
                  container: calendarConfig.tooltip_container,
                  html: true,
                });
              },
              eventClick: function (event, element) {
                // Set a cookie for edit event and come back in day view.
                setCookie("ajax-modal-data-event", event.entityId, 0.1);

                // attendeesIds.length = 0;
                // Remove old edit event botton an add new one for the clicked event.
                $("#edit_button").remove();
                $("#delete_button").remove();
                // Emptying the dropdown list for the student dashboard links.
                $("#dahboardsDropMenu").empty();

                // Make the student status list empty.
                $("#studentStatusList").empty();
                $("#studentStatusList").append(
                  `<div class="alert alert-warning" role="alert">Student not found!</div>`
                );
                // adding the class of active to tab.
                $("#appointmentDetailsTab").addClass("active");
                $("#appointmentDetails").addClass("active");
                $("#appointmentDetailsLink").attr("aria-expanded", "true");
                $("#statusListTab").removeClass("active");
                $("#editStatus").removeClass("active");
                $("#deleteStatus").removeClass("active");
                $("a#editStatusLink").attr("aria-expanded", "false");
                $(".modal-footer div.fresh").remove();

                // New Buttons
                add_event_pupup_buttons(event);
                                
                load_entity_pupup_on_button_click();
                
                formModalOpen();
                
                // On click event for post button.
                postButtonClick();
                

                // Delete Button
                add_event_delete_button_confirm();

                // $('#modal-footer').append('<a type="button" id="edit_button" class="btn btn-success" href="/events/'+ event.entityId +'/edit?destination=/admin/content/events?source=weekView">Edit</a>');
                $.ajax({
                  cache: false,
                  url: popupCallback,
                  type: "GET",
                  dataType: "json",
                  data: { id: event.entityId },
                  error: function () {
                    $("#spinner-amt").hide();
                    //alert('Oops! Try again.');
                  },
                  success: function (response) {
                    $("#spinner-amt").hide();

                    // Add Status to popup. 
                    add_status_to_pupup(response, event);
                  },
                });
              },

              // Day view right click event handling.
              dayRightclick: function (date, jsEvent, view) {
                date._i[1]++;
                window.contextMenu.init({ preventDoubleContext: false });
                var instructor = $('[name="instructor"]').val();
                // If the instructor not selected and we has event in local storage.
                if (getEventOperation() && instructor == "") {
                  window.contextMenu.attach(".fc-body", [
                    { header: "Copy/Paste" },
                    { text: "Paste", class: "disabled" },
                    {
                      text: "Cancel",
                      action: function () {
                        removeEventOperation();
                      },
                    },
                  ]);
                }
                // If no event in local storage or the instructor not selected in week view.
                else if (!getEventOperation() || instructor == "") {
                  window.contextMenu.attach(".fc-body", [
                    { header: "Copy/Paste" },
                    { text: "Paste", class: "disabled" },
                  ]);
                } else {
                  window.contextMenu.attach(".fc-body", [
                    { header: "Copy/Paste" },
                    {
                      text: "Paste",
                      action: function () {
                        var data = getEventOperation();
                        // The newsly picked date and time.
                        data.newDate = date._i;
                        instructor = /\(([^)]+)\)/.exec(instructor)[1];
                        if (instructor) {
                          data.newInstructor = instructor;
                        }

                        // console.log(data);
                        $.ajax({
                          cache: false,
                          url: copyPaste,
                          type: "GET",
                          dataType: "json",
                          data: data,
                          error: function () {
                            console.log("Oops! Try again.");
                          },
                          success: function (response) {
                            // Remove the old event that need move to new place.
                            if (data.operation == "move") {
                              $("#calendar").fullCalendar(
                                "removeEvents",
                                data.id
                              );
                            }
                            // Adding the id for every event after paste in new place.
                            response.id = response.entityId;
                            // Adding new Event in full calendar day view.
                            $("#calendar").fullCalendar(
                              "renderEvent",
                              response,
                              true
                            );
                          },
                        });
                        // This should be run After the paste is done by
                        // the back-end returning success.
                        removeEventOperation();
                        window.contextMenu.destroy(".fc-body");
                      },
                    },
                    // Add a cancel button for removing event from local storage.
                    {
                      text: "Cancel",
                      action: function () {
                        removeEventOperation();
                      },
                    },
                  ]);
                }

                // Prevent browser context menu:
                return true;
              },

              // Day view event right click event handling.
              eventRightclick: function (event, jsEvent, view) {
                window.contextMenu.init({ preventDoubleContext: false });
                // alert('an event has been rightclicked!');
                window.contextMenu.attach(".fc-body", [
                  { header: "Copy/Paste" },
                  {
                    text: "Cut",
                    action: function () {
                      addEventOperation(event, "move");
                      getEventOperation();

                      window.contextMenu.destroy(".fc-body");
                    },
                  },
                  {
                    text: "Copy",
                    action: function () {
                      addEventOperation(event, "copy");
                      getEventOperation();

                      window.contextMenu.destroy(".fc-body");
                    },
                  },
                ]);
                // Prevent browser context menu:
                return true;
              },

              // The main part of gettind data and manipulate them
              // to show those in proper format in the callendar.
              // To match with resources here the resourceId should match
              // with the ids that provided in the recources.
              // Also to get proper location according to time slot
              // it need the correct start and end params that should
              // be in correct date format like: 2019-07-18T19:30:00.
              events: {
                // Resource route to load Instructors.
                url: eventsCallback,
                method: "GET",
                data: function () {
                  var fields = jQuery(".calendar_filter_form").serializeArray();
                  var datas = {};
                  jQuery.each(fields, function (index, val) {
                    /* iterate through array or object */
                    datas[val.name] = val.value;
                  });
                  // After setup successfully, make calendar visible.
                  $("#calendar").css("opacity", "1");

                  return datas;
                },
                failure: function () {
                  alert("There was an error while fetching events!");
                },
              },
            });
            // Update min and max hours after calendar build.
            setStudioHours();
          }
          // Update the calendar when date field data changed.
          $("#calendar_date").change(function (event, element) {
            updateCalendarData();
          });

          // Update the calendar when apply button pressed.
          $("#filter_form_submited").click(function (event, element) {
            updateCalendarData();
          });

          // Update the calendar when Day view navigated.
          jQuery("body").on(
            "click",
            "button.fc-prev-button, button.fc-next-button",
            function (event) {
              updateCalendarData();
            }
          );

          // Update the calendar when enter button pressed.
          $(document).on("keypress", function (event) {
            if (event.which == 13) {
              updateCalendarData();
            }
          });

          // Define each part that need the filter autocomplete.
          // Each attribute should be setted on main template.
          var selector = '[name="student"]';
          autoCompleteFilter(
            selector,
            $(selector).attr("entity"),
            "title",
            $(selector).attr("bundle")
          );

          // Run all needed action to update the calendar view after apply changes.
          function updateCalendarData() {
            // Add the start date of the week in week view.
            getCalendarDateField();
            $("#calendar").fullCalendar("removeEvents");
            $("#calendar").fullCalendar("refetchEvents");

            // Just update the official hours for the selected date.
            setStudioHours();
          }
          // custom function to load items on cutom filter inputs.
          function autoCompleteFilter(selector, eckType, field, bundle) {
            $(selector).autocomplete({
              source: function (request, response) {
                jQuery.ajax({
                  cache: false,
                  url: window.location.origin + "/amt_dayview/autocomplete",
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
        });
    },
  };

  // Add the start date of the week in week view.
  function getCalendarDateField() {
    var date = $("#calendar_date").val();
    if (date) {
      // Get the week and change to date.
      date = date.split("-W");
      date = new Date(date[0], 0, 1 + (date[1] - 1) * 7);
      $("#calendar").fullCalendar("gotoDate", date);
    }
  }

  /**
   * Add the start date after creating events.
   */
  $.fn.ajaxCallbackGoToDate = function (date) {
    if (date) {
      $("#calendar").fullCalendar("gotoDate", date);
    }
  };

  // Close pupop after the modal opening.
  jQuery("#calendarfilter + .dropdown ul li a").click(function (event) {
    jQuery(this).parents(".dropdown.open").removeClass("open");
  });

  // Close events modal after click outsite of the modal window.
  // jQuery('.modal.calendar').click(function(event) {
  //   jQuery('.modal.calendar').css({
  //     display: 'none',
  //     opacity: '0'
  //   });
  // });

  // Onclick create cookie for date we want create events.
  jQuery("#add-new-event-dropdown-toggle").click(function () {
    // Added for updating the date field filter.
    var timeInfo = $("#calendar").fullCalendar("getView");
    // Set a cookie for edit event and come back in day view.
    setCookie("ajax-modal-data", timeInfo.start.format(), 0.1);
  });

  // By defautl load data for an instructor.
  $(document).ready(function () {

    // Check if any teacher is provided.
    let searchParams = new URLSearchParams(window.location.search)
    if(searchParams.has('instructor') && searchParams.get('instructor').length > 0) {
      $('.instructors_list option[value='+searchParams.get('instructor')+']').attr('selected','selected');
    }
    else {
      // if no teach provided, then select the first available instructor (remove the first null option, the select placeholder).
      $('.instructors_list option[value=""]').remove();
      $('.instructors_list option:first').attr('selected', 'selected');
    }

    // If any teacher was selected, then apply filter.
    if ($('.instructors_list').val().length > 0) {
      $('#filter_form_submited').click();
    }
  });

  /**
   * Each day of week for dayview has specific studio hours that needed
   * to assign after fetching related data.
   */
  function setStudioHours() {
    // Get the week start and end date.
    var currentDate = $("#calendar").fullCalendar("getDate");
    // console.log(currentDate);
    if(currentDate){
      jQuery('input[name="start"]').val(
        currentDate.startOf("week").format("YYYY-MM-DD")
      );
      jQuery('input[name="end"]').val(
        currentDate.endOf("week").add(1, "days").format("YYYY-MM-DD")
      );
    }

    var fields = jQuery(".calendar_filter_form").serializeArray();
    var requestData = {};

    jQuery.each(fields, function (index, val) {
      /* iterate through array or object */
      requestData[val.name] = val.value;
    });
    requestData.onlytime = "true";
    // Ajax function to update callendar based on official hovers.
    jQuery.ajax({
      cache: false,
      url: window.location.origin + "/amt_dayview/load",
      type: "GET",
      charset: "UTF-8",
      data: requestData,
      error: function () {
        console.log("Oops! Try again.");
      },
      success: function (resp) {
        var newTimeConfig = {};
        newTimeConfig.minTime = resp.start;
        newTimeConfig.maxTime = resp.end;

        // Set valid min and max time for day view.
        $("#calendar").fullCalendar("option", newTimeConfig);

        // Today button need to remap after become enable.
        jQuery("button.fc-today-button").click(function (event) {
          // Make Calendar disable up to get new data.

          // Before official hours setuped, make calendar not visible.
          setStudioHours();
        });
      },
    });
  }
  $(document).on("click", '.delete_event_modal', function () {
    $.ajax({
      cache: false,
      url: window.location.origin + "/amt_dayview/delete/event",
      type: "POST",
      dataType: "json",
      data: {id : jQuery('#delete_button').attr('data-id')},
      error: function () {
        console.log("Oops! Try again.");
      },
      success: function (response) {
        location.reload(true);
      },
      complete: function (response) {
        location.reload(true);
      },

    });
  });

  jQuery(document).on('change', 'input[name="lesson_date[date]"]', function(){
    let date = jQuery(this).val();
    jQuery('input[name="field_showed[0][value][date]"]').val(date);
  })
  
})(jQuery);
