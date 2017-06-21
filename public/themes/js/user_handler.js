(function ($) {
    'use strict';
	$(document).ready( function() {
		$('.delete-user').click( function() {

			var username = $('.delete-user').attr('data-collect');
			var url = '/users/deleteCurrentUser';

			 if (confirm('Are you sure?')) {

			 	$.post(url, {user: username}).then( function (result) {
                    $('.user-profile, .delete-user-button').remove();
                    var element = $('.user-container');
                    element.append(result.content);
                    element.fadeIn(200).fadeOut(400).fadeIn(200);
                    if (result.status != 'fail') {
                        setTimeout(function() {
                            window.location.href = "/users/all";
                        }, 2000);
                    }
                });
			 }
		});
	});
})(jQuery);