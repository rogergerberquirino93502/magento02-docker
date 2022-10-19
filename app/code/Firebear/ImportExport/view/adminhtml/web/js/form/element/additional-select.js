/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/element/select',
        'uiRegistry',
        'mage/translate'
    ],
    function ($, _, Acstract, reg, $t) {
        'use strict';

        function parseOptions(nodes, captionValue)
        {
            var caption,
                value;

            nodes = _.map(nodes, function (node) {
                value = node.value;

                if (value === null || value === captionValue) {
                    if (_.isUndefined(caption)) {
                        caption = node.label;
                    }
                }

                return node;
            });

            return {
                options: _.compact(nodes),
                caption: _.isString(caption) ? caption : false
            };
        }

        function indexOptions(data, result)
        {
            var value;

            result = result || {};

            data.forEach(function (item) {
                value = item.value;

                if (Array.isArray(value)) {
                    indexOptions(value, result);
                } else {
                    result[value] = item;
                }
            });

            return result;
        }


        return Acstract.extend(
            {
                default: {

                    listens : {
                        'value': 'onChangeValue'
                    }
                },
                initConfig: function (config) {
                    var options = config.options,
                        captionValue = this.captionValue || '',
                        result = parseOptions(options, captionValue);
                    var newResult = [];
                    _.each(result.options, function (element, key) {
                        if (element.label != result.caption) {
                            newResult.push(element);
                        }
                    });
                    result.options = newResult;
                    this.caption = result.caption;
                    _.extend(config, result);
                    this._super();

                    return this;
                },

                setOptions: function (data) {
                    var isVisible;

                    this.indexedOptions = indexOptions(data);

                    this.options(data);

                    if (this.customEntry) {
                        isVisible = !!result.options.length;

                        this.setVisible(isVisible);
                        this.toggleInput(!isVisible);
                    }

                    return this;
                }
            }
        )
    }
);
