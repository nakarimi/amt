(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.amt_general_collapsible = {
    loaded: false,
    attach: function (context, settings) {
      if (Drupal.behaviors.amt_general_collapsible.loaded) {
        return;
      }
      Drupal.behaviors.amt_general_collapsible.loaded = true;
      init();
    },
  };

  function init() {
    $(document).ready(function () {

      // // This js makes the standings tab collapsible in both lesson and group lesson forms.
      // // Select standing tab div.
      // var standingTab =
      //   "div.field--name-field-date-and-time + div[id^='edit-group-standing']";
      // // Select standing tab sub divs or childs.
      // var standingTabContent =
      //   "div.panel-heading + div[id^='edit-group-standing']";
      // // this is to prevent js from running multiple times.

      // // At first hide sub items of standing tab.
      // $(standingTabContent).slideUp().removeClass("in");
      // // Select that div which have title of "Standing Lesson" in Group Lesson.
      // $("div a:contains(Standing Lesson)").click(function () {
      //   // Makes the div toggle.
      //   $(standingTabContent).slideToggle("slow");
      // });
      // // Select that div which have title of "Standing Appointment" in Lesson.
      // $("div a:contains(Standing Appointment)").click(function () {
      //   // Makes the div toggle.
      //   $(standingTabContent).slideToggle("slow");
      // });

      // Get default duration for other cases
      // var main_time_length = '';
      // var durationInterval = setInterval(durationDefault, 500);
      // function durationDefault() {
      //   if (jQuery("select[id*='edit-field-duration']").length > 0) {
      //     main_time_length = $("select[id*='edit-field-duration']").val();
      //     clearInterval(durationInterval);
      //   }      
      // }
      $("select[id*='edit-field-type']").change(function (e) {

        let selected = $(this).children("option:selected").html();
        if (
          selected.indexOf("Dance Evaluation") >= 0 ||
          selected.indexOf("PreOriginal") >= 0
        ) {
          jQuery.ajax({
            url: window.location.origin + "/amt_general/get_lessontype_length",
            type: "GET",
            charset: "UTF-8",
            data: {
              type: selected,
            },
            success: function (resp) {
              $("select[id*='edit-field-duration']").val(resp);
            },
          });
        // } else {
        //   $("select[id*='edit-field-duration']").val(main_time_length);
        }
      }); 

    });
  }
})(jQuery, Drupal);
