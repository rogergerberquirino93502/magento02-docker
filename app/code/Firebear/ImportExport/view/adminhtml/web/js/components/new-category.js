/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define([
    'Magento_Ui/js/form/element/ui-select',
    'jquery',
    'uiRegistry'
], function (Select,jQuery, reg) {
    'use strict';

    return Select.extend({
        defaults: {
            ajaxUrl:""
        },
        /**
         * Parse data and set it to options.
         *
         * @param {Object} data - Response data object.
         * @returns {Object}
         */
        initConfig: function (config) {
            this._super(config);
            var data = JSON.parse(localStorage.getItem('list_categories'));
            var parent = reg.get(this.ns +'.' + this.ns + '.source_data_map_container_category.source_data_categories_map');
            //parent.showSpinner(true);

            if (data !== null && typeof data === 'object') {
                config.options = data;
            } else {
                jQuery.ajax({
                    type: "GET",
                    url: this.ajaxUrl,
                    async: false,
                    success: function (array) {
                        config.options = array;
                        localStorage.setItem('list_categories', JSON.stringify(array));
                        parent.showSpinner(false);
                    }
                });
            }
            this._super(config);

            return this;
        },
        setParsed: function (data) {
            var option = this.parseData(data);

            if (data.error) {
                return this;
            }
            var options = this.options();
            
            var newCategoryPath = '';
            jQuery.ajax(
                {
                    type      : "POST",
                    data      : {'categoryId': data.category['entity_id']},
                    showLoader: true,
                    url       : self.BASE_URL+'job/categoryNew',
                    dataType  : "json",
                    async: false,
                    success   : function (result, status) {
                        newCategoryPath = result.data;
                    },
                    error     : function () {
                        self.error($t('Error on General : Error with loading category path.'));
                    },
                }
            );
            option.label = newCategoryPath;
            option.value = newCategoryPath;
            var dataJSON = JSON.parse(localStorage.getItem('list_categories'));

            options.push(option);
            if (dataJSON !== null) {
                localStorage.setItem('list_categories', JSON.stringify(options));
            }
            this.options(options);
        },

        /**
         * Normalize option object.
         *
         * @param {Object} data - Option object.
         * @returns {Object}
         */
        parseData: function (data) {

            return {
                'is_active': data.category['is_active'],
                value      : data.category.name,
                label      : data.category.name,
                path       : ''
            };
        }
    });
});
