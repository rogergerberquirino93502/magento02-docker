/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

/**
 * @api
 */
define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/components/html',
    'mageUtils',
    'ko',
    'uiRegistry'
], function ($, _, Component, utils, ko, registry) {
    'use strict';

    return Component.extend({

        defaults: {
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            }
        },

        /** @inheritdoc */
        initContainer: function (parent) {
            this._super();

            var content = this.content();
            this.content(content.replace(/__recordId__/g, parent.recordId));

            parent.on('active', this.onContainerToggle);

            return this;
        },

        /**
         * Initializes regular properties of instance.
         *
         * @returns {Abstract} Chainable.
         */
        initConfig: function () {
            var uid = utils.uniqueid(),
                name,
                scope;

            this._super();

            scope = this.dataScope.split('.');

            name = scope.length > 1 ? scope.slice(1, -1) : scope;
            name.push(this.relatedInput);

            registry.set('price.input.name.' + registry.get(this.parentName).recordId, utils.serializeName(name.join('.')));
            registry.set('price.input.' + registry.get(this.parentName).recordId, this);

            _.extend(this, {
                uid: uid,
                noticeId: 'notice-' + uid,
                errorId: 'error-' + uid,
                inputName: utils.serializeName(name.join('.')),
            });

            return this;
        },

        /**
         * Initializes observable properties of instance
         *
         * @returns {Object} Chainable.
         */
        setInitialValue: function () {
            if (_.isUndefined(this.value()) && !this.default) {
                this.clear();
            }

            return this._super();
        }
    });
});
