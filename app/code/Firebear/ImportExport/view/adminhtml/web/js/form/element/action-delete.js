/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'underscore',
    'Magento_Ui/js/form/element/abstract',
    'Firebear_ImportExport/js/form/element/general'
    ],
    function (_, Acstract, general) {
        'use strict';

        return Acstract.extend(general).extend(
            {
                /**
                 * Delete record instance
                 * update data provider dataScope
                 *
                 * @param {Object} parents
                 */
                deleteRecord: function (parents) {
                    this.value(1);
                    parents[0].deleteRecord(this.containers[0].index, this.containers[0].recordId);

                    return this;
                }
            }
        );
    }
);
