/**
 * scan-directory-checkbox
 *
 * @copyright Copyright © 2020 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */
define([
    'jquery',
    'Firebear_ImportExport/js/form/element/checkbox-switch'
], function ($, CheckboxSwitch) {
    'use strict';

    return CheckboxSwitch.extend({
        onCheckedChanged: function (newChecked) {
            if (newChecked === true) {
                $('.import-job-edit #save_and_run').attr('disabled', true);
            } else {
                $('.import-job-edit #save_and_run').attr('disabled', false);
            }
            return this._super();
        }
    });
});
