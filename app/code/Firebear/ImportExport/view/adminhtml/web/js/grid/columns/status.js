/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'underscore',
    'Magento_Ui/js/grid/columns/column'
    ],
    function (_, Column) {
        'use strict';

        return Column.extend(
            {

                defaults: {
                    bodyTmpl: 'Firebear_ImportExport/grid/cells/status',
                    fieldClass: {
                        'data-grid-thumbnail-cell': true
                    }
                },

                getIsActive: function (record) {
                    return (record[this.index] == 1);
                },

                /**
                 * Retrieves label associated with a provided value.
                 *
                 * @returns {String}
                 */
                getLabel: function () {

                    var options = this.options || [],
                    values = this._super(),
                    label = [];

                    if (!Array.isArray(values)) {
                        values = [values];
                    }

                    values = values.map(
                        function (value) {
                            return value + '';
                        }
                    );

                    options.forEach(
                        function (item) {
                            if (_.contains(values, item.value + '')) {
                                label.push(item.label);
                            }
                        }
                    );

                    return label.join(', ');
                }

                /*eslint-enable eqeqeq*/
            }
        );
    }
);
