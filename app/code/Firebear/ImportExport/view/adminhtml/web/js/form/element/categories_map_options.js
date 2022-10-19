/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Firebear_ImportExport/js/form/element/selectize',
        'uiRegistry',
        'mage/translate'
    ],
    function ($, _, Acstract, reg, $t) {
        'use strict';

        return Acstract.extend(
            {
                defaults  : {
                    sourceOptions: null,
                    listens      : {
                        'value': 'onSelectValueChange'
                    }
                },
                initialize: function () {
                    this._super();
                    var self = this;
                    reg.get(
                        this.parentName,
                        function (object) {
                            var data = reg.get(self.provider).data;
                            var record = reg.get(object.parentName);
                            if (_.size(record.prevData)) {
                                _.each(
                                    record.prevData,
                                    function (element) {
                                        var currentElementData = object.data();
                                        if (currentElementData.record_id == element.record_id) {
                                            self.value(element.source_category_data_import);
                                        }
                                    }
                                )
                            } else if ("special_map_category" in data && _.size(data.special_map_category) > 0) {
                                _.each(
                                    data.special_map_category.source_data_categories_map,
                                    function (element) {
                                        var currentElementData = object.data();
                                        if (currentElementData.record_id == element.record_id) {
                                            self.value(element.source_category_data_import);
                                        }
                                    }
                                )
                            }
                        }
                    );
                    return this;
                },

                initConfig: function (config) {
                    if (localStorage.getItem('categories') == 'undefined') {
                        var options = null;
                    } else {
                        var options = JSON.parse(localStorage.getItem('categories'));
                    }
                    var newOptions = [];
                    newOptions.push({label: $t('Select Path'), value: ''});
                    _.each(
                        options,
                        function (value) {
                            newOptions.push({label: value, value: value});
                        }
                    );
                    config.options = newOptions;
                    this._super();
                    this.sourceOptions = config.options;

                    return this;
                },
            }
        )
    }
);
