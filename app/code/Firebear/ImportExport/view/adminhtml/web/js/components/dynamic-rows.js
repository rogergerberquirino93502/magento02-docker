/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/dynamic-rows/dynamic-rows',
        'Magento_Ui/js/lib/spinner',
        'uiRegistry',
        'jquery',
        'underscore',
        'mage/translate'
    ],
    function (Element, loader, reg, $, _, $t) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    prevData: null,
                    addReset: true,
                    addResetLabel: $t('Reset mapping'),
                    addCustom:true,
                    addCustomLabel: $t("Add New Custom"),
                    custom:0
                },
                deleteRecords: function () {
                    this.destroyChildren();
                    this.recordData([]);
                    this.reload();
                    this.showSpinner(false);
                },
                deleteRecord: function (index, recordId) {
                    this.prevData = _.clone(this.recordData());
                    this._super(index, recordId);
                },
                processingAddCustomChild: function (ctx, index, prop) {
                    this.custom = 1;
                    this.processingAddChild(ctx, index, prop);
                },
                processingAddOrigChild: function (ctx, index, prop) {
                    this.custom = 0;
                    this.processingAddChild(ctx, index, prop);
                },
                addChild: function (data, index, prop) {

                    if (typeof data.custom !== 'undefined') {
                        this.custom = data.custom;
                    }

                    return this._super(data, index, prop);
                },
            }
        );
    }
);
