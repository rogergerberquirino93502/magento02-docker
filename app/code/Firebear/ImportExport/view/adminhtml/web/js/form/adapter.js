/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    var buttons = {
        'reset':            '#reset',
        'save':             '#save',
        'saveAndContinue':  '#save_and_continue',
        'saveAndRun':       '#save_and_run'
    },
        selectorPrefix = '',
        eventPrefix;

    /**
     * Initialize listener.
     *
     * @param {Function} callback
     * @param {String} action
     */
    function initListener(callback, action)
    {
        var selector    = selectorPrefix ? selectorPrefix + ' ' + buttons[action] : buttons[action],
            elem        = $(selector)[0];

        if (!elem) {
            return;
        }

        if (elem.onclick) {
            elem.onclick = null;
        }

        $(elem).on('click' + eventPrefix, callback);
    }

    /**
     * Destroy listener.
     *
     * @param {String} action
     */
    function destroyListener(action)
    {
        var selector    = selectorPrefix ? selectorPrefix + ' ' + buttons[action] : buttons[action],
            elem        = $(selector)[0];

        if (!elem) {
            return;
        }

        if (elem.onclick) {
            elem.onclick = null;
        }

        $(elem).off('click' + eventPrefix);
    }

    return {

        /**
         * Attaches events handlers.
         *
         * @param {Object} handlers
         * @param {String} selectorPref
         * @param {String} eventPref
         */
        on: function (handlers, selectorPref, eventPref) {
            selectorPrefix = selectorPrefix || selectorPref;
            eventPrefix = eventPref;
            _.each(handlers, initListener);
            selectorPrefix = '';
        },

        /**
         * Removes events handlers.
         *
         * @param {Object} handlers
         * @param {String} eventPref
         */
        off: function (handlers, eventPref) {
            eventPrefix = eventPref;
            _.each(handlers, destroyListener);
        }
    };
});
