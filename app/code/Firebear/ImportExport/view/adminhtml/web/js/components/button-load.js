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
                    showMap: 0,
                    elementTmpl: 'Firebear_ImportExport/form/element/button-load',
                    loadmapUrl: null,
                    defaultOptions: null,
                    imports: {
                        setOptions: '${$.parentName}.platforms:value'
                    }
                },
                initialize: function () {
                    this._super();
                    return this;
                },
                initConfig: function (config) {
                    this._super();
                    this.defaultOptions = JSON.parse(config.options);
                    return this;
                },
                setOptions: function (value) {
                    this.visible(false);
                    var self = this;
                    var result = _.find(this.defaultOptions, function (num, key) {
                        return key == value;
                    });
                    if (_.size(result) > 0) {
                        self.visible(true);
                    }
                },
                initObservable: function () {
                    return this._super()
                        .observe('error showMap');
                },
                action: function () {
                    var button = registry.get(this.parentName + '.validate_button');
                    var removeMapping = registry.get(this.ns + '.' + this.ns + '.source.remove_current_mappings');
                    button.error('');
                    if (this.validateGeneral()) {
                        var map = registry.get(this.ns + '.' + this.ns + '.source_data_map_container.source_data_map');
                        var mapAttributeValue = registry.get(this.ns + '.' + this.ns + '.source_data_map_container_attribute_values.source_data_attribute_values_map');
                        var mapCategory = registry.get(this.ns + '.' + this.ns + '.source_data_map_container_category.source_data_categories_map');
                        if (removeMapping !== undefined && removeMapping.value() == 1) {
                            map.deleteRecords();
                            mapAttributeValue.deleteRecords();
                        }
                        map._updateCollection();
                        mapCategory.deleteRecords();
                        mapCategory._updateCollection();
                      //  deleteRecords
                        this.generateAttributesMap();
                    }
                },
                validateGeneral: function () {
                    var params = registry.get(this.ns + '.' + this.ns + '.source');
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
                        this.error($t('Please configure File Path.'))
                        return false;
                    } else {
                        this.error('');
                    }

                    return true;
                },
                generateAttributesMap: function () {
                    var ajaxSend = this.ajaxSend.bind(this);
                    var addChilds = this.addChilds.bind(this);
                    this.getParams().then(ajaxSend).then(addChilds);
                },
                addChilds: function () {
                    this.error('');
                    var maps = JSON.parse(localStorage.getItem('map'));
                    var columns = JSON.parse(localStorage.getItem('columns'));
                    var options = JSON.parse(localStorage.getItem('options'));
                    var linked = {};
                    var nothings = [];
                    var self = this;
                    var map = registry.get(this.parentName + '.source_data_map');
                    var number = map.getChildItems().length;
                    _.each(columns, function (element, key) {
                        var finded = 0;
                        var number = -1;
                        _.each(maps, function (index, num) {
                            if (num == element) {
                                number = index.reference;
                            }
                        });
                        if (number != -1) {
                            finded  = 1;
                            linked[number] = element;
                        }
                        if (!finded) {
                            _.each(options, function (index) {
                                if (index.label == element || index.value == element) {
                                    finded = 1;
                                }
                            });
                        }
                        if (!finded) {
                            nothings.push(element);
                        }
                    });
                    var defaults = {};
                    _.each(maps, function (index, num) {
                        if (num == index.reference && index.default != '') {
                            defaults[index.reference] = index.default;
                        }
                    });
                      var count  = 0;
                      count = _.size(linked) + _.size(nothings);

                    for (var i = number; i < number + _.size(linked) + _.size(defaults); i++) {
                        map.processingAddChild(false, i, false);
                    }
                    _.each(
                        linked,
                        function (element, key) {
                            registry.get(
                                self.parentName + ".source_data_map." + number + '.source_data_system',
                                function (system) {
                                    system.value(key);
                                }
                            );
                            registry.get(
                                self.parentName + ".source_data_map." + number + '.source_data_import',
                                function (system) {
                                    system.value(element);
                                }
                            );
                            number++;
                        }
                    );
                    _.each(defaults, function (element, key) {
                        registry.get(
                            self.parentName + ".source_data_map." + number + '.source_data_system',
                            function (system) {
                                system.value(key);
                            }
                        );
                        registry.get(
                            self.parentName + ".source_data_map." + number + '.source_data_import',
                            function (system) {
                                system.value('');
                            }
                        );
                        registry.get(
                            self.parentName + ".source_data_map." + number + '.source_data_replace',
                            function (system) {
                                system.value(element);
                            }
                        );
                        number++;
                    });
                    if (_.size(nothings) > 0) {
                        this.error([$t('The import may not work correctly. No match for fields: ') + nothings.join(", ")]);
                    }
                }
            }
        )
    }
);
