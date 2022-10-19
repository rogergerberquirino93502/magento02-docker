/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'underscore',
    'jquery',
    'mageUtils',
    'uiRegistry',
    'Firebear_ImportExport/js/form/element/additional-select',
    'uiLayout'
    ],
    function (_, $, utils, registry, Abstract, layout) {
        'use strict';

        return Abstract.extend(
            {
                defaults: {
                    fullOptions: null,
                    dataOptions:{},
                    imports: {
                        'setCode': '${$.ns}.${$.ns}.settings.entity:code'
                    },
                },
                initObservable: function () {
                    this._super();

                    this.observe('code notice');
                    this.fullOptions = this.options();
                    return this;
                },
                initConfig: function (config) {
                    this._super();
                    this.dataOptions = JSON.parse(config.sourceOptions);

                    return this;
                },
                setCode: function (value) {
                    var self = this;
                    var newOptions = [];
                    var data = this.fullOptions;
                    _.each(
                        data,
                        function (el) {
                            if (el.code == value) {
                                newOptions.push(el);
                            }
                        }
                    );
                    this.setOptions(newOptions);
                },
                onUpdate: function (value) {
                    this._super();
                    var self = this;
                    var element = registry.get('import_job_form.import_job_form.settings.entity').value();
                    _.each(
                        this.dataOptions,
                        function (el, key) {
                            if (key == element) {
                                if (value in el ) {
                                    self.notice(el[value]);
                                } else {
                                    self.notice('');
                                }
                            }
                        }
                    );
                    return this;
                },
            }
        );
    }
);
