/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
define(
    [
        'jquery',
        'underscore',
        'Firebear_ImportExport/js/form/element/additional-select',
        'uiRegistry',
        'mage/translate'
    ],
    function ($, _, Abstract, reg, $t) {
        'use strict';
        return Abstract.extend(
            {
                default: {
                    listens: {
                        'value': 'onChangeValue'
                    },
                    importSource: null
                },

                onUpdate: function () {
                    this._super();
                    var source = reg.get(this.ns + '.' + this.ns + '.source.import_source');
                    if (source !== undefined) {
                        if (source.value() !== undefined) {
                            this.importSource = source.value();
                        } else {
                            if (this.importSource) {
                                source.value(this.importSource);
                            }
                        }
                    }
                }
            }
        )
    }
);
