/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'jquery',
    'ko',
    'underscore',
    'Firebear_ImportExport/js/abstract/element/cron',
    'Magento_Ui/js/lib/validation/validator'
    ],
    function ($, ko, _, Cron, validator) {
        'use strict';

        return Cron.extend(
            {
                getList: function () {
                    var fields = [];
                    var self = this;
                    _.each(
                        this.listFields,
                        function (item, key) {
                            var collect = {label: '', key: self.listFields[key]};
                            fields.push(collect)
                        }
                    );

                    return fields;
                },

                OnBlurEvent: function () {
                },

                validateQty: function (value) {
                    return validator('validate-number', value);
                },
                onExternalValue: function (value) {
                    if (typeof value == 'string') {
                        this.setExpr(value);
                    }
                }
            }
        );
    }
);
