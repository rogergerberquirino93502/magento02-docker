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
        'mage/translate'
    ],
    function (Element, registry, layout, utils, jQuery, _, $t) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    elementTmpl: 'Firebear_ImportExport/form/element/button-xslt',
                    loadmapUrl: null,
                    errors: '',
                    error: false,
                    notice: '',
                    showMap: 0,
                    uodate: 0,
                    visible: true
                },
                initObservable: function () {
                    return this._super()
                        .observe('error showMap notice errors');

                },
                action: function () {
                    this.error('');
                    var form = registry.get(this.ns + '.' + this.ns);
                    if (!form.additionalInvalid && !form.source.get('params.invalid')) {
                        this.generateAttributesMap();
                    }
                },
                getParams: function () {
                    var form = jQuery.Deferred();
                    var formElements = new Array();
                    var self = this;
                    registry.get(
                        this.provider,
                        function (object) {
                            var elems = object.data;
                            formElements.push('file_path+' + elems['file_path']);
                            formElements.push('xslt+' + elems['xslt']);
                            formElements.push('import_source+' + elems['import_source']);
                            formElements.push('type_file+' + elems['type_file']);
                            formElements.push('host+' + elems['host']);
                            formElements.push('port+' + elems['port']);
                            formElements.push('username+' + elems['username']);
                            formElements.push('password+' + elems['password']);

                        }
                    );
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
                        }
                    );
                    form.resolve(formElements);

                    return form.promise();
                },
                generateAttributesMap: function () {
                    this.notice('');
                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getParams().then(ajaxSend);
                },
                ajaxSend: function (elements) {
                    var form = jQuery.Deferred();
                    var self = this;
                    if (_.size(elements) > 0) {
                        var data = {
                            form_data: elements
                        };
                        jQuery.ajax(
                            {
                                type: "POST",
                                data: data,
                                showLoader: true,
                                url: self.loadmapUrl,
                                success: function (result, status) {
                                    var window = registry.get(self.parentName + '.xslt_result');
                                    if (result.error) {
                                        self.error(true);
                                        self.errors(result.error[0].split(/\r?\n/));
                                        window.visible(false);
                                    } else {
                                        window.fullValue(result.result);
                                        window.visible(true);
                                        form.resolve(true);
                                    }
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    self.error($t('status code: '+ jqXHR.status + ', errorThrown: ' + errorThrown));
                                },
                                dataType: "json"
                            }
                        );
                    }
                    return form.promise();
                }
            }
        );
    }
);
