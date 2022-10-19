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
                    selectExpr: null,
                    exports: {
                        "selectExpr": '${$.parentName}.cron:onExternalValue'
                    },
                    imports: {
                        'selectIn': '${$.parentName}.cron:value'
                    }
                },

                initialize: function () {
                    this._super();

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
                initObservable: function () {
                    this._super();
                    this.observe('selectExpr', this.selectExpr);
                    return this;
                },
                onChange: function () {
                    this.selectExpr(this.expr[this.value()]);
                },
                onUpdate: function () {
                    this._super();
                },
                selectIn: function (value) {
                    var self = this;
                    var result = _.indexBy(
                        self.expr,
                        function (expr, key) {
                            if (expr == value) {
                                return key
                            }
                        }
                    );

                        delete result[undefined];
                    console.log(value);
                    if (_.size(result) > 0) {
                        var keys = _.keys(result);
                        this.value(keys[0]);
                    } else {
                        if (value.length == 4) {
                            this.value('NONE');
                        } else {
                            this.value('C');
                        }
                    }
                }
            }
        );
    }
);
