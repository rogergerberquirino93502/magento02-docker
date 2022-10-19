define([
    'jquery',
    'Magento_Ui/js/dynamic-rows/dnd'
], function ($, Element) {
    'use strict';

    return Element.extend({

        /**
         * Get copy original record
         *
         * @param {Object} record - original record instance
         * @returns {Object} draggable record instance
         */
        getRecordNode: function (record) {
            let $record = $(record),
                table = $record.parents('table')[0].cloneNode(true),
                $table = $(table);
            $table.find('tr').remove();
            let recordOrigin = $record.parents('tr')[0],
                $recordOrigin = $(recordOrigin),
                recordNew = recordOrigin.cloneNode(true),
                $recordNew = $(recordNew);
            $recordOrigin.find('select').each(function(i,elem) {
                $recordNew.find('#' + elem.id).val(elem.value);
            });
            $table.append(recordNew);

            return table;
        }
    });
});
