/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/element/select',
        'uiRegistry'
    ],
    function ($, _, Acstract, reg) {
        'use strict';

        var isTouchDevice = typeof document.ontouchstart !== 'undefined';

        return Acstract.extend(
            {
                defaults: {
                    valueUpdate: 'afterkeydown',
                    listVisible: false,
                    value: null,
                    search:"",
                    links: {
                        value: '${ $.provider }:${ $.dataScope }'
                    },
                    options: [],
                    listens: {
                        'search': 'onSearch',
                        'value': 'onUpdateValue'
                    },
                    classes:"list",
                    multiselectFocus: false
                },

                initialize: function () {
                    this._super();
                    this.on('search', this.onSeacrh.bind(this));
                    this.on('value', this.onUpdateValue.bind(this));
                    this.changeOptions();

                    return this;
                },
                initObservable: function () {
                    this._super()
                       .observe(['options', 'value', 'classes', 'search', 'revert', 'listVisible', 'multiselectFocus']);
                    return this;
                },
                openList: function () {
                    this.revert(true);
                },
                onSeacrh: function (value) {
                    this.filterValues(value);
                },
                onUpdateValue: function (value) {
                    this.value(value);
                },
                changeOptions: function () {
                    var options = this.options();
                    _.each(options, function (item) {
                        item.visible = "block";
                    });
                    this.setOptions(options);
                },
                filterValues: function (value) {
                    var options = this.options();
                    _.each(options, function (item) {
                        if (item.label.toLowerCase().indexOf(value.toLowerCase()) !== -1) {
                            item.visible = 'block';
                        } else {
                            item.visible = 'none';
                        }
                    });

                    this.setOptions([]);
                    this.setOptions(options);
                },
                getList: function () {
                    return this.options();
                },
                changeValue: function (value, parent) {
                    parent.value(value.value);
                },
                onFocusIn: function (ctx, event) {
                    !this.cacheUiSelect ? this.cacheUiSelect = event.target : false;
                    this.multiselectFocus(true);
                },
                onFocusOut: function () {
                    this.multiselectFocus(false);
                },

                outerClick: function () {
                    this.listVisible() ? this.listVisible(false) : false;
                    console.log(this.listVisible());
                    if (isTouchDevice) {
                        this.multiselectFocus(false);
                    }
                },
                toggleListVisible: function () {
                    this.listVisible(!this.listVisible());

                    return this;
                },
                isSelected: function (value) {
                    return this.multiple ? _.contains(this.value(), value) : this.value() === value;
                },
                toggleOptionSelected: function (data) {
                    var isSelected = this.isSelected(data.value);

                    if (!this.multiple) {
                        if (!isSelected) {
                            this.value(data.value);
                        }
                        this.listVisible(false);
                    }
                    return this;
                },
            }
        )
    }
);
