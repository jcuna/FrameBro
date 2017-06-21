/**
 * Created by Jon Garcia on 10/5/15.
 * This file handles all ajax requests set up in the backend via the AjaxRequest API
 */
bro = new Framebro();

$(document).ready( function () {
    'use strict';

    $.ajaxSetup({
        cache: true
    });

    var modalLoading = ".modal-loading";

    /**
     * bind elements to the dom()
     */
    bindElements();
    bindRequest();

    $(document).on({

        ajaxStart: function () {
            if ($(modalLoading).length) {
                $(modalLoading).show();
            }
        },

        ajaxStop: function () {
            if ($(modalLoading).length) {
                $(modalLoading).hide();
            }
        }
    });
});

var bindElements = function () {
    'use strict';
    /**
     * make every element with class of datepicker a datepicker element.
     */
    $( ".datepicker" ).datepicker({
        changeYear: true,
        yearRange: "c-60:c-00"
    });

    loadAddMore();

    /**
     * if text item is of class text-list, let's make it add as list
     */
    if ($.fn.textList !== undefined) {
        $(".text-list").textList();
    }

    $(".file-upload").upload();

};

/**
 * loads the addMore jQuery plugin
 */
var loadAddMore = function() {
    'use strict';

    var selectors = $(".add-more, .addMore");

    if (selectors.length !== 0 && $.fn.addMore === undefined) {
        $.getScript("/framework/addMore.js", function (data, textStatus, jqxhr) {
            selectors.addMore('add-more-button');
        });
    } else if (selectors.length !== 0) {
        selectors.addMore('add-more-button');
    }
};

/**
 * bind events
 * */
var bindRequest = function () {
    "use strict";

    $.each(bro.settings.Ajax, function () {
        // the selector of the current element event being bound.
        var selector = this.selector;

        //holds the current element
        bro.requests[selector] = this;

        bro.resolveBinding(selector);

    });
};

/**
 * after ajax request.
 */
bro.complete( function (e) {
    'use strict';

    var selector = bro.currentSelector;

    if (bro.responseStatus === 200 && bro.requestUrl === '/AjaxController') {

        //if there's a custom callback.
        if ( bro.requests[selector].jsCallback ) {

            var namespaces = bro.requests[selector].jsCallback.split(".");
            var func = namespaces.pop();
            var context = window;
            for (var i = 0; i < namespaces.length; i++) {
                context = context[namespaces[i]];
            }
            return window[func].call(func, bro.response);

        } else {

            // if there's a redirect message, drop everything and do it.
            if (typeof bro.responseRedirect !== "undefined") {
                window.location.href = bro.responseRedirect;
            } else {

                /**
                 * otherwise, let's continue updating the DOM.
                 * @type {*|string|string}
                 */

                var jsMethod;
                 // if we want to override jquery method for this call
                if (typeof bro.jQueryMethodOverride !== "undefined") {
                    jsMethod = bro.jQueryMethodOverride;
                } else {
                    jsMethod = bro.requests[selector].jQueryMethod;
                }

                var effect = bro.requests[selector].effect;
                var wrapper = $(bro.requests[selector].wrapper);
                var el = $(bro.response).css('display', 'none');

                var $alertSelector = '#window-alerts';

                //within an ajax call, alerts return in the
                // new generated element from the server.
                $($alertSelector).remove();

                wrapper[jsMethod](el);
                $(el)[effect]();

                //if returned a modal window, alert is in the back,
                // so let's bring it forward.
                if ($(".modal-window").length) {
                    var alert = $($alertSelector).detach();
                    $(".modal-content").prepend(alert);
                }
            }

            // reset a few values and bind events to DOM again.
            bro.responseStatus = bro.requests[selector] = bro.response = null;
            bindRequest();
            bindElements();
            bindCKeditor();
        }
    }
});


/**
 * Object Cuna Framework
 * @constructor
 */
