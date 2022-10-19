/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define([
    'jquery',
    'Firebear_ImportExport/js/modal/modal-component',
    'mage/storage',
    'uiRegistry',
    'mage/translate'
], function ($, Parent, storage, reg, $t) {
    'use strict';

    return Parent.extend({

        page: 0,

        ajaxSend: function (file) {
            this.end = 0;
            var lastEntityValue = '';
            var job = reg.get(this.job).data.entity_id;
            var lastEntity = reg.get(this.ns + '.' + this.ns + '.settings.last_entity_id');

            if (localStorage.getItem('jobId') && (typeof job == 'undefined' || !job)) {
                job = localStorage.getItem('jobId');
            }
            var object = reg.get(this.name + '.debugger.debug');
            var url = this.url + '?form_key=' + window.FORM_KEY;
            url += '&id=' + job + '&file=' + file + '&last_entity_value=' + lastEntityValue;

            var page = this.page + 1;
            this.page = page;
            url = url + '&page=' + page;

            this.currentAjax = this.urlAjax + '?file=' + file;
            if (lastEntity.value()) {
                lastEntityValue = lastEntity.value();
                url = url + '&last_entity_id=' + lastEntityValue;
                this.currentAjax = this.currentAjax + '&last_entity_id=' + lastEntityValue;
            }

            var urlAjax = this.currentAjax;
            $('.run').attr('disabled', true);
            var self = this;


            this.loading(true);
            storage.get(
                url
            ).done(
                function (response) {
                    var entity = reg.get(self.ns + '.' + self.ns + '.settings.entity');

                    if (entity.value() == 'catalog_product' && response['export_by_page' + job]) {
                        self.ajaxSend(file);
                    } else {
                        object.value(response.result);
                        $('.run').attr('disabled', false);
                        self.loading(false);
                        self.isNotice(response.result);
                        self.notice($t('The process is over'));
                        self.isError(!response.result);
                        if (response.file) {
                            self.isHref(response.result);
                            self.href(response.file);
                            if (lastEntity.value() < response.last_entity_id) {
                                lastEntity.value(response.last_entity_id);
                            }
                        }
                        self.end = 1;
                        self.page = 0;
                    }
                }
            ).fail(
                function (response) {
                    $('.run').attr('disabled', false);
                    self.loading(false);
                    self.isNotice(false);
                    self.isError(true);
                    self.end = 1;
                    self.page = 0;
                }
            );
            if ((self.page == 1) && (self.end != 1)) {
                setTimeout(self.getDebug.bind(self, urlAjax), 1500);
            }
        },
        toggleModal: function () {
            this._super();
            var object = reg.get(this.name + '.debugger.debug');
            object.showDebug(false);
        },
        getDebug: function (urlAjax) {
            var object = reg.get(this.name + '.debugger.debug');
            var self = this;
            $.get(urlAjax).done(function (response) {
                var text = response.console;
                if (text.length > 0) {
                    var array = text.split('<span text="item"></span><br/>');
                }
                urlAjax = self.currentAjax + '&number=0';
                if (text.length > 0) {
                    $('#debug-run').html(text);
                    $('.debug').scrollTop($('.debug')[0].scrollHeight);
                }
                if (self.end != 1) {
                    setTimeout(self.getDebug.bind(self, urlAjax), 1500);
                }
            }).fail(function (response) {
                self.finish(false);
                self.error(response.responseText);
            });
        },
    });
});
