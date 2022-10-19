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
                changeSource: function (value) {
                    var self = this;
                    this.sourceExt = value;
                    var oldValue = this.value();
                    var data = JSON.parse(localStorage.getItem('list_filtr'));
                    var exists = 0;
                    if (value == 'order') {
                        value = 'orders';
                    }
                    if (data !== null && typeof data === 'object') {
                        if (value in data) {
                            exists = 1;
                            self.setOptions(data[value]);
                            self.value(oldValue);
                        }
                    }
                    if (exists == 0) {
                        var parent = reg.get(this.ns +'.' + this.ns + '.source_data_filter_container.source_filter_map');
                        parent.showSpinner(true);
                        $.ajax({
                            type: "POST",
                            url: this.ajaxUrl,
                            data: {entity: value},
                            async:false,
                            success: function (array) {
                                var newData = JSON.parse(localStorage.getItem('list_filtr'));
                                if (newData === null) {
                                    newData = {};
                                }
                                newData[value] = array;
                                localStorage.setItem('list_filtr', JSON.stringify(newData));
                                self.setOptions(array);
                                self.value(oldValue);
                                parent.showSpinner(false);
                            }
                        });
                    }
                }
            }
        )
    }
);
