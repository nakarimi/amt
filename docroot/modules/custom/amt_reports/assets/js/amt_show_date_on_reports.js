(function ($) {

  Drupal.behaviors.amt_reports = {
    attach: function(context, settings) {
      // Append the date range next to page title.
      let selectr = $('.rprt_table tbody tr td:last-child').text();
      // If there was any date in the selector.
      if (selectr.length > 10) {
        let range = selectr.split('To');
        let newRagne = ' (<b>'+ range[0] + '</b> To <b>' + range[1] + '</b>)';
  
        if ($('h1.page-header').text().indexOf('(') == -1) {
          $('h1.page-header').append(newRagne);
        }
      }

      window.onscroll = function() {
        if (window.pageYOffset > 100){
          $('.main-container [role="heading"]').addClass('fixedTop');   
        }
        else {
          $('.main-container [role="heading"]').removeClass('fixedTop'); 
        }
      }

      // Make the student inventory table a datatable.
      // $('body', context).once('amt_reports').each(function () {
      //   $('#studentsInventory').DataTable({
      //     order: [[0, "DESC"]],
      //     searching: true,
      //     bFilter: false,
      //     pageLength: 25,
      //   });

      // });  
    }
  };
}(jQuery));
