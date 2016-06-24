(function ($) {
	$(document).ready(function(){
		cat = '' ;
		$("#new_category").keyup(function(){
			getCat();
		});
		$('#add_category_button').click(function(){
			getCat();
			catCreate(cat);
			return;
		});

		$('#new_category').keypress(function (e) {
			var key = e.which;
			if (key == 13) { //enter key code
				catCreate(cat);
				return;
			}
		});
	});
		var catCreate = function(cat) {
			if (cat !== '') {
				$("div[id='new-category']").removeClass('has-error');
				$(".cat-error").remove();
				$("#new_category").css('position', 'absolute');
				$("#new_category").animate({
					bottom: 200,
					height: 0,
					width: 0,
					opacity: 0.5
				}, 600, "linear", function() {
					var url = '/category/add';
					var posting = $.post( url, { category: cat } );
					posting.done(function(data) {
						result = $.parseJSON(data);
						if (result.response == 'fail') {
							var newElement = $('<div class="full-width float-left cat-error alert alert-danger" role="alert">');
							var elemSpan = $('<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>');
							var elemSpan2 = $('<span class="sr-only">Error:</span>' + ' ' + result.content + '</div>');
							$(newElement).append(elemSpan);
							$(newElement).append(elemSpan2);
						   	$(newElement).appendTo('.col-md-offset-7');
						}
						else {
	                        $('#category-wrapper').fadeOut(300, function(){
	                        	var cats = result.content;
	                        	$('#category-wrapper').empty();
	                        	$('#category-wrapper').append(cats);
	                        	$('	#category-wrapper').fadeIn().delay(300);
                        	});
						}
					});
					$("#new_category").removeAttr('style');
				});
			}
			else {
				$("div[id='new-category']").addClass('has-error');
			}
			$("#new_category").val('');
		}
		var getCat = function() {
			cat = $("#new_category").val();
			cat = $.trim(cat);
		}
})(jQuery);