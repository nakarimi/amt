(function ($) {

  Drupal.behaviors.amt_reports = {
    attach: function(context, settings) {

      // Add the sort feature to the student list table.
      // if the sort param exist then add the icon to related colomn.
      var icon = `<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-caret-down-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
      </svg>`;
      var urlParams = new URLSearchParams(window.location.search);
      $('th svg').remove();
      $('[sort="'+urlParams.get('sort')+'"]').addClass(urlParams.get('stype')).append(icon);
      urlParams.delete('page');
      $('.pagination .page-item').each(function(index, el) {
        $(this).children('a').attr('href', '/students-list?' + urlParams.toString() + '&page=' + $(this).children('a').html());
      });

      // By clicking on each column title add the key to the url and redirect the page to it.
      icon = `<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-arrow-repeat" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
        <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
      </svg>`
      $('#studentsListTable tr th:not(.tableCellHideOnPrint):not(.disableSort)').click(function(event) {
        /* Act on the event */
        $('th svg').remove();

        $('[sort="'+$(this).attr('sort')+'"]').append(icon);
        var urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('sort');
        urlParams.delete('page');
        if ($(this).hasClass('ASC')) {
          urlParams.delete('stype');
          urlParams.append('stype', 'DESC');                    
        }
        else{
          urlParams.delete('stype');
          urlParams.append('stype', 'ASC');          
        }
        urlParams.append('sort', $(this).attr('sort'));
        urlParams.append('page', $('.pagination .active.page-item a').html());
        location.replace('/students-list?' + urlParams.toString());
      });


      // Selecing the instructor filter.
      var selector = '[name="name"]';
      autoCompleteFilter(selector, 'contacts', 'title', 'student_record');
      selector = '[name="email"]';
      autoCompleteFilter(selector, 'contacts','field_email', 'student_record');
      selector = '[name="instructor"]';
      autoCompleteFilter(selector, 'user','name', 'instructor');
      // custom function to load items on custom filter inputs.
      function autoCompleteFilter(selector, eckType, field, bundle) {
        $(selector).autocomplete({
          source: function(request, response) {
            jQuery.ajax({
              url: window.location.origin + '/amt_reports/autocomplete',
              type: 'POST',
              charset: 'UTF-8',
              data: {
                'eckType': eckType,
                'term': request.term,
                'bundle': bundle,
                'field': field,
              },
              success: function(resp) {
                response(resp);
              },
              error: function() {
                alert('Error Occured');
              }
            });
          },
          select: function(event, ui) {
            event.preventDefault();
            // When one student is selected, write only the name of the student.
            // Not the studen last name.
            // This applies only for the Name input field, because we only want
            // to have the first name of the student because the list that comes
            // from the data and needed to be filtered has only first name of the student.
            if (selector == '[name="name"]' || selector == '[name="email"]' || selector == '[name="instructor"]') {
              $(this).val(ui.item.label);
            }
            else {
              $(this).val(ui.item.label + ' (' + ui.item.value + ')' );
            }
          }
        });
      }
    }
  };

}(jQuery));
