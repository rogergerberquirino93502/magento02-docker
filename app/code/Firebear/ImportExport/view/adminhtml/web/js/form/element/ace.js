/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 *
 */

define([
    'Magento_Ui/js/lib/view/utils/async',
    'underscore',
    'ko',
    'ace/ace',
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'Magento_Variable/variables'
], function ($, _, ko, ace, Abstract, reg) {
    'use strict';

    return Abstract.extend({
        defaults: {
            value: '',
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            },
            showSpinner:    false,
            loading:        false,
            editor: null,
            fullValue: null
        },
        initialize: function () {
            this._super();
            var self = this;
            $.async({
                component: this,
                selector: 'div.specialFire > div'
            }, function (element) {
                var data = reg.get(self.provider).data;
                var editor = ace.edit($(element).attr('id'));
                editor.setTheme("ace/theme/eclipse");
                editor.session.setMode("ace/mode/xml");
                editor.session.setValue(this.value());
                editor.session.setUseWrapMode(true);
                editor.session.setNewLineMode("unix");
            //    editor.setOption('maxLines', 5000);
                editor.$blockScrolling = Infinity;


                editor.setShowPrintMargin(true);
                editor.renderer.setShowGutter(true);
                editor.renderer.setOption('showLineNumbers', true);
                editor.session.on('change', function (delta) {
                    data[self.index] = editor.session.getValue();
                });
            }.bind(this));

            return this;
        },

        initObservable: function () {
            this._super()
                .observe(['value','fullValue']);

            return this;
        },
    });
});
