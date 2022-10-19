/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/grid/provider',
    ],
    function (Element) {
        'use strict';

        return Element.extend({
            reload: function (options) {
                    this.params = _.extend({}, this.params, JSON.parse(localStorage.getItem('params')) || {});
                    this.params.filters_modifier = window.real_filters_modifier.filters_modifier;
                    return this._super();
            }

        });
    }
);
