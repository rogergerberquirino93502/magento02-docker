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
                    elementTmpl: 'Firebear_ImportExport/form/element/button',
                    loadmapUrl: null,
                    imports: {
                        toggleVisibility: '${$.parentName}.import_source:value'
                    },
                    error: '',
                    notice: '',
                    showMap: 0,
                    validMap: 0,
                    update: 0,
                    visible: false
                },

                initialize: function () {
                    this._super();

                    return this;
                },

                initObservable: function() {
                    return this._super()
                    .observe('error showMap notice');

                },

                _setClasses: function() {
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

                toggleVisibility: function(selected) {
                    this.isShown = true;//(selected != undefined);
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                },

                action: function () {
                    this.error('');
                    var form = registry.get(this.ns + '.' + this.ns);
                    var butonVallid = registry.get(this.ns + '.' + this.ns +".source_data_map_container.validate_button");
                    butonVallid.error('');
                    butonVallid.note('');
                    form.validate();
                    if (!form.additionalInvalid && !form.source.get('params.invalid')) {
                        this.validateGeneral();
                    }
                },

                validateGeneral: function() {
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
                            self.showMap(0);
                        } else {
                            self.error('');
                            self.generateAttributesMap();
                        }
                    });
                },

                loadForm: function() {
                    this.validateGeneral();
                },

                generateAttributesMap: function() {
                    this.notice('');
                    var ajaxSend = this.ajaxSend.bind(this);
                    this.getParams().then(ajaxSend);
                },
            }
        );
    }
);
