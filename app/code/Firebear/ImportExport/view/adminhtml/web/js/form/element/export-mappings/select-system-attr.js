/**
 * select-system-attr
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */
define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'domReady!'
], function ($, _, Abstract, reg) {
    'use strict';

    /**
     * Select dropdown ajax for systemAttributes
     */
    return Abstract.extend({
        defaults: {
            sourceExt: null,
            sourceOptions: null,
            imports: {
                changeSource: '${$.parentName}.source_data_entity:value'
            },
            ajaxUrl: '',
        },

        /**
         *
         * @returns {initialize}
         */
        initialize: function () {
            var self = this;
            this._super();

            self.value.subscribe(function () {
                self.updateValueOptions();
            });

            return this;
        },

        /**
         *
         * @returns {updateValueOptions}
         */
        updateValueOptions: function () {
            var self = this;
            var exportAttr = reg.get(this.parentName + '.source_data_export');
            if (self.value()) {
                if (!exportAttr.value() && exportAttr.initialValue) {
                    exportAttr.value(exportAttr.initialValue);
                } else if (!exportAttr.value() && !exportAttr.initialValue) {
                    exportAttr.value(self.value());
                }
            } else {
                exportAttr.value('');
            }
            return this;
        },

        /**
         *
         * @param config
         * @returns {*}
         */
        initConfig: function (config) {
            this._super();
            this.sourceOptions = JSON.parse(this.sourceOptions);
            return this;
        },

        /**
         *
         * @param entityValue
         */
        changeSource: function (entityValue) {
            var self = this;
            var oldValue = self.value();
            var data = JSON.parse(localStorage.getItem('list_values'));
            var exists = 0;
            if (data !== null && typeof data === 'object') {
                if (entityValue in data) {
                    if (data[entityValue].error === true) {
                        localStorage.removeItem('list_values');
                        data = null;
                        exists = 0;
                    } else {
                        exists = 1;
                        self.setOptions(data[entityValue]);
                        self.value(oldValue);
                    }
                }
            }
            if (exists === 0) {
                var parent = reg.get(this.ns + '.' + this.ns + '.source_data_map_container.source_data_map');
                parent.showSpinner(true);
                var param = {
                    entity: entityValue,
                    form_key: window.FORM_KEY
                };
                $.ajax({
                    type: "POST",
                    url: this.ajaxUrl,
                    data: param,
                    success: function (response, status) {
                        if (status === "success") {
                            var newData = JSON.parse(localStorage.getItem('list_values'));
                            if (newData === null) {
                                newData = {};
                            }
                            newData[entityValue] = response;
                            localStorage.setItem('list_values', JSON.stringify(newData));
                            self.setOptions(response);
                            self.value(oldValue);
                            parent.showSpinner(false);
                        }
                    }
                });
            }
        }
    });
});
