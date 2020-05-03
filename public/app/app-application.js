function Application() {

    /** Токен пользователя */
    this.token = $('meta[name="token"]').attr('content');
    this.tempToken = false; // Временный токен неавторизиированного пользователя

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
                    $('#images-data').append(`<div class="mx-auto my-3 px-2 text-left" style="max-width: 400px;" id="file-block-${row.id}">
                        <div class="text-danger"><strong>Ошибка</strong> ${row.error}</div>
                        <small>Файл ${row.name}</small>
                    </div>`);

                }
                else {

                    app.fileList.push(row);
                    let imgId = app.fileList.length - 1;

                    $('#images-data').append(`<div class="d-flex align-items-center mx-auto my-2 px-2" style="max-width: 400px;" id="file-block-${row.id}">
                        <div class="card h-100 cursor-pointer hover-link" data-id="${imgId}" onclick="app.showImg(this);" style="width: 100px;">
                            <div class="item-responsive item-16by9">
                                <div class="item-responsive-content"></div>
                                <img src="${row.link}" class="img-fluid" alt="${row.name}" onload="$(this).removeClass('d-none');">
                            </div>
                            <input type="hidden" name="images[]" value="${row.id}" />
                        </div>
                        <div class="flex-grow-1 text-truncate px-2 text-left">${row.name}</div>
                        <div class="delete-button"><i class="fas fa-trash hover-link" onclick="application.deleteFileAddApplication(this);" data-id="${row.id}" data-file="${imgId}" title="Удалить"></i></div>
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

    this.deleteFileAddApplication = e => {

        let id = $(e).data('id'),
            imgId = $(e).data('file');

        $(e).removeClass('fa-trash').addClass('fa-spin fa-spinner');

        $('#file-block-'+id).remove();
        delete app.fileList[imgId];

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
            page: app.page,
        };

        $('#applications-list #loading-applications').removeClass('d-none');
        app.progress = true;

        app.ajax(`/api/token${this.token}/service/getApplicationsList`, data, json => {

            $('#applications-list #loading-applications').addClass('d-none');

            app.page = json.data.next;
            app.progress = false;

            app.scrollDoit(this.getApplicationsList);

            if (json.error || (this.lastApplicationId == 0 && json.data.rows == 0))
                return $('#applications-list').append(`<p class="lead mt-4">Заявок не найдено</p>`);

            $.each(json.data.applications, (i,row) => {

                if (Number(row.id) > this.lastApplicationId)
                    this.lastApplicationId = Number(row.id);

                let html = this.getHtmlApplicationListRow(row);
                $('#list-application').append(html);

                // $('[data-toggle="tooltip"]').tooltip();

                if (this.lastApplicationId < row.id)
                    this.lastApplicationId = row.id;

            });

            if (json.data.next > json.data.last) {
                app.progressEnd = true;
                $('#applications-list').append(`<small class="d-block my-2 opacity-40">Это все данные</small>`);
            }

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
                    <span class="mr-2 opacity-50">#${row.ida}</span>
                    <span class="font-weight-bold">${row.bus}</span>
                </div>
                <small>${row.dateAdd}</small>
            </div>
            <p class="mb-1">${row.breaksListText}</p>
            ${row.comment ? `<p class="mb-2"><i class="fas fa-quote-left opacity-50 mr-1"></i>${row.comment}</p>` : ``}
            <div class="d-flex w-100 justify-content-left">
                <div class="mr-3">
                    <i class="fas ${row.projectIcon} opacity-50"></i>
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

            this.data = json.data;

            $('.loading-in-body').removeClass('d-flex');

            // Вывод заявки
            $('#content-application').append(this.getHtmlOneApplicationRequest(json.data.application));

            // Блок комментариев
            this.addBlockService().addBlockComment();
            
            // Вывод кнопок
            $('#content-application').append(this.getHtmlApplicationsButtonsRow(json.data));

            $('[data-toggle="tooltip"]').tooltip();

        });

    }
    /** HTML блок заявки */
    this.getHtmlOneApplicationRequest = row => {

        // Блок с фотографиями
        let images = '';

        $.each(row.imagesData, (i,img) => {

            app.fileList.push(img);

            let imgId = app.fileList.length - 1;

            images += `<div class="col mb-2 px-1 hover-link">
                <div class="card h-100" data-id="${imgId}" onclick="app.showImg(this);">
                    <div class="item-responsive item-16by9">
                        <div class="item-responsive-content"></div>
                        <img src="${img.link}" class="d-none img-fluid" alt="${img.name}" onload="$(this).removeClass('d-none');">
                    </div>
                </div>
            </div>`;
        });


        return `<div class="card text-left my-3" id="application-request">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">${row.bus}</h5>
                    <small class="opacity-80">${row.dateAdd}</small>
                </div>
                <h6 class="card-subtitle pt-1 opacity-70">#${row.ida} ${row.clientName}</h6>
                ${this.getHtmlStatusApplication(row)}
                <p class="mt-2 mb-1 font-weight-light">${row.breaksListText}</p>
                ${row.comment ? `<p class="mb-1 font-weight-light font-italic"><i class="fas fa-quote-left opacity-50 mr-2"></i>${row.comment}</p>` : ``}
                ${this.getHtmlBottomIcons(row)}
                <div class="row row-cols-2 row-cols-md-3${images != "" ? ' mt-3' : ''} px-2">${images}</div>
            </div>
        </div>`;

    }
    /** Вывод нижней панели иконок */
    this.getHtmlBottomIcons = row => {
        return `<div class="d-flex justify-content-left align-items-center mt-1">
            <i class="fas ${row.projectIcon} mr-3 opacity-60"></i>
            ${row.userId ? `<i class="fas fa-user mr-3" title="Добавлена пользователем" data-toggle="tooltip"></i>` : ``}
            ${row.priority ? `<i class="fas fa-exclamation-circle text-warning mr-3" title="Приоритеная заявка" data-toggle="tooltip"></i>` : ``}
            ${row.problem ? `<i class="fas fa-exclamation-triangle text-danger mr-3" title="Проблемная заявка" data-toggle="tooltip"></i>` : ``}
            ${row.combineData[0] ? `<div class="mr-3 font-weight-bold" title="Присоединённых заявок" data-toggle="tooltip"><i class="fas fa-network-wired"></i> ${row.combineData.length}</div>` : ``}
            ${row.changed ? `<i class="fas fa-exchange-alt text-danger mr-3" title="Подменный фонд" data-toggle="tooltip"></i>` : ``}
            ${row.del ? `<span class="mr-3 text-danger"><i class="fas fa-trash" title="Удалена" data-toggle="tooltip"></i></span>` : ``}
        </div>`;
    }
    /** Вывод статуса заявки */
    this.getHtmlStatusApplication = row => {

        if (row.del)
            return `<div><span class="text-danger font-weight-bold">Удалена</span>${row.delComment ? (row.deleteDate ? ` ${row.deleteDate}` : '')+` по причине: ${row.delComment}` : ''}</div>`;

        if (row.combine)
            return `<div class="text-success font-weight-bold"><i class="fas fa-angle-double-right"></i> Присоединена к заявке</div>`;

        if (row.changed && !row.changedId)
            return `<div class="text-primary font-weight-bold"><i class="fas fa-exchange-alt"></i> Подменный фонд</div>`;

        if (row.done && row.cansel)
            return `<div class="text-danger font-weight-bold"><i class="fas fa-ban"></i> Отменена</div>`;
        
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

        if (data.buttons.cansel && !data.buttons.changed)
            right += `<button type="button" class="btn btn-secondary mb-3 mx-1" onclick="application.applicationCansel(this);" title="Отменить заявку" id="app-cansel"><i class="fas fa-ban"></i></button>`;

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

    /** Добавление блоков сервиса */
    this.addBlockService = () => {

        if (!this.data.service)
            return this;

        $.each(this.data.service, (i,row) => {

            let html = this.getHtmlRowService(row);

            $('#content-application').append(html);

        });

        return this;

    }

    /** html блока сервиса */
    this.getHtmlRowService = row => {

        // Блок с фотографиями
        let images = '',
            acts = '';

        $.each(row.imagesData, (i,img) => {

            app.fileList.push(img);

            let imgId = app.fileList.length - 1;

            images += `<div class="col mb-2 px-1 hover-link">
                <div class="card h-100" data-id="${imgId}" onclick="app.showImg(this);">
                    <div class="item-responsive item-16by9">
                        <div class="item-responsive-content"></div>
                        <img src="${img.link}" class="d-none img-fluid" alt="${img.name}" onload="$(this).removeClass('d-none');">
                    </div>
                </div>
            </div>`;

        });

        if (row.actDwn || row.act) {
            acts += `<p class="my-0">
                ${row.act ? `<i class="fas fa-pen-square hover-link mr-1 text-center" style="width: 20px;" data-toggle="tooltip" title="Подготовить акт" onclick="application.actEditData(this);" data-id="${row.id}"></i>` : ``}
                ${row.actDwn ? `<i class="fas fa-file-download hover-link text-center" style="width: 20px;" data-toggle="tooltip" title="Скачать акт" onclick="application.actDownload(this);" data-id="${row.id}"></i>` : ``}
            </p>`;
        }

        return `<div class="card my-3 text-left">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${row.changefond ? 'Подменный фонд' : 'Сервис'}</strong>
                    <small class="opacity-80">${row.dateAdd}</small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <p class="my-0 font-weight-light">${row.usersList}</p>
                    ${acts}
                </div>
                <p class="my-0 font-weight-light">${row.repairsList}</p>
                ${row.comment ? `<p class="mb-1 font-weight-light font-italic"><i class="fas fa-quote-left opacity-50 mr-2"></i>${row.comment}</p>` : ``}
                <div class="row row-cols-2 row-cols-md-3${images != "" ? ' mt-3' : ''} px-2">${images}</div>
            </div>
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

    this.applicationCansel = e => {

        $(e).blur();

        this.modal = $('#modal-cansel-application');
        this.modal.modal('show');

        this.modal.find('#save-data').prop('disabled', true);
        this.modal.find('[name="comment"]').prop('disabled', true);

        app.modalLoading(this.modal, 'show');

        app.ajax(`/api/token${app.token}/service/getListCansel`, {
            id: this.data.application.id,
            project: this.data.application.project,
            client: this.data.application.clientId,
        }, json => {

            app.modalLoading(this.modal, 'hide');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.modal.find('#save-data').prop('disabled', false);
            this.modal.find('[name="comment"]').prop('disabled', false);

            this.modal.find('#cansel-points').empty();

            json.data.points.forEach(row => {
                this.modal.find('#cansel-points').append(`<div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" name="cansel[]" id="customCheck${row.id}" value="${row.id}">
                    <label class="custom-control-label" for="customCheck${row.id}">${row.name}</label>
                </div>`);
            });

        }, err => {
            app.modalLoading(this.modal, 'hide');
        });

    }

    this.applicationCanselSave = e => {

        $(e).prop('disabled', true);
        app.modalLoading(this.modal, 'show');

        let data = this.modal.find('form').serializeArray();
        data.push({name: 'id', value: this.data.application.id});

        app.ajax(`/api/token${this.token}/service/applicationCanselSave`, data, json => {

            if (json.error) {
                app.modalLoading(this.modal, 'hide');
                return app.globalAlert(json.error, json.done, json.code);
            }

            location.reload();

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
            comment: this.modal.find('form textarea').val(),
        }
        app.ajax(`/api/token${this.token}/service/applicationDelete`, data, json => {

            app.modalLoading(this.modal, 'hide');
            this.modal.modal('hide');

            if (json.error)
                return $('#application-panel #app-delete').prop('disabled', true);;

            $('#application-panel').remove();

            let html = this.getHtmlOneApplicationRequest(json.data.application);
            $('#application-request').replaceWith(html);

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
    this.application = {};
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
            this.application = appli;

            // Пункты завершения заявки
            let htmlrepairs = "";
            $.each(json.data.repairs, (i,row) => {

                if (row.master == 0) {
                    htmlrepairs += `<div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="checkbox-point-${row.id}" name="repairs[]" value="${row.id}" data-change="${row.changed}" data-fond="${row.fond}" data-type="repairs" data-serials="${row.serials}" onchange="application.searchChangeAndFondPoint(this);">
                        <label class="custom-control-label" for="checkbox-point-${row.id}">${row.name}</label>
                    </div>`;
                }
                else {

                    htmlrepairs += row.subpoints.length > 0 ? `<div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="checkbox-point-${row.id}" data-for="${row.id}" onclick="application.checkMasterPoint(this);" data-master="${row.id}" onchange="application.searchChangeAndFondPoint(this);">
                        <label class="custom-control-label" for="checkbox-point-${row.id}">${row.name}</label>
                    </div>` : '';
                    
                    htmlrepairs += `<div class="subpoints-content" id="subpoints-for-${row.id}">`;
                    $.each(row.subpoints, (key,sub) => {

                        htmlrepairs += `<div class="custom-control custom-checkbox ml-4">
                            <input type="checkbox" class="custom-control-input checkbox-point-${row.id}" id="checkbox-subpoint-${sub.id}" name="subrepairs[]" value="${sub.id}" onclick="application.checkSubPoint(this);" data-for="${row.id}" data-change="${sub.changed}" data-fond="${sub.fond}" data-type="subrepairs" data-serials="${sub.serials}" onchange="application.searchChangeAndFondPoint(this);">
                            <label class="custom-control-label" for="checkbox-subpoint-${sub.id}">${sub.name}</label>
                        </div>`;

                    });
                    htmlrepairs += `</div>`;

                }

            });

            if (htmlrepairs == "")
                htmlrepairs = '<div class="text-center text-muted my-4">Пункты не настроены</div>';


            // Список коллег из избранного
            let collegue = ``;
            $.each(json.data.favorites, (i,row) => {
                collegue += this.getHtmlRowCheckboxCollegue(row);
            });


            $('#content').append(`<form class="mt-3 mx-auto content-block-width" id="content-application-done">
                <div class="card my-3" id="content-application-done-card">
                    <div class="card-body py-3">
                        <h5 class="card-title mb-0">Завершение заявки</h5>
                        <h6 class="card-subtitle pt-1 opacity-70">#${appli.ida} от ${appli.dateAddTime}</h6>
                        <div class="font-weight-bold"><i class="fas fa-bus"></i> ${appli.bus}</div>
                        <small>${appli.breaksListText}</small>
                        <hr />

                        <div class="position-relative">
                            <div class="font-weight-bold mb-2">Совместное выполнение</div>  
                            <div class="input-group flex-nowrap" id="search-collegue-block"  data-toggle="dropdown">
                                <input type="text" class="form-control" placeholder="Поиск коллеги..." aria-label="Поиск коллеги..." aria-describedby="addon-wrapping" id="search-collegue">
                            </div>
                            <div class="dropdown-menu w-100 shadow" id="search-result" aria-labelledby="search-collegue">
                                <p class="text-muted mb-0 px-3">Начните поиск по ФИО или логину</p>
                            </div>
                        </div>
                        <div id="collegue-list">${collegue}</div>
                        <hr />

                        <div class="font-weight-bold mb-2">Выполненные работы</div>  
                        <div id="repair-points" class="text-left">${htmlrepairs}</div>
                        <hr />
                        <div class="input-group">
                            <textarea class="form-control" name="comment" aria-label="Введите дополнительный комментарий..." placeholder="Введите дополнительный комментарий..." rows="5"></textarea>
                        </div>

                        <hr />
                        <div class="font-weight-bold mb-3">Загрузите фотографии</div>
                        <div class="px-2 position-relative text-left" id="files-list">
                            <div class="py-1 px-2 block-added-photo">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Фото передней части</p>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$(this).parent().find('input[type=file]').trigger('click');">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                    <input type="file" class="d-none" accept="image/*" onchange="application.uploadFileForDone(this);" data-name="photo_bus" />
                                </div>
                            </div>
                            <hr />
                            <div class="py-1 px-2 block-added-photo">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Фото устройства</p>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$(this).parent().find('input[type=file]').trigger('click');">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                    <input type="file" class="d-none" accept="image/*" onchange="application.uploadFileForDone(this);" data-name="photo_device" />
                                </div>
                            </div>
                            <hr />
                            <div class="py-1 px-2 block-added-photo">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Фото экрана</p>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$(this).parent().find('input[type=file]').trigger('click');">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                    <input type="file" class="d-none" accept="image/*" onchange="application.uploadFileForDone(this);" data-name="photo_screen" />
                                </div>
                            </div>
                            <hr />
                            <div class="py-1 px-2 block-added-photo">
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="m-0">Прочее</p>
                                    <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$(this).parent().find('input[type=file]').trigger('click');">
                                        <i class="fa fa-plus" aria-hidden="true"></i>
                                    </button>
                                    <input type="file" class="d-none" accept="image/*" onchange="application.uploadFileForDone(this);" data-name="photo_other" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between px-2">
                    <button type="button" class="btn btn-dark mb-3" onclick="application.doneApplicationCansel(this);">Отмена</button>
                    <button type="button" class="btn btn-success mb-3" onclick="application.doneApplicationSave(this);">Завершить</button>
                </div>
                <input type="hidden" value="${appli.id}" name="id" />
                ${appli.changed ? '<input type="hidden" value="1" name="thisfonddone" />' : ''}
            </form>`);

            $('#content-application-done').submit(function() {
                return false;
            });
                

            $('#search-collegue').autocomplete({
                source: application.searchCollegue,
                minLength: 0,
            });

        });

    }

    this.getHtmlRowCheckboxCollegue = row => {
        return `<div class="d-flex justify-content-between align-items-center mt-2" id="checkbox-line-user-${row.id}">
            <div class="custom-control custom-checkbox text-left">
                <input type="checkbox" class="custom-control-input" id="user-add-${row.id}" name="useradd[]" value="${row.id}" />
                <label class="custom-control-label" for="user-add-${row.id}">${row.fio}</label>
            </div>
            ${row.favorit == "none" ? `<i class="fas fa-star fa-for-hover text-warning" onclick="application.userFavorit(this);" data-id="${row.id}"></i>` : `<i class="${row.favorit ? `fas fa-star` : `far fa-star`} fa-for-hover${row.favorit ? ` text-warning` : ``}" onclick="application.userFavorit(this);" data-id="${row.id}"></i>`}
        </div>`;
    }

    this.searchResultUsers = {};
    this.searchCollegue = (request, responce) => {

        let data = {
            projectId: this.application.clientId,
            search: String(request.term).trim(),
        }

        app.ajax(`/api/token${this.token}/service/searchCollegue`, data, json => {

            $('#search-collegue-block').dropdown('show');

            $('#search-result').empty();

            this.searchResultUsers = json.data.users;

            $.each(json.data.users, (i,row) => {
                $('#search-result').append(`<button class="dropdown-item" type="button" onclick="application.selectUserAddFromSearch(this);" data-key="${i}">${String(row.fio).replace(data.search, `<mark class="p-0">${data.search}</mark>`)} <b>@${String(row.login).replace(data.search, `<mark class="p-0">${data.search}</mark>`)}</b>${row.favorit > 0 ? ` <i class="fas fa-star text-warning"></i>` : ``}</button>`);
            });

            if (!json.data.users.length)
                $('#search-result').append(`<p class="text-muted mb-0 px-3">По запросу "<b>${data.search}</b>" ничего не найдено</p>`);

        });

    }

    /** Выбор пользователя для совместного выполнения */
    this.selectUserAddFromSearch = e => {

        let key = $(e).data('key');
        $('#search-collegue-block').dropdown('hide');

        if (!$(`#collegue-list #checkbox-line-user-${this.searchResultUsers[key].id}`).length)
            $('#collegue-list').append(this.getHtmlRowCheckboxCollegue(this.searchResultUsers[key]));
        
        $(`input#user-add-${this.searchResultUsers[key].id}`).prop('checked', true);

        $('#search-collegue').val('');
        $('#search-result').html(`<p class="text-muted mb-0 px-3">Начните поиск по ФИО или логину</p>`);

    }

    /** Проверка выбранных пунктов для формирования формы */
    this.searchChangeAndFondPoint = e => {

        let data = {
            master: $(e).data('master') ? $(e).data('master') : false,
            fond: false,
            serials: false,
            change: 0,
            changeList: [],
        };

        $('form #repair-points input[type="checkbox"]').each(function() {

            let rowfond = $(this).data('fond') ? $(this).data('fond') : false,
                rowchange = $(this).data('change') ? $(this).data('change') : false,
                serials = $(this).data('serials') ? $(this).data('serials') : false,
                rowid = $(this).attr('id'),
                type = $(this).data('type'),
                val = $(this).val(),
                name = $(`label[for="${rowid}"]`).text();
                
            if ($(this).prop('checked') && rowfond)
                data.fond = true;

            if ($(this).prop('checked') && rowchange) {
                data.change += 1;
                data.changeList.push({type, val, name, serials});
            }

            if ($(this).prop('checked') && serials)
                data.serials = true;
            

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

        console.log(data);

        $.each(data, (i,row) => {

            if ($(`#files-list #photo-${row.type}-${row.val}`).length) {
                $(`#files-list #photo-${row.type}-${row.val}`).show();
                $(`#files-list #photo-${row.type}-${row.val} .input-for-changed-photo`).prop('disabled', false);
            }
            else {
                $('#files-list').append(`<div class="element-for-changed-photo" id="photo-${row.type}-${row.val}">
                    <hr />
                    <div class="py-1 px-2 block-added-photo">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="m-0">${row.name} (фото старого)</p>
                            <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$(this).parent().find('input[type=file]').trigger('click');">
                                <i class="fa fa-plus" aria-hidden="true"></i>
                            </button>
                            <input type="hidden" class="input-for-changed-photo" name="required[]" value="old_photo_${row.type}_${row.val}" />
                            <input type="file" class="d-none" accept="image/*" onchange="application.uploadFileForDone(this);" data-changed="1" data-name="old_photo_${row.type}_${row.val}" data-descr="${row.name} (фото старого)" />
                        </div>
                        ${row.serials ? `<div class="input-group mt-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            </div>
                            <input type="hidden" class="input-for-changed-photo" name="serials[]" value="serial_old_${row.type}_${row.val}" />
                            <input type="text" class="form-control" placeholder="Серийный номер" aria-label="Серийный номер" name="serial_old_${row.type}_${row.val}">
                        </div>` : ``}
                    </div>
                    <hr />
                    <div class="py-1 px-2 block-added-photo">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="m-0">${row.name} (фото нового)</p>
                            <button type="button" class="btn btn-primary btn-sm btn-add-photo" onclick="$(this).parent().find('input[type=file]').trigger('click');">
                                <i class="fa fa-plus" aria-hidden="true"></i>
                            </button>
                            <input type="hidden" class="input-for-changed-photo" name="required[]" value="new_photo_${row.type}_${row.val}" />
                            <input type="file" class="d-none" accept="image/*" onchange="application.uploadFileForDone(this);" data-changed="1" data-name="new_photo_${row.type}_${row.val}" data-descr="${row.name} (фото нового)" />
                        </div>
                        ${row.serials ? `<div class="input-group mt-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                            </div>
                            <input type="hidden" class="input-for-changed-photo" name="serials[]" value="serial_new_${row.type}_${row.val}" />
                            <input type="text" class="form-control" placeholder="Серийный номер" aria-label="Серийный номер" name="serial_new_${row.type}_${row.val}">
                        </div>` : ``}
                    </div>
                </div>`);
            }

        });
    }

    /** Отмена завршения заявки */
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
    this.checkuseradd = false;
    this.doneApplicationSave = e => {

        let data = $('#content-application-done').serializeArray(),
            checkuser = this.checkuseradd;

        $.each(data, (i,row) => {
            if (row.name == "useradd[]")
                checkuser = true;
        });

        if (!checkuser)
            return $('#modal-no-useradd').modal('show');

        $(e).prop('disabled', true);
        app.formValidRemove(this.modal);

        app.ajax(`/api/token${this.token}/service/applicationDone`, data, json => {

            $(e).prop('disabled', false);

            if (json.error) {
                app.formValidErrors(this.modal, json.inputs);
                return app.globalAlert(json.error, json.done, json.code);
            }

            $(e).prop('disabled', true);
            location.reload();

        });

    }
    /** Завершение без коллеги */
    this.applicationSaveNoUser = e => {
        $('#modal-no-useradd').modal('hide');
        this.checkuseradd = true;
        this.doneApplicationSave();
    }

    /** Загрузка файла завершения заявок */
    this.uploadFileForDone = e => {

        let formData = new FormData(),
            files = $(e).prop('files'),
            block = $(e).parents('.block-added-photo'),
            name = $(e).data('name'),
            descr = $(e).data('descr');

        // Пройти в цикле по всем файлам
	    for (var i = 0; i < files.length; i++)
            formData.append('images[]', files[i]);

        formData.append('razdel', "appdone");

        if (descr)
            formData.append('description', descr);

        app.file(`/api/token${this.token}/service/uploadFileForDone`, formData, json => {

            setTimeout(() => {
                block.find('.looooo').remove();
            }, 600);
            $(e).val('');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $.each(json.data.files, (i,row) => {

                if (row.error) {
                    block.append(`<div class="input-group input-group-sm mt-2">
                        <div class="input-group-prepend" title="${row.name}">
                            <span class="text-danger font-weight-bold"><i class="fas fa-times"></i> Ошибка</span>
                        </div>
                        <input type="text" class="form-control" placeholder="Наименование файла" value="${row.error} (${row.name})" readonly />
                    </div>`);
                }
                else {

                    app.fileList.push(row);
                    let imgId = app.fileList.length - 1;

                    block.append(`<div class="d-flex align-items-center mx-auto mt-2 px-2" style="max-width: 500px;" id="file-added-${row.id}">
                        <div class="card h-100 cursor-pointer hover-link" data-id="${imgId}" onclick="app.showImg(this);" style="width: 100px;">
                            <div class="item-responsive item-16by9">
                                <div class="item-responsive-content"></div>
                                <img src="${row.link}" class="img-fluid" alt="${row.name}" onload="$(this).removeClass('d-none');">
                            </div>
                            <input type="hidden" value="${row.id}" name="${name}[]" />
                        </div>
                        <div class="flex-grow-1 text-truncate px-2 text-left">${row.name}</div>
                        <div class="delete-button">
                            <i class="fas fa-trash hover-link" onclick="application.deleteFile(this);" data-type="appdone" data-id="${row.id}" title="Удалить"></i>
                        </div>
                    </div>`);

                }
            });

        }, () => {

            block.append(`<div class="d-flex align-items-center looooo" style="z-index: 10;">
                <div class="progress w-100">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>`);

        }, percent => {

            block.find('.progress-bar').css('width', percent+"%");
            
            if (percent > 99)
                block.find('.progress-bar').addClass('progress-bar-striped progress-bar-animated');

        }, err => {

            setTimeout(() => {
                block.find('.looooo').remove();
            }, 600);
            $(e).val('');

            return app.globalAlert("Сервер не справился с загрузкой файлов, если Вы загружаете одновременно несколько файлов, попробуйте загрузить их по одному. Если ошибка повторится, то обновите страницу и попробуйте загрузить файлы снова и, если ошибка повторится, обратитесь к администрации сайта", "error", err.status);

        }, true);

    }

    this.deleteFile = e => {

        let data = {
            id: $(e).data('id'),
            token: this.token,
            tempToken: this.tempToken,
        };

        app.deleteFile(data, json => {

            $('#file-added-'+json.data.id).remove();

        });

    }

    /** Добавление/Удаление коллеги из избранного */
    this.userFavorit = e => {

        let data = {
            id: $(e).data('id'),
        };

        $(e).removeAttr('onclick');

        $(e).animate({fontSize: '110%'}, 50)
        .animate({fontSize: '90%'}, 50)
        .animate({fontSize: '110%'}, 50)
        .animate({fontSize: '90%'}, 50)
        .animate({fontSize: '100%'}, 50);

        let animate = setInterval(() => {

            $(e).animate({fontSize: '110%'}, 50)
            .animate({fontSize: '90%'}, 50)
            .animate({fontSize: '110%'}, 50)
            .animate({fontSize: '90%'}, 50)
            .animate({fontSize: '100%'}, 50);
            
        }, 200);

        app.ajax(`/api/token${this.token}/service/userFavorit`, data, json => {

            $(e).attr('onclick', 'application.userFavorit(this);');

            clearInterval(animate);

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            if (json.data.add)
                $(e).removeClass('fas far text-warning').addClass('fas text-warning');
            else
                $(e).removeClass('fas far text-warning').addClass('far');

        });

    }

    this.actEditData = e => {

        let data = {
            id: $(e).data('id'),
        };

        $(e).removeAttr('onclick').removeClass('fa-pen-square').addClass('fa-spin fa-spinner');
        app.modal = $('#modal-application-act');

        app.ajax(`/api/token${this.token}/service/actEditData`, data, json => {

            app.modalLoading(app.modal, 'hide');

            $(e).attr('onclick', 'application.actEditData(this);').removeClass('fa-spin fa-spinner').addClass('fa-pen-square');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);
                
            app.modal.find('#modal-application-act-label').text(`Акт №${json.data.value.num}`);

            app.modal.find('#engineer').html('<option selected value="0">Выберите исполнителя...</option>');
            json.data.users.forEach(row => {
                app.modal.find('#engineer').append(`<option value="${row.id}"${row.admin ? ` class="font-weight-bold"` : ``}>${row.fio}</option>`);
            });

            app.modal.find('#engineer').val(json.data.service.actData.engineer ? json.data.service.actData.engineer : json.data.service.userId);
            app.modal.find('#asdu').val(json.data.service.actData.asdu ? json.data.service.actData.asdu : "");
            app.modal.find('#remark').val(json.data.service.actData.remark ? json.data.service.actData.remark : json.data.value.remark);


            app.modal.find('form [name="id"]').val(json.data.service.id);
            app.modal.find('#save-data').prop('disabled', false);

            app.modal.modal('show');

        }, err => {

            $(e).attr('onclick', 'application.actEditData(this);').removeClass('fa-spin fa-spinner').addClass('fa-pen-square');

        });

    }

    this.actSaveData = e => {

        $(e).prop('disabled', true);
        app.modalLoading(app.modal, 'show');
        let data = app.modal.find('form').serializeArray();

        app.ajax(`/api/token${this.token}/service/actSaveData`, data, json => {

            app.modalLoading(app.modal, 'hide');
            app.modal.modal('hide');
            
        }, err => {

            $(e).prop('disabled', false);
            app.modalLoading(app.modal, 'hide');

        });
        
    }

    this.actDownload = e => {

        let data = {
            id: $(e).data('id'),
        };

        $(e).removeAttr('onclick').removeClass('fa-file-download').addClass('fa-spin fa-spinner');

        app.ajax(`/api/token${this.token}/service/actDownload`, data, json => {

            if (json.error) {
                $(e).attr('onclick', 'application.actDownload(this);').removeClass('fa-spin fa-spinner').addClass('fa-file-download');
                return app.globalAlert(json.error, json.done, json.code);
            }

            app.globalAlert(`Файл сформирован, сейчас начнется скачивание, если этого не произошло <a href="${json.data.link}" download>воспользуйтесь прямой ссылкой</a>`, json.done);
            location.href = json.data.link;

            setTimeout(() => {
                $(e).attr('onclick', 'application.actDownload(this);').removeClass('fa-spin fa-spinner').addClass('fa-file-download');
            }, 1500);

        }, err => {

            $(e).attr('onclick', 'application.actDownload(this);').removeClass('fa-spin fa-spinner').addClass('fa-file-download');

        });

    }

}
var application = new Application;