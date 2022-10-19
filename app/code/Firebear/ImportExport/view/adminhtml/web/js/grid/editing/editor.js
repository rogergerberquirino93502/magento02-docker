/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'jquery',
    'underscore',
    'mageUtils',
    'uiLayout',
    'mage/translate',
    'Magento_Ui/js/grid/editing/editor'
    ],
    function ($, _, utils, layout, $t, Editor) {
        'use strict';

        return Editor.extend(
            {
                defaults: {
                    templates: {
                        record: {
                            component: 'Firebear_ImportExport/js/grid/editing/record'
                        },
                    },
                }
            }
        );
    }
);
