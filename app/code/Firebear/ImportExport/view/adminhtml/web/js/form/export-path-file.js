/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Firebear_ImportExport/js/form/dep-file',
        'uiRegistry'
    ],
    function (Element, reg) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    listens: {
                        "${$.ns}.${$.ns}.behavior.behavior_field_file_format:value": "onChangeValue"
                    }
                },
                onChangeValue: function (value) {
                    if (this.isShown) {
                        this.value('');
                    }
                }
            }
        );
    }
);
