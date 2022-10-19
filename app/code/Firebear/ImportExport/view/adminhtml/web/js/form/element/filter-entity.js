/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/element/select',
        'Firebear_ImportExport/js/form/element/general',
        'uiRegistry',
        'moment',
        'mageUtils',
        'Magento_Ui/js/lib/validation/validator',
        'ko',
        'mage/calendar',
        'mage/translate',
        'jquery/ui'
    ],
    function ($, _, Acstract, general, reg, moment, utils, validator, ko, calendar, $t) {
        'use strict';

        String.prototype.firstLetterCaps = function () {
            return this.charAt(0).toUpperCase() + this.slice(1);
        };
        var defaults = {
            dateFormat: 'mm\/dd\/yyyy',
            showsTime: false,
            timeFormat: null,
            buttonImage: null,
            buttonImageOnly: null,
            buttonText: $t('Select Date')
        };

        return Acstract.extend(general).extend(
            {
                defaults: {
                    valueUpdate: 'afterkeydown',
                    sourceExt: null,
                    sourceOptions: null,
                    uid1:null,
                    uid2:null,
                    dateOptions: {},
                    typeText: false,
                    typeSelect: false,
                    typeDate: false,
                    typeNot: false,
                    typeInt: false,
                    types: ['text', 'select', 'date', 'not', 'int', 'range'],
                    genType: 'text',
                    timeOffset: 0,
                    checked: false,
                    inputDateFormat: 'y-MM-dd',
                    outputDateFormat: 'MM/dd/y',
                    pickerDateTimeFormat: '',
                    pickerDefaultDateFormat: 'MM/dd/y', // ICU Date Format
                    pickerDefaultTimeFormat: 'h:mm a',
                    elementName: '',
                    number: '',
                    typesEntity: ['catalog_category', 'catalog_product', 'advanced_pricing', 'customer', 'customer_address', 'catalog_price_rule'],
                    validationParams: {
                        dateFormat: '${ $.outputDateFormat }'
                    },
                    shiftedValue: '',
                    secondShiftedValue: '',
                    fromValue: '',
                    toValue: '',
                    excludeValue: '',
                    textValue: '',
                    selectValue: '',
                    entity: '${$.parentName}.source_filter_entity',
                    imports: {
                        changeSource: '${$.parentName}.source_filter_field:value'
                    },
                    listens: {
                        'textValue': 'onTextValueChange',
                        'shiftedValue': 'onShiftedValueChange',
                        'secondShiftedValue': 'onSecondShiftedValueChange',
                        'fromValue': 'onFromValueChange',
                        'toValue': 'onToValueChange',
                        'excludeValue': 'onExcludeValueChange',
                        'selectValue': 'onSelectValueChange'
                    }
                },
                configureDataScope: function () {
                    var recordId,
                        prefixName,
                        suffixName;

                    // Get recordId
                    recordId = this.parentName.split('.').last();

                    prefixName = this.dataScopeToHtmlArray(this.prefixName);
                    this.elementName = this.prefixElementName + recordId;

                    suffixName = '';

                    if (!_.isEmpty(this.suffixName) || _.isNumber(this.suffixName)) {
                        suffixName = '[' + this.suffixName + ']';
                    }
                    this.inputName = prefixName + '[' + this.elementName + ']' + suffixName;

                    suffixName = '';

                    if (!_.isEmpty(this.suffixName) || _.isNumber(this.suffixName)) {
                        suffixName = '.' + this.suffixName;
                    }

                    this.exportDataLink = 'data.' + this.prefixName + '.' + this.elementName + suffixName;
                    this.exports.value = this.provider + ':' + this.exportDataLink;
                },
                initialize: function () {
                    this._super();

                    ko.bindingHandlers.dateground = {

                        init: function (el, valueAccessor) {
                            var config = valueAccessor(),
                                observable,
                                options = {};

                            _.extend(options, defaults);

                            if (typeof config === 'object') {
                                observable = config.storage;
                                _.extend(options, config.options);
                            } else {
                                observable = config;
                            }
                            $(el).calendar(options);

                            if (observable()) {
                                $(el).datepicker(
                                    'setDate',
                                    moment(
                                        observable(),
                                        utils.convertToMomentFormat(
                                            options.dateFormat + (options.showsTime ? ' ' + options.timeFormat : '')
                                        )
                                    ).toDate()
                                );
                            }
                            $(el).blur();

                            ko.utils.registerEventHandler(el, 'change', function () {
                                observable(this.value);
                            });
                        },
                        update: function (el, valueAccessor) {
                            var config = valueAccessor(),
                                observable,
                                options = {};

                            _.extend(options, defaults);

                            if (typeof config === 'object') {
                                observable = config.storage;
                                _.extend(options, config.options);
                            } else {
                                observable = config;
                            }

                            if (observable()) {
                                $(el).datepicker(
                                    'setDate',
                                    moment(
                                        observable(),
                                        utils.convertToMomentFormat(
                                            options.dateFormat + (options.showsTime ? ' ' + options.timeFormat : '')
                                        )
                                    ).toDate()
                                );
                            }
                        }
                    }

                    return this;
                },
                initConfig: function (config) {
                    this._super();
                    this.sourceOptions = JSON.parse(this.sourceOptions);
                    var scope = this.dataScope;
                    var name = scope.split('.').slice(1);

                    this.elementName = name[0];
                    this.number = _.last(name);
                    if (!this.dateOptions.dateFormat) {
                        this.dateOptions.dateFormat = this.pickerDefaultDateFormat;
                    }

                    if (!this.dateOptions.timeFormat) {
                        this.dateOptions.timeFormat = this.pickerDefaultTimeFormat;
                    }

                    this.prepareDateTimeFormats();
                    this.uid1 = utils.uniqueid();
                    this.uid2 = utils.uniqueid();

                    return this;
                },
                initObservable: function () {
                    var type = '';
                    var count = 0;
                    _.each(this.types, function (index) {
                        if (count > 0) {
                            type += " ";
                        }
                        type += "type" + index.firstLetterCaps();
                        count++;
                    });
                    this._super()
                        .observe(type)
                        .observe(['textValue'])
                        .observe(['shiftedValue'])
                        .observe(['secondShiftedValue'])
                        .observe(['fromValue'])
                        .observe(['toValue'])
                        .observe(['excludeValue'])
                        .observe(['selectValue']);

                    return this;
                },
                changeTypes: function (el) {
                    var self = this;
                    _.each(this.types, function (index) {
                        var bool = false;
                        if (index == el) {
                            self.genType = index;
                            bool = true;
                        }
                        var type = "type" + index.firstLetterCaps();
                        self[type](bool);
                    })
                },
                changeSource: function (value) {
                    var oldValue = this.value();
                    var self = this;
                    var finded = 0;

                    var entity = reg.get(this.entity);
                    var types = self.typesEntity;

                    var type = entity.value();
                    if (_.indexOf(types, entity.value()) != -1) {
                        type = 'attr';
                    }
                    if (type == 'order') {
                        type = 'orders';
                    }
                    var data = JSON.parse(localStorage.getItem('list_filtres'));
                    var exists = 0;
                    if (data !== null && typeof data === 'object') {
                        if (value in data) {
                            exists = 1;
                            var array = data[value];
                            if (array.field == value) {
                                finded = 1;
                                self.changeTypes(array.type);
                                if (array.type == 'select') {
                                    self.setOptions(array.select);
                                } else {
                                    self.setOptions([]);
                                }
                            }
                        }
                        if (!finded) {
                            self.changeTypes('not');
                            self.setOptions([]);
                        }
                        if (oldValue) {
                            self.getToType(oldValue);
                        }

                        self.value(oldValue);
                    }

                    if (exists == 0 && typeof value != 'undefined') {
                        var parent = reg.get(this.ns + '.' + this.ns + '.source_data_filter_container.source_filter_map');
                        parent.showSpinner(true);

                        $.ajax({
                            type: "POST",
                            url: this.ajaxUrl,
                            data: {entity: entity.value(), attribute: value, type: type},
                            success: function (array) {
                                var newData = JSON.parse(localStorage.getItem('list_filtres'));
                                if (newData === null) {
                                    newData = {};
                                }
                                newData[value] = array;
                                localStorage.setItem('list_filtres', JSON.stringify(newData));
                                if (array.field == value) {
                                    finded = 1;
                                    self.changeTypes(array.type);
                                    if (array.type == 'select') {
                                        if (!('noCaption' in array)) {
                                            self.caption('Select');
                                        }
                                        self.setOptions(array.select);
                                    } else {
                                        self.setOptions([]);
                                    }
                                }
                                if (!finded) {
                                    self.changeTypes('not');
                                    self.setOptions([]);
                                }
                                if (oldValue) {
                                    self.getToType(oldValue);
                                }

                                self.value(oldValue);
                                parent.showSpinner(false);
                            }
                        });
                    }
                },
                setInitialValue: function () {
                    this.initialValue = this.value();
                    this.on('value', this.onUpdate.bind(this));
                    this.isUseDefault(this.disabled());

                    return this;
                },
                getToType: function (value) {
                    switch (this.genType) {
                        case 'select':
                            this.selectValue(value);
                            break;
                        case 'date':
                            var array = value.split(":");
                            var dateFormat = this.outputDateFormat;
                            var shiftedValue = array[0] ? moment(array[0], dateFormat) : '';
                            var shiftedSecondValue = array[1] ? moment(array[1], dateFormat) : '';
                            $.async({
                                component: this,
                                selector: 'input'
                            }, function (element) {
                               // $("#"+this.uid1).datepicker("refresh");
                               // $("#"+this.uid2).val(array[1]);
                            }.bind(this));
                            this.shiftedValue(shiftedValue);
                            this.secondShiftedValue(shiftedSecondValue);
                            break;
                        case 'int':
                            var array = value.split(":");
                            this.fromValue(array[0]);
                            this.toValue(array[1]);
                            break;
                        case 'range':
                            var array = value.split(":");
                            this.fromValue(array[0]);
                            this.toValue(array[1]);
                            this.excludeValue(array[2]);
                            break;
                        case 'text':
                            this.textValue(value);
                            break;
                        default:
                            this.textValue(value);
                    }
                },
                onTextValueChange: function (value) {
                    this.value(value);
                },
                onSelectValueChange: function (value) {
                    this.value(value);
                },
                /**
                 * Prepares and sets date/time value that will be sent
                 * to the server.
                 *
                 * @param {String} shiftedValue
                 */
                onShiftedValueChange: function (shiftedValue) {
                    var value;
                    if (shiftedValue) {
                        var momentValue = moment(shiftedValue, this.pickerDateTimeFormat);
                        value = momentValue.format(this.outputDateFormat);
                    } else {
                        value = '';
                    }
                    var newValue = value + ':' + this.formatOutputDateFormat(this.secondShiftedValue());
                    if (this.value() !== newValue) {
                        this.value(newValue);
                    }
                },
                onSecondShiftedValueChange: function (shiftedValue) {
                    var value;
                    if (shiftedValue) {
                        var momentValue = moment(shiftedValue, this.pickerDateTimeFormat);
                        value = momentValue.format(this.outputDateFormat);
                    } else {
                        value = '';
                    }
                    var newValue =  this.formatOutputDateFormat(this.shiftedValue()) + ':' + value;
                    if (this.value() !== newValue) {
                        this.value(newValue);
                    }
                },
                formatOutputDateFormat: function (value) {
                    if (value instanceof Object) {
                        return value.format(this.outputDateFormat);
                    }
                    return value;
                },
                onFromValueChange: function (value) {
                    var self = this;
                    if (!this.validateNumber(value).passed) {
                        value = value.slice(0, -1);
                        this.fromValue(value);
                    }
                    var text = {
                        from: value,
                        to: self.toValue()
                    };

                    this.value(value + ':' + this.toValue());
                },
                onExcludeValueChange: function (value) {
                    this.value(this.fromValue() + ':' + this.toValue() + ':' + value);
                },
                onToValueChange: function (value) {
                    var self = this;
                    if (!this.validateNumber(value).passed) {
                        value = value.slice(0, -1);
                        this.fromValue(value);
                    }
                    this.value(self.fromValue() + ':' + value);

                },

                /**
                 * Prepares and converts all date/time formats to be compatible
                 * with moment.js library.
                 */
                prepareDateTimeFormats: function () {
                    this.pickerDateTimeFormat = this.dateOptions.dateFormat;

                    if (this.dateOptions.showsTime) {
                        this.pickerDateTimeFormat += ' ' + this.dateOptions.timeFormat;
                    }
                    this.pickerDateTimeFormat = utils.convertToMomentFormat(this.pickerDateTimeFormat);

                    if (this.dateOptions.dateFormat) {
                        this.outputDateFormat = this.dateOptions.dateFormat;
                    }

                    this.inputDateFormat = utils.convertToMomentFormat(this.inputDateFormat);
                    this.outputDateFormat = utils.convertToMomentFormat(this.outputDateFormat);

                    this.validationParams.dateFormat = this.outputDateFormat;
                },
                validateNumber: function (value) {
                    return validator('validate-number', value);
                },
            }
        )
    }
);
