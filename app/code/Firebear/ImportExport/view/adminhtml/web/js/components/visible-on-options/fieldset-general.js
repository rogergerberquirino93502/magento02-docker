/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/components/fieldset',
        'uiRegistry'
    ],
    function (Fieldset, reg) {
        'use strict';
        return Fieldset.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.parentName}.settings.entity:value',
                        mapVisible: '${$.ns}.${$.ns}.source.check_button:showMap'
                    },
                    openOnShow: true,
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
                    var button = reg.get(this.ns + "." + this.ns + ".source.check_button");
                    var bool = 1;
                    if (button !== undefined) {
                        if (button.component == 'Firebear_ImportExport/js/components/button-check') {
                            bool = button.showMap();
                        }
                    }
                    this.visible((this.isShown == true && bool == 1) ? true : false);
                },
                initConfig: function () {
                    this._super();
                    return this;
                },
                mapVisible: function (value) {
                    this.visible((this.isShown == true && value == 1) ? true : false);
                }
            }
        );
    }
);
