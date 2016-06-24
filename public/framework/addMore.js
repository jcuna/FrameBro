/**
 * Created by Jon Garcia on 4/10/16.
 *
 * jQuery plugin to allow to add more fields of the type that has the class add-more or addMore
 *
 * @param cssClass
 */
$.prototype.addMore = function(cssClass) {
    'use strict';

    //selector is a jquery object and we only do it to the last element.
    var selector = this;

    var css = cssClass || 'btn btn-default';

    var addMore = new AddMore(selector, css);

    //initialize
    addMore.initialize();
    //add add more button
    addMore.buttonsSetup();
};

/**
 *
 * @param selector
 * @param cssClass
 * @returns AddMore
 * @constructor
 */
function AddMore (selector, cssClass) {
    'use strict';

    var clone;

    return {

        button: $("<button>", {
            id: 'add-more-button',
            text: 'Add more',
            class: cssClass
        }),

        remove: $("<button>", {
            id: 'remove-more-button',
            text: 'Remove',
            class: cssClass
        }),

        index: 0,

        // initializes field setup
        initialize: function () {

            var that = this;

            var inputFields = 'input, select, radio, checkbox';

            if (selector.is(inputFields) && selector.fieldName !== '') {

                this.fieldToArrayField(selector);

            } else {

                var childSelector = selector.find(inputFields);

                if (childSelector.length === 1) {

                    this.fieldToArrayField(childSelector);

                } else if (childSelector.length > 1) {

                    var count = childSelector.length / selector.length;
                    var i = 0;

                    childSelector.each(function(a, b) {

                        if (i === count) {
                            that.index++;
                            i = 0;
                        }
                        that.fieldToArrayField($(b), true);
                        i++
                    });
                }
            }

            this.index++;
        },

        /**
         * Fields must be array since they will contain multiple values.
         * @param element
         * @param withIndex
         */
        fieldToArrayField: function (element, withIndex)
        {
            var bracketsMatch = /\[\d+?\]|\[\]/g;
            var fieldName = element[0].name;
            var appended = '[]';
            var hasArrayBrackets = fieldName.match(bracketsMatch);

            if (withIndex === undefined) {

                element[0].name = hasArrayBrackets ? fieldName : fieldName + appended;

            } else {

                appended = '[' + this.index + ']';

                if (hasArrayBrackets) {

                    element[0].name = fieldName.replace(bracketsMatch, appended);
                    //console.log(element[0].name)

                } else {

                    element[0].name = fieldName + appended;
                }
            }
        },

        // setup add more button
        buttonsSetup: function () {
            'use strict';

            var that = this;

            selector.last().after(this.button);

            this.button.after(this.remove);

            this.button.on('click', function (e) {
                e.preventDefault();
                that.addField();
            });

            this.remove.on('click', function (e) {
                e.preventDefault();
                $(clone).remove();
            });
        },

        // add extra field by cloning the original field.
        addField: function () {

            clone = selector.first().clone();

            clone.find('input').val("");

            selector.first().after(clone);

            this.initialize();
        }
    }
}