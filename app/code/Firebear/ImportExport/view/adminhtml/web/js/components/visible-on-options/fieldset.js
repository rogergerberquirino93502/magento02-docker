/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Magento_Ui/js/form/components/fieldset'
    ],
    function (Fieldset) {
        'use strict';
        return Fieldset.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    disableForEntities: [
                        'import_jobs',
                        'export_jobs'
                    ],
                    imports: {
                        toggleVisibility: '${$.parentName}.settings.entity:value'
                    },
                    openOnShow: true,
                    isShown: false,
                    inverseVisibility: false
                },

                /**
                 * Toggle visibility state.
                 *
                 * @param {String} selected
                 */
                toggleVisibility: function (selected) {
                    this.isShown = !Object.keys(this.valuesForOptions).length || (selected in this.valuesForOptions);
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);

                    if (this.openOnShow) {
                        this.opened(this.inverseVisibility ? !this.isShown : this.isShown);
                    }
                },
                /**
                 * Toggle visibility state by entity value
                 *
                 * @param {string} source
                 */
                toggleVisibilityByEntityValue: function (source) {
                    if (_.contains(this.disableForEntities, source)) {
                        this.visible(false);
                    }
                },

                initConfig: function () {
                    this._super();
                    return this;
                },
            }
        );
    }
);
