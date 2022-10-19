/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'underscore',
        'Magento_Ui/js/form/element/abstract',
        'Firebear_ImportExport/js/form/element/general'
    ],
    function (_, Acstract, general) {
        'use strict';

        return Acstract.extend(general).extend(
            {
                defaults: {
                    base: false
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

                changeText: function (value) {
                    if (this.base && !this.value()) {
                        this.value(value);
                    }
                    if (!this.base) {
                        this.base = true;
                    }
                }
            }
        );
    }
);
