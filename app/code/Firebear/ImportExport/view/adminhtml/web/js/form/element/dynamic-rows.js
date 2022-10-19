define(
    [
        'mageUtils',
        'underscore',
        'uiLayout',
        'Magento_Ui/js/dynamic-rows/dynamic-rows'
    ],
    function (utils, _, layout, uiCollection) {
    'use strict';

    return uiCollection.extend({
        /**
         * Delete record
         *
         * @param {Number} index - row index
         *
         */
        deleteRecord: function (index, recordId) {
            var recordInstance,
                lastRecord,
                recordsData;

            if (this.deleteProperty) {
                recordsData = this.recordData();
                recordInstance = _.find(this.elems(), function (elem) {
                    return elem.index === index;
                });
                recordInstance.destroy();
                this.elems([]);
                this._updateCollection();
                this.removeMaxPosition();
                recordsData[recordInstance.index][this.deleteProperty] = this.deleteValue;
                this.recordData(recordsData);
                this.reinitRecordData();
                this.reload();
            } else {
                this.update = true;

                if (~~this.currentPage() === this.pages()) {
                    lastRecord =
                        _.findWhere(this.elems(), {
                            index: this.startIndex + this.getChildItems().length - 1
                        }) ||
                        _.findWhere(this.elems(), {
                            index: (this.startIndex + this.getChildItems().length - 1).toString()
                        });

                    lastRecord.destroy();
                }

                recordsData = this._getDataByProp(recordId);
                this._updateData(recordsData);
                this.update = false;
            }

            this._reducePages();
            this._sort();
        },

        /**
         * Add child components
         *
         * @param {Object} data - component data
         * @param {Number} index - record(row) index
         * @param {Number|String} prop - custom identify property
         *
         * @returns {Object} Chainable.
         */
        addChild: function (data, index, prop) {
            var template = this.templates.record,
                child;
        
            if(this.index == "source_filter_map"){
                this.maxPosition = this.countFilterRows();
            }

            index = index || _.isNumber(index) ? index : this.maxPosition;
            prop = prop || _.isNumber(prop) ? prop : index;
            _.extend(this.templates.record, {
                recordId: prop
            });

            child = utils.template(template, {
                collection: this,
                index: index
            });
            layout([child]);

            return this;
        },
        
        /**
         * Count filter rows
         * 
         * @returns {Number} index - row index
         */
        countFilterRows: function(){
            return this.getChildItems().length
        }
    });
});