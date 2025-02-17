jQuery(document).ready(function(){
	if(jQuery('.timeline .timeline_nav')) {
		jQuery('.timeline .timeline_nav').stickyfloat({duration: 500});
	}
	
	jQuery('.timeline .section').each(function(){
		jQuery(this).afterScroll(function(){
			// After we have scolled past the top
			var year = jQuery(this).attr('id');
			jQuery('ol.timeline_nav li').removeClass('current');
			jQuery('ol.timeline_nav li#menu_year_' + year).addClass('current');
		});
	});
});