/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'underscore',
    'mageUtils',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'uiLayout'
    ],
    function (_, utils, registry, Abstract, layout) {
        'use strict';

        return Abstract.extend(
            {
                defaults: {
                    expr: {},
                    listens: {
                        '${$.parentName}.cron:value':'onChange'
                    }
                   /* imports: {
                        'onChange':'${$.parentName}.cron:value'
                    }*/
                },

                initialize: function () {
                    this._super();
                    this.updateCron(true);

                    return this;
                },

                initConfig: function (config) {
                    this._super();

                    var self = this;
                    _.each(
                        config.options,
                        function (index) {
                            self.expr[index.value] = index.expr;
                        }
                    );

                    return this;
                },
                onUpdate: function () {
                    this._super();
                    this.updateCron(false);
                },
                onChange: function (value) {
                    var self = this;
                    var find  = 0;
                    var thisValue = this.value();
                    if (value.length == 0) {
                        find = 1;
                        this.value('NONE');
                    }
                    _.each(this.expr, function (item, key) {
                        if (item == value) {
                            if (thisValue != key) {
                                self.value(key);
                            }
                            find = 1;
                        }
                    });
                    if (find == 0 && thisValue != 'C') {
                        this.value('C');
                    }
                },
                updateCron: function (check) {
                    var self = this;
                    var setCron = false;
                    registry.get(
                        this.parentName + '.cron',
                        function (cron) {
                            if (check && cron.value()) {
                                setCron = true;
                            }
                            if (!setCron) {
                                cron.setExpr(self.expr[self.value()]);
                            }
                        }
                    );
                }
            }
        );
    }
);
