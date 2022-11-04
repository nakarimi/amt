(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.amt_general_1 = {
    loaded: false,
    attach: function (context, settings) {
      if (Drupal.behaviors.amt_general_1.loaded) {
        return;
      }
      Drupal.behaviors.amt_general_1.loaded = true;
      init();
    },
  };

  function init() {
    $(document).ready(function () {
      
      var field_students = '[data-drupal-selector*="form-field-students-target-id"]';
      var stu_wrapper = "[id*='form-field-students-wrapper']";
      var group_selector = stu_wrapper + " .input-group";
      var stu_account = '[data-drupal-selector*="form-field-student-account-target-id"]';

      // Override selectors for contact (attendee) form.
      if ($('.region-content > form').attr('id') == 'attendees-attendance-edit-form') {
        stu_account = '[data-drupal-selector*="field-student-account-target-id"]';
        field_students = '[data-drupal-selector*="field-students-target-id"]';
        stu_wrapper = ['#edit-field-students-wrapper'];
        group_selector = stu_wrapper + " .input-group";
      }
      
      // Edit attendance load default couples
      var str = $(stu_account).val();
      if (typeof str != 'undefined' && str.length > 0) {
        str = str.substring(str.lastIndexOf("(") + 1, str.lastIndexOf(")"));
        var data = [];
        $.ajax({
          url:
            window.location.origin + "/amt_validations/student_account_contact",
          type: "GET",
          charset: "UTF-8",
          data: { id: str },
          success: function (response) {
            let old = $(field_students).val();
            data = response.replace("	", "").split(", ");
            $(stu_wrapper + " .form-check").remove();
            if (data.length > 1) {
              $(group_selector).addClass("hide");
              $(group_selector).after(`
              <div class="form-check">
                <input class="form-check-input student-couple" type="radio" name="contacts" value="${
                  data[0]
                }, ${data[1]}" id="flexCheckDefault1" ${
                data[0] + ", " + data[1] == old ? "checked" : ""
              }>
                <label class="form-check-label" for="flexCheckDefault1">
                  ${data[0].split(" ")[0]} ${data[0].split(" ")[1]} and ${data[1].split(" ")[0]} ${data[1].split(" ")[1]}
                </label>
              </div>`);
              $(group_selector).after(`
            <div class="form-check">
              <input class="form-check-input student-couple" type="radio" name="contacts" value="${
                data[1]
              }" id="flexCheckDefault2" ${data[1] == old ? "checked" : ""} >
              <label class="form-check-label" for="flexCheckDefault2">
                ${data[1].split(" ")[0]} ${data[1].split(" ")[1]}
              </label>
            </div>`);
              $(group_selector).after(`
            <div class="form-check">
              <input class="form-check-input student-couple" type="radio" name="contacts" value="${
                data[0]
              }" id="flexCheckDefault" ${data[0] == old ? "checked" : ""}>
              <label class="form-check-label" for="flexCheckDefault">
                ${data[0].split(" ")[0]} ${data[0].split(" ")[1]}
              </label>
            </div>`);
            } else {
              $(group_selector).removeClass("hide");
            }
            $(".student-couple").change(function (e) {
              $(field_students).val($(this).val().split(", "));
            });
          },
        });
      }
      // Edit attendance load default couples
    });
  }

  $.fn.addCoupleCheckbox = function (data) {
    var field_students =
      '[data-drupal-selector*="form-field-students-target-id"]';
    var stu_wrapper = "[id*='form-field-students-wrapper']";
    var group_selector = stu_wrapper + " .input-group";
    // var value = $(field_students).val();
    $(stu_wrapper + " .form-check").remove();
    if (data.length > 1) {
      $(group_selector).addClass("hide");
      $(group_selector).after(`
        <div class="form-check">
          <input class="form-check-input student-couple" type="radio" name="contacts" value="${
            data[0]
          }, ${data[1]}" id="flexCheckDefault1" checked>
          <label class="form-check-label" for="flexCheckDefault1">
            ${data[0].split(" ")[0]} ${data[0].split(" ")[1]} and ${data[1].split(" ")[0]} ${data[1].split(" ")[1]}
          </label>
        </div>`);
      $(group_selector).after(`
      <div class="form-check">
        <input class="form-check-input student-couple" type="radio" name="contacts" value="${
          data[1]
        }" id="flexCheckDefault2">
        <label class="form-check-label" for="flexCheckDefault2">
        ${data[1].split(" ")[0]} ${data[1].split(" ")[1]}
        </label>
      </div>`);
      $(group_selector).after(`
      <div class="form-check">
        <input class="form-check-input student-couple" type="radio" name="contacts" value="${
          data[0]
        }" id="flexCheckDefault">
        <label class="form-check-label" for="flexCheckDefault">
        ${data[0].split(" ")[0]} ${data[0].split(" ")[1]}
        </label>
      </div>`);
    } else {
      $(group_selector).removeClass("hide");
    }
    $(".student-couple").change(function (e) {
      $(field_students).val($(this).val().split(", "));
    });
  };
})(jQuery, Drupal);