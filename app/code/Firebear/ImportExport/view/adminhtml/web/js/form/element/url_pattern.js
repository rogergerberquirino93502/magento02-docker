/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry'
], function ($, _, Abstract, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            isShown: false,
            visible: false,
            imports: {
                toggleEntityVisibility: '${$.ns}.${$.ns}.settings.entity:value'
            },
        },

        toggleVisibility: function (isShown) {
            this.isShown = isShown !== '0';
            this.visible(this.isShown);
        },

        toggleEntityVisibility: function (selected) {
            this.isShown = (selected in this.valuesForOptions);
            let productUrlPattern = registry.get(this.parentName + '.enable_product_url_pattern').visible();
            this.visible(!!this.isShown && productUrlPattern)
        },
    });
});

