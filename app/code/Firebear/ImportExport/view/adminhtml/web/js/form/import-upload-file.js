/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
define(
    [
        'jquery',
        'Magento_Ui/js/form/element/file-uploader',
        'uiRegistry',
        'Magento_Ui/js/lib/validation/validator',
        'underscore'
    ],
    function ($, Element, reg, validator, _) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    previewTmpl: 'Firebear_ImportExport/form/element/uploader/preview',
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.parentName}.import_source:value',
                        toggleByPlatform: 'import_job_form.import_job_form.settings.platforms:value'
                    },
                    uploaderConfig: {
                        url: null,
                        acceptFileTypes: /(\.|\/)(csv|xml)$/i,
                    },
                    isShown: false,
                    inverseVisibility: false,
                    visible:false,
                    isLoading: false,
                    maxFileSize: 1
                },

                initialize: function () {
                    this._super();
                    validator.addRule('validate-max-size-number', function (size, maxSize) {
                        return maxSize === false || size < maxSize;
                    },$.mage.__('File you are trying to upload exceeds maximum file size limit.( '+ this.formatSize(this.maxFileSize) + ' )'))
                    return this;
                },

                toggleVisibility: function (selected) {
                    this.isShown = selected in this.valuesForOptions;
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                    if (!this.visible()) {
                        this.value('');
                    }
                },

                addFile: function (file) {
                    var name = file.path + file.file;
                    var path_file = reg.get(this.parentName + '.file_file_path');
                    path_file.value(name);
                    return this;
                },

                onFileUploaded: function (e, data) {
                    var path_file = reg.get(this.parentName + '.file_file_path');
                    path_file.value('');
                    this._super();
                },

                isSizeExceeded: function (file) {
                     return validator('validate-max-size-number', file.size, this.maxFileSize);
                },

                toggleBySource: function (selected) {
                    this.toggleVisibility(selected);
                },

                toggleByPlatform: function (selected) {
                    var defaultSource = reg.get(this.parentName + '.import_source').getInitialValue();
                    if (!defaultSource) {
                        defaultSource = 'file';
                    }
                    if (!selected || selected === undefined) {
                        this.toggleVisibility(defaultSource);
                        return;
                    }
                    var entity = reg.get(this.ns + '.' + this.ns + '.settings.entity');
                    if (entity === undefined) {
                        this.toggleVisibility(selected);
                        return;
                    }

                    var value = entity.value() + '_' + selected;
                    value = value in this.platformForm ? value : defaultSource;
                    this.toggleVisibility(value);
                }
            }
        );
    }
);
