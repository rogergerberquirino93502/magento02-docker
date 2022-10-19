/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/element/select'
    ],
    function (Element) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.parentName}.translate_version:value'
                    },
                    isShown: false,
                    inverseVisibility: false,
                    visible: false
                },

                toggleVisibility: function (selected) {
                    this.isShown = selected in this.valuesForOptions;
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                }
            }
        );
    }
);
