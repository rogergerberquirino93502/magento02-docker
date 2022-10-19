/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'underscore',
        'Magento_Ui/js/form/components/fieldset',
        'uiRegistry'
    ],
    function (_, Fieldset, reg) {
        'use strict';
        return Fieldset.extend(
            {
                defaults: {
                    disableForEntities: [
                        'import_jobs',
                        'export_jobs'
                    ],
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.parentName}.source.check_button:showMap',
                        toggleVisibilityByEntityValue: 'import_job_form.import_job_form.settings.entity:value'
                    },
                    openOnShow: true,
                    isShown: false,
                    inverseVisibility: false
                },

                /**
                 * Toggle visibility state.
                 *
                 * @param {Number} selected
                 */
                toggleVisibility: function (selected) {
                    this.isShown = selected;
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                    if (this.isShown) {
                        var map = reg.get(this.ns + '.' + this.ns + '.source_data_map_container.source_data_map');
                        if (map !== undefined) {
                            map.showSpinner(false);
                        }
                    }
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
