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
    'Magento_Ui/js/form/element/abstract',
    'uiLayout'
    ],
    function (_, $, utils, registry, Abstract, layout) {
        'use strict';

        return Abstract.extend(
            {
                defaults      : {
                    valueUpdate   : 'afterkeydown',
                    templateNotice: '',
                },
                initialize    : function () {
                    this._super();
                    this.changeNotice(this.value());
                    return this;
                },
                initObservable: function () {
                    this._super();
                    this.observe('notice');
                    return this;
                },
                onUpdate      : function (value) {
                    this._super();
                    this.changeNotice(value);
                    return this;
                },

                changeNotice: function (value) {
                    var notice = this.templateNotice;
                    notice = notice.replace(new RegExp("\%u", 'g'), value);
                    this.notice(notice);
                }
            }
        );
    }
);
