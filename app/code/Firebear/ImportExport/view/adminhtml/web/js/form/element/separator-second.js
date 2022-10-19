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
    './separator',
    'uiLayout'
    ],
    function (_, $, utils, registry, Separator, layout) {
        'use strict';

        return Separator.extend(
            {
                defaults      : {
                    valueMain: null,
                    imports:{
                        'setValueMain': '${$.parentName}.category_levels_separator:value'
                    }
                },
                initialize    : function () {
                    this._super();
                    this.changeNotice();
                    return this;
                },
                initObservable: function () {
                    this._super();
                    this.observe('valueMain');
                    return this;
                },
                onUpdate      : function (value) {
                    this._super();
                    this.changeNotice();
                    return this;
                },
                setValueMain  : function (value) {
                    this.valueMain(value);
                    this.changeNotice();
                },
                changeNotice  : function () {
                    var notice = this.templateNotice;
                    var self = this;
                    notice = notice.replace(new RegExp("\%u2", 'g'), self.value()).replace(new RegExp("\%u1", 'g'), self.valueMain());
                    this.notice(notice);
                }
            }
        );
    }
);
