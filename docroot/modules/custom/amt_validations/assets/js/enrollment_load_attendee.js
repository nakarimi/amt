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
      var EnrollmentListURL = window.location.origin + "/enrollments/";
      var EnrollmentCheckURL = window.location.origin + "/enrollmentserrors/";

      var wrapper = 'div[id*="field-enrollment-wrapper"]';
      var enrollmentfield = 'input[data-drupal-selector*="field-enrollment"]';
      var studentfield = 'input[data-drupal-selector*="field-student-account"]';
      
      // check if the code already been initialzied and wrapper exists
      // because this code only works on ajax complete
      // inline forms are ajax loaded content
      if (!initialized2 && $(wrapper).length) {
        initialized2 = true;
        // create a uniqe ID based on inline entity form id
        var createID = $(wrapper).attr("id") + "-newEnrollmentListID";
        // check if it's already been created because this code runs eveytime an ajax trigger completes
        if ($("#" + createID).length == 0) {
          var newSelectElement = $("<select>").appendTo(wrapper + " > div");
          newSelectElement.append(
            $("<option>")
              .attr("value", "")
              .text("Please Select Student Account")
          );
          newSelectElement.addClass("form-select").addClass("form-control");
          newSelectElement.attr("id", createID);
          newSelectElement.after("<br><div id='enrollmentError'></div>");
          newSelectElement.prop("disabled", true);
        }

        // hide the real enrollment feild
        $(enrollmentfield).addClass("hidden");
        // hide the box enrollment feild.
        $(wrapper + " > div > div > span").addClass("hidden");
        // Check if the student feild has value (mean edit page) it loads the content.
        // But the new Element should not have value. because it should not run
        // when ajax loads again in this page.
        var valueofAccount = $(studentfield).val();
        var NewelementValue = $("#" + createID).val();
        if (valueofAccount && !NewelementValue) {
          LoadEnrollmentContent(
            EnrollmentListURL,
            valueofAccount,
            createID,
            enrollmentfield
          );
        }

        // fill newly added element (select) with options from json based on student ID
        $(document).on("change", studentfield, function () {
          LoadEnrollmentContent(
            EnrollmentListURL,
            $(this).val(),
            createID,
            false
          );
        });
        // Add value based on selected value of new element (enrollment)
        // and pass it to real field.
        $(document).on("change", "#" + createID, function () {
          var enrollmentValue = $(this).val();
          // Get the Text label of selected value.
          var text =
            document.getElementById(createID).options[
              document.getElementById(createID).selectedIndex
            ].innerHTML;
          $(enrollmentfield).val(text + " (" + enrollmentValue + ")");
          var lessontype = "notset";
          var studentfieldvv = $(studentfield)
            .val()
            .split(" ")
            .pop()
            .replace(/\D/g, "");
          var ErrorDataURL =
            EnrollmentCheckURL +
            lessontype +
            "/" +
            studentfieldvv +
            "/" +
            enrollmentValue;
          $("#enrollmentError").load(ErrorDataURL);
        });
      }
    });
    initialized2 = false;
  }
  
  function LoadEnrollmentContent(
    EnrollmentListURL,
    valueofAccount,
    createID,
    enrollmentfield
  ) {
    valueofAccount = valueofAccount.split(" ").pop().replace(/\D/g, "");
    var newSelectElement = $("#" + createID);
    // Add a temperory Loading Option to select.
    newSelectElement.html($("<option>").text("loading..."));
    // Disable the Element while it's loading the JSON.
    newSelectElement.prop("disabled", true);

    // Start request for enrollment list data.
    // The request patern is like this: /enrollments/1212/1.
    if(!valueofAccount) {
      newSelectElement.html(
        $("<option>").text("No Enrollment Attached to This Account...")
      );
    }else{
      $.getJSON(
        EnrollmentListURL +
          valueofAccount +
          "/" +
          window.location.pathname.indexOf("events"),
        function (data) {
          if (data === undefined || data.length == 0) {
            newSelectElement.html(
              $("<option>").text("No Enrollment Attached to This Account...")
            );
          } else {
            // Add Empty Option.
            newSelectElement.html($("<option>").text("Select Enrollment"));

            // Load Item From json file to select element.
            $.each(data, function (key, val) {
              // Add any other enrollments that fetched.
              newSelectElement.append($("<option>").attr("value", key).text(val));
            });

            // Enable the Select Element after it's filled up.
            newSelectElement.prop("disabled", false);
            // Set Value to New Element if Enrollment has value.
            if (enrollmentfield) {
              var valueofEnrollment = $(enrollmentfield)
                .val()
                .split(" ")
                .pop()
                .replace(/\D/g, "");
              newSelectElement.val(valueofEnrollment);
            }
          }
        }
      );
    }
  }
})(jQuery, Drupal);
