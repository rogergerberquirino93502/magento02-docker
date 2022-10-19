/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal-component',
    'mage/storage',
    'uiRegistry',
    'mage/translate'
], function ($, Parent, storage, reg, $t) {
    'use strict';

    return Parent.extend({
        defaults: {
            url: '',
            urlAjax: '',
            beforeUrl: '',
            urlProcess:'',
            urlCheck: '',
            currentAjax:'',
            terminateUrl:'',
            job: 0,
            loading: false,
            template: 'Firebear_ImportExport/form/modal/modal-component',
            editUrl:'',
            isJob:0,
            end:0,
            isNotice: true,
            isError: false,
            href:'',
            isHref: false,
            counter:0,
            notice:$t('Job saved successfully - please click Run button for launch'),
            error:$t('Error')
        },
        actionRun: function () {
            this.isNotice(false);
            this.isError(false);
            $('.debug').html('');
            var job = reg.get(this.job).data.entity_id;
            if (job == '') {
                job = localStorage.getItem('jobId');
                this.isJob = 1;
            }
            var berforeUrl = this.beforeUrl + '?id=' + job;
            var ajaxSend = this.ajaxSend.bind(this);
            this.getFile(berforeUrl).then(ajaxSend);
        },
        initObservable: function () {
            this._super()
                .observe('loading isNotice notice isHref href error isError');
            return this;
        },
        ajaxSend: function (file) {
            this.end = 0;
            this.counter = 0;
            var job = reg.get(this.job).data.entity_id;
            if (localStorage.getItem('jobId')) {
                job = localStorage.getItem('jobId');
            }
            var object = reg.get(this.name + '.debugger.debug');
            object.percent(0);
            object.percentWidth('0%');
            var url = this.url +     '?form_key='+ window.FORM_KEY;
            url +=  '&id=' + job + '&file=' + file;
            this.currentAjax = this.urlAjax + '?file=' + file;
            var urlAjax = this.currentAjax;

            $('.run').attr('disabled', true);
            var self = this;
            this.loading(true);
            storage.get(
                url
            ).done(
                function (response) {
                    if (response.result != false) {
                        object.value(true);
                        var url = self.urlCheck + '?form_key='+ window.FORM_KEY + '&job=' + job + '&file=' + file;
                        $.get(url).done(function (response) {
                            if (response.result > 0) {
                                var urls = [];
                                object.percent(10);
                                object.percentWidth('10%');
                                var step = Math.round((80 / response.result)*100)/100;
                                var finish = false;
                                if (response.result > 0) {
                                    finish = self.setData(response.result, 0, 0, job, file, step, object, 1);
                                }
                            } else {
                                self.finish(true);

                                return true;
                            }
                        }).fail(
                            function (response) {
                                object.value(false);
                                self.finish(false);
                                self.error(response.responseText);
                            }
                        );
                        self.isError(false);
                    } else {
                        object.value(false);
                        self.setData(0, 0, 0, job, file, 0, object, 0);
                    }
                }
            ).fail(
                function (response) {
                    self.finish(false);
                    self.error(response.responseText);
                }
            );
            if (self.end != 1) {
                setTimeout(self.getDebug.bind(self, urlAjax), 3500);
            }
        },
        getDebug: function (urlAjax) {
            var object = reg.get(this.name + '.debugger.debug');
            var self = this;
            $.get(urlAjax).done(function (response) {
                var text = response.console;
                var array = text.split('<span text="item"></span><br/>');
                if (text.length > 0 && _.size(array) > 0) {
                    self.counter += _.size(array) - 1;
                    urlAjax = self.currentAjax + '&number=' + self.counter;
                }
                if (text.length > 0) {
                    $('#debug-run').append(text);
                    $('.debug').scrollTop($('.debug')[0].scrollHeight);
                }
                if (self.end != 1) {
                    setTimeout(self.getDebug.bind(self, urlAjax), 3500);
                }
            }).fail(function (response) {
                self.finish(false);
                self.error(response.responseText);
            });
        },
        getFile:function (beforeUrl) {
            var object = $.Deferred();
            var file = '';
            storage.get(
                beforeUrl
            ).done(
                function (response) {
                    file = response;
                    object.resolve(file);
                }
            ).fail(
                function (response) {
                    file = null;
                    object.resolve(file);
                }
            );
            return object.promise();
        },
        setData:function (counter, count, error, job, file, step, object, status) {
            var  self = this;
            var urlData = self.urlProcess + '?form_key=' + window.FORM_KEY + '&number=' + count + '&job=' + job + '&file=' + file +'&error=' + error;
            var terminateUrl = self.terminateUrl +'?job=' + job + '&file=' + file+ '&status=' + status;
            if (count <= counter - 1) {
                $.get(
                    urlData
                ).done(
                    function (response) {
                        var percent = Math.round(object.percent() * 100) / 100 + step;
                        object.percent(percent);
                        object.percentWidth(percent + '%');
                        if (response.result == true) {
                            self.setData(counter, count + 1, parseInt(response.count), job, file, step, object, status);
                        } else {
                            self.finish(false);
                        }
                    }
                ).fail(
                    function (response) {
                        self.finish(false);
                        self.error(response.responseText);
                    }
                );
            } else {
                storage.get(
                    terminateUrl
                ).done(
                    function (response) {
                        if (response.result) {
                            self.finish(true);

                            return true;
                        } else {
                            self.finish(false);

                            return false;
                        }
                    }
                ).fail(
                    function (response) {
                        self.finish(false);

                        return false;
                    }
                );
            }

            return true;
        },
        finish: function (bool) {
            var self = this;
            self.end = 1;
            $('.run').attr('disabled', false);
               self.loading(false);
               
              var object = reg.get(this.name + '.debugger.debug');
              object.percent(100);
              object.percentWidth('100%');
            if (bool == false) {
                self.isNotice(false);
                self.isError(true);
            } else {
                self.isNotice(true);
                self.isError(false);
                self.notice($t('The process is over'));
            }
        },
        toggleModal: function () {
            this._super();
           // this.isNotice(false);
            this.isHref(false);
            this.isError(false);
            $('.debug').html('');
        },
        /**
         * Close moda
         */
        closeModal: function () {
            this._super();
            this.notice('Job saved successfully - please click Run button for launch');
            if (this.isJob) {
                location.href = this.editUrl + 'entity_id/' + localStorage.getItem('jobId');
            }
        },
        onSuccess: function (promise, data) {
            var errors;

            if (data.error) {
                errors = _.map(data.messages, this.createError, this);

                promise.reject(errors);
            } else {
                promise.resolve(data);
            }
        },
        
    });
});
