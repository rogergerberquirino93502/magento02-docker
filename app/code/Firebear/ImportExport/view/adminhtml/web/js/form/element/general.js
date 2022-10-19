/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'underscore'
    ],
    function (_) {
        'use strict';

        return {
            defaults: {
                prefixName       : '',
                prefixElementName: '',
                elementName      : '',
                suffixName       : ''
            },

            /**
             * Parses options and merges the result with instance
             *
             * @returns {Object} Chainable.
             */
            initConfig: function () {
                this._super();
                this.configureDataScope();

                return this;
            },

            /**
             * Configure data scope.
             */
            configureDataScope: function () {
                var recordId,
                prefixName,
                suffixName;

                // Get recordId
                recordId = _.last(this.parentName.split('.'));

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

                this.exportDataLink = this.dataScope = 'data.' + this.prefixName + '.' + this.elementName + suffixName;
                this.exports.value = this.links.value = this.provider + ':' + this.dataScope;

            },

            /** @inheritdoc */
            destroy: function () {
                this._super();

                this.source.remove(this.exportDataLink);
            },

            /**
             * Get HTML array from data scope.
             *
             * @param   {String} dataScopeString
             * @returns {String}
             */
            dataScopeToHtmlArray: function (dataScopeString) {
                var dataScopeArray, dataScope, reduceFunction;

                reduceFunction = function (prev, curr) {
                    return prev + '[' + curr + ']';
                };

                dataScopeArray = dataScopeString.split('.');

                dataScope = dataScopeArray.shift();
                dataScope += dataScopeArray.reduce(reduceFunction, '');

                return dataScope;
            }
        };
    }
);
