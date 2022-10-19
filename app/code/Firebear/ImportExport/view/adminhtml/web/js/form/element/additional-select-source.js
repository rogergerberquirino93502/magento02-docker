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
    function ($, _, Abstract, reg, $t) {
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


        return Abstract.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    imports: {
                        setOptionsAdv: '${$.parentName}.type_file:value',
                        toggleByPlatform: 'import_job_form.import_job_form.settings.platforms:value'
                    },
                    sourceOptions: null,
                    isShown: false,
                    inverseVisibility: false,
                    visible: true
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
                    var useApiSettings = reg.get('import_job_form.import_job_form.settings.use_api'),
                    isApi = 0;

                    if (useApiSettings !== undefined) {
                        isApi = useApiSettings.value();
                    }

                    if (this.sourceOptions == null) {
                        this.sourceOptions = this.options();
                    }
                    var options = this.sourceOptions;
                    var prevValue = this.value();
                    var newOptions = [];
                    _.each(options, function (element, index) {
                        if (parseInt(isApi) !== parseInt(element.api)) {
                            return;
                        }
                        if (element.depends === "" || $.inArray(value, element.depends) !== -1) {
                            newOptions.push(element);
                        }
                    });
                    this.options(newOptions);
                },

                toggleVisibility: function (isShown) {
                    this.isShown = isShown;
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                    if (!this.visible()) {
                        this.value('');
                    }
                },

                toggleByPlatform: function (selected) {
                    if (!selected || selected === undefined) {
                        this.toggleVisibility(true);
                        return;
                    }

                    var entity = reg.get(this.ns + '.' + this.ns + '.settings.entity');
                    if (entity === undefined) {
                        this.toggleVisibility(selected);
                        return;
                    }

                    var value = entity.value() + '_' + selected;
                    value = value in this.platformForm ? false : true;
                    this.toggleVisibility(value);
                }
            }
        )
    }
);
