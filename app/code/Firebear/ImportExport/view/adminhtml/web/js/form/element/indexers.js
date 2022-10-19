/**
 * @copyright: Copyright © 2020 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/element/multiselect',
    ],
    function ($, _, Abstract) {
        'use strict';

        return Abstract.extend(
            {
                defaults: {
                    isShown: false,
                    visible: false
                },

                toggleVisibility: function (isShown) {
                    this.isShown = isShown !== '0';
                    this.visible(this.isShown);
                    if (!this.visible()) {
                        this.value('');
                    }
                },
            }
        )
    }
);
