// Checking or Unchecking all students if the column heading checkbox is changed.
jQuery(document).on(
  "change",
  "#mass_student_department_checkbox_all",
  function () {
    jQuery(".student_checkbox_student_department_update:checkbox").prop(
      "checked",
      jQuery(this).prop("checked")
    );
  }
);

// When the mass student department update form is being submitted.
jQuery(document).on('submit', '#mass_student_department_update_form', function(e) {
  prepare_and_attach_data_to_form(e);
});

/**
 * Preparing and attaching appropriate
 * data to the form before submission.
 * 
 * This function adds the array of selected
 * students and the current path to the
 * mass student department update form before
 * it's submission.
 * @param {object} e 
 */
function prepare_and_attach_data_to_form(e) {

  // Get the list of chosen students in an array.
  let students_array = create_array_of_students_for_department_update();

  // If the list of selected students is not empty.
  if (students_array.length > 0) {

    // Add the current URL to the form. (used later for redirection)
    jQuery('#mass_student_department_update_form input[name="redirection_path"]').val(window.location.href);

    // Add the list of selected students (array) to the form.
    jQuery('#mass_student_department_update_form input[name="students"]').val(JSON.stringify(students_array));
  }

  // If no student was chosen.
  else {
    e.preventDefault();
    alert('Please Select at least one student for department update!');
  }
}

/**
 * Creating array of selected students
 * 
 * This function is called when the form for
 * mass student department update is being
 * submitted. The function is going to crawl
 * the list of students in the modal and get
 * their student_account ids and save them in
 * an array and then return the array where
 * this function is called.
 * 
 * @returns array
 */
function create_array_of_students_for_department_update() {
  let selectedStudents = [];
  jQuery('.student_checkbox_student_department_update').each(function() {
    // Select the ones that are checked.
    if (jQuery(this).is(':checked')) {

      // Push it to a new array.
      selectedStudents.push(jQuery(this).data('id'));
    }
  });

  return selectedStudents;
}
