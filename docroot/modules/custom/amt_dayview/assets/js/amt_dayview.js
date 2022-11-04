/*jshint esversion: 6 */
(function ($) {
  /*
    Adding Event Operation ID and Type to localStorage.
    in case of Move and Copy.
  */
  function addEventOperation(event, Operation) {
    var instructor = $(
      '[data-resource-id="' + event.resourceIds[0] + '"] div'
    ).html();
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
      // Add custom tooltip to show any where that user clicked.
      $("body", context).once("body")
        .append(`<div class="popover fade bottom in" role="tooltip" id="custom-popover" style="top: 670px; left: 670px; display: none;">
                    <div class="arrow" style="left: 50%;"></div>
                    <div class="popover-content">
                      <div style=" display: grid; ">
                        <button type="button" class="btn btn-default" target_element="lesson" title="Book Lesson" >Lesson</button>
                        <button type="button" class="btn btn-default" target_element="service" title="Book Service">Service</button>
                        <button type="button" class="btn btn-default" target_element="schedule" title="Book Schedule">Schedule</button>
                        <button type="button" class="btn btn-default" target_element="group_lesson" title="Book Group Lesson">Group Lesson</button>
                      </div>
                    </div>
                </div><div class="popover fade right in" role="tooltip" id="selected-hours" style="top: 670px; left: 670px; display: none;">
                <div class="popover-data">
                </div>
              </div>`);
      // Custom action on tooltip button click.
      $(".popover button a, .popover button").click(function (event) {
        event.preventDefault();
        $(".use-ajax.hidden#" + $(this).attr("target_element")).click();
        $(".popover").hide();
      });
      $(".popover").mouseleave(function (event) {
        $(".popover").hide();
      });

      // Event Listener for the student dashboard dropdown on dayview popup.
      $("#studentDashboardsDropdownButton").on({
        mouseenter: function () {
          // Display the student dashboard links menu.
          $("#dahboardsDropMenu").css("display", "block");
        },
        mouseleave: function () {
          // Hide the student dashboard links menu.
          $("#dahboardsDropMenu").css("display", "none");
        },
      });

      // End of custom tooltip.
      // Apply the myCustomBehaviour effect to the elements only once.
      $("#calendar", context)
        .once("calendar")
        .each(function () {
          if (Drupal.behaviors.amt_dayview.init) {
            Drupal.behaviors.amt_dayview.init();
            Drupal.behaviors.amt_dayview.init = undefined;
          }

          // Add Loader on ajax calls
          $("body").once("body").prepend(`
            <div id='spinner-amt'>
              <img src='/modules/custom/amt_dayview/assets/js/spinner.gif'>
            </div>
          `);
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
              console.log("Oops! Try again.");
            }
          });

          // Modal in dayview has an issue with .modal() function.
          // Then I do it just by changing the css property.
          $('[aria-hidden="true"], [data-dismiss="modal"]').click(function (
            event
          ) {
            /* Act on the event */
            $(".calendar.modal").css({
              display: "none",
              opacity: "0",
            });
            removeCookie('ajax-modal-data-event');
          });

          // Define all ajax calling path for dayview.
          var eventsCallback = window.location.origin + "/amt_dayview/load";
          var resourcesCallback =
            window.location.origin + "/amt_dayview/resources";
          var popupCallback = window.location.origin + "/amt_dayview/popup";
          var configStartSessionTimeURL =
            window.location.origin + "/amt_dayview/configurations-session";
          var studioWorkingHours;
          var copyPaste = window.location.origin + "/amt_copypaste";

          // Get data with name from url.
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
              console.log("Oops! Try again.");
            },
            success: function (response) {
              response = JSON.parse(response);
              studioWorkingHours = response.office_hours;
              // After edit of event and come back to day view and set the date.
              if ($.urlParam("date")) {
                response.defaultDate = $.urlParam("date");
              }
              setConfiguration(response);
            },
          });

          /**
           * Callendar configuration for dayview.
           *
           * @param {[array]} calendarConfig
           *   Configuration fetched from server that user set on dayview configuration page.
           */
          function setConfiguration(calendarConfig) {
            // Main configuration on callendar options.
            $("#calendar").fullCalendar({
              defaultDate: calendarConfig.defaultDate,

              // Set the start day of week.
              firstDay: calendarConfig.start_day,

              // Set the default view that calendar will loaded on.
              defaultView: "agendaDay",

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
              minTime: calendarConfig.office_hours.minTime,
              maxTime: calendarConfig.office_hours.maxTime,
              groupByDateAndResource: calendarConfig.date_resource,

              // Settings for manage the callendar header options.
              header: {
                left: calendarConfig.left,
                center: calendarConfig.center,
                right: calendarConfig.right,
              },
              // --------------------------------
              // Make the cells of calendar clickable to create events.
              selectable: true,
              // When the events over loped it set false the selectable of calendar
              selectOverlap: function (event) {
                // console.log(event);
                if (String(event.className[0]) == "Cancelled") {
                  return true;
                }
                return false;
              },
              selectAllow: function (date) {
                // console.log(date);
                // Find the time diff for checking the druation.
                var fromTime = new Date(date.start.format()).getTime() / 1000;
                var toTime = new Date(date.end.format()).getTime() / 1000;
                var timeDiff = (toTime - fromTime) / 3600; // will give difference in hrs
                var left = event.pageX;
                var top = event.pageY;
                var theHeight = $("#selected-hours").height();
                $("#selected-hours").show();
                $("#selected-hours .popover-data").html(timeDiff).css({
                  "min-width": "20px",
                  "text-align": "center",
                });
                if (timeDiff > 9) {
                  $("#selected-hours .popover-data").css("color", "red");
                } else {
                  $("#selected-hours .popover-data").css("color", "black");
                }
                $("#selected-hours").css("left", left + "px");
                $("#selected-hours").css("top", top - theHeight / 2 + "px");
                $("#selected-hours").css({
                  "z-index": "9999",
                  position: "absolute",
                  display: "block",
                });

                // var events = $('#calendar').fullCalendar('clientEvents', function(evt)
                //  {
                //   if (evt.resourceId == date.resourceId) {
                //     console.log(evt.start + ' ' + date.end);
                //   }
                //      return (evt.start == date.start
                //              && evt.end == date.end
                //              && evt.resourceId == date.resourceId);
                //  });
                //  return events.length > 0;
              },
              select: function (startDate, endDate, jsEvent, view, resource) {
                $("#selected-hours").hide();
                // Set a cookie for creating new event with this Info.
                setCookie(
                  "ajax-modal-data",
                  startDate.format() +
                    "," +
                    endDate.format() +
                    "," +
                    resource.id,
                  0.1
                );
                // For displaying pop-up to day view and booking events.
                var left = jsEvent.pageX;
                var top = jsEvent.pageY;
                var theHeight = $("#custom-popover").height();
                $("#custom-popover").show();
                $("#custom-popover").css(
                  "left",
                  left - $("#custom-popover").width() / 2 + "px"
                );
                $("#custom-popover").css(
                  "top",
                  top - theHeight / 2 + 20 + "px"
                );
                $("#custom-popover").css("display", "block");
              },
              // End clickable Cells--------------------------------
              viewRender: function (view, element) {
                // Get the Day Title and Convert to Date
                var CurrentDayDate = new Date(view.title);
                // Load JSON Config file.
                $.getJSON(configStartSessionTimeURL, function (dataJSON) {
                  // Convert JSON Text To Object
                  var dataObject = JSON.parse(dataJSON);
                  // Convert Json To array to be used as dynamic key
                  var dataArray = Object.entries(dataObject);
                  // search for times and gray the colors.
                  dataArray[CurrentDayDate.getDay()][1].forEach(function (
                    item
                  ) {
                    $('tr[data-time="' + item + '"] td:nth-child(2)').css(
                      "background",
                      "#ccc"
                    );
                  });
                });
                // console.log('current date ', CurrentDayDate);
                let currentDate = moment(CurrentDayDate).format(
                  "dddd, MMMM DD, YYYY"
                );
                let amiWeekNumber = get_ami_week_number(
                  moment(CurrentDayDate).format("Y"),
                  CurrentDayDate
                );
                $(".fc-center h2").attr("date", $(".fc-center h2").text());
                $(".fc-center h2").text(
                  currentDate + " - Week # " + amiWeekNumber
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
                // var timeInfo = $('#calendar').fullCalendar('getView');  // This is not used anywhere.
                // Set a cookie for edit event and come back in day view.
                setCookie("ajax-modal-data-event", event.entityId, 0.1);

                // On popup click, empty the attendessIds array.
                attendeesIds.length = 0;
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

                $(".btn-loading").on("click", function () {
                  $(this).attr(
                    "data-loading-text",
                    '<span class="ajax-throbber glyphicon-spin icon glyphicon glyphicon-refresh spin" aria-hidden="true"></span> ' +
                      $(this).html()
                  );
                  var $btn = $(this).button("loading");
                });

                // On click event for post button.
                postButtonClick();
                
                // Delete Button
                add_event_delete_button_confirm();

                // Set the data-delete attribute of the delete
                // button to false and it prevent to delete the
                // first time delete of event.
                $("#statusListTab").click(function () {
                  $("#delete_button").attr("data-delete", "false");
                });
                $("#appointmentDetailsTab").click(function () {
                  $("#delete_button").attr("data-delete", "false");
                });

                $.ajax({
                  cache: false, // Don't store this request to browser cache, since everytime updated data is needed.
                  url: popupCallback,
                  type: "GET",
                  dataType: "json",
                  data: { id: event.entityId },
                  error: function () {
                    console.log("Oops! Try again.");
                  },
                  success: function (response) {
                    // Add Status to popup. 
                    add_status_to_pupup(response, event);
                  },
                });
              },

              // Create a Tooltip for Hovering on Instrucor Cell
              resourceRender: function (resourceObj, $th) {
                $th.html(
                  $(
                    '<div style="cursor: pointer;">' +
                      resourceObj.title +
                      "</div>"
                  ).popover({
                    content: resourceObj.info,
                    trigger: calendarConfig.instructor_trigger,
                    placement: calendarConfig.instructor_placement,
                    container: calendarConfig.instructor_container,
                    html: true,
                  })
                );
              },
              // Define the Callendar colomn name.
              // This part thould be dynamic and will
              // define by instractor names.
              resources: function (callback) {
                // Added for updating the date field filter.
                var timeInfo = $("#calendar").fullCalendar("getView");

                jQuery('input[name="start"], #calendar_date').val(
                  timeInfo.start.format()
                );
                jQuery('input[name="end"]').val(timeInfo.end.format());
                $(".path-day-view .fc-right").html("");
                $.ajax({
                  cache: false,
                  url: resourcesCallback,
                  type: "GET",
                  dataType: "json",
                  data: jQuery(".calendar_filter_form").serialize(),
                  error: function () {
                    console.log("Oops! Try again.");
                  },
                  success: function (response) {
                    // Remove All popup boxes on instructors before loading.
                    $(".fc-widget-header th > div").popover("hide");
                    // Remove All popup boxes on instructors before loading in case the above skip it.
                    $("div[role=tooltip][id^=popover]").remove();
                    var privateLessonDaily = 0;
                    var privateLessonWeekly = 0;
                    var privateTakenLessonDaily = 0;
                    var privateTakenLessonWeekly = 0;
                    var counted = [];
                    jQuery.each(response, function (index, val) {
                      let id = parseInt(val.id);
                      if (!counted.includes(id)) {

                        // Booked Leesons.
                        privateLessonWeekly += val.privateLesson[1];
                        privateLessonDaily += val.privateLesson[0];
                        
                        // Taken (Posted) Lessons.
                        privateTakenLessonWeekly += val.privatePostedLesson[1];
                        privateTakenLessonDaily += val.privatePostedLesson[0];

                        counted.push(id);
                      }
                    });

                    $(".path-day-view .fc-right").html(
                      "<div class='summary-counts'><label>Overall Summary: </label>" +
                        "<span title='Booked Daily'> " + privateLessonDaily + "</span> " +
                        "<sup title='Taken Daily'>(" + privateTakenLessonDaily + ")</sup> " +
                        "<b> / </b>" +
                        "<span title='Booked Weekly'>" + privateLessonWeekly + "</span> " +
                        "<sup title='Taken Weekly'>(" + privateTakenLessonWeekly + ")</sup> " +
                      "</div>"
                    );
                    
                    callback(response);
                  },
                });
              },

              // Day view right click event handling.
              dayRightclick: function (date, jsEvent, view) {
                date._i[1]++;
                window.contextMenu.init({ preventDoubleContext: false });
                // If we has not event in local storage.
                if (!getEventOperation()) {
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
                        data.newInstructor = view.resourceId;

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
                cache: false, // Don't store this request to browser cache, since everytime updated data is needed.
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
                  return datas;
                },
                failure: function () {
                  alert("there was an error while fetching events!");
                },
                success: function (response) {
                  update_appointments_list_global_variables(response);
                  jQuery('#update_multiple_status_dropdown_btn').attr('disabled', false);
                },
              },
            });
            setStudioHours(studioWorkingHours);
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
            $("#calendar").fullCalendar("removeEvents");
            // Add the start date of the week in week view.
            getCalendarDateField();
            $("#calendar").fullCalendar("refetchEvents");

            // Just update the official hours for the selected date.
            setStudioHours(studioWorkingHours);
          }

          /**
           * Custom function to load items on cutom filter inputs.
           * @param  {[string]} selector
           *   The selector name that autocomplete will apply on.
           * @param  {[string]} eckType
           *   This is the entity type that data should fetch from.
           * @param  {[string]} field
           *   The field that data should fetch.
           * @param  {[string]} bundle
           *   Entity bundle type to make search faster and more effective.
           *
           */
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
                    priority: $('[name="instructor_category"]').val(),
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

  /**
   * Add the start date of the week in week view.
   */
  function getCalendarDateField() {
    var date = $("#calendar_date").val();
    if (date) {
      $("#calendar").fullCalendar("gotoDate", date);
    }
  }

  /**
   * Add the start date after creating events.
   */
  $.fn.ajaxCallbackGoToDate = function (date) {
    if (date) {
      $("#calendar_date").val(date);
      $("#filter_form_submited").click();
    }
  };

  /**
   * Each day of week for dayview has specific studio hours that needed
   * to assign after fetching related data.
   *
   * @param {[string]} studioWorkingHours
   *   The Office hours that setuped on dayview configuration page.
   */
  function setStudioHours(studioWorkingHours) {
    if ($(".fc-center h2").attr("date") == undefined) {
      var titleDate = $(".fc-center h2").text();
    } else {
      var titleDate = $(".fc-center h2").attr("date");
    }
    var CurrentDayDate = new Date(titleDate);
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
      error: function (e) {
        console.log("Oops! Try again.", e);
      },
      success: function (resp) {
        var matchedDayHours = [];
        switch (CurrentDayDate.getDay()) {
          case 0:
            matchedDayHours = studioWorkingHours.Sunday;
            break;
          case 1:
            matchedDayHours = studioWorkingHours.Monday;
            break;
          case 2:
            matchedDayHours = studioWorkingHours.Tuesday;
            break;
          case 3:
            matchedDayHours = studioWorkingHours.Wednesday;
            break;
          case 4:
            matchedDayHours = studioWorkingHours.Thursday;
            break;
          case 5:
            matchedDayHours = studioWorkingHours.Friday;
            break;
          case 6:
            matchedDayHours = studioWorkingHours.Saturday;
        }

        var newTimeConfig = {};
        // if(matchedDayHours != undefined) {
        newTimeConfig.minTime = matchedDayHours.start;
        newTimeConfig.maxTime = matchedDayHours.end;
        if (
          Date.parse("01/01/2011 " + matchedDayHours.start) >
          Date.parse("01/01/2011 " + resp.start)
        ) {
          newTimeConfig.minTime = resp.start;
        }
        if (
          Date.parse("01/01/2011 " + matchedDayHours.end) <
          Date.parse("01/01/2011 " + resp.end)
        ) {
          newTimeConfig.maxTime = resp.end;
        }
        // }

        // Set valid min and max time for day view.
        $("#calendar").fullCalendar("option", newTimeConfig);

        // Today button need to remap after become enable.
        jQuery("button.fc-today-button").click(function (event) {
          // Make Calendar disable up to get new data.

          // Before official hours setuped, make calendar not visible.
          setStudioHours(studioWorkingHours);
        });

        update_appointments_list_global_variables(resp);
      },
    });
  }

  // Make the instructor field empty when instructor category changed.
  // Display the items that match with selected category.
  $(
    'select[name="instructor"] option[type="' +
      $('[name="instructor_category"]').val() +
      '"]'
  ).css("display", "block");

  // Hide the items that nor match with selected category.
  $(
    'select[name="instructor"] :not(option[type="' +
      $('[name="instructor_category"]').val() +
      '"])'
  ).css("display", "none");

  $('[name="instructor_category"]').change(function (event) {
    // Set the first element after change the category.
    jQuery('select[name="instructor"]').val(
      jQuery('select[name="instructor"] option:first').val()
    );
    // Display the items that match with selected category.
    $('select[name="instructor"] option[type="' + $(this).val() + '"]').css(
      "display",
      "block"
    );
    // Hide the items that nor match with selected category.
    $(
      'select[name="instructor"] :not(option[type="' +
        $(this).val() +
        '"], option:first)'
    ).css("display", "none");
  });

  // Close pupop after the modal opening.
  jQuery("#calendarfilter + .dropdown ul li a").click(function (event) {
    jQuery(this).parents(".dropdown.open").removeClass("open");
  });

  // Close events modal after click outside of the modal window.
  $("body").on("click", ".modal", function (e) {
    if ($(e.target).hasClass("modal")) {
      removeCookie('ajax-modal-data-event');
      jQuery(".modal.calendar,.modal.calendar-cell-click").css({
        display: "none",
        opacity: "0",
      });
    }
  });

  // Onclick create cookie for date we want create events.
  jQuery("#add-new-event-dropdown-toggle").click(function () {
    // Added for updating the date field filter.
    var timeInfo = $("#calendar").fullCalendar("getView");
    // Set a cookie for edit event and come back in day view.
    setCookie("ajax-modal-data", timeInfo.start.format(), 0.1);
  });

  // This is for scrolling the window in day view and
  // sticky the instructor name in the top of window.
  $(window).scroll(function () {
    var screenPosition = $(".fc-view-container").offset();

    let adminToolbar = $("#toolbar-administration").offset();
    if (adminToolbar == null || typeof adminToolbar === "undefined") {
      if ($(this).scrollTop() > screenPosition.top) {
        addStickyClass();
      } else {
        removeStickyClass();
      }
    } else {
      if (screenPosition && adminToolbar) {
        if ($(this).scrollTop() > screenPosition.top - adminToolbar.top) {
          if ($(window).width() < 610) {
            addStickyClass();
          } else if ($(window).width() < 975) {
            addStickyClass($("#toolbar-bar").height());
          } else {
            addStickyClass(adminToolbar.top);
          }
        } else {
          removeStickyClass();
        }
      }
    }
  });

  // This add sticky and calculate the top space.
  $("#toolbar-bar > .toolbar-tab > a").click(function () {
    var screenPosition = $(".fc-view-container").offset();
    let adminToolbar = $("#toolbar-administration").offset();
    if ($(this).scrollTop() > screenPosition.top - adminToolbar.top) {
      addStickyClass(adminToolbar.top);
    } else {
      removeStickyClass();
    }
  });

  function addStickyClass(topDistance = 0) {
    $("thead.fc-head").addClass("sticky").css("top", topDistance);
  }

  function removeStickyClass() {
    $("thead.fc-head").removeClass("sticky");
  }

  // This is a customer calculation of week number for day_view based on the AMI week calculation.
  function get_ami_week_number(year, given_date) {
    var date = new Date(year, 0, 1);

    // find first saturday of the year and return the date
    while (date.getDay() !== 6) {
      date.setDate(date.getDate() + 1);
    }

    let week = 1;
    let endOfWeek = new Date(date);
    let startOfWeek = new Date(endOfWeek);
    startOfWeek.setDate(endOfWeek.getDate() - 6);

    given_date = given_date.getTime();

    let from = new Date(startOfWeek).getTime(); // gives 1486492200000
    let to = new Date(endOfWeek).getTime();

    while (!(given_date >= from && given_date <= to)) {
      to = new Date(endOfWeek.setDate(endOfWeek.getDate() + 7)).getTime();
      let toCopy = new Date(to);
      from = new Date(toCopy.setDate(toCopy.getDate() - 6)).getTime(); // gives 1486492200000
      week++;
    }

    return week;
  }

  $(document).ready(function () {
    // Open active event modal. (As Client requested make this disable for now)
    // var eventModalID = jQuery.cookie("ajax-modal-data-event");
    // removeCookie('ajax-modal-data-event');
    // if(eventModalID){
    //   interval = setInterval(showModal, 200);
    //   function showModal() {
    //     if (jQuery('.event_title#' + eventModalID).length > 0) {
    //       clearInterval(interval);
    //       jQuery('.event_title#' + eventModalID).click();
    //     }
    //   }  
    // }
  });
})(jQuery);
