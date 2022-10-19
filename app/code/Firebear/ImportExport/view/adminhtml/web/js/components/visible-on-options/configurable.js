/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/element/abstract',
    ],
    function (Abstract) {
        'use strict';
        return Abstract.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.parentName}.configurable_type:value'
                    },
                    isShown: false,
                    inverseVisibility: false
                },

                /**
                 * Toggle visibility state.
                 *
                 * @param {Number} selected
                 */
                toggleVisibility: function (selected) {
                    this.isShown = (selected in this.valuesForOptions);
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                }
            }
        );
    }
);
