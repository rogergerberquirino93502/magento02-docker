/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Firebear_ImportExport/js/form/element/select',
        'uiRegistry'
    ],
    function ($, _, Select, reg) {
        'use strict';

        return Select.extend(
            {
                defaults: {
                    sourceExt : null,
                    sourceOptions: null,
                    ajaxUrl:"",
                    imports : {
                        changeSource: '${$.ns}.${$.ns}.settings.entity:value'
                    }
                },

                /**
                 * @param {String} value
                 *
                 */
                changeSource: function (value) {
                    var self = this,
                        key = 'option_' + value;

                    if (reg.has(key)) {
                        self.setOptions(reg.get(key));
                        return;
                    }

                    var parent = reg.get(this.ns + '.' + this.ns + '.source_data_replacing_container.source_data_replacing');
                    parent.showSpinner(true);
                    jQuery.ajax({
                        type: "GET",
                        data: "entity_type=" + value,
                        url: this.ajaxUrl,
                        async: false,
                        success: function (options) {
                            localStorage.setItem('list_attributes', JSON.stringify(options));
                            self.setOptions(options);
                            reg.set(key, options);
                            parent.showSpinner(false);
                        }
                    });
                }
            }
        )
    }
);
