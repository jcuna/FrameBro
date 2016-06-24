/**
 * Created by Jon on 2/11/16.
 * This is a jQuery plugin that allows to use text fields as list.
 */
$.prototype.textList = function() {

    var addUlElement = function(thisItem, name) {
        var newElement = name.match(/([a-zA-Z_-]+)/i)[0];
        var ulElement = "ul." + newElement;
        if ($(ulElement).length === 0) {
            var ul = $("<ul>", {class: newElement + ' text-list-item'});
            $(thisItem).after($(ul));
        }
        return ulElement;
    };

    var parent = this;
    $(this).each(function() {

        var name = this.name;
        var arrThisField = { class: name };
        var thisItem = "input[name='" + this.name + "']";
        var ulElement = addUlElement(thisItem, this.name);

        $('input[name^="'+arrThisField.class+'["]').each( function() {
            $(ulElement).append($("<li>", { text: $(this).val() }));
        });
        parent[name] = arrThisField;
    });

    /**
     * If the user looses focus, it moves the item for them
     */
    $(this).focusout(function() {
        var customE = jQuery.Event("keydown");
        customE.which = 13;
        customE.keyCode = 13;
        $(this).trigger(customE);
    });


    $( this ).on( 'keydown keyup', function ( e ) {
        var input_name = this.name;
        var thisItem = "input[name='" + input_name + "']";
        var that = this;
        if ( e.type === 'keyup' ) {
            that.textValue = $( thisItem ).val();
        }

        if (( e.type === 'keydown' && ( e.keyCode === 13 || e.keyCode === 188 )) || e.customTag ) {
            e.preventDefault();
            var ulElement = addUlElement(thisItem, input_name);
            if ( that.textValue ) {
                $(ulElement).append($("<li>",
                    {text: that.textValue}
                ));
                var inputElement = $('<input>').attr({type: 'hidden', name: input_name + '[]'});
                $(that).after(inputElement);

                setTimeout(function () {
                    inputElement.val(that.textValue);
                }, 1);
                $(thisItem).val("");
            }
        }
    });
};