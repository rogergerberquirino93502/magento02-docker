/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'underscore',
    'mageUtils',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'uiLayout',
    'mage/translate'
    ],
    function (_, utils, registry, Abstract, layout, $t) {
        'use strict';

        return Abstract.extend(
            {
                defaults: {
                    valueUpdate: 'afterkeydown',
                    fieldMin: '',
                    fieldH: '',
                    fieldD: '',
                    fieldM: '',
                    fieldDW: '',
                    fields: ([]),
                    listFields: ['fieldMin', 'fieldH', 'fieldD', 'fieldM', 'fieldDW'],
                    listens: {
                        'fieldMin': 'onUpdateFieldMin',
                        'fieldH': 'onUpdateFieldH',
                        'fieldD': 'onUpdateFieldD',
                        'fieldM': 'onUpdateFieldM',
                        'fieldDW': 'onUpdateFieldDW',
                    }
                },
                initialize: function () {
                    this._super();
                    this.on('fieldMin', this.onUpdateFieldMin.bind(this));
                    this.on('fieldH', this.onUpdateFieldH.bind(this));
                    this.on('fieldD', this.onUpdateFieldD.bind(this));
                    this.on('fieldM', this.onUpdateFieldM.bind(this));
                    this.on('fieldDW', this.onUpdateFieldDW.bind(this));
                    var self = this;
                    if (this.value().length > 0) {
                        this.setExpr(this.value());
                    }

                    return this;
                },

                initConfig: function (config) {
                    this._super();

                    this.fields = null;

                    return this;
                },

                initObservable: function () {
                    this._super();
                    this.observe(this.listFields);
                    this.observe('fields', this.fields);
                    return this;
                },

                getList: function () {
                    var fields = [];
                    var self = this;
                    _.each(
                        this.fields(),
                        function (item, key) {
                            var collect = {label: item.label, field: self.listFields[key]};
                            fields.push(collect)
                        }
                    );

                    return fields;
                },

                setExpr: function (expr) {
                    if (expr.length > 1) {
                        var newArray = expr.split(" ");
                        var self = this;
                        _.each(
                            newArray,
                            function (item, key) {
                                var field = self.listFields[key];
                                self[field](item);
                            }
                        );
                    } else {
                        var self = this;
                        for (var i=0; i<5; i++) {
                            var field = self.listFields[i];
                            self[field]('');
                        }
                    }
                    this.onUpdateValue();
                },

                onUpdateFieldMin: function (value) {
                    this.fieldMin(value);
                    this.onUpdateValue();
                },
                onUpdateFieldH: function (value) {
                    this.fieldH(value);
                    this.onUpdateValue();
                },
                onUpdateFieldD: function (value) {
                    this.fieldD(value);
                    this.onUpdateValue();
                },
                onUpdateFieldM: function (value) {
                    this.fieldM(value);
                    this.onUpdateValue();
                },
                onUpdateFieldDW: function (value) {
                    this.fieldDW(value);
                    this.onUpdateValue();
                },

                onUpdateValue: function () {
                    var empty = " ";
                    var self = this;
                    var str = "";
                    var count = 0;
                    _.each(
                        this.listFields,
                        function (field, key) {
                            if (self[field]().length > 0) {
                                count++;
                            }
                            if (count > 0 && self[field]().length == 0) {
                                self[field]('*');
                            }
                            str += self[field]();
                            if (key != _.size(self.listFields) - 1) {
                                str += empty;
                            }
                        }
                    );
                    this.value(str);
                }
            }
        );
    }
);
