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
        'mage/translate'
    ],
    function ($, _, Acstract, reg, $t) {
        'use strict';

        return Acstract.extend(
            {
                defaults: {
                    sourceExt       : null,
                    sourceOptions: null,
                    imports      : {
                        changeSource: '${$.ns}.${$.ns}.settings.entity:value'
                    }
                },
                initConfig  : function (config) {
                    var options = JSON.parse(localStorage.getItem('options'));
                    if (options) {
                        config.options = options;
                    }
                    this._super();
                    this.sourceOptions = JSON.parse(this.sourceOptions);
                    return this;
                },
                changeSource: function (value) {
                    this.sourceExt = value;
                    var oldValue = this.value();
                    if (value in this.sourceOptions) {
                        var options = this.sourceOptions[value];
                        options.unshift({label: $t('Select A Column'), value: ''});
                        this.setOptions(this.sourceOptions[value]);
                    }
                    this.value(oldValue);
                }
            }
        )
    }
);
