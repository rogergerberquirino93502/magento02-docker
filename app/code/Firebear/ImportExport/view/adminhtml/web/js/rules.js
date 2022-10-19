/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

/**
 * @api
 */

define([
    'Magento_Rule/rules',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'prototype',
    'uiRegistry',
    'jquery/jquery.parsequery'
], function (VarienRulesForm, jQuery, alert, translate, prototype, registry) {
    'use strict';

    VarienRulesForm.prototype.initialize = function (parent, newChildUrl, index) {
        this.parent = $(parent);
        this.newChildUrl = newChildUrl;
        this.shownElement = null;
        this.updateElement = null;
        this.chooserSelectedItems = $H({});
        this.readOnly = false;
        this.index = index;
        this.relatedInput = null;
        this.values = {};

        this.parseInitialValues();

        if (this.parent) {
            var elems = this.parent.getElementsByClassName('rule-param');

            for (var i = 0; i < elems.length; i++) {
                this.initParam(elems[i]);
            }
        }

        this.setInititlaValues();
    };

    VarienRulesForm.prototype.parseInitialValues = function () {
        var self = this;
        var input = registry.get('price.input.' + this.index);

        if (!input) {
            return;
        }
        var inputScope = input.name.split('.');
        inputScope = inputScope.length > 1 ? inputScope.slice(0, -1) : inputScope;
        inputScope.push(input.relatedInput);
        var inputName = inputScope.join('.');

        this.relatedInput = registry.get(inputName);
        if (typeof this.relatedInput != 'undefined') {
            var values = this.relatedInput.value();
            values = values.toQueryParams();
            var result = {};

            jQuery.each(values, function (key, value) {
                key = key.replace(/\]\[/g, ':');
                key = key.replace(/\[/g, ':');
                key = key.replace(/\]/g, '');

                result = jQuery.extend(true, {}, result, self.generateArray(key, value));
            });

            this.values = result;
        }
    };

    VarienRulesForm.prototype.generateArray = function (key, value) {
        var array = {};
        var index = key.indexOf(':');

        if (index >= 0) {
            array[key.substr(0, index)] = this.generateArray(key.substr(index + 1), value);
        } else {
            array[key] = value;
        }

        return array;
    };

    VarienRulesForm.prototype.setInititlaValues = function () {
        var self = this;

        if (jQuery.isEmptyObject(this.values)) {
            return;
        }

        jQuery.each(this.values['rule']['conditions'], function (index, cond) {
            if (index.indexOf('--') === -1) {
                jQuery.each(cond, function (key, value) {
                    var elem = jQuery(self.parent).find('*[name="rule[conditions][' + index + '][' + key + ']"]');
                    if (elem.length) {
                        elem.val(value);
                        self.fireEvent(elem[0], 'change');
                    }
                });
            } else {
                var elem = jQuery(self.parent).find('.rule-param-new-child .element-value-changer')[0];
                var children_ul_id = elem.id.replace(/__/g, ':').replace(/[^:]*$/, 'children').replace(/:/g, '__');
                var children_ul = $(self.parent).select('#' + children_ul_id)[0];

                var new_elem = document.createElement('LI');
                new_elem.className = 'rule-param-wait';
                new_elem.innerHTML = jQuery.mage.__('This won\'t take long . . .');
                children_ul.insertBefore(new_elem, $(elem).up('li'));

                new Ajax.Request(self.newChildUrl, {
                    evalScripts: true,
                    parameters: {
                        form_key: FORM_KEY,
                        type: cond.type + '|' + cond.attribute,
                        id: index,
                        operator: cond.operator,
                        value: cond.value
                    },
                    onComplete: self.onAddNewChildComplete.bind(self, new_elem),
                    onSuccess: function (transport) {
                        if (self._processSuccess(transport)) {
                            $(new_elem).update(transport.responseText);
                            self.relatedInput.value(jQuery(self.parent).find('select, input, textarea').serialize());
                        }
                    }.bind(self),
                    onFailure: self._processFailure.bind(self)
                });
            }
        });
    };

    VarienRulesForm.prototype.fireEvent = function (elem, eventName) {
        var evt = document.createEvent('HTMLEvents');
        evt.initEvent(eventName, true, true);
        elem.dispatchEvent(evt);
    };

    VarienRulesForm.prototype.showChooserElement = function (chooser) {
        this.chooserSelectedItems = $H({});

        if (chooser.hasClassName('no-split')) {
            this.chooserSelectedItems.set(this.updateElement.value, 1);
        } else {
            var values = this.updateElement.value.split(','),
                s = '';

            for (var i = 0; i < values.length; i++) {
                s = values[i].strip();

                if (s != '') {
                    this.chooserSelectedItems.set(s, 1);
                }
            }
        }
        new Ajax.Request(chooser.getAttribute('url'), {
            evalScripts: true,
            parameters: {
                'form_key': FORM_KEY, 'selected[]': this.chooserSelectedItems.keys(),
                'record': this.index
            },
            onSuccess: function (transport) {
                if (this._processSuccess(transport)) {
                    jQuery(chooser).html(transport.responseText);
                    this.showChooserLoaded(chooser, transport);
                    jQuery(chooser).trigger('contentUpdated');
                }
            }.bind(this),
            onFailure: this._processFailure.bind(this)
        });
    };

    VarienRulesForm.prototype.hideParamInputField = function (container, event) {
        Element.removeClassName(container, 'rule-param-edit');
        var label = Element.down(container, '.label'),
            elem;

        if (!container.hasClassName('rule-param-new-child')) {
            elem = Element.down(container, '.element-value-changer');

            if (elem && elem.options) {
                var selectedOptions = [];

                for (var i = 0; i < elem.options.length; i++) {
                    if (elem.options[i].selected) {
                        selectedOptions.push(elem.options[i].text);
                    }
                }

                var str = selectedOptions.join(', ');

                label.innerHTML = str != '' ? str : '...';
            }

            elem = Element.down(container, 'input.input-text');

            if (elem) {
                var str = elem.value.replace(/(^\s+|\s+$)/g, '');

                elem.value = str;

                if (str == '') {
                    str = '...';
                } else if (str.length > 30) {
                    str = str.substr(0, 30) + '...';
                }
                label.innerHTML = str.escapeHTML();
            }
        } else {
            elem = container.down('.element-value-changer');

            if (elem.value) {
                this.addRuleNewChild(elem);
            }
            elem.value = '';
        }

        // Firebear code
        this.relatedInput.value(jQuery(this.parent).find('select, input, textarea').serialize());
        // Firebear code end

        if (elem && elem.id && elem.id.match(/__value$/)) {
            this.hideChooser(container, event);
            this.updateElement = null;
        }

        this.shownElement = null;
    };

    return VarienRulesForm;
});
