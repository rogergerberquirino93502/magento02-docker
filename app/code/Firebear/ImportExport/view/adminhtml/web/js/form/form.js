/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        './adapter',
        'uiRegistry',
        'Magento_Ui/js/lib/spinner',
        'rjsResolver'
    ],
    function ($, Form, adapter, reg, loader, resolver) {
        'use strict';

        /**
         * Collect form data.
         *
         * @param {Array} items
         * @returns {Object}
         */
        function collectData(items)
        {
            var result = {};

            items = Array.prototype.slice.call(items);

            items.forEach(function (item) {
                switch (item.type) {
                    case 'checkbox':
                        result[item.name] = +!!item.checked;
                        break;
                    case 'radio':
                        if (item.checked) {
                            result[item.name] = item.value;
                        }
                        break;

                    default:
                        result[item.name] = item.value;
                }
            });

            return result;
        }

        return Form.extend(
            {
                defaults: {
                    nameModal:'',
                },
                initialize: function () {
                    this._super();
                    localStorage.setItem('list_values', null);
                    localStorage.setItem('list_filtr', null);
                    localStorage.setItem('list_filtres', null);
                    localStorage.setItem('list_categories', null);
                    resolver(this.checkButton, this);

                    return this;
                },
                initAdapter: function () {
                    adapter.on({
                        'reset': this.reset.bind(this),
                        'save': this.save.bind(this, true, {}),
                        'saveAndContinue': this.save.bind(this, false, {}),
                        'saveAndRun': this.saveAndRun.bind(this, {})
                    }, this.selectorPrefix, this.eventPrefix);

                    return this;
                },

                destroyAdapter: function () {
                    adapter.off([
                        'reset',
                        'save',
                        'saveAndContinue',
                        'saveAndRun'
                    ], this.eventPrefix);

                    return this;
                },
                saveAndRun: function (data) {
                    this.validate();
                    var button = reg.get(this.ns + "." + this.ns + ".source.check_button");
                    var next = 1;

                    if (button.component == 'Firebear_ImportExport/js/components/button-check') {
                        next = button.validMap;
                    }

                    button.error('');
                    if (!this.additionalInvalid && !this.source.get('params.invalid') && next) {
                        this.setAdditionalData(data)
                            .extSubmit(true);
                    }
                    if (!next) {
                        button.error('Can\'t save job');
                    }
                },
                save: function (redirect, data) {
                    this.validate();
                    var button = reg.get(this.ns + "." + this.ns + ".source.check_button");
                    var next = 1;

                    if (button.component == 'Firebear_ImportExport/js/components/button-check') {
                        next = button.validMap;
                    }
                    button.error('');
                    if (!next) {
                        button.error('Can\'t save job');
                    } else {
                        this._super(redirect, data);
                    }
                },
                setAdditionalData: function (data) {
                    _.each(data, function (value, name) {
                        this.source.set('data.' + name, value);
                    }, this);

                    return this;
                },
                extSubmit: function (redirect) {
                    localStorage.removeItem('jobId');
                    loader.get(this.name).show();
                    var self = this;
                    var additional = collectData(this.additionalFields),
                        source = this.source;
                    _.each(additional, function (value, name) {
                        source.set('data.' + name, value);
                    });
                    var postData = this.source.get('data');
                    var type = reg.get(self.ns + "." + self.ns + ".source.import_source");
                    if (typeof type != 'undefined') {
                        if (type.value() == 'google') {
                            var filePath = reg.get(self.ns + "." + self.ns + ".source.google_google_file_path");
                            postData.google_file_path = filePath.value();
                        }
                    }
                    postData = this.recorrectData(postData);
                    // postData = this.recorrectMapData(postData);
                    $.ajax({
                        type: "POST",
                        url: this.source.submit_url,
                        data: postData,
                        success: function (msg) {
                            loader.get(self.name).hide();
                             $('body').notification('clear');
                            if (msg != false && typeof msg.messages == 'undefined') {
                                localStorage.setItem('jobId', msg);
                                reg.set(self.namespace + "." + self.namespace + ".general.entity:value", msg);
                                reg.get(self.namespace + "." + self.namespace + "." + self.nameModal).toggleModal();
                            } else {
                                $.each(msg.messages, function (key, message) {
                                    $('body').notification('add', {
                                        error: true,
                                        message: message,

                                        /**
                                        * Inserts message on page
                                        * @param {String} msg
                                        */
                                        insertMethod: function (msg) {
                                            $('.page-main-actions').after(msg);
                                        }
                                    });
                                });
                            }
                        }
                    });
                },

                checkButton: function () {
                    var data = reg.get(this.provider).data;
                    reg.get('import_job_form.import_job_form.general.title', function (name) {
                        if (name.value()) {
                            reg.get('import_job_form.import_job_form.source.check_button', function (object) {
                                object.update = 1;
                                object.validateGeneral();
                            });
                            if (_.size(data['source_data_categories_map']) > 0) {
                                reg.get('import_job_form.import_job_form.source_data_map_container_category.load_categories_button', function (object) {
                                    object.loadForm();
                                });
                            }
                        }
                    });
                },
                recorrectData: function (data) {
                    var self = this;
                    var objects = reg.get(self.ns + "." + self.ns + ".source_data_filter_container.source_filter_map");
                    if (typeof objects != 'undefined') {
                        var list = [];
                        _.each(objects.elems(), function (elem, index) {
                            var listSecond = {};
                            _.each(elem.elems(), function (elemTop) {
                                listSecond[elemTop.prefixName] = elemTop.value();
                            });
                            list[index] = listSecond;
                        });
                        if (_.size(list) > 0) {
                            data.source_filter_field.delete = [];
                            data.source_filter_field.entity = [];
                            data.source_filter_field.order = [];
                            data.source_filter_field.value = [];
                            data.source_filter_filter.value = [];
                            _.each(list, function (elem, index) {
                                data.source_filter_field.delete[index] = "";
                                data.source_filter_field.entity[index] = elem["source_filter_field.entity"];
                                data.source_filter_field.order[index] = "";
                                data.source_filter_field.value[index] = elem["source_filter_field.value"];
                                data.source_filter_filter.value[index] = elem["source_filter_filter.value"];
                            });
                        };
                    }

                    return data;
                },
                recorrectMapData: function (data) {
                    var self = this;
                    var objects = reg.get(self.ns + "." + self.ns + ".source_data_map_container.source_data_map");
                    if (typeof objects != 'undefined') {
                        var list = [];
                        _.each(objects.elems(), function (elem, index) {
                            var listSecond = {};
                            _.each(elem.elems(), function (elemTop) {
                                listSecond[elemTop.prefixName] = elemTop.value();
                            });
                            list[index] = listSecond;
                        });

                        if (_.size(list) > 0) {
                            if (_.size(data.source_data_export)) {
                                data.source_data_export.delete = [];
                                data.source_data_export.order = [];
                                data.source_data_export.value = [];
                                data.source_data_replace.value = [];
                                data.source_data_system.entity = [];
                                data.source_data_system.value = [];
                                _.each(list, function (elem, index) {
                                    data.source_data_export.delete[index] = "";
                                    data.source_data_export.order[index] = "";
                                    data.source_data_export.value[index] = elem["source_data_export.value"];
                                    data.source_data_replace.value[index] = elem["source_data_replace.value"];
                                    data.source_data_system.entity[index] = elem["source_data_system.entity"];;
                                    data.source_data_system.value[index] = elem["source_data_system.value"];;
                                });
                            }
                        };
                    }

                    return data;
                },
                submit: function (redirect) {
                    var self = this;

                    var additional = collectData(this.additionalFields),
                        source = this.source;
                    _.each(additional, function (value, name) {
                        source.set('data.' + name, value);
                    });
                    var postData = this.source.get('data');

                    var type = reg.get(self.ns + "." + self.ns + ".source.import_source");
                    if (typeof type != 'undefined') {
                        if (type.value() == 'google') {
                            var filePath = reg.get(self.ns + "." + self.ns + ".source.google_google_file_path");
                            this.source.set('data.google_file_path',filePath.value());
                        }
                    }

                    postData = this.recorrectData(postData);

                    source.set('data', postData);

                    this._super(redirect);
                }
            }
        );
    }
);
