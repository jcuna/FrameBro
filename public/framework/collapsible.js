/**
 * Created by Jon on 8/6/16.
 */

$(document).ready( function () {

    bro.complete( function() {

        if ($(".collapsed").length) {
            Collapsible.hideAllCollapsed();
        }

        if ($(".collapsible").length) {
            Collapsible.initialize(".collapsible");
        }
    })
});

var Collapsible = {

    element: null,

    initialize: function (cssClass) {
        'use strict';

        var that = this;

        $(cssClass).on('click', function() {
            that.element = $(this);
            if (that.element.hasClass("collapsed")) {
                that.expand();
            } else {
                that.collapse();
            }
        })
    },

    /**
     * expand a collapsed element.
     */
    expand: function () {
        'use strict';
        var targetElement = this.getTargetElement();
        $(targetElement).slideDown();
        this.element.removeClass('collapsed');
    },

    /**
     * collapse elemenet
     */
    collapse: function () {
        'use strict';
        var targetElement = this.getTargetElement();
        $(targetElement).slideUp();
        this.element.addClass('collapsed');
    },

    /**
     * get target element
     *
     * @returns {*}
     */
    getTargetElement: function () {
        'use strict';
        var targetElement = this.element.children()[0];

        if (targetElement === undefined) {
            targetElement = this.element.next()[0];
        }

        return targetElement;
    },

    hideAllCollapsed: function () {
        'use strict';

        $.each($(".collapsed"), function (a, b) {
            Collapsible.element = $(b);
            var target = Collapsible.getTargetElement();
            $(target).hide();
        });
    }
};