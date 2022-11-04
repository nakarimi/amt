jQuery(function(){
  'use strict';

  jQuery('#print-student-agreement').on('click', function() {
    jQuery('#student-agreement-wrapper').print({
      stylesheet: '../css/print.css',
    });
  });
});