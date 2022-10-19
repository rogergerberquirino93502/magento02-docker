/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/element/checkbox-set',
        'uiRegistry'
    ],
    function (Element, reg) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.ns}.${$.ns}.settings.entity:value'
                    },
                    isShown: false,
                    inverseVisibility: false,
                    visible: false,
                    listens: {
                        'value': 'onValueChange'
                    },
                    entityCode: '',
                    lastEntityId: '',
                },
                initialize: function () {
                    this._super();
                    return this;
                },
                initObservable: function () {
                    this._super();

                    this.observe('entityCode');
                    this.observe('lastEntityId');
                    var entity = reg.get(this.ns + '.' + this.ns + '.settings.entity'),
                        lastEntity = reg.get(this.ns + '.' + this.ns + '.settings.last_entity_id');
                    if (this.entityCode() === '' || this.entityCode() !== entity.value()) {
                        this.entityCode(entity.value());
                        this.lastEntityId(lastEntity.value());
                    }

                    return this;
                },
                toggleVisibility: function (selected) {
                    this.isShown = selected in this.valuesForOptions;
                    var lastEntity = reg.get(this.ns + '.' + this.ns + '.settings.last_entity_id');
                    if (this.entityCode() === '' || this.entityCode() !== selected) {
                        this.entityCode(selected);
                        lastEntity.value(0);
                    }
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                },
                initConfig: function (config) {
                    this._super();
                },
                onValueChange: function (value) {
                    var lastEntity = reg.get(this.ns + '.' + this.ns + '.settings.last_entity_id'),
                        entity = reg.get(this.ns + '.' + this.ns + '.settings.entity');
                    if (_.size(value) > 1) {
                        var lastValue = _.last(value);
                        var obj = this.seacrhEl(lastValue);
                        if (obj !== undefined) {
                            if (obj['parent'].length && _.indexOf(value, obj['parent']) == -1
                                && !this.searchParent(lastValue, value)
                            ) {
                                value.pop();
                                this.value(value);
                                // lastEntity.value(0);
                            }
                        }
                    }

                    if (this.entityCode() === '' || this.entityCode() !== entity.value()) {
                        lastEntity.value(0);
                    }
                },
                seacrhEl: function (val) {
                    var element;
                    _.each(
                        this.options,
                        function (obj) {
                            if (obj.value == val) {
                                element = obj;
                            }
                        }
                    );

                    return element;
                },
                searchParent: function (val, value) {
                    var parents = [];
                    var self = this;
                    _.each(
                        value,
                        function (item) {
                            if (item != value) {
                                var obj = self.seacrhEl(item);
                                if (obj !== undefined) {
                                    parents.push(obj['parent']);
                                }
                            }
                        }
                    );
                    return (_.indexOf(parents, val) == -1) ? false : true;
                }
            }
        );
    }
);
