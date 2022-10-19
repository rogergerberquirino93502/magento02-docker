/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'Firebear_ImportExport/js/components/dynamic-rows',
        'Magento_Ui/js/lib/spinner',
        'uiRegistry',
        'jquery',
        'underscore'
    ],
    function (Element, loader, reg, $, _) {
        'use strict';

        return Element.extend(
            {
                initialize: function () {

                    var self = this;
                    this._super();
                    reg.get('import_job_form.import_job_form.source_data_map_container_category.new_category_button', function (button) {
                        if (_.size(self.recordData()) == 0) {
                            self.visible(false);
                            if (button != undefined) {
                                button.visible(false);
                            }
                        }
                    });
                    return this;
                },
                onChange: function (value) {
                    if (value == 1) {
                        this.visible(true);
                        var button = reg.get('import_job_form.import_job_form.source_data_map_container_category.new_category_button');
                        if (button != undefined) {
                            button.visible(true);
                        }
                    }
                },
               /* initChildren: function () {
                    var self = this;
                    if (_.size(this.recordData()) != 0) {
                        reg.get('import_job_form.import_job_form.source_data_map_container_category.load_categories_button', function (button) {
                            button.loadForm().done(function (result) {
                                self.showSpinner(true);
                                self.getChildItems().forEach(function (data, index) {
                                    self.addChild(data, this.startIndex + index);
                                }, self);
                                return self;
                            });

                        });
                        return self;
                    } else {
                        this.showSpinner(true);
                        this.getChildItems().forEach(function (data, index) {
                            this.addChild(data, this.startIndex + index);
                        }, this);

                        return this;
                    }
                }*/
            }
        );
    }
);
