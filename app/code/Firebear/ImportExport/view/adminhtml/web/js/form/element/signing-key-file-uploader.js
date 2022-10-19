/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'Magento_Ui/js/form/element/file-uploader',
        'uiRegistry'
    ],
    function ($, Element, registry) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    listens: {
                        "${$.ns}.${$.ns}.source.type_file:value": "onFormatValue"
                    },
                    imports : {
                        toggleVisibility: '${$.parentName}.import_source:value'
                    },
                    visible: false
                },
                toggleVisibility: function (selected) {
                    this.isShown = selected in this.valuesForOptions;
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                },
                addFile: function (file) {
                    var name = file.path + file.file,
                        path_file = registry.get(this.name + '_path');
                    path_file.value(name);
                    this._super();
                },
                removeFile: function (e, data) {
                    var path_file = registry.get(this.name + '_path');
                    path_file.value('');
                    this._super();
                },
                onFormatValue: function (value) {
                    this.clear();
                }
            }
        );
    }
);
