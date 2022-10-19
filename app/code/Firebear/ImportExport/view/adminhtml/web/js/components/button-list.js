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
                    elementTmpl: 'Firebear_ImportExport/form/element/button-list',
                    options: [],
                    defaultOptions: null,
                    typeFile:'',
                    platform: '',
                    imports: {
                        setOptions: '${$.ns}.${$.ns}.settings.platforms:value',
                        setSource: '${$.ns}.${$.ns}.source.type_file:value',
                        setEntity: '${$.ns}.${$.ns}.settings.entity:value'
                    }
                },

                initConfig: function (config) {
                    this._super();
                    this.defaultOptions = JSON.parse(config.options);
                    this.options = '';
                    return this;
                },
                setOptions: function (value) {
                    this.platform = value;
                    var entity = registry.get(this.ns + '.' + this.ns + '.settings.entity');
                    if (entity !== 'undefined') {
                        this.entity = entity.value();
                    }
                    var typeFile = registry.get(this.ns + '.' + this.ns + '.source.type_file');
                    if (typeFile !== 'undefined') {
                        this.typeFile = typeFile.value();
                    }

                    this.prepareOptions();
                },
                setSource: function (value) {
                    var entity = registry.get(this.ns + '.' + this.ns + '.settings.entity');
                    if (entity !== undefined) {
                        this.entity = entity.value();
                    }
                    this.typeFile = value;

                    this.prepareOptions();
                },
                setEntity: function (value) {
                    this.typeFile = registry.get(this.ns + '.' + this.ns + '.source.type_file').value();
                    this.entity = value;

                    this.prepareOptions();
                },
                prepareOptions: function () {
                    this.options([]);
                    var self = this;
                    var newList = [];
                    if (this.platform !== '' && this.typeFile) {
                        var data = this.defaultOptions;

                        _.each(
                            data,
                            function (element) {
                                if (element.type === self.platform && element.entity === 'github_link') {
                                    var url = element.href;
                                    var newObject = {
                                        href:   url,
                                        label:  element.label,
                                        type:   element.type,
                                        target: '_blank'
                                    };
                                    newList.push(newObject);
                                }
                                var url = element.href + 'source/' + self.typeFile;

                                if (typeof self.entity !== undefined && self.entity) {
                                    url += '/entity/' + self.entity;
                                }

                                if (element.type === self.platform && element.entity === self.entity) {
                                    var newObject = {
                                        href:   url,
                                        label:  element.label,
                                        type:   element.type,
                                        target: '_self'
                                    };
                                    newList.push(newObject);
                                }

                            }
                        );

                        this.options(newList);
                    }
                },
                initObservable: function () {
                    this._super()
                        .observe({options:[]});
                    return this;
                },
            }
        );
    }
);
