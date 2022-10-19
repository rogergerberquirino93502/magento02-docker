/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/element/multiselect',
        'uiRegistry',
        'mage/translate'
    ],
    function ($, _, Abstract, reg, $t) {
        'use strict';

        return Abstract.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    sourceOptions: null,
                    isShown: false,
                    inverseVisibility: false,
                    visible: false
                },

                toggleVisibility: function (isShown) {
                    this.isShown = isShown !== '1';
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                    if (!this.visible()) {
                        this.value('');
                    }
                },

            }
        )
    }
);
