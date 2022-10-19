/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'underscore',
    'Magento_Ui/js/form/element/abstract'
    ],
    function (_, Acstract) {
        'use strict';

        return Acstract.extend(
            {
                defaults: {
                    percent:0,
                    percentWidth: '0%',
                    showDebug:true
                },
                initObservable: function () {
                    this._super()
                        .observe('percent percentWidth showDebug');
                    return this;
                },
            }
        );
    }
);
