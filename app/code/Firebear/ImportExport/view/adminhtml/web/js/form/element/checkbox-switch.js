/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/form/element/single-checkbox',
], function (SingleCheckbox) {
    'use strict';

    return SingleCheckbox.extend({
        defaults: {
            valuesForOptions: [],
            sourceOptions: null,
            isShown: false,
            inverseVisibility: false,
            imports: {
                toggleVisibility: '${$.ns}.${$.ns}.settings.entity:value'
            },
            visible: false
        },

        toggleVisibility: function (selected) {
            this.isShown = (selected in this.valuesForOptions);
            this.visible(this.isShown ? true : false)
        },
    });
});
