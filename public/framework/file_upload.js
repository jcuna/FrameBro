/**
 * Created by Jon on 2/12/16.
 * Custom file upload using ajax.
 */
var elem;
$.prototype.upload = function() {

    elem = this;

    $(this).each(function(){
       addFields(this);
    });

// Add events
    $(this).on('change', prepareUpload);

};

// Grab the files and set them to our variable
function prepareUpload(event)
{
    // Variable to store your files
    var file;

    file = event.target.files;
    var data = new FormData();
    var name = this.name;

    $.each(file, function(key, value)
    {
        data.append(name, value);
    });
    data.append( 'dir', 'profiles' );
    bro.Request( '/a-upload', data)

}

bro.complete( function (e) {
    if (bro.responseStatus === 200 && bro.requestUrl === '/a-upload') {
        addProfilePics();
    }
});

var addProfilePics = function() {
    if ($('div.images-container').length === 0) {
        var ulElement = $("<ul>", {class: 'thumbnails'});
        var divElement = $("<div>", {class: 'images-container'});
        var parent = $(elem).parent();
        $(parent).after($(divElement));
        $(divElement).append($(ulElement));
    }

    var fileLoc = ( bro.response !== undefined && bro.response !== null ) ? bro.response : $(elem).val();

    var img = $("<span>", { style: 'background-image: url(' + fileLoc + ')', class: 'thumbnail' });
    $('.thumbnails').append($(img));
    var inputName = $(elem).attr('name');
    if (bro.response) {
        var input = $('<input>').attr({type: 'hidden', name: inputName + '[]'});
        $(elem).after($(input));
        setTimeout(function () {
            input.val(bro.response);
        }, 1);
    }
};

/**
 *
 * @param field
 */
var addFields = function(field) {
    $('input[name^="'+field.name+'["]').each( function() {
        elem = this;
        addProfilePics();
    });
};