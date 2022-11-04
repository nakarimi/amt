(function ($) {

  Drupal.behaviors.amt_dashboard = {
    attach: function(context, settings) {

      // Add Loader on ajax calls
      $("body").once('body').prepend("<div id='spinner-amt'><img src='/modules/custom/amt_dayview/assets/js/spinner.gif'></div>");
      $(document).ajaxSend(function(){
        $('#spinner-amt').show();
      });
      $(document).ajaxComplete(function(){
        $('#spinner-amt').hide();
      });

      // Apply the myCustomBehaviour effect to the elements only once.
      $('#dashboard_charts', context).once('dashboard_charts').each(function () {
        // Defining the route for charts load via ajax call.
        var chartsDataURL = window.location.origin + '/amt_dashboard/charts';
        // Start of the ajax call.
        $.ajax({
          url: chartsDataURL,
          type: 'POST',
          dataType: "json",
          data : {chartCategory: 'lessons'},
          // If the call was not successfull.
          error: function() {
            $('#lessons_booked').html('Lessons Chart Failed!');
          },
          // If the call was successfull.
          success: function(response){
            response = JSON.parse(response);
            // This information is for chart label.
            var bookedTitle = {'title': 'Lessons Booked Chart', 'vTitle' : 'Number of lessons', 'hTitle': 'Months', 'format': 'decimal'};
            dashboardDrawingChart(response.lessons_booked, bookedTitle, 'lessons_booked');
          },
          complete: function (){
            $.ajax({
              url: chartsDataURL,
              type: 'POST',
              dataType: "json",
              data : {chartCategory: 'payments'},
              error: function() {
                $('#paymentChart').html('Payments Chart Failed!');
              },
              success : function (response) {
                response = JSON.parse(response);
                var paymentTitle = {'title': 'Payments', 'vTitle' : 'Total Payments', 'hTitle': 'Months', 'format': 'currency'};
                // Create the enrollment sold chart with google chart.
                dashboardDrawingChart(response.payments, paymentTitle, 'paymentChart');
              },
              complete: function () {
                $.ajax({
                  url: chartsDataURL,
                  type: 'POST',
                  dataType: "json",
                  data : {chartCategory: 'enrollments'},
                  error: function() {
                    $('#enrollment_sold_chart').html('Enrollments Chart Failed!');
                  },
                  success : function (response) {
                    response = JSON.parse(response);
                    var enrollmentTitle = {'title': 'Enrollment Sold Chart', 'vTitle' : 'Total Sold', 'hTitle': 'Months', 'format': 'currency'};
                    dashboardDrawingChart(response.enrollment, enrollmentTitle, 'enrollment_sold_chart');
                  },
                });
              }
            });
          },
        });
      });
      function dashboardDrawingChart(Data, optionData, chartSelector) {
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawVisualization);
        // function for drawing the chart based on data provided.
        function drawVisualization() {
          // Some raw data (not necessarily accurate).
          var data = google.visualization.arrayToDataTable(Data);
          // Add $ sign if chart displays money.
          if(optionData.format == "currency"){
            var formatter = new google.visualization.NumberFormat({pattern: '$###,###'});
            formatter.format(data, 1);
            formatter.format(data, 2);
          }
          var options = {
            title : optionData.title,
            // Title for vertical direction of the chart.
            vAxis: {title: optionData.vTitle, format: optionData.format},
            // Title for horizontal direction of the chart.
            hAxis: {title: optionData.hTitle},
            // Chart type.
            seriesType: 'bars',
            tooltip: {isHtml: 'true'},
            series: {5: {type: 'line'}}
          };
          // Outputting the chart in the provided ID releated to the corresponding div.
          var chart = new google.visualization.ComboChart(document.getElementById(chartSelector));
          chart.draw(data, options);
        }
      }
    }
  };

}(jQuery));
