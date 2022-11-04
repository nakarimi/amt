(function ($) {

    Drupal.behaviors.amt_reports = {
      attach: function(context, settings) {
          // This is a variable for saving the date inditactor.
          $('#studentsFilters', context).once('studentsFilters').each(function () {
          var studentsListDataTable = $('#studentsListTable').DataTable({
            "order": [[ 0, 'asc' ]],
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "pageLength": 50,
            "info": false,
            "searching": true,
            "sDom": "ltipr",
            "columnDefs": [
              { "orderable": false, "targets": [6,7] }
            ],
            
          });

          // List of selectors and the target columns for filteration.
          var inputFilters = [
            [
              '#nameFilter',
              '.studentsNamesColumn',
            ],
            [
              '#emailFilter',
              '.studentEmailsColumn'
            ],
            [
              '#instructorFilter',
              '.instructorsColumn',
            ],
            [
              '#studentDepartmentFilter',
              '.studentDepartmentColumn',
            ],
            [
              '#dateFilter',
              '.upCommingLessonColumn',
            ],
            [
              '#inactive',
              '.status',
            ],
          ];

          // Creating an event listener to apply the filters.
          $('#filterStudentsButton').on('click', function(){
            // Loop for all 6 filters in the students list.
            console.log(inputFilters);
            for(i = 0; i < inputFilters.length; i++) {
              
              // If the value of the current filter is not empty.
              if ($(inputFilters[i][0]).val() != "") {
                // Get the value of the current fitler.
                var val = $(inputFilters[i][0]).val();
                // Apply the filter on the table.
                studentsListDataTable.column(inputFilters[i][1]).search(val ? '^'+val+'$' : '', true, false).draw(); // note columns(0) here
              }
              // If the value of the filter is emtpy.
              else {
                // Reset the table for the current filter.
                studentsListDataTable.column(inputFilters[i][1]).search('').draw();
              }
            }
          });
          
          // Applying the Autocomplete on the corresponding filters.
          autoCompleteFilter(inputFilters[0][0], inputFilters[0][1]);
          autoCompleteFilter(inputFilters[1][0], inputFilters[1][1]);
          autoCompleteFilter(inputFilters[2][0], inputFilters[2][1]);

          /*
          This function is called to apply the autocomplete
          for the three inputs of student names, student emails
          and instructors names in Students list page.

          @param selector This parameter refers to the input filter.
          @param columnHeading This parameter refers to the column class
          of the related data.
          */
          function autoCompleteFilter(selector, columnHeading) {
            var columnData = [];
            // Getting the unique data from the corresponding column.
            var newarray = studentsListDataTable.columns(columnHeading).data().eq(0).unique();
            for (var i = 0; i < newarray.length; i++) {
              columnData.push({ "label":  newarray[i] });
            }

            /*
            Start of the autocomplete events section.

            This section provided autocomplete for the three
            filters of name, email and instructor for the students
            list.
            
            Autocomplete for names.
            */
            $(selector).autocomplete({
              source: columnData,
            });
            // End of Auto autocomplete section.
          }
        });
      }
    };
  
  }(jQuery));
