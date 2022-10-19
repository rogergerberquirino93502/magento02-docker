/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'underscore',
        'mageUtils',
        'uiLayout',
        'Magento_Ui/js/grid/editing/record'
    ],
    function (_, utils, layout, Record) {
        'use strict';

        return Record.extend(
            {
                defaults: {
                    templates: {
                        fields: {
                            multitext: {
                                component: 'Firebear_ImportExport/js/grid/columns/cron',
                                template: 'Firebear_ImportExport/grid/cells/cron'
                            },
                            frequency: {
                                component: 'Firebear_ImportExport/js/grid/columns/frequency-select',
                                template: 'Firebear_ImportExport/grid/cells/frequency-select',
                                options: '${ JSON.stringify($.$data.column.options) }'
                            }
                        }
                    }
                }
            }
        );
    }
);
