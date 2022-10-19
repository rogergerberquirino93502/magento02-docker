/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Firebear_ImportExport/js/form/element/filter-select',
        'uiRegistry',
        'moment',
        'mageUtils',
        'Magento_Ui/js/lib/validation/validator'
    ],
    function ($, _, Abstract, reg, moment, utils, validator) {
        'use strict';

        return Abstract.extend(
            {
                initialize: function () {
                    this._super();
                    this.entity = this.ns + '.'+ this.ns  +'.settings.entity';
                    return this;
                }
            }
        )
    }
);
