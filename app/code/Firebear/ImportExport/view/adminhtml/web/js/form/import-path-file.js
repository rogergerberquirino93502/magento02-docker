/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Firebear_ImportExport/js/form/import-dep-file',
        'Magento_Ui/js/lib/spinner',
        'uiRegistry',
        'jquery'
    ],
    function (Element, loader, reg, $) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    listens: {
                        "value": "onChangeValue",
                        "${$.ns}.${$.ns}.source.type_file:value": "onFormatValue"
                    }
                },
                onChangeValue: function (value) {
                    if (this.isShown) {
                        var map = reg.get(this.ns + '.' + this.ns + '.source_data_map_container.source_data_map');
                        var mapAttributeValue = reg.get(this.ns + '.' + this.ns + '.source_data_map_container_attribute_values.source_data_attribute_values_map');
                        var removeMapping = reg.get(this.ns + '.' + this.ns + '.source.remove_current_mappings');
                        if (removeMapping !== undefined && removeMapping.value() == 1) {
                            map.deleteRecords();
                            mapAttributeValue.deleteRecords();
                        }
                        map._updateCollection();
                        this.resetCategoryMapping();
                        reg.get(this.ns + '.' + this.ns + '.source.check_button').showMap(0);
                        reg.get(this.ns + '.' + this.ns + '.source.check_button').validMap = 0;
                        /**
                         * @todo Need to make dynamic AJAX to select sheet
                         */
                        this.fetchSheet();
                    }
                },

                fetchSheet: function () {
                    var self = this;
                    var selected = reg.get(this.parentName + '.type_file').value();
                    var fetchSheet = reg.get(this.parentName + '.xlsx_sheet');
                    if (selected in fetchSheet.valuesForOptions) {
                        if (_.size(self.getFormElements()) > 0) {
                            var source = reg.get(this.ns + '.' + this.ns + '.source.import_source');
                            jQuery.ajax({
                                url: fetchSheet.url,
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    form_data: self.getFormElements(),
                                    source_type: source.value(),
                                    form_key: window.FORM_KEY
                                },
                                complete: function (response) {
                                    fetchSheet.options(response.responseJSON);
                                }
                            });
                        }
                    }
                },

                getFormElements: function () {
                    var formElements = [];
                    var provider = reg.get(this.provider);
                    _.each(
                        provider.data,
                        function (element, key) {
                            if (element != null && element.length > 0) {
                                formElements.push(key + '+' + element);
                            }
                        }
                    );
                    return formElements;
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
