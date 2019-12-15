require(['jquery'], function($) {

/* If we are in a course page */
if ($("body.pagelayout-course").length > 0) {
	var actionTitle = '';

	/******************************************/
	/**** Replace lesson and activity icon ****/
	$.each(['lesson', 'quiz'], function( index, value ) {
		$("li.activity."+value).each(function(){
			var activity_class_list = $(this).attr('class');
			if (activity_class_list.includes('not_completed')) {
				$(this).find('.activityinstance img').attr('src', '/theme/kpdesktop/pix/'+value+'_in_progress.png').css('visibility', 'visible');
			} else if (activity_class_list.includes(' completed')) {
				$(this).find('.activityinstance img').attr('src', '/theme/kpdesktop/pix/'+value+'_completed.png').css('visibility', 'visible');
			} else {
				$(this).find(".activityinstance .dimmed img").attr('src', '/theme/kpdesktop/pix/'+value+'_not_started.png');
			}
		});
	});
}

/*************************************************************/
/**** Select all course by default when creating new user ****/
/**** blocks/iomad_company_admin/company_user_create_form.php ****/
if ($("#page-blocks-iomad_company_admin-company_user_create_form #currentcourses").length > 0) {
	// selecting all the course in the Essential category
	$("#currentcourses").val([7,9,10,11,12,13,14,15,16,17,18]).change();
}


$(".table-rowspanHover td").hover(function() {
	$el = $(this);
	$el.parent().addClass("rowSpanHover");
	if ($el.parent().has('td[rowspan]').length == 0) {
		$el.parent().prevAll('tr:has(td[rowspan]):first').find('td[rowspan]')
			.addClass("rowSpanHover");
	}
}, function() {
	$el.parent().removeClass("rowSpanHover").prevAll('tr:has(td[rowspan]):first')
		.find('td[rowspan]').removeClass("rowSpanHover");
});

});