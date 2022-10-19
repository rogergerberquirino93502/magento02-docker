/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/element/select',
        'uiRegistry'
    ],
    function (Element, reg) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    listens: {
                        "value": "onChangeValue",
                        "${$.ns}.${$.ns}.source.type_file:value": "onFormatValue"
                    },
                    imports: {
                        toggleVisibility: '${$.parentName}.type_file:value'
                    },
                    isShown: false,
                    inverseVisibility: false,
                    visible: false
                },

                toggleVisibility: function (selected) {
                    this.isShown = selected in this.valuesForOptions;
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                },

                onChangeValue: function (value) {
                    if (this.isShown) {
                        var map = reg.get(this.ns + '.' + this.ns + '.source_data_map_container.source_data_map');
                        var mapAttributeValue = reg.get(this.ns + '.' + this.ns + '.source_data_map_container_attribute_values.source_data_attribute_values_map');
                        var mapCategory = reg.get(this.ns + '.' + this.ns + '.source_data_map_container_category.source_data_categories_map');
                        var removeMapping = reg.get(this.ns + '.' + this.ns + '.source.remove_current_mappings');
                        if (removeMapping !== undefined && removeMapping.value() == 1) {
                            map.deleteRecords();
                            mapAttributeValue.deleteRecords();
                        }
                        map._updateCollection();
                        mapCategory.deleteRecords();
                        mapCategory._updateCollection();
                        reg.get(this.ns + '.' + this.ns + '.source.check_button').showMap(0);
                        reg.get(this.ns + '.' + this.ns + '.source.check_button').validMap = 0;
                    }
                },

                onFormatValue: function (value) {
                    if (this.isShown) {
                        this.value('');
                    }
                }
            }
        );
    }
);
