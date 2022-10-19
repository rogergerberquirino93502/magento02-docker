/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'Magento_Ui/js/form/element/abstract'
    ],
    function (Element) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    valuesForOptions : [],
                    imports          : {
                        toggleVisibility: '${$.parentName}.export_source_entity:value'
                    },
                    isShown          : false,
                    inverseVisibility: false,
                    visible:false
                },

                toggleVisibility: function (selected) {
                    this.isShown = selected in this.valuesForOptions;
                    if (selected == 'txt') {
                        this.validation.max_text_length = 100;
                    } else {
                        this.validation.max_text_length = 1;
                    }
                    if (selected != 'xml') {
                        this.validate();
                    }
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                }
            }
        );
    }
);
