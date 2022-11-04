(function ($) {

  Drupal.behaviors.amt_validations = {
    attach: function(context, settings) {

    	// Set Default Hours with js, because module can't handle it correclty.
    	$('#edit-field-availability-value td:nth-child(2) div input').val("11:45:00");
    	$('#edit-field-availability-value td:nth-child(3) div input').val("20:30:00");
	    // Apply the myCustomBehaviour effect to the elements only once.
	    $('#edit-field-availability', context).once('edit-field-availability').each(function () {
	    	// Change the text of first lesson available to Copy to all others.
	        $('#edit-field-availability-value-0-operations-data-copy').html("Copy to all others").click(function(e) {
		    	e.preventDefault();
		    	// On click change on all remaining button for fill of all office hour time.
		    	$('a:contains("Copy previous day")').click();
	      	});
	    });
    }
  };
}(jQuery));