function Framebro() {
    'use strict';
    return {
        /**
         * event to fire after ajax request.
         * @type {Event}
         */
        requestComplete: new Event('requestComplete'),

        /**
         * Sends ajax request to server and dispatches event.
         *
         * @param url
         * @param requestData
         * @param method
         * @param selector
         * @returns {Request}
         * @constructor
         */
        Request: function (url, requestData, method, selector) {

            var that = this;
            that.response = null;
            that.responseStatus = null;
            var contentType = (requestData instanceof FormData) ? false : 'application/json';
            method = method || 'POST';
            $.ajax({
                method: method,
                url: url,
                data: requestData,
                cache: false,
                dataType: 'json',
                processData: false, // Don't process the files
                contentType: contentType,
                error: function(error) {
                    that.handleError(error, selector);
                }
            }).done(function (response) {
                that.response = response.data;
                that.responseStatus = response.status;
                that.responseRedirect = response.redirect;
                that.jQueryMethodOverride = response.jQueryMethodOverride;
                that.requestUrl = url;
                that.currentSelector = selector;
                document.dispatchEvent(that.requestComplete);
            });
            return this;
        },

        requestUrl: '',
        /**
         * this page's ajax settings
         */
        settings: {},
        /**
         * the current request params
         */
        requests: {},
        /**
         * the response data after an ajax call
         */
        response: null,
        /**
         * the response status code after an ajax call
         */
        responseStatus: null,

        /**
         * if there's a response redirect, we redirect to that location
         */
        responseRedirect: null,

        /**
         * Override current jQuery Method
         */
        jQueryMethodOverride: null,

        /**
         * The current jquery selector
         */
        currentSelector: null,

        /**
         * event listener
         * @param callback
         */
        complete: function ( callback ) {

            var that = this;
            document.addEventListener('requestComplete', function (e) {
                if (typeof callback === 'function') {
                    callback(e);
                    that.jumpToAlert();
                }
            }, false);
        },

        /**
         * Handles errors returned from the servers
         *
         * @param selector
         * @param error
         */
        handleError: function ( error, selector ) {
            var jsMethod = bro.requests[selector].jQueryMethod;
            var wrapper = $(bro.requests[selector].wrapper);
            var el = error.responseText;
            wrapper[jsMethod](el);
        },

        /**
         * if there's an alert in the screen, let's jump to it.
         */
        jumpToAlert: function ()  {

            if ($("#window-alerts").length > 0) {
                var url = location.href;
                location.href = "#window-alerts";
                history.replaceState(null, null, url);
            }
        },

        /**
         * Sends off one or more selectors to the binding method.
         *
         * @param selector
         * @returns {boolean}
         */
        resolveBinding: function (selector) {

            if (this.hasMultipleElements(selector)) {
                return true;

            } else {
                // else let's continue binding.
                this.bindElement(selector);
            }
        },

        /**
         * If more than one selectors have been registered within a single AjaxCall register
         *
         * @param origElement
         * @returns {boolean}
         */
        hasMultipleElements: function (origElement) {
            var that = this;
            var elements = origElement.split(',');
            if (elements.length > 1) {
                $.each(elements, function (k, element) {
                    var trimmedElement = element.trim();
                    that.requests[trimmedElement] = that.requests[origElement];
                    that.bindElement(trimmedElement);
                });
                return true;
            }
        },

        /**
         * Binds a selector
         *
         * @param selector
         * @returns {boolean}
         */
        bindElement: function (selector) {

            var that = this;

            // In some cases, we may want to bind events for elements not yet in the dom.
            // Since there're not in the dom, we will not bind them until later.
            if ( $(selector).length === 0 ) {
                return true;
            }
            // If event is already bound, we shouldn't bind it again.
            var objEvent = $._data( $(selector)[0], 'events' );

            if ( objEvent !== undefined && objEvent.hasOwnProperty(that.requests[selector].event ) ) {
                return true;
            }

            $(selector).on(that.requests[selector].event, function (e) {
                e.preventDefault();

                //weather this is a form.
                var isForm = $(this).closest('form').is('form') ? $(this).closest('form') : false;

                var processData = {};
                var request;

                // if is a form, let's add all element values to a form object on submit.
                if (isForm) {
                    $(isForm).on('submit', function (e) {

                        processData = new FormData();
                        $.each(e.target.elements, function ( input, value ) {
                            if ( value.type === "checkbox" || value.type === 'radio' ) {
                                if ( value.checked === true ) {
                                    processData.append(value.name, value.value);
                                }
                            } else if ( value.className.indexOf('ckeditor') > -1 ) {
                                var thisValue = CKEDITOR.instances[value.name].getData();
                                processData.append(value.name, thisValue);
                            } else {
                                processData.append(value.name, value.value);
                            }
                        });
                        processData.append('ajax', JSON.stringify(that.requests[selector]));
                        request = processData;

                        e.preventDefault();
                    });
                    $(isForm).submit();

                    // if it's not a form, then let's send a json request with all the data we can collect
                } else {
                    processData['element'] = $(this).prop("outerHTML");
                    $.extend(processData, { ajax: that.requests[selector] });
                    request = JSON.stringify(processData);
                }

                that.Request('/AjaxController', request, that.requests[selector].httpMethod, selector);
            });
        }
    }
}

var bindCKeditor = function () {
    $(".ckeditor").each(function (){
        CKEDITOR.replace( this.name );
    });
};
