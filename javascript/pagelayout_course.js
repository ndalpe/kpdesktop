require(['jquery'], function($) {

	/* If we are in a course page */
	if ($("body.pagelayout-course").length > 0) {
		var actionTitle = '';

		$.each(['lesson', 'quiz'], function( index, value ) {
			$("li.activity."+value).each(function(){
				actionTitle = $(this).find('span.actions img').attr('title');
				if (typeof actionTitle !== 'undefined') {
					if (actionTitle.match("^Completed")) {
						$(this).find('.activityinstance img').attr('src', '/theme/kpdesktop/pix/'+value+'_completed.png');
					}

					if (actionTitle.match("^Not completed")) {
						$(this).find('.activityinstance img').attr('src', '/theme/kpdesktop/pix/'+value+'_in_progress.png');
					}
				} else {
					$(this).find(".activityinstance .dimmed img").attr('src', '/theme/kpdesktop/pix/'+value+'_not_started.png');
				}
			});
		});
	}
});