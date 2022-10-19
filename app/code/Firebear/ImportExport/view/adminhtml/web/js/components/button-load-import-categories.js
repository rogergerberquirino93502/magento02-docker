/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
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
                    loadcategoriesUrl: null,
                    error: '',
                    note: '',
                    visible: true,
                    loading:0
                },

                initialize: function () {
                    this._super();

                    return this;
                },
                initObservable: function () {
                    return this._super()
                        .observe('error note loading');
                },
                action: function () {
                    this.generateAttributesMap();
                },
                loadForm: function () {
                    var form = jQuery.Deferred();
                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getData().then(ajaxSend).then(function (result) {
                        form.resolve(result);
                    });

                    return form.promise();
                },
                generateAttributesMap: function () {
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
                                self.ns + '.' + self.ns + '.xml_conf',
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
                                if (typeof element == 'object') {
                                    if (typeof element[0]['source_data_system'] !== 'undefined') {
                                        _.each(element, function (mapDataElement) {
                                            var objectData = mapDataElement;
                                            formElements.push(objectData);
                                        });
                                    }
                                }
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
                    self.error('');
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
                                        showLoader: true,
                                        url: self.loadcategoriesUrl,
                                        success: function (result, status) {
                                            if (result.error) {
                                                var error = [];
                                                error.push($t(result.error));
                                                self.error(error);
                                                form.reject(false);
                                            } else {
                                                localStorage.setItem('categories', JSON.stringify(result.categories));
                                                self.note($t('Categories loading completely.'));
                                                var mapping = registry.get('import_job_form.import_job_form.source_data_map_container_category.source_data_categories_map');
                                                if (_.size(mapping.recordData()) > 0) {
                                                    mapping.reload();
                                                }
                                            }
                                            form.resolve(true);
                                        },
                                        error: function () {
                                            var error = [];
                                            error.push($t('Error on General : You have not selected a Entity Type yet or wrong File Path!'));
                                            self.error(error);
                                            form.reject(false);
                                        },
                                        dataType: "json"
                                    }
                                );
                            }
                        );
                    }
                    this.loading(1);

                    return form.promise();
                },
            }
        );
    }
);
