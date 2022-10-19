/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Firebear_ImportExport/js/form/element/additional-select',
        'uiRegistry',
        'mage/translate',
        'mageUtils'
    ],
    function ($, _, Acstract, reg, $t, utils) {
        'use strict';

        return Acstract.extend(
            {
                defaults: {
                    sourceOptions: null
                },
                initialize: function () {
                    this._super();
                    var self = this;
                    var options = [];
                    if (typeof (localStorage.getItem('columns')) !== "undefined"
                        && localStorage.getItem('columns') !== null
                        && localStorage.getItem('columns') !== 'undefined'
                    ) {
                        options = JSON.parse(localStorage.getItem('columns'));
                    }
                    self.updateOptions(options);
                    return this;
                },
                initConfig: function (config) {
                    this._super();
                    this.sourceOptions = config.options;

                    return this;
                },
                normalizeData: function (value) {
                    return utils.isEmpty(value) ? '' : value;
                },
                updateOptions: function (options) {
                    var newOptions = [];
                    newOptions.push({label: $t('Select A Column'), value: ''});
                    _.each(
                        options,
                        function (value) {
                            newOptions.push({label: value, value: value});
                        }
                    );

                    this.setOptions(newOptions);

                    var oldVal = this.value();

                    if (oldVal !== 'undefined'
                        && oldVal !== ""
                    ) {
                        this.value(oldVal);
                        localStorage.setItem('configurable_field', oldVal);
                    } else {
                        this.value(localStorage.getItem('configurable_field'));
                    }
                },
                onChangeValue: function (value) {
                    if (value !== 'undefined'
                        && value !== ""
                    ) {
                        this.value(value);
                        localStorage.setItem('configurable_field', value);
                    } else {
                        this.value(localStorage.getItem('configurable_field'));
                    }
                }
            }
        )
    }
);
