/**
 * select-entity
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */
define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function ($, _, Abstract, reg) {
    'use strict';

    /**
     * Select Entity extened only for mapping select Dropdown
     */
    return Abstract.extend({
        defaults: {
            sourceExt: null,
            sourceOptions: null,
            imports: {
                changeSource: '${$.ns}.${$.ns}.settings.entity:value',
                addDependency: '${$.ns}.${$.ns}.behavior.behavior_field_order:value'
            },
            listens: {
                '${$.ns}.${$.ns}.behavior.behavior_field_order:value': 'addDependency'
            }
        },

        /**
         * Intialize function
         * @returns {*}
         */
        initialize: function () {
            this._super();
            return this;
        },

        /**
         * Init Config to parse sourceOptions
         * @param config
         * @returns {initConfig}
         */
        initConfig: function (config) {
            this._super();
            this.sourceOptions = JSON.parse(this.sourceOptions);
            return this;
        },

        /**
         * Change Source Value based on imported Value
         * @param value
         */
        changeSource: function (value) {
            this.sourceExt = value;
            if (value in this.sourceOptions) {
                var newData = [];
                var data = this.sourceOptions[value];
                _.each(data, function (index) {
                    if (!('labeltitle' in index)) {
                        index.labeltitle = index.label;
                    }
                    newData.push(index);
                });
                this.setOptions(newData).addDependency();
            }
        },

        /**
         * Add dependency configs (usually for orders)
         * @param dep
         */
        addDependency: function (dep) {
            if (typeof dep !== 'undefined') {
                var entity = reg.get(this.ns + '.' + this.ns + '.settings.entity');
                var field = reg.get(this.ns + '.' + this.ns + '.behavior.behavior_field_' + entity.value());

                if (field !== undefined) {
                    dep = field.value();
                    if (_.size(dep) > 0) {
                        var valueEl = this.value();
                        var select = reg.get(this.parentName + '.source_data_system');
                        if (select != undefined) {
                            var oldValue = select.value();
                        }
                        var options = this.sourceOptions[this.sourceExt];
                        var newOptions = [];
                        _.each(
                            options,
                            function (value, key) {
                                if (value.dep != undefined) {
                                    if (_.indexOf(dep, value.value) != -1) {
                                        newOptions.push(value);
                                    }
                                }
                            }
                        );
                        this.setOptions(newOptions);
                        this.value(valueEl);
                        if (select != undefined) {
                            select.value(oldValue);
                        }
                    } else {
                        var newOptions = [];
                        this.setOptions(newOptions);
                    }
                }
            }
        }
    });
});
