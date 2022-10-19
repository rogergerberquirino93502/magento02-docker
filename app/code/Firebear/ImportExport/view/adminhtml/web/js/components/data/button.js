/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'uiRegistry',
    'jquery',
    'underscore',
    'mage/translate',
    'uiRegistry'
    ],
    function (registry, jQuery, _, $t, reg) {
        'use strict';

        return {
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
                                    formElements.push(element.dataScope.replace('data.','') + '+' + element.value())
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
                                            formElements.push(element.dataScope.replace('data.','') + '+' + element.value())
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
                                            formElements.push(element.dataScope.replace('data.','') + '+' + element.value())
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
            getData  : function () {
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
            ajaxSend : function (elements) {
                var form = jQuery.Deferred();
                var self = this;
                if (_.size(elements) > 0) {
                    registry.get(
                        this.ns + '.' + this.ns + '.source.import_source',
                        function (source) {
                            var data = {
                                form_data  : elements,
                                source_type: source.value()
                            };
                            var type = registry.get(self.ns + '.' + self.ns + '.settings.platforms');
                            var locale = registry.get(self.ns + '.' + self.ns + '.general.language');
                            var jobId = registry.get(self.ns + '.' + self.ns + '.general.entity_id');
                            if (type.value()) {
                                data['type'] = type.value();
                            }
                            if (locale.value()) {
                                data['language'] = locale.value();
                            }
                            if (jobId.value()) {
                                data['job_id'] = jobId.value();
                            }

                            jQuery.ajax(
                                {
                                    type      : "POST",
                                    data      : data,
                                    showLoader: true,
                                    url       : self.loadmapUrl,
                                    success   : function (result, status) {
                                        if (result.error) {
                                            self.error($t(result.error));
                                            self.showMap(0);
                                        } else {
                                            localStorage.removeItem('columns');
                                            localStorage.removeItem('options');
                                            localStorage.removeItem('map');
                                            localStorage.setItem('columns', JSON.stringify(result.columns));
                                            localStorage.setItem('options', JSON.stringify(result.options));
                                            localStorage.setItem('map', JSON.stringify(result.map));

                                            /**
                                             * Update "Attribute / column name on file" field
                                             * options after validate new file
                                             */
                                            self.updateConfigurableOptions(result.columns);

                                            var configurableSwitch = registry.get(self.ns + '.' + self.ns + '.configurable.configurable_switch');
                                            if (configurableSwitch) {
                                                configurableSwitch.onUpdate();
                                            }

                                            if ("update" in self) {
                                                if (self.update == 1) {
                                                    var object = reg.get('import_job_form.import_job_form.source_data_map_container.source_data_map');
                                                    object.reload();
                                                }
                                            }
                                            localStorage.setItem('categories', JSON.stringify(result.categories));
                                            /* check if CART PRICE RULE is the choosen entity to import */
                                            var ruleMessage=JSON.stringify(result.ruleMessages);
                                            if (ruleMessage==undefined||ruleMessage==null) {
                                                if ("messages" in self) {
                                                    self.messages(result.messages);
                                                }
                                                if ('notice' in self) {
                                                    self.notice($t('File validated successfully. Please review and update custom mapping settings if necessary and click Save & Run to run the import job'));
                                                }
                                            }
                                            /* show messages if CART PRICE RULE is the choosen entity to import */
                                            var show=JSON.stringify(result.show);
                                            if (ruleMessage!=null && ruleMessage==='"Success"') {
                                                self.notice($t('File validated successfully: Uploaded file is valid for cart price rule.'));
                                            } else if (ruleMessage!=null && ruleMessage==='"Error"') {
                                                self.error([$t('Error on General: Uploaded file is not valid for cart price rule.')]);
                                            }
                                            if (_.contains(
                                                ['import_jobs', 'export_jobs'],
                                                reg.get('import_job_form.import_job_form.settings.entity').value()
                                            )) {
                                                show = false;
                                            }
                                            /* Hide mapping accordion if CART PRICE RULE is the choosen entity to import */
                                            if (show!=null) {
                                                self.showMap(0);
                                            } else {
                                                self.showMap(1);
                                            }
                                            if ("validMap" in self) {
                                                self.validMap = 1;
                                            }
                                            form.resolve(true);
                                        }
                                    },
                                    error     : function () {
                                        self.error([$t('Error on General : You have not selected a Entity Type yet or wrong File Path!')]);
                                    },
                                    dataType  : "json"
                                }
                            );
                        }
                    );
                }
                return form.promise();
            },
            updateConfigurableOptions: function (options) {
                var element = registry.get(this.ns + '.' + this.ns + '.configurable.configurable_field');
                if (element) {
                    element.updateOptions(options);
                }
            }
        }
    }
);
