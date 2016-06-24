// Gets submitted data and puts it back inside ckeditor.

CKEDITOR.once( 'instanceReady', function( evt ) {
	var t = ckeditor();
} );
var ckeditor = function() {
	for (var key in this.CKEDITOR.instances) {
		var name = this.CKEDITOR.instances[key].name;
		var content = document.getElementById(name).value;
		var editor = CKEDITOR.instances[key];
		editor.setData(content);
	}
}
