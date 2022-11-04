(function ($) {
    
  jQuery('.view-filters').prepend( '<h1 id="filtersTitle" class="filterViewCollapse"> <span class="collapseBtn"> <img src="core/misc/icons/bebebe/hamburger.svg"> </span> Filters </h1>' );
  jQuery(document).on( 'click', '.filterViewCollapse', function(e){
    jQuery('.view-filters .views-exposed-form').toggle('slow');
  })
 
}(jQuery));
