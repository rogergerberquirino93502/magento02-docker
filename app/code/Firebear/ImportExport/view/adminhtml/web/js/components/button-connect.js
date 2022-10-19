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

        return Element.extend(button).extend(
            {
                defaults: {
                    elementTmpl: 'Firebear_ImportExport/form/element/connect',
                    loadmapUrl: null,
                    imports: {
                        toggleVisibility: '${$.parentName}.export_source_entity:value'
                    },
                    error: '',
                    noite: '',
                    visible: false
                },

                initialize: function () {
                    this._super();

                    return this;
                },
                initObservable: function () {
                    return this._super()
                        .observe('error note');
                },
                _setClasses: function () {
                    var additional = this.additionalClasses,
                        classes;

                    if (_.isString(additional) && additional.trim().length) {
                        additional = this.additionalClasses.trim().split(' ');
                        classes = this.additionalClasses = {};

                        additional.forEach(
                            function (name) {
                                classes[name] = true;
                            },
                            this
                        );
                    }

                    _.extend(
                        this.additionalClasses,
                        {
                            _error: this.error
                        }
                    );

                    return this;
                },
                toggleVisibility: function (selected) {
                    this.isShown = (selected in this.valuesForOptions);
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                },
                action: function () {
                    this.validateGeneral();
                },
                validateGeneral: function () {
                    var self = this;

                    registry.get(this.parentName, function (params) {
                        var elems = params.elems();
                        var errors = 0;
                        _.each(
                            elems,
                            function (element) {
                                if (element.visible() && element.componentType != 'container'
                                    && element.required() && !element.value()
                                ) {
                                    errors++;
                                }
                            }
                        );
                        if (errors > 0) {
                            self.error($t('Please configure File Path.'))
                        } else {
                            self.error('');
                            self.generateAttributesMap();
                        }
                    });
                },
                loadForm: function () {
                    this.validateGeneral();
                },
                generateAttributesMap: function () {

                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getParams().then(ajaxSend);

                },

                ajaxSend: function (elements) {
                    this.note('');
                    this.error('');
                    var form = jQuery.Deferred();
                    var self = this;
                    if (_.size(elements) > 0) {
                        var data = {
                            form_data: elements,
                        };
                        jQuery.ajax(
                            {
                                type: "POST",
                                data: data,
                                showLoader: true,
                                url: self.loadmapUrl,
                                success: function (result, status) {
                                    if (!result) {
                                        self.error([$t('Fail! Can\'t connect ')]);
                                    } else {
                                        self.note($t("Success! Your connection is ready!"));
                                        form.resolve(true);
                                    }
                                },
                                error: function () {
                                    self.error([$t('Error on General : You have not selected a Entity Type yet or wrong File Path!')]);
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
