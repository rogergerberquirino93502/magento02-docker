/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/components/insert-listing',
        'uiRegistry'
    ],
    function (Listing, reg) {
        'use strict';
        return Listing.extend({
            defaults: {
                template: 'Firebear_ImportExport/form/insert',
            },
            render: function (params) {
                var data = reg.get(this.provider).data;
                if (data !== undefined && data.entity_id !== undefined) {
                    this.params.filters_modifier.job_id.value = data.entity_id;
                    this._super(params);
                    params = _.extend({}, this.params, params || {});
                    delete(params.namespace);
                    localStorage.setItem('params', JSON.stringify(params));
                    window.real_filters_modifier = this.params = _.extend({}, this.params, JSON.parse(localStorage.getItem('params')) || {});
                }
                return this;
            },
        })
    }
);
