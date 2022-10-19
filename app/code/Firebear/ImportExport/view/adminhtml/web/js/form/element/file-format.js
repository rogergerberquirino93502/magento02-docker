/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/element/select',
        'jquery',
    ],
    function (Element, $) {
        'use strict';

        return Element.extend(
            {
                onAfterRender: function() {
                    try {
                        this.hideOptions();
                    } catch (e) {
                        console.error(e);
                    }
                },

                onUpdate: function() {
                    this._super();

                    try {
                        this.hideOptions();
                    } catch (e) {
                        console.error(e);
                    }
                },

                hideOptions: function() {
                    this.hideEntityOptions();
                    this.hideSourceOptions();
                    this.hideFileOptions();
                },

                hideEntityOptions: function() {
                    let $entity = $("select[name='entity']");
                    $entity.find('option').show();

                    let dependencies = this.getEntityOptions();
                    if (dependencies.hasOwnProperty(this.value())) {
                        $entity.val(dependencies[this.value()]);
                        $entity.change();

                        $entity.find('option[value!=' + dependencies[this.value()] + ']').hide();
                    }
                },

                hideSourceOptions: function() {
                    let $entity = $("select[name='export_source_entity']");
                    let $options = $entity.find('option');
                    $options.show();

                    let dependencies = this.getSourceOptions();
                    if (dependencies.hasOwnProperty(this.value())) {
                        $entity.val(dependencies[this.value()]);
                        $entity.change();

                        $entity.find('option[value!=' + dependencies[this.value()] + ']').hide();
                    } else {
                        let hiddenValues = [];
                        $options.each(function(key, node) {
                            let value = $(node).val();
                            if (dependencies.hasOwnProperty(value) && value === dependencies[value]) {
                                hiddenValues.push(value);
                                $(node).hide();
                            }
                        });

                        if (hiddenValues.indexOf($entity.val()) !== -1) {
                            $entity.val($($options[0]).val());
                            $entity.change();
                        }
                    }
                },

                hideFileOptions: function () {
                    let $entity = $("select[name='entity']");
                    let files = this.getFileOptions();
                    let fileFormat = $("select[name='behavior_field_file_format']");
                    let $options = fileFormat.find('option');
                    $options.show();

                    if (files.hasOwnProperty($entity.val())) {
                        this.value(files[$entity.val()]);

                        fileFormat.find('option[value!=' + files[$entity.val()] + ']').hide();
                    }
                },

                getEntityOptions: function() {
                    return {
                        'google': 'catalog_product'
                    };
                },

                getSourceOptions: function() {
                    return {
                        'google': 'google'
                    };
                },

                getFileOptions: function() {
                    return {
                        'import_jobs' : 'csv',
                        'export_jobs' : 'csv'
                    }
                },

                toggleVisibility: function (source) {
                    let files = this.getFileOptions();
                    let fileFormat = $("select[name='behavior_field_file_format']");
                    let $options = fileFormat.find('option');
                    $options.show();

                    if (files.hasOwnProperty(source)) {
                        this.value(files[source]);
                        fileFormat.find('option[value!=' + files[source] + ']').hide();
                    }
                }
            }
        );
    }
);
