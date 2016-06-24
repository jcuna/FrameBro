(function ($) {
	$(document).ready(function() {
		$('.delete-user').click(function() {
			var username = $('.delete-user').attr('data-collect');
			var url = '/users/deleteCurrentUser'
			 if (confirm('Are you sure?')) {
			 	var posting = $.post( url, { user: username } );
			 	$('.user-profile, .delete-user-button').remove();
			 	posting.done(function(data) {
			 		var result = $.parseJSON(data);

			    	$('.user-container').append(result.content);
			    	$('.user-container').fadeIn(200).fadeOut(400).fadeIn(200);
				  	if (result.response != 'fail') {
					   	setTimeout(function() {
						 	window.location.href = "/users/all";
						}, 2000);
					}
			  	});
			 }
		});
	});
})(jQuery);