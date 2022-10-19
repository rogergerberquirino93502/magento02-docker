/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/form/element/single-checkbox',
    'uiRegistry',
    'underscore'
], function (SingleCheckbox, registry, _) {
    'use strict';

    return SingleCheckbox.extend({

        /**
         * @inheritdoc
         */
        onUpdate: function () {
            var self = this;
            var fieldset = registry.get(this.parentName);
            var formElements = [
                'configurable_create',
                'configurable_type',
                'configurable_field',
                'configurable_variations',
                'copy_simple_value'
            ];

            _.each(fieldset.elems(), function (elem) {
                if (formElements.indexOf(elem.index) >= 0) {
                    elem.visible(self.checked());
                }
            });

            var type = registry.get(this.parentName + '.configurable_type');
            var symbol = registry.get(this.parentName + '.configurable_symbols');
            var part = registry.get(this.parentName + '.configurable_part');
            if (self.checked()) {
                symbol.toggleVisibility(type.value());
                part.toggleVisibility(type.value());
            } else {
                symbol.toggleVisibility(0);
                part.toggleVisibility(0);
            }


            return this._super();
        },
    });
});
