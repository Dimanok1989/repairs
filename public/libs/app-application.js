function Application() {

    /** Токен пользователя */
    this.token = $('meta[name="token"]').attr('content');

    /** Объект модального окна */
    this.modal;

    /** Данные текущие */
    this.json;

    /** Выбор проекта при заведении новой заявки */
    this.selectedProject = e => {

        let project = $(e).data('project');
        $('#select-project').addClass('d-none');
        $('#form-break, #selected-project-'+project).removeClass('d-none');
        $('[name="project"]').val(project);

    }

    /** Сохранение новой заявки */
    this.addNewApplication = e => {

        let form = $('#form-break');
        let data = form.serializeArray();

        $(e).prop('disabled', true);
        $('#loading-add-application').removeClass('d-none').addClass('d-flex');

        app.formValidRemove(form);

        app.ajax(`/api/addNewApplication`, data, json => {

            if (json.error) {

                $('#loading-add-application').removeClass('d-flex').addClass('d-none');
                $(e).prop('disabled', false);

                app.formValidErrors(form, json.inputs);
                form.data('valid', 1);
                return app.globalAlert(json.error, json.done, json.code);

            }

            $(location).attr('href', json.data.link);

        }, err => {

            $('#loading-add-application').removeClass('d-flex').addClass('d-none');
            $(e).prop('disabled', false);

            let jsonerror = typeof err.responseJSON == "object" ? err.responseJSON : false;
            return app.globalAlert("Произошла неизвестная ошибка сервера", "error", err.status, false, jsonerror);

        });

    }

    /** Проверка наличия заявок по номеру машину */
    this.checkNumber = e => {
        let data = {
            number: $(e).val(),
        }
        console.log(data);
    }

    /** Загрузка файлов */
    this.uploadFiles = (e) => {

        let formData = new FormData(),
            files = $(e).prop('files');

        // Пройти в цикле по всем файлам
	    for (var i = 0; i < files.length; i++)
            formData.append('images[]', files[i]);
            
        let url = "/api/uploadImagesAddApplication";

        if (this.token)
            url += "?token=" + this.token;

        app.file(url, formData, json => {

            $('#imagesformbutton, #button-add-application').prop('disabled', false);
            $('#imagesformprogress').addClass('d-none')
            .find('.progress-bar').css('width', 0).removeClass('bg-success').addClass('bg-dark');
            $('#imagesform').val('');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $.each(json.data.files, (i,row) => {

                if (row.error) {
                    $('#images-data').append(`<div class="col mb-4">    
                        <div class="card text-white bg-danger h-100 text-left"> 
                            <div class="card-header bg-transparent p-2">Ошибка</div>                 
                            <div class="card-body p-2">
                                <small>${row.name} ${row.error}</small>
                            </div>
                        </div>
                    </div>`);
                }
                else {
                    $('#images-data').append(`<div class="col mb-4" id="card-img-row-${row.id}">    
                        <div class="card h-100">
                            <div class="item-responsive item-16by9">
                                <div class="item-responsive-content"></div>
                                <img src="${row.link}" class="d-none" alt="${row.name}" onload="application.loadedImg(this);">
                            </div>
                            <input type="hidden" name="images[]" value="${row.id}" />
                            <!-- <div class="card-body p-2">
                                <button type="button" class="btn btn-danger btn-sm" onclick="application.deleteImg(this); data-id="${row.id}"><i class="fas fa-trash"></i></button>
                            </div> -->
                        </div>
                    </div>`);
                }
            });

        }, () => {

            $('#imagesformbutton, #button-add-application').prop('disabled', true);
            $('#imagesformprogress').removeClass('d-none')
            .find('.progress-bar').css('width', 0).removeClass('bg-success').addClass('bg-dark');

        }, percent => {

            $('#imagesformprogress .progress-bar').css('width', percent+"%");
            
            if (percent > 99)
                setTimeout(() => {
                    $('#imagesformprogress .progress-bar').removeClass('bg-dark').addClass('bg-success');
                }, 600);
                

        }, err => {

            $('#imagesformbutton, #button-add-application').prop('disabled', false);
            $('#imagesformprogress').addClass('d-none')
            .find('.progress-bar').css('width', 0).removeClass('bg-success').addClass('bg-dark');
            $('#imagesform').val('');

            return app.globalAlert("Сервер не справился с загрузкой файлов, если Вы загружаете одновременно несколько файлов, попробуйте загрузить их по одному. Если ошибка повторится, то обновите страницу и попробуйте загрузить файлы снова и, если ошибка повторится, обратитесь к администрации сайта", "error", err.status);

        }, true);

    }

    this.loadedImg = e => {

        let img = $(e).attr('src');

        $(e).parent().find('div').css({
            background: 'url('+img+') 50% no-repeat',
            backgroundSize: '100% auto'
        });

    }

    /** Последний полученный идентификатор заявки */
    this.lastApplicationId = 0;

    /** Вывод списка заявок */
    this.getApplicationsList = () => {

        let data = {
            client: $('#applications-list').data('client'),
            project: $('#applications-list').data('project'),
        };

        $('#applications-list #loading-applications').removeClass('d-none');

        app.ajax(`/api/token${this.token}/service/getApplicationsList`, data, json => {

            $('#applications-list #loading-applications').addClass('d-none');

            if (json.error || (this.lastApplicationId == 0 && Object.keys(json.data.applications).length == 0))
                return $('#applications-list').prepend(`<h3 class="mt-4">Заявок не найдено</h3><p class="lead">Все заявки выполнены</p>`);

            $.each(json.data.applications, (i,row) => {

                if (Number(row.id) > this.lastApplicationId)
                    this.lastApplicationId = Number(row.id);

                $('#list-application').append(this.getHtmlApplicationListRow(row));

                // $('[data-toggle="tooltip"]').tooltip();

            });

        });

    }

    /** Одна строка заявки из списка заявок */
    this.getHtmlApplicationListRow = (row) => {

        let color = "";
        if (row.changed)
            color = "list-group-item-primary";
        else if (row.priority)
            color = "list-group-item-warning";

        return `<a href="/id${row.linkId}" class="list-group-item list-group-item-action text-left ${color}">
            <div class="d-flex w-100 justify-content-between">
                <div>
                    <span class="mr-2 opacity-50">#${row.id}</span>
                    <span class="font-weight-bold">${row.bus} @${row.clientLogin}</span>
                </div>
                <small>${row.dateAdd}</small>
            </div>
            <p class="mb-1">${row.breaksListText}</p>
            ${row.comment ? `<p class="mb-2"><i class="fas fa-quote-left opacity-50 mr-1"></i>${row.comment}</p>` : ``}
            <div class="d-flex w-100 justify-content-left">
                <div class="mr-3">
                    <i class="fas ${row.projectIcon}"></i>
                </div>
                <div class="mr-3">
                    <i class="fas fa-comments"></i>
                    <strong>${row.comments}</strong>
                </div>
                <div class="mr-3">
                    <i class="fas fa-camera"></i>
                    <strong>${row.images.length}</strong>
                </div>                
                ${row.combineCount ? `<div class="mr-3"><i class="fas fa-network-wired" title="Присоединено заявок"></i><strong>${row.combineCount}</strong></div>` : ``}
                ${row.priority ? `<div class="mr-3"><i class="fas fa-exclamation-circle" title="Приоритеная заявка"></i></div>` : ``}
                ${row.problem ? `<div class="mr-3 text-danger" title="Проблемная заявка" data-toggle="tooltip"><i class="fas fa-exclamation-triangle"></i></div>` : ``}
                ${row.changed ? `<div class="mr-3 text-primary" title="Подменный фонд" data-toggle="tooltip"><i class="fas fa-exchange-alt"></i></div>` : ``}
            </div>
        </a>`;

    }

    /** Страница вывода данных страницы заявки */
    this.getOneApplicationData = link => {

        let data = {
            link,
            token: this.token,
        }

        app.ajax(`/api/getOneApplicationData`, data, json => {

            console.log(json.data.buttons);
            this.data = json.data;

            $('.loading-in-body').removeClass('d-flex');

            // Вывод заявки
            $('#content-application').append(this.getHtmlOneApplicationRequest(json.data.application));

            // Блок комментариев
            this.addBlockComment();
            
            // Вывод кнопок
            $('#content-application').append(this.getHtmlApplicationsButtonsRow(json.data));

            $('[data-toggle="tooltip"]').tooltip();

        });

    }
    /** HTML блок заявки */
    this.getHtmlOneApplicationRequest = row => {

        return `<div class="card text-left my-3" id="application-request">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">${row.bus}</h5>
                    <small class="opacity-80">${row.dateAdd}</small>
                </div>
                <h6 class="card-subtitle pt-1 opacity-70">#${row.id} ${row.clientName}</h6>
                ${this.getHtmlStatusApplication(row)}

                <p class="mt-2 mb-1 font-weight-light">${row.breaksListText}</p>

                ${row.comment ? `<p class="mb-1 font-weight-light font-italic"><i class="fas fa-quote-left opacity-50 mr-2"></i>${row.comment}</p>` : ``}

                <div class="d-flex justify-content-left align-items-center mt-1">
                    <i class="fas ${row.projectIcon} mr-3 opacity-60"></i>
                    ${row.userId ? `<i class="fas fa-user mr-3" title="Добавлена пользователем" data-toggle="tooltip"></i>` : ``}
                    ${row.priority ? `<i class="fas fa-exclamation-circle text-warning mr-3" title="Приоритеная заявка" data-toggle="tooltip"></i>` : ``}
                    ${row.problem ? `<i class="fas fa-exclamation-triangle text-danger mr-3" title="Проблемная заявка" data-toggle="tooltip"></i>` : ``}
                    ${row.combineData[0] ? `<div class="mr-3 font-weight-bold" title="Присоединённых заявок" data-toggle="tooltip"><i class="fas fa-network-wired"></i> ${row.combineData.length}</div>` : ``}
                    ${row.changed ? `<i class="fas fa-exchange-alt text-danger mr-3" title="Подменный фонд" data-toggle="tooltip"></i>` : ``}
                    ${row.del ? `<span class="mr-3 text-danger"><i class="fas fa-trash" title="Удалена" data-toggle="tooltip"></i> Удалена</span>` : ``}
                </div>
                
            </div>
        </div>`;

    }
    /** Вывод статуса заявки */
    this.getHtmlStatusApplication = row => {

        if (row.del)
            return '';

        if (row.combine)
            return `<div class="text-success font-weight-bold"><i class="fas fa-angle-double-right"></i> Присоединена к заявке #${row.combine}</div>`;

        if (row.changed && !row.changedId)
            return `<div class="text-primary font-weight-bold"><i class="fas fa-exchange-alt"></i> Подменный фонд</div>`;
        
        if (row.done || (row.done && row.changed && row.changedId))
            return `<div class="text-success font-weight-bold"><i class="fas fa-check-square"></i> Завершена</div>`;

        if (row.priority && row.problem)
            return `<div class="text-danger font-weight-bold"><i class="fas fa-exclamation-circle"></i> В приоритете с проблемой</div>`;

        if (row.problem)
            return `<div class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> В работе с проблемой</div>`;

        if (row.priority)
            return `<div class="text-warning font-weight-bold"><i class="fas fa-briefcase"></i> В работе с приоритетом</div>`;
        
        return `<div class="font-weight-bold"><i class="fas fa-briefcase"></i> В работе</div>`;

    }
    /** Кнопки управления заявкой */
    this.getHtmlApplicationsButtonsRow = data => {

        if (!data.buttons)
            return '';

        let left = '',
            right = '';

        if (data.buttons.problem)
            left += `<button type="button" class="btn btn-dark mb-3 mx-1" onclick="application.problemCommentApplication(this);" title="Отметить проблему"><i class="fas fa-exclamation-triangle"></i></button>`;

        if (data.buttons.combine)
            left += `<button type="button" class="btn btn-dark mb-3 mx-1" onclick="application.applicationCombineOpen(this);" title="Присоединить заявку"><i class="fas fa-network-wired" style="width: 20px;"></i></button>`;

        if (data.buttons.del)
            left += `<button type="button" class="btn btn-danger mb-3 mx-1" onclick="application.applicationDelete(this);" title="Удалить заявку" id="app-delete"><i class="fas fa-trash"></i></button>`;

        if (data.buttons.combined)
            right += `<a class="btn btn-dark mb-3 mx-1" href="${data.application.combineLink}" role="button"><i class="fas fa-angle-double-right"></i></a>`;

        if (data.buttons.changed)
            right += `<button type="button" class="btn btn-dark mb-3 mx-1" onclick="application.doneApplicationStart(this);"><i class="fas fa-exchange-alt" style="width: 20px;"></i></button>`;
        else if (data.buttons.done)
            right += `<button type="button" class="btn btn-dark mb-3 mx-1" onclick="application.doneApplicationStart(this);"><i class="fas fa-check-square" style="width: 20px;"></i></button>`;

        return `<div class="d-flex justify-content-between" id="application-panel">
            <div class="text-left">${left}</div>
            <div class="text-right">${right}</div>
        </div>`;

    }

    /** Добавление блока комментариев */
    this.addBlockComment = () => {

        if (!this.data.comments)
            return this;

        // Формирование блоков с комментариями
        let comments = "",
            commentAdd = "";

        $.each(this.data.comments, (i,row) => {
            comments += this.getCommentHtmlRow(row);
        });

        if (comments == "")
            comments += `<p class="my-2 opacity-40 comments-empty">Комментариев ещё нет</p>`;

        if (this.data.addcomments) {
            commentAdd = `<div class="card-footer text-muted bg-white p-3 position-relative" id="form-send-comment">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="w-100 position-relative">
                        <input type="text" class="form-control-plaintext for-new-comment" id="text-new-comment" placeholder="Введите комментарий..." max="250" onkeyup="application.writeComment(event);">
                        <small class="position-absolute" style="bottom: -10px; left: 0px;"></small>
                    </div>
                    <div class="position-relative">
                        <i class="fas fa-paper-plane fa-for-hover fa-2x ml-2" id="send-comment" onclick="application.sendApplicationComment(this);"></i>
                    </div>
                </div>
            </div>`;
        }

        $('#content-application').append(`<div class="card my-3" id="application-comments">
            <div class="card-body py-2">
                <h5 class="card-title">Комментарии</h5>
                ${comments}
            </div>
            ${commentAdd}
        </div>`);

        return this;

    }
    this.getCommentHtmlRow = row => {
        return `<p class="mt-2 mb-0 text-left text-break">
            ${row.problem ? `<i class="fas fa-exclamation-triangle text-danger mr-1"></i>` : ``}
            <span class="text-primary font-weight-bold">${row.fio}</span>
            <span class="opacity-60">${row.date}</span>
            ${row.problem ? `<span class="text-danger font-weight-bold">Отметил проблему</span>` : ``}
            <span>${row.comment}</span>                
        </p>`;
    }

    this.writeComment = e => {

        if (e.keyCode == 13)
            return $('#send-comment').trigger('click');

        let len = $('#text-new-comment').val().trim().length;

        if (len > 250)
            $('#application-comments .card-footer small').addClass('text-danger');
        else
            $('#application-comments .card-footer small').removeClass('text-danger');

        if (len == 0)
            $('#application-comments .card-footer small').text('');
        else
            $('#application-comments .card-footer small').text(`${len}/250 символов`);

    }
    this.sendApplicationComment = e => {

        $(e).removeClass('text-danger');

        if ($('#text-new-comment').val().trim().length == 0) {
            $(e).addClass('text-danger')
            .animate({fontSize: '2.3em'}, 50)
            .animate({fontSize: '1.8em'}, 50)
            .animate({fontSize: '2.1em'}, 50)
            .animate({fontSize: '1.9em'}, 50)
            .animate({fontSize: '2em'}, 50);
            return;
        }

        $(e).removeAttr('onclick');

        $('#form-send-comment').append(`<div class="d-flex justify-content-center align-items-center looooo">
            <div class="spinner-grow text-dark" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>`);

        let data = {
            id: this.data.application.id,
            comment: $('#text-new-comment').val().trim(),
        }

        app.ajax(`/api/token${this.token}/service/sendApplicationComment`, data, json => {

            $(e).attr('onclick', 'application.sendApplicationComment(this);');
            $('#form-send-comment .looooo').remove();

            if (json.error) {
                $(e).addClass('text-danger')
                .animate({fontSize: '2.3em'}, 50)
                .animate({fontSize: '1.8em'}, 50)
                .animate({fontSize: '2.1em'}, 50)
                .animate({fontSize: '1.9em'}, 50)
                .animate({fontSize: '2em'}, 50);
                return;
            }

            $('#application-comments .card-body .comments-empty').remove();
            $('#application-comments .card-body').append(this.getCommentHtmlRow(json.data.comment));

            $('#text-new-comment').val('');            
            $('#application-comments .card-footer small').text('');            

        });

    }

    /** Ввод комментария проблемной заявки */
    this.problemCommentApplication = e => {

        $(e).blur();
        this.modal = $('#modal-problem-comment');
        this.modal.find('#save-data').prop('disabled', false);
        this.modal.find('#comment-count').removeClass('text-danger font-weight-bold').text('0/250 символов');
        this.modal.find('form')[0].reset();

        this.modal.modal('show');

    }
    this.checkComment = e => {
        let len = $(e).val().length;
        this.modal.find('#comment-count').text(`${len}/250 символов`);
        if (len > 250)
            this.modal.find('#comment-count').addClass('text-danger font-weight-bold');
        else
            this.modal.find('#comment-count').removeClass('text-danger font-weight-bold');
    }
    /** Сохранение комментария проблемной заявки */
    this.problemCommentApplicationSave = e => {

        $(e).prop('disabled', true);
        app.modalLoading(this.modal, 'show');

        let data = this.modal.find('form').serializeArray();
        data.push({name: "problem", value: 1});
        data.push({name: "id", value: this.data.application.id});

        app.ajax(`/api/token${this.token}/service/sendApplicationComment`, data, json => {

            $(e).prop('disabled', false);
            app.modalLoading(this.modal, 'hide');

            if (json.error)
                return app.globalAlert(json.error, json.done);

            $('#application-comments .card-body .comments-empty').remove();
            $('#application-comments .card-body').append(this.getCommentHtmlRow(json.data.comment));
            $('#application-request').replaceWith(this.getHtmlOneApplicationRequest(json.data.application));

            this.modal.modal('hide');

        });

    }

    this.applicationDelete = e => {
        $(e).blur();
        this.modal = $('#modal-delete-application');
        this.modal.modal('show');
    }
    this.applicationDeleteSave = e => {
        app.modalLoading(this.modal, 'show');
        let data = {
            id: this.data.application.id,
        }
        app.ajax(`/api/token${this.token}/service/applicationDelete`, data, json => {

            app.modalLoading(this.modal, 'hide');
            this.modal.modal('hide');

            if (json.error)
                return $('#application-panel #app-delete').prop('disabled', true);;

            $('#application-panel').remove();
            $('#application-request').replaceWith(this.getHtmlOneApplicationRequest(json.data.application));

        });
    }

    /** Объединение заявок */
    this.applicationCombineOpen = e => {

        $(e).prop('disabled', true).find('i')
        .removeClass('fa-network-wired').addClass('fa-spin fa-spinner');
        this.modal = $('#modal-application-combine');

        let data = {
            bus: this.data.application.bus,
            id: this.data.application.id,
            clientId: this.data.application.clientId,
        }

        app.ajax(`/api/token${this.token}/service/applicationCombineOpen`, data, json => {

            this.modal.modal('show');
            $(e).prop('disabled', false).find('i')
            .removeClass('fa-spin fa-spinner').addClass('fa-network-wired');
            this.modal.find('#save-data').prop('disabled', true);
            this.modal.find('form').empty();

            if (json.data.applications.length) {

                $.each(json.data.applications, (i,row) => {
                    this.modal.find('form').append(`<div class="custom-control custom-radio">
                        <input type="radio" id="customRadio${i}" name="combine" class="custom-control-input" value="${row.id}" onchange="application.modal.find('#save-data').prop('disabled', false);">
                        <label class="custom-control-label" for="customRadio${i}">#${row.id} <span class="text-muted mr-2">${row.dateAdd}</span> ${row.breaksListText}</label>
                    </div>`);
                });

                // this.modal.find('#save-data').prop('disabled', false);

            }
            else
                this.modal.find('form').append(`<div class="my-4 text-muted text-center">Заявок для присоединения не найдено</div>`);

        });

    }

    /** Сохранение выбранной к присоединению мастер-заявки */
    this.applicationCombine = e => {

        $(e).prop('disabled', true);
        let data = this.modal.find('form').serializeArray();
        data.push({name: "id", value: this.data.application.id});

        app.modalLoading(this.modal, 'show');

        app.ajax(`/api/token${this.token}/service/applicationCombine`, data, json => {

            if (json.error) {
                $(e).prop('disabled', false);
                app.modalLoading(this.modal, 'hide');
                return app.globalAlert(json.error, json.done, json.code);
            }

            location.href = json.data.link;

        });
        
    }

    /** Формирование страницы завршения заявки */
    this.doneApplicationStart = e => {

        $(e).prop('disabled', true).find('i')
        .removeClass('fa-check-square').addClass('fa-spin fa-spinner');

        let data = {
            id: this.data.application.id,
        }

        app.ajax(`/api/token${this.token}/service/doneApplicationStart`, data, json => {

            $('#content-application').hide();
            $(e).prop('disabled', false).find('i')
            .removeClass('fa-spin fa-spinner').addClass('fa-check-square');

            let appli = json.data.application;
            console.log(appli);

            // Пункты завершения заявки
            let htmlrepairs = "";
            $.each(json.data.repairs, (i,row) => {

                if (row.master == 0) {
                    htmlrepairs += `<div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="checkbox-point-${row.id}" name="repairs[]" value="${row.id}" data-change="${row.changed}" data-fond="${row.fond}" data-type="repairs" onchange="application.searchChangeAndFondPoint(this);">
                        <label class="custom-control-label" for="checkbox-point-${row.id}">${row.name}</label>
                    </div>`;
                }
                else {

                    htmlrepairs += `<div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="checkbox-point-${row.id}" data-for="${row.id}" onclick="application.checkMasterPoint(this);" data-master="${row.id}" onchange="application.searchChangeAndFondPoint(this);"${row.subpoints.length > 0 ? '' : ' disabled'}>
                        <label class="custom-control-label" for="checkbox-point-${row.id}">${row.name}</label>
                    </div>`;
                    
                    htmlrepairs += `<div id="subpoints-for-${row.id}">`;
                    $.each(row.subpoints, (key,sub) => {

                        htmlrepairs += `<div class="custom-control custom-checkbox ml-4">
                            <input type="checkbox" class="custom-control-input checkbox-point-${row.id}" id="checkbox-subpoint-${sub.id}" name="subrepairs[]" value="${sub.id}" onclick="application.checkSubPoint(this);" data-for="${row.id}" data-change="${sub.changed}" data-fond="${sub.fond}" data-type="subrepairs" onchange="application.searchChangeAndFondPoint(this);">
                            <label class="custom-control-label" for="checkbox-subpoint-${sub.id}">${sub.name}</label>
                        </div>`;

                    });
                    htmlrepairs += `</div>`;

                }

            });

            $('#content').append(`<form class="mt-3 mx-auto" id="content-application-done" style="max-width: 700px;">
                <div class="card my-3" id="content-application-done-card">
                    <div class="card-body py-3">
                        <h5 class="card-title mb-0">Завершение заявки</h5>
                        <h6 class="card-subtitle pt-1 opacity-70">#${appli.id} от ${appli.dateAddTime}</h6>
                        <div class="font-weight-bold"><i class="fas fa-bus"></i> ${appli.bus}</div>
                        <small>${appli.breaksListText}</small>
                        <hr />
                        <div class="font-weight-bold mb-2">Выполненные работы</div>  
                        <div id="repair-points" class="text-left px-3">${htmlrepairs}</div>
                        <hr />
                        <div class="font-weight-bold mb-3">Загрузите фотографии</div>
                        <div class="px-2 position-relative text-left" id="files-list">
                            <div class="px-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Фото передней части</p>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$('#input-for-photo').trigger('click');">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <hr />
                            <div class="px-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Фото устройства</p>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$('#input-for-photo').trigger('click');">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <hr />
                            <div class="px-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Фото экрана</p>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$('#input-for-photo').trigger('click');">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <input type="file" class="d-none" accept="image/*" id="input-for-photo" />
                            <input type="file" class="d-none" accept="image/*" id="input-for-changed-photo" data-changed="1" />
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between px-2">
                    <button type="button" class="btn btn-dark mb-3" onclick="application.doneApplicationCansel(this);">Отмена</button>
                    <button type="button" class="btn btn-success mb-3" onclick="application.doneApplicationSave(this);">Завершить</button>
                </div>
            </form>`);

        });

    }

    /** Проверка выбранных пунктов для формирования формы */
    this.searchChangeAndFondPoint = e => {

        let data = {
            master: $(e).data('master') ? $(e).data('master') : false,
            fond: false,
            change: 0,
            changeList: [],
        };

        $('form #repair-points input[type="checkbox"]').each(function() {

            let rowfond = $(this).data('fond') ? $(this).data('fond') : false,
                rowchange = $(this).data('change') ? $(this).data('change') : false,
                rowid = $(this).attr('id'),
                type = $(this).data('type'),
                val = $(this).val(),
                name = $(`label[for="${rowid}"]`).text();
                
            if ($(this).prop('checked') && rowfond)
                data.fond = true;
            if ($(this).prop('checked') && rowchange) {
                data.change += 1;
                data.changeList.push({type, val, name});
            }

        });

        if (data.fond && !$('#content-application-done input#thisfond').length)
            $('#content-application-done').append('<input type="hidden" name="thisfond" id="thisfond" value="thisfond" />');
        else if (!data.fond && $('#content-application-done input#thisfond').length)
            $('#content-application-done input#thisfond').remove();

        this.showHideFormPhotoChange(data.changeList);

    }

    /** Скрытие/отображение формы замены оборудования */
    this.showHideFormPhotoChange = data => {
        $('#files-list .input-for-changed-photo').prop('disabled', true);
        $('#files-list .element-for-changed-photo').hide();
        $.each(data, (i,row) => {

            if ($(`#files-list #photo-${row.type}-${row.val}`).length) {
                $(`#files-list #photo-${row.type}-${row.val}`).show();
                $(`#files-list #photo-${row.type}-${row.val} .input-for-changed-photo`).prop('disabled', false);
            }
            else {
                $('#files-list').append(`<div class="element-for-changed-photo" id="photo-${row.type}-${row.val}">
                    <hr />
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <p class="m-0">${row.name} (фото старого)</p>
                        <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$('#input-for-changed-photo').trigger('click');">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                        <input type="hidden" class="input-for-changed-photo" name="reqired[]" value="old_photo_${row.type}_${row.val}" />
                    </div>
                    <hr />
                    <div class="d-flex justify-content-between align-items-center px-2">
                        <p class="m-0">${row.name} (фото нового)</p>
                        <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$('#input-for-changed-photo').trigger('click');">
                            <i class="fa fa-plus" aria-hidden="true"></i>
                        </button>
                        <input type="hidden" class="input-for-changed-photo" name="reqired[]" value="new_photo_${row.type}_${row.val}" />
                    </div>
                </div>`);
            }

        });
    }

    this.doneApplicationCansel = e => {

        $('#content-application-done').remove();
        $('#content-application').show();

    }

    /** Функция выделения всех чекбоксов-подпунктов мастер чекбоксом */
    this.checkMasterPoint = e => {
        let id = $(e).data('for');
        $(`#subpoints-for-${id} .checkbox-point-${id}`).prop('checked', $(e).prop('checked'));
    }
    this.checkSubPoint = e => {
        let id = $(e).data('for'),
            count = $(`#subpoints-for-${id} input`).length,
            checked = 0;

        $(`#subpoints-for-${id} input`).each(function() {
            checked += $(this).prop('checked') ? 1 : 0;
        });

        $(`#checkbox-point-${id}`).prop('indeterminate', false);

        if (count == checked) {
            $(`#checkbox-point-${id}`).prop('checked', true);
        }
        else if (count > checked && checked != 0) {
            $(`#checkbox-point-${id}`).prop('indeterminate', true);
        }
        else {
            $(`#checkbox-point-${id}`).prop('checked', false);
        }
    }

    /** Завершение заявки */
    this.doneApplicationSave = e => {

        let data = $('#content-application-done').serializeArray();

        console.log(data);

    }

}
var application = new Application;