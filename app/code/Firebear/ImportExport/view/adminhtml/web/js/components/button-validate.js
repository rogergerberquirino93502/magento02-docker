/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/components/button',
        'uiRegistry',
        'uiLayout',
        'mageUtils',
        'jquery',
        'underscore',
        'mage/translate',
        'Firebear_ImportExport/js/components/data/button'
    ],
    function (Element, registry, layout, utils, jQuery, _, $t, button) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    elementTmpl: 'Firebear_ImportExport/form/element/validate',
                    loadmapUrl: null,
                    error: '',
                    note: '',
                    visible: true,
                    imports: {
                        onHideError: '${$.parentName}.platforms:value'
                    }
                },

                initialize: function () {
                    this._super();

                    return this;
                },
                initObservable: function () {
                    return this._super()
                        .observe('error note');
                },
                action: function () {
                    this.generateAttributesMap();
                },
                loadForm: function () {
                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getData().then(ajaxSend);
                },
                generateAttributesMap: function () {
                    this.error('');
                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getParams().then(ajaxSend);
                },
                getParams: function () {
                    var form = jQuery.Deferred();
                    var formElements = new Array();
                    var self = this;
                    registry.get(
                        this.ns + '.' + this.ns + '.source',
                        function (object) {
                            var elems = object.elems();
                            _.each(
                                elems,
                                function (element) {
                                    if (element.visible() && element.componentType != 'container') {
                                        formElements.push(element.dataScope.replace('data.', '') + '+' + element.value())
                                    }
                                }
                            );
                            registry.get(
                                self.ns + '.' + self.ns + '.behavior',
                                function (object) {
                                    _.each(
                                        object.elems(),
                                        function (element) {
                                            if (element.visible() && element.componentType != 'container') {
                                                formElements.push(element.dataScope.replace('data.', '') + '+' + element.value())
                                            }
                                        }
                                    );
                                }
                            );
                            registry.get(
                                self.ns + '.' + self.ns + '.settings',
                                function (object) {
                                    _.each(
                                        object.elems(),
                                        function (element) {
                                            if (element.visible() && element.componentType != 'container') {
                                                formElements.push(element.dataScope.replace('data.', '') + '+' + element.value())
                                            }
                                        }
                                    );
                                }
                            );
                            registry.get(
                                self.ns + '.' + self.ns + '.source_data_map_container',
                                function (object) {
                                    var records = [];
                                    _.each(
                                        object.elems(),
                                        function (element) {
                                            if (element.visible() && element.addButton == true) {
                                                _.each(element.elems(), function (index) {
                                                    records.push(index.data());
                                                });
                                                formElements.push('records' + '+' + JSON.stringify(records));
                                            }
                                        }
                                    );
                                }
                            );
                            form.resolve(formElements);
                        }
                    );

                    return form.promise();
                },
                getData: function () {
                    var form = jQuery.Deferred();
                    var formElements = new Array();
                    var prodivder = registry.get(this.provider);
                    _.each(
                        prodivder.data,
                        function (element, key) {
                            if (element != null && element.length > 0) {
                                formElements.push(key + '+' + element);
                            }
                        }
                    );
                    form.resolve(formElements);

                    return form.promise();
                },
                ajaxSend: function (elements) {
                    var form = jQuery.Deferred();
                    var self = this;
                    self.note('');
                    if (_.size(elements) > 0) {
                        registry.get(
                            this.ns + '.' + this.ns + '.source.import_source',
                            function (source) {
                                var data = {
                                    form_data: elements,
                                    source_type: source.value()
                                };
                                var type = registry.get(self.ns + '.' + self.ns + '.settings.platforms');
                                var locale = registry.get(self.ns + '.' + self.ns + '.general.language');
                                var jobId = registry.get(self.ns + '.' + self.ns + '.general.entity_id');
                                if (jobId.value()) {
                                    data['job_id'] = jobId.value();
                                }
                                if (type.value()) {
                                    data['type'] = type.value();
                                }
                                if (locale.value()) {
                                    data['language'] = locale.value();
                                }

                                jQuery.ajax(
                                    {
                                        type: "POST",
                                        data: data,
                                        loaderContext: '.popup-loading',
                                        showLoader: true,
                                        url: self.loadmapUrl,
                                        success: function (result, status) {
                                            if (result.error) {
                                                self.error($t(result.error));
                                            } else {
                                                self.note($t('Import data validation is complete.'))
                                            }
                                            form.resolve(true);
                                        },
                                        error: function () {
                                            self.error([$t('Error on General : You have not selected a Entity Type yet or wrong File Path!')]);
                                        },
                                        dataType: "json"
                                    }
                                );
                            }
                        );
                    }
                    return form.promise();
                },
                onHideError: function (value) {
                    this.error('');
                }
            }
        );
    }
);
