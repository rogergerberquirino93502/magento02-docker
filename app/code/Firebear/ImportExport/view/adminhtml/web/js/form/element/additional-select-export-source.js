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

        function parseOptions(nodes, captionValue) {
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

        function indexOptions(data, result) {
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
                defaults: {
                    imports: {
                        setOptionsAdv: '${$.parentName}.behavior_field_file_format:value'
                    },
                    sourceOptions: null
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

                    this.sourceOptions = newResult;
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
                },
                setOptionsAdv: function (value) {
                    var isApi = reg.get('import_export_job_form.import_export_job_form.settings.use_api').value();
                    if (this.sourceOptions == null) {
                        this.sourceOptions = this.options();
                    }
                    var options = this.sourceOptions;
                    var prevValue = this.value();
                    var newOptions = [];
                    _.each(options, function (element, index) {
                        if (isApi !== element.api) return;
                        if (element.depends == "" || $.inArray(value, element.depends) !== -1) {
                            newOptions.push(element);
                        }
                    });

                    this.options(newOptions);
                }
            }
        )
    }
);
