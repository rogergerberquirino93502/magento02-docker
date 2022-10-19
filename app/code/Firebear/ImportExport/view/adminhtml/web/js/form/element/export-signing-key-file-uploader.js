/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'Firebear_ImportExport/js/form/element/signing-key-file-uploader'
    ],
    function ($, Element) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    imports : {
                        toggleVisibility: '${$.parentName}.export_source_entity:value'
                    },
                    visible: false
                }
            }
        );
    }
);
