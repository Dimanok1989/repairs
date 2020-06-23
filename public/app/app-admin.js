function Admin() {

    this.token = $('meta[name="token"]').attr('content');

    /** Объект модального окна */
    this.modal;

    /** Функция активации кнопки модального окна при внесении изменения */
    this.changeDataModal = e => {
        
        let button = $(e).data('save-button') ?? "#save-data";
        this.modal.find('button'+button).prop('disabled', false);
        
    }

    /** Загрузка страницы списка сотрудников */
    this.getUsersList = (search = false) => {

        let data = {
            page: app.page,
        };

        if (search && search != "")
            data.text = search;

        app.scrollDoit(this.getUsersList);

        $('#loading-rows').show();
        app.progress = true;

        app.ajax(`/api/token${this.token}/admin/getUsersList`, data, json => {

            $('#loading-rows').hide();
            app.progress = false;
            app.page++;

            if (!json.data)
                $('#list-users').append(`<div class="text-center my-4 text-muted">Данных нет</div>`);

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $.each(json.data.users, (i,row) => {
                $('#list-users').append(this.getHtmlUserRow(row));
            });

            $('#no-result').remove();
            if (search && json.data.users.length == 0 && app.page == 1)
                $('#sub-content').append(`<div class="mt-4" id="no-result">По запросу "<span class="font-weight-bold">${search}</span>" ничего не найдено</div>`);

            if (json.data.next > json.data.last)
                app.progressEnd = true;

        });

    }
    this.getSearchUsersList = (request, responce) => {

        app.page = 0;
        $('#list-users').empty();
        this.getUsersList(String(request.term).trim());

    }

    this.getHtmlUserRow = (row, update = false) => {

        return `<li id="user-list-item-${row.id}" class="list-group-item list-group-item-action- text-left${row.ban == 1 ? ' list-group-item-danger' : ''}"${update ? ` style="opacity: .4;" ` : ''} data-user="${row.id}">
            <div class="d-flex w-100 justify-content-between mb-1">
                <div class="d-flex align-items-baseline">
                    <!-- <div class="btn-group" role="group">
                        <button id="btn-user-menu-${row.id}" type="button" class="btn ${row.ban == 1 ? 'btn-danger' : 'btn-dark'} btn-sm align-middle rounded-circle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-ellipsis-v" style="width: 14px;"></i></button>
                        <div class="dropdown-menu" aria-labelledby="btn-user-menu-${row.id}">
                            <button class="dropdown-item" type="button" onclick="admin.userData(this);" data-id="${row.id}"><i class="fas fa-user-edit mr-1"></i>Редактировать</button>
                            <button class="dropdown-item" type="button" data-id="${row.id}" onclick="admin.userAccess(this);"><i class="fas fa-user-cog mr-1"></i>Права доступа</button>
                            <button class="dropdown-item user-ban" type="button" onclick="admin.userBan(this);" data-ban="${row.ban}"><i class="fas ${row.ban == 1 ? 'fa-user-check' : 'fa-user-slash'} mr-1"></i>${row.ban == 1 ? 'Разблокировать' : 'Заблокировать'}</button>
                        </div>
                    </div> -->
                    <i class="d-block fas fa-ellipsis-v hover-link text-center" id="btn-user-menu-${row.id}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="width: 20px;"></i>
                    <div class="dropdown-menu shadow" aria-labelledby="btn-user-menu-${row.id}">
                        <button class="dropdown-item" type="button" onclick="admin.userData(this);" data-id="${row.id}"><i class="fas fa-user-edit mr-1"></i>Редактировать</button>
                        <button class="dropdown-item" type="button" data-id="${row.id}" onclick="admin.userAccess(this);"><i class="fas fa-user-cog mr-1"></i>Права доступа</button>
                        <button class="dropdown-item user-ban" type="button" onclick="admin.userBan(this);" data-ban="${row.ban}"><i class="fas ${row.ban == 1 ? 'fa-user-check' : 'fa-user-slash'} mr-1"></i>${row.ban == 1 ? 'Разблокировать' : 'Заблокировать'}</button>
                        <button class="dropdown-item" type="button" data-id="${row.id}" onclick="admin.passReset(this);"><i class="fas fa-key mr-1"></i>Сбросить пароль</button>
                    </div>
                    <h5 class="d-inline mb-0 ml-2">@${row.login}</h5>
                </div>
                ${row.date ? `<small>был ${row.date}</small>` : ``}
            </div>
            <p class="m-0">${row.fio ? row.fio : ``}${row.ban == 1 ? '<i class="fas fa-ban ml-3"></i> <strong>ЗАБЛОКИРОВАН</strong>' : ''}</p>
            <div class="d-flex w-100 justify-content-between align-items-center">
                <p class="m-0${row.color ? ` text-${row.color} font-weight-bold` : ``}">${row.name}</p>
            </div>
        </li>`;

    }

    this.userData = function(e, newuser = false) {

        let id = $(e).data('id') ?? false;
        let data = {};

        if (newuser) {
            id = false;
            $('#modal-user #save-user-data').data('id', id);
        }

        if (id)
            data.id = id;

        $('#modal-user #loading-modal').removeClass('d-none');
        $('#modal-user #modal-add-title').text('Новый сотрудник');
        $('#modal-user #save-user-data').prop('disabled', false); 
        $('#modal-user form').removeClass('was-validated');
        $('#modal-user form #login').prop('disabled', false); 
        $('#modal-user form')[0].reset();

        $('#modal-user form .is-invalid').each(function() {
            $(this).removeClass('is-invalid');
        });

        $('#modal-user form #firstname, #modal-user form #lastname').on('change', this.autoLogin);
        $('#modal-user form #login').on('change', () => {
            this.autoLoginOff = $('#modal-user form #login').val() ? true : false;
        });
        this.autoLoginOff = false;

        app.ajax(`/api/token${this.token}/admin/getDataForUser`, data, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $('#modal-user').modal('show');

            $('#modal-user form #group').html(`<option selected disabled value="">Выберите группу...</option>`);
            $.each(json.data.group, (i,row) => {
                $('#modal-user form #group').append(`<option value="${row.id}">${row.name}</option>`);
            });
            
            if (json.data.user) {

                let user = json.data.user;

                $('#modal-user form #login').prop('disabled', true).val(user.login);
                $('#modal-user form #firstname').val(user.firstname);
                $('#modal-user form #lastname').val(user.lastname);
                $('#modal-user form #fathername').val(user.fathername);
                $('#modal-user form #phone').val(user.phone);
                $('#modal-user form #group').val(user.groupId);

                $('#modal-user #modal-add-title').text('Данные сотрудника');
                $('#modal-user #save-user-data').data('id', user.id);

                $('#modal-user form #firstname, #modal-user form #lastname, #modal-user form #login').off('change');

            }

            $('#modal-user #loading-modal').addClass('d-none');
            $('#modal-user form #phone').mask("+7 (999) 999-9999");

        });

    }

    this.autoLoginOff = false;
    this.autoLogin = e => {

        if (this.autoLoginOff)
            return this;

        let data = {
            lastname: $('#modal-user form #lastname').val(),
            firstname: $('#modal-user form #firstname').val(),
            login: $('#modal-user form #login').val(),
        };

        $('#modal-user form #login').prop('disabled', true);
        $('#modal-user #save-user-data').prop('disabled', true);

        app.ajax(`/api/token${this.token}/admin/autoLogin`, data, json => {

            $('#modal-user form #login').prop('disabled', false);
            $('#modal-user #save-user-data').prop('disabled', false);

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $('#modal-user form #login').val(json.data.login);

        });

    }

    this.saveUser = (e) => {

        $(e).prop('disabled', true);
        $('#modal-user #loading-modal').removeClass('d-none');
        $('#modal-user form').removeClass('was-validated');
        
        $('#modal-user form .is-invalid').each(function() {
            $(this).removeClass('is-invalid');
        });

        let data = $('#modal-user form').serializeArray(),
            id = $(e).data('id');

        if (id)
            data.push({name: 'id', value: id});

        app.ajax(`/api/token${this.token}/admin/saveUser`, data, json => {

            $(e).prop('disabled', false);
            $('#modal-user #loading-modal').addClass('d-none');

            if (json.error) {

                $.each(json.inputs, (i,row) => {
                    $(`#modal-user form [name="${row}"]`).addClass('is-invalid');
                });

                return app.globalAlert(json.error, json.done, json.code);

            }

            $('#modal-user').modal('hide');

            let html = this.getHtmlUserRow(json.data.user, true);

            if ($(`#list-users #user-list-item-${json.data.id}`).length)
                $(`#list-users #user-list-item-${json.data.id}`).replaceWith(html);
            else
                $('#list-users').prepend(html);

            $(`#list-users #user-list-item-${json.data.id}`).animate({opacity: 1});

            if (json.data.pass)
                return app.globalAlert(`Создана учетная запись для сотрудника${json.data.user.fio ? ` (${json.data.user.fio})` : ``}, сообщите ему данные для авторизации:<br/>Логин: <strong>${json.data.user.login}</strong><br/>Пароль: <strong>${json.data.pass}</strong>`, json.done);

            if (json.data.upd)
                app.globalAlert("Данные сотрудника обнолены", json.done, false, 3000);

        });
        
    }

    /** Сбросить пароль */
    this.passReset = e => {

        let data = {
            id: $(e).data('id'),
        };

        this.modal = $('#modal-user-reset-pass');

        this.modal.find('#loading-modal').removeClass('d-none');
        this.modal.find('#save-data').prop('disabled', true).text('Сбросить').data('id', data.id);
        this.modal.find('#pass-user').text('******');
        this.modal.find('#fio-user').removeClass('text-success').text('Поиск данных...');

        this.modal.modal('show');

        app.ajax(`/api/token${this.token}/admin/passReset`, data, json => {

            this.modal.find('#loading-modal').addClass('d-none');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.modal.find('#save-data').prop('disabled', false);
            this.modal.find('#fio-user').text(json.data.user.fio);

        });

    }

    this.passResetDone = e => {

        let data = {
            id: $(e).data('id'),
        };

        $(e).prop('disabled', true);
        this.modal.find('#loading-modal').removeClass('d-none');

        app.ajax(`/api/token${this.token}/admin/passResetDone`, data, json => {

            $(e).prop('disabled', false)
            this.modal.find('#loading-modal').addClass('d-none');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $(e).text('Сбросить ещё');
            this.modal.find('#pass-user').addClass('text-success').text(json.data.newpass);

        });

    }

    /** Блокировка/Разблокировка сотрудника */
    this.userBan = (e) => {

        let data = {
            ban: +$(e).data('ban'),
            id: +$(e).parents('.list-group-item').data('user'),
        }

        $(e).parents('.list-group-item').append(`<div class="position-absolute d-flex justify-content-center loading-row-user position-absolute w-100" style="top: 15px;">
            <div class="spinner-border" role="status">
                <span class="sr-only">Загрузка...</span>
            </div>   
        </div>`);

        app.ajax(`/api/token${this.token}/admin/userBan`, data, json => {

            $(e).parents('.list-group-item').find('.loading-row-user').remove();

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);
            
            let html = this.getHtmlUserRow(json.data.user);
            $(`#list-users #user-list-item-${json.data.user.id}`).replaceWith(html);

            app.globalAlert(`Сотрудник <strong>@${json.data.user.login}</strong> ${data.ban == 1 ? 'разблокирован' : 'заблокирован'}`, json.done, false, 2000);

        });

    }

    /** Список индивидуальных прав сотрудника */
    this.accesslist = [];
    this.userAccess = (e) => {

        this.modal = $('#modal-user-access');

        let data = {
            id: $(e).data('id') ?? false,
        }

        this.modal.find('#loading-modal').removeClass('d-none');
        this.modal.find('#modal-add-title').text('Индивидуальные права');
        this.modal.find('#save-data').prop('disabled', false);
        this.modal.find('form').empty();

        app.ajax(`/api/token${this.token}/admin/userGetAccess`, data, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.modal.find('form').append(`<div class="text-center">${json.data.user.fio}</div>`);
            this.modal.find('form').append(`<div class="text-center mb-3${json.data.user.color ? ` text-${json.data.user.color} font-weight-bold` : ``}">${json.data.user.name}</div>`);
            this.modal.find('form').append(`<input type="hidden" name="id" value="${data.id}" />`);

            this.accesslist = json.data.access;

            $.each(this.accesslist, (i,row) => {
                this.modal.find('form').append(`<div class="custom-control custom-switch" id="selected-${i}-box">
                    <input type="checkbox" class="custom-control-input" id="check-${i}" name="${i}" ${json.data.useraccess[i] !== false ? `checked` : ``} />
                    <label class="custom-control-label" for="check-${i}">${row ? row : i}</label>
                </div>
                <div class="mb-2 text-center${json.data.useraccess[i] !== false ? `` : ` d-none`}" id="selected-${i}">
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="sel-${i}-0" name="sel-${i}" class="custom-control-input"${json.data.useraccess[i] === 0 ? ` checked` : ``} value="0">
                        <label class="custom-control-label" for="sel-${i}-0">Запретить</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="sel-${i}-1" name="sel-${i}" class="custom-control-input"${json.data.useraccess[i] == 1 ? ` checked` : ``} value="1">
                        <label class="custom-control-label" for="sel-${i}-1">Разрешить</label>
                    </div>
                </div>`);

                this.modal.find(`form input#check-${i}`).on('change', function() {

                    let id = $(this).attr('name'),
                        check = $(this).prop('checked');
            
                    if (check) {
                        admin.modal.find(`form #selected-${id}`).removeClass('d-none');
                    }
                    else {
                        admin.modal.find(`form #selected-${id}`).addClass('d-none')
                        .find('input').each(function() {
                            $(this).prop('checked', false);
                        });
                    }
            
                });

            });

            this.modal.modal('show');
            this.modal.find('#loading-modal').addClass('d-none');

        });

    }

    /** Сохранение прав досутупа по группе */
    this.saveUserAccess = (e) => {

        let data = this.modal.find('form').serializeArray();

        $(e).prop('disabled', true);
        this.modal.find('#loading-modal').removeClass('d-none');

        app.ajax(`/api/token${this.token}/admin/saveUserAccess`, data, json => {

            $(e).prop('disabled', false);
            this.modal.find('#loading-modal').addClass('d-none');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.modal.modal('hide');

        });

    }

    /** Загрузка страницы списка групп сотрудников */
    this.getUsersGroupsList = () => {

        let data = {
            page: app.page,
        }

        app.ajax(`/api/token${this.token}/admin/getUsersGroupsList`, data, json => {

            $('#loading-rows').hide();

            if (!json.data)
                $('#list-groups').append(`<div class="text-center my-4 text-muted">Данных нет</div>`);

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $.each(json.data.groups, (i,row) => {
                $('#list-groups').append(this.getHtmlUserGroupRow(row));
            });

            if (!json.data.end)
                app.page = json.data.page;

        });

    }

    this.getHtmlUserGroupRow = (row, update = false) => {

        return `<li id="group-list-item-${row.id}" class="list-group-item list-group-item-action text-left${row.color ? ` list-group-item-${row.color}` : ``}"${update ? ` style="opacity: .4;" ` : ''}data-id="${row.id}">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">
                    <span>${row.name}</span>
                    <span class="badge badge-primary ml-2">${row.countUsers}</span>
                </h5>
                <div class="btn-group btn-group-sm" role="group" aria-label="Управление группой">
                    <button type="button" class="btn btn-secondary" data-id="${row.id}" onclick="admin.usersGroupAccess(this);"><i class="fas fa-users-cog"></i></button>
                    <button type="button" class="btn btn-secondary" data-id="${row.id}" onclick="admin.usersGroupData(this);"><i class="far fa-edit"></i></button>
                </div>
            </div>
            ${row.descript ? `<p class="m-0">${row.descript}</p>` : ``}
        </li>`;

    }

    this.usersGroupData = (e) => {

        let data = {},
            id = $(e).data('id') ?? false,
            modal = $('#modal-group');

        if (id)
            data.id = id;

        modal.find('#loading-modal').removeClass('d-none');
        modal.find('#modal-add-title').text('Новая группа');
        modal.find('#save-group-data').prop('disabled', false); 

        modal.find('form #login').prop('disabled', false); 
        modal.find('form')[0].reset();

        modal.find('form .is-invalid').each(function() {
            $(this).removeClass('is-invalid');
        });

        app.ajax(`/api/token${this.token}/admin/getDataForUsersGroups`, data, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            modal.modal('show');
            
            if (json.data.group) {

                let group = json.data.group;

                modal.find('form #namegroup').val(group.name);
                modal.find('form #descriptgroup').val(group.descript);
                modal.find('form #colorgroup').val(group.color);

                modal.find('#modal-add-title').text('Данные группы');
                modal.find('#save-group-data').data('id', group.id);

            }

            modal.find('#loading-modal').addClass('d-none');

        });

    }

    this.saveGroup = (e) => {

        let modal = $('#modal-group');

        $(e).prop('disabled', true);
        modal.find('#loading-modal').removeClass('d-none');
        modal.find('form').removeClass('was-validated');
        
        modal.find('form .is-invalid').each(function() {
            $(this).removeClass('is-invalid');
        });

        let data = modal.find('form').serializeArray(),
            id = $(e).data('id');

        if (id)
            data.push({name: 'id', value: id});

        app.ajax(`/api/token${this.token}/admin/saveGroup`, data, json => {

            $(e).prop('disabled', false);
            modal.find('#loading-modal').addClass('d-none');

            if (json.error) {

                $.each(json.inputs, (i,row) => {
                    modal.find(`form [name="${row}"]`).addClass('is-invalid');
                });

                return app.globalAlert(json.error, json.done, json.code);

            }

            modal.modal('hide');

            let html = this.getHtmlUserGroupRow(json.data.group, true);

            if ($(`#list-groups #group-list-item-${json.data.id}`).length)
                $(`#list-groups #group-list-item-${json.data.id}`).replaceWith(html);
            else
                $('#list-groups').prepend(html);

            $(`#list-groups #group-list-item-${json.data.id}`).animate({opacity: 1});

            if (json.data.upd)
                app.globalAlert("Данные группы обнолены", json.done, false, 2000);

        });
        
    }

    this.usersGroupAccess = (e) => {

        this.modal = $('#modal-group-access');

        let data = {
            id: $(e).data('id') ?? false,
        }

        this.modal.find('#loading-modal').removeClass('d-none');
        this.modal.find('#modal-add-title').text('Настройка группы');
        this.modal.find('#save-group-access-data').prop('disabled', false); 

        app.ajax(`/api/token${this.token}/admin/usersGroupGetAccess`, data, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            let group = json.data.group;
            this.modal.find('form').empty();

            this.modal.find('form').append(`<input type="hidden" name="id" value="${data.id}" />`);

            $.each(json.data.access, (i,row) => {
                this.modal.find('form').append(`<div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="${row.COLUMN_NAME}" name="${row.COLUMN_NAME}" ${group[row.COLUMN_NAME] == 1 ? `checked` : ``}>
                    <label class="custom-control-label" for="${row.COLUMN_NAME}">${row.COLUMN_COMMENT ? row.COLUMN_COMMENT : row.COLUMN_NAME}</label>
                </div>`);
            });

            /** Добавление прав по заказчикам */
            if (json.data.clients.length)
                this.modal.find('form').append(`<div class="mt-3 font-weight-bold text-center">Доступ к заказчикам</div>`);

            $.each(json.data.clients, (i,row) => {
                this.modal.find('form').append(`<div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="clientAccess${row.id}" name="clientAccess[]" ${json.data.clientsAccess[row.id] == 1 ? `checked` : ``} value="${row.id}">
                    <label class="custom-control-label" for="clientAccess${row.id}">${row.name}</label>
                </div>`);
            });

            this.modal.modal('show');

            this.modal.find('#modal-add-title').text(group.name);
            this.modal.find('#save-group-data').data('id', group.id);

            this.modal.find('#loading-modal').addClass('d-none');

        });

    }
    /** Сохранение прав досутупа по группе */
    this.saveGroupAccess = (e) => {

        let data = this.modal.find('form').serializeArray();

        $(e).prop('disabled', true);
        this.modal.find('#loading-modal').removeClass('d-none');

        app.ajax(`/api/token${this.token}/admin/saveGroupAccess`, data, json => {

            $(e).prop('disabled', false);
            this.modal.find('#loading-modal').addClass('d-none');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.modal.modal('hide');

        });

    }

    this.projectData = (e) => {

        this.modal = $('#modal-client-add');

        this.modal.find('#loading-modal').removeClass('d-none');
        this.modal.find('#save-data').prop('disabled', false); 
        this.modal.find('form')[0].reset();

        this.modal.find('form .is-invalid').each(function() {
            $(this).removeClass('is-invalid');
        });

        this.modal.modal('show');
        this.modal.find('#loading-modal').addClass('d-none');

    }

    this.projectDataSave = (e) => {
        
        let data = this.modal.find('form').serializeArray();

        $(e).prop('disabled', true);

        this.modal.find('form .is-invalid').each(function() {
            $(this).removeClass('is-invalid');
        });

        app.ajax(`/api/token${this.token}/admin/saveNewProject`, data, json => {

            $(e).prop('disabled', false);

            $.each(json.inputs, (i,row) => {
                this.modal.find(`form [name="${row}"]`).addClass('is-invalid');
            });

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $('#list-data').prepend(this.getHtmlProjectRow(json.data.project, true));

            setTimeout(() => {
                $(`#list-data #list-item-${json.data.project.id}`).removeClass('list-group-item-success');
            }, 3500);

            this.modal.modal('hide');

        });

    }

    /** Список проектов */
    this.getProjectsList = (e) => {

        let data = {
            page: app.page,
        }

        app.ajax(`/api/token${this.token}/admin/getProjectsList`, data, json => {

            $('#loading-rows').hide();

            if (!json.data)
                $('#list-data').append(`<div class="text-center my-4 text-muted">Данных нет</div>`);

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            if (!json.data.rows || json.data.rows.length == 0)
                return $('#list-data').append(`<div class="text-center my-4 text-muted">Данных нет</div>`);

            $.each(json.data.rows, (i,row) => {
                $('#list-data').append(this.getHtmlProjectRow(row));
            });

            if (!json.data.end)
                app.page = json.data.page;

        }, err => {

            $('#loading-rows').hide();
            $('#list-data').append(`<div class="text-center my-4 text-muted">Данных нет</div>`);
            return app.globalAlert("Произошла неизвестная ошибка сервера", "error", err.status, false, err.responseJSON);

        });

    }

    this.getHtmlProjectRow = (row, update = false) => {

        return `<a href="/admin/projects/${row.id}" id="list-item-${row.id}" class="list-group-item list-group-item-action text-left d-flex justify-content-between${update ? ` list-group-item-success` : ``}">
            <div>
                <h5 class="mb-0">${row.name} @${row.login}</h5>
                <div><strong class="text-${row.access == 1 ? 'success' : 'danger'}">${row.access == 1 ? 'Включен' : 'Выключен'}</strong></div>
            </div>
            <div><i class="fas fa-chevron-right"></i></div>
        </li>`;

    }

    /** Загрузка данных проекта */
    this.getProjectsData = (id) => {

        let data = {id};

        app.ajax(`/api/token${this.token}/admin/getProjectsData`, data, json => {
            
            $('#table-data #loading-rows').addClass('d-none');

            /** Вывод проектов */
            $.each(json.data.types, (i,row) => {

                let html = `<div class="card my-3 text-left" id="card-${i}" data-pr="${i}">
                    <div class="card-header border-bottom-0 d-flex justify-content-between">
                        <span class="header-titile-card">
                            <strong>${row}</strong>
                        </span>
                        <div onclick="admin.showProject(this);" data-open="${json.data.project.listpoints}"><i class="fas fa-chevron-down fa-for-hover"${json.data.project.listpoints == 1 ? ' style="transform: rotate(540deg);"' : ''}></i></div>                    
                    </div>
                    <div class="card-body border-top${json.data.project.listpoints == 1 ? '' : ' d-none'} text-center px-2">
                        <button class="btn btn-primary btn-sm" data-pr="${i}" onclick="admin.addPoint(this);"><i class="fas fa-plus-square mr-3"></i>Добавить пункт</button>
                    </div>
                </div>`;

                $('#table-data #break').append(html);
                $('#table-data #repair').append(html);
                $('#table-data #canseled').append(html);

            });

            /** Вывод пунктов неисправностей */
            this.printPointBreak(json.data.project.break);
            /** Вывод пунктов ремонта */
            this.printPointRepair(json.data.project.repair);
            /** Вывод пунктов отмены */
            this.printPointCansel(json.data.project.canseled);

        });

    }

    /** Вывод пунктов неисправностей */
    this.printPointBreak = (data) => {

        $.each(data, (key,rows) => {

            $(`#table-data #break #card-${key} .card-body`).prepend(`<ul class="list-group mb-3"></ul>`);
            $(`#table-data #break #card-${key} .header-titile-card`).append(`<span class="badge badge-primary badge-pill ml-2">${rows.length}</span>`);

            $.each(rows, (i,row) => {
                $(`#table-data #break #card-${key} ul`).append(this.htmlPointBreak(row));
            });

        });

    }
    /** HTML код одной строки пункта */
    this.htmlPointBreak = (row, update = false) => {
        return `<li class="list-group-item list-group-item-action d-flex justify-content-between px-2 py-1${row.del == 1 ? ' list-group-item-secondary' : '' }" id="point-break-${row.id}"${update ? ' style="opacity: .4; transition: .3s;"' : ''}>
            <div class="text-left">${row.name}</div>
            <div onclick="admin.removeBreakPoint(this);" data-del="${row.del}" data-type="${row.type}" data-id="${row.id}"><i class="fas fa-eye${row.del == 1 ? '-slash' : '' } fa-for-hover"></i></div>
        </li>`;
    }

    /** Вывод пунктов ремонта */
    this.printPointRepair = (data) => {

        $.each(data, (key,rows) => {

            let card = $(`#table-data #repair #card-${key}`);

            card.find('.card-body').prepend(`<ul class="list-group mb-3"></ul>`);
            card.find('.header-titile-card').append(`<span class="badge badge-primary badge-pill ml-2">${rows.length}</span>`);

            $.each(rows, (i,row) => {

                card.find('ul').append(this.htmlPointRepair(row));

                let count = 0;
                $.each(row.subpoints, (sub,subrow) => {
                    subrow.masterdel = row.del == 1 ? true : false;
                    card.find('ul').append(this.htmlSubPointRepair(subrow));
                    count++;
                });
                card.find(`#point-repair-${row.id} span.badge`).text(count);

            });

        });

    }
    /** HTML код одной строки пункта */
    this.htmlPointRepair = (row, newrow = false) => {
        return `<li class="list-group-item list-group-item-action d-flex justify-content-between px-2 py-1${row.del == 1 ? ' list-group-item-secondary' : (newrow ? ' list-group-item-success' : '')}" id="point-repair-${row.id}">
            <div class="text-left">${row.name}${row.master == 0 ? `<strong class="ml-2">${row.norm}</strong>` : `<span class="badge badge-primary badge-pill ml-2">0</span>`}</div>
            <div>
                ${(row.master == 1 && row.del == 0) ? `<div class="d-inline mx-1" onclick="admin.addPoint(this);" data-type="${row.type}" data-point="${row.id}"><i class="fas fa-plus fa-for-hover"></i></div>` : `<div class="d-inline mx-1" onclick="admin.addPoint(this);" data-type="${row.type}" data-id="${row.id}" data-sub="0"><i class="far fa-edit fa-for-hover"></i></div>`}
                <div class="d-inline mx-1" onclick="admin.removeRepairPoint(this);" data-del="${row.del}" data-type="${row.type}" data-id="${row.id}"><i class="fas fa-eye${row.del == 1 ? '-slash' : '' } fa-for-hover"></i></div>                
            </div>
        </li>`;
    }
    /** HTML код одной строки подпункта ремонта */
    this.htmlSubPointRepair = (row, newrow = false) => {
        return `<li class="list-group-item list-group-item-action list-item-slave-${row.repairId} d-flex justify-content-between px-2 py-1${row.del == 1 ? ' list-group-item-secondary' : (newrow ? ' list-group-item-success' : '')}${row.masterdel ? ' disabled' : ''}" id="sub-point-repair-${row.id}">
            <span class="pl-2"><i class="fas fa-circle fa-xs mr-2 align-middle" style="opacity: .2; font-size: 20%;"></i>${row.name}<strong class="ml-2">${row.norm}</strong></span>
            <div>
                <div class="d-inline mx-1" onclick="admin.addPoint(this);" data-type="${row.type}" data-id="${row.id}" data-sub="1" data-point="${row.repairId}"><i class="far fa-edit fa-for-hover"></i></div>
                <div class="d-inline mx-1" onclick="admin.removeRepairSubPoint(this);" data-del="${row.del}" data-type="${row.type}" data-id="${row.id}"><i class="fas fa-eye${row.del == 1 ? '-slash' : '' } fa-for-hover"></i></div>                
            </div>
        </li>`;
    }

    /** Вывод пунктов отмены */
    this.printPointCansel = data => {

        $.each(data, (key,rows) => {

            $(`#table-data #canseled #card-${key} .card-body`).prepend(`<ul class="list-group mb-3"></ul>`);
            $(`#table-data #canseled #card-${key} .header-titile-card`).append(`<span class="badge badge-primary badge-pill ml-2">${rows.length}</span>`);

            let html;

            $.each(rows, (i,row) => {
                html = this.htmlPointCansel(row);
                $(`#table-data #canseled #card-${key} ul`).append(html);
            });

        });

    }
    /** HTML код одной строки подпункта ремонта */
    this.htmlPointCansel = row => {
        return `<li class="list-group-item list-group-item-action d-flex justify-content-between px-2 py-1${row.del == 1 ? ' list-group-item-secondary' : '' }" id="point-canseled-${row.id}">
            <div class="text-left">${row.name}</div>
            <div onclick="admin.removeCanselPoint(this);" data-del="${row.del}" data-type="${row.type}" data-id="${row.id}"><i class="fas fa-eye${row.del == 1 ? '-slash' : '' } fa-for-hover"></i></div>
        </li>`;
    }

    /** Раскрытие списка пунктов проекта */
    this.showProject = (e) => {

        let open = +$(e).data('open'),
            card = $(e).parents('.card');

        if (open) {
            $(e).data('open', 0).find('i').css({transition: '.3s', transform: 'rotate(0deg)'});
            card.find('.card-body').addClass('d-none');
        }
        else {
            $(e).data('open', 1).find('i').css({transition: '.3s', transform: 'rotate(540deg)'});
            card.find('.card-body').removeClass('d-none');
        }

    }

    /** Окно добавления пункта */
    this.addPoint = (e) => {

        let project = $(e).data('pr') ? $(e).data('pr') : $(e).parents('.card').data('pr'),
            type = $(e).parents('.tab-content').attr('id'),
            id = $(e).data('id') ? $(e).data('id') : false,
            point = $(e).data('point') ? $(e).data('point') : false;

        if (type == "break" || type == "canseled") {
            this.modal = $('#modal-add-point-break');
            if (type == "break")
                this.modal.find('h5.modal-title').text("Пункт неисправности");
            else if (type == "canseled")
                this.modal.find('h5.modal-title').text("Пункт отмены заявки");
        }
        else
            this.modal = $('#modal-add-point-repair');

        this.modal.find('form')[0].reset();
        this.modal.find('form [name="point"]').remove();

        this.modal.find('form #norma').prop('disabled', false);
        this.modal.find('form #slave-form input').prop('disabled', false);
        this.modal.find('form #forchangedserials').prop('disabled', true);
        this.modal.find('form #forchangedfond').prop('disabled', true);

        this.modal.find(`form .is-invalid`).each(function() {
            $(this).removeClass('is-invalid');
        });

        this.modal.find('form [name="type"]').val(type);
        this.modal.find('form [name="project"]').val(project);

        if (point) {
            this.modal.find('form').append(`<input type="hidden" name="point" value="${point}">`);
            this.modal.find('form #master-select').addClass('d-none');
        }
        else
            this.modal.find('form #master-select').removeClass('d-none');

        this.modal.find('form [name="name"]').prop('disabled', false);
        this.modal.find('form [name="norma"]').prop('disabled', false);
        this.modal.find('form [name="master"]').prop('disabled', false);

        this.modal.find('form [name="id"]').remove();

        if (!id && (type == "break" || type == "canseled")) {
            app.modalLoading(this.modal, 'hide');
            return this.modal.modal('show');
        }

        let data = {
            id,
            project,
            type,
            point,
            sub: $(e).data('sub'),
        };

        this.modal.modal('show');
        app.modalLoading(this.modal, 'show');

        app.ajax(`/api/token${this.token}/admin/getPointProjectsData`, data, json => {

            this.modal.find('#device').html('<option selected value="">Не выбрано</option><option value="add">Ввести вручную</option>');

            if (json.data.devicesGroup.length)
                this.modal.find('#device').append('<optgroup label="Группы оборудования" id="device-group"></optgroup>');

            json.data.devicesGroup.forEach(row => {
                this.modal.find('#device #device-group').append(`<option value="g-${row.id}">${row.name}</option>`);
            });

            if (json.data.devicesGroup.length)
                this.modal.find('#device').append('<optgroup label="Оборудование" id="device-row"></optgroup>');

            json.data.devices.forEach(row => {
                this.modal.find('#device #device-row').append(`<option value="d-${row.id}">${row.name}</option>`);
            });

            app.modalLoading(this.modal, 'hide');
            
            if (!id)
                return false;

            if (json.data.point.length == 0)
                return app.globalAlert("Данные пункта не получены", "error");

            let form = this.modal.find('form');

            form.find('[name="name"]').val(json.data.point.name).prop('disabled', true);
            form.find('[name="norma"]').val(json.data.point.norm).prop('disabled', true);
            form.find('[name="master"]').prop('disabled', true);

            form.find('[name="device"]').val(json.data.point.deviceSelect);

            if (json.data.point.id)
                form.append(`<input type="hidden" name="id" value="${json.data.point.id}">`);

            if (json.data.point.changed == 1)
                form.find('[name="forchanged"]').prop('checked', true).trigger('change');

            if (json.data.point.serials == 1)
                form.find('[name="forchangedserials"]').prop('checked', true).trigger('change');

            if (json.data.point.fond == 1)
                form.find('[name="forchangedfond"]').prop('checked', true).trigger('change');

        });

    }

    /** Сохранение нового пункта */
    this.savePointBreak = (e) => {

        $(e).prop('disabled', true);
        app.modalLoading(this.modal, 'show');

        this.modal.find(`.is-invalid`).each(function() {
            $(this).removeClass('is-invalid');
        });

        let data = this.modal.find('form').serializeArray();        

        app.ajax(`/api/token${this.token}/admin/savePointBreak`, data, json => {

            $(e).prop('disabled', false);
            app.modalLoading(this.modal, 'hide');

            if (json.error) {
                $.each(json.inputs, (i,row) => {
                    this.modal.find(`[name="${row}"]`).addClass('is-invalid');
                });
                return app.globalAlert(json.error, json.done, json.code);
            }

            this.modal.modal('hide');
            let pr = $(`#table-data #${json.data.type} #card-${json.data.point.type}`);

            if (!pr.find('ul').length)
                pr.find('.card-body').prepend(`<ul class="list-group mb-3"></ul>`);

            let html;
            
            if (json.data.type == "break")
                html = this.htmlPointBreak(json.data.point);
            else if (json.data.type == "canseled")
                html = this.htmlPointCansel(json.data.point);

            pr.find('ul').append(html);

            $(`#point-${json.data.type}-${json.data.point.id}`).css('opacity', '.2')
            .animate({opacity: '1'}, 250);

            if (!pr.find('.header-titile-card .badge').length)
                pr.find('.header-titile-card').append(`<span class="badge badge-primary badge-pill ml-2">0</span>`);

            let count = +pr.find('.header-titile-card .badge').text()+1;
            pr.find('.header-titile-card .badge').text(count);

        });

    }

    /** Удаление возврат пункта неисправностей */
    this.removeBreakPoint = e => {

        let data = {
            id: $(e).data('id'),
            del: +$(e).data('del'),
            type: +$(e).data('type'),
        }

        $(e).find('i').removeAttr('onclick')
        .removeClass('fa-eye-slash fa-eye fa-for-hover').addClass('fa-spin fa-spinner');

        app.ajax(`/api/token${this.token}/admin/removeBreakPoint`, data, json => {

            if (json.error) {
                $(e).find('i').attr('onclick', 'admin.removeBreakPoint(this);')
                .removeClass('fa-spin fa-spinner')
                .addClass(data.del == 1 ? 'fa-eye-slash fa-for-hover' : 'fa-eye fa-for-hover');
                return app.globalAlert(json.error, json.done, json.code);
            }

            $(`#table-data #break #point-break-${json.data.point.id}`)
            .replaceWith(this.htmlPointBreak(json.data.point));

        });

    }

    /** Удаление возврат пункта отмены */
    this.removeCanselPoint = e => {

        let data = {
            id: $(e).data('id'),
            del: +$(e).data('del'),
            type: +$(e).data('type'),
        }

        $(e).find('i').removeAttr('onclick')
        .removeClass('fa-eye-slash fa-eye fa-for-hover').addClass('fa-spin fa-spinner');

        app.ajax(`/api/token${this.token}/admin/removeCanselPoint`, data, json => {

            if (json.error) {
                $(e).find('i').attr('onclick', 'admin.removeCanselPoint(this);')
                .removeClass('fa-spin fa-spinner')
                .addClass(data.del == 1 ? 'fa-eye-slash fa-for-hover' : 'fa-eye fa-for-hover');
                return app.globalAlert(json.error, json.done, json.code);
            }

            $(`#table-data #canseled #point-canseled-${json.data.point.id}`)
            .replaceWith(this.htmlPointCansel(json.data.point));

        });

    }

    /** Выбор мастер пункта ремонта */
    this.selectMasterPointRepair = e => {

        let master = $(e).prop('checked'),
            norm = $(e).parents('form').find('#slave-form input');

        if (master) {
            norm.prop('disabled', true);
        }
        else {
            norm.prop('disabled', false);
            $('#forchanged').trigger('change');
        }

    }

    /** Выбор мастер пункта ремонта */
    this.selectPointRepairEnterSerials = e => {

        let checked = $(e).prop('checked'),
            checkbox = $('#forchangedserials, #forchangedfond, #device');

        if (checked)
            checkbox.prop('disabled', false);
        else
            checkbox.prop('disabled', true);

    }

    /** Сохранение нового пункта по ремонту */
    this.savePointRepair = (e) => {

        $(e).prop('disabled', true);
        app.modalLoading(this.modal, 'show');

        this.modal.find(`.is-invalid`).each(function() {
            $(this).removeClass('is-invalid');
        });

        this.modal.find('form [name="name"]').prop('disabled', false);
        this.modal.find('form [name="norma"]').prop('disabled', false);
        this.modal.find('form [name="master"]').prop('disabled', false);

        let data = this.modal.find('form').serializeArray();       

        app.ajax(`/api/token${this.token}/admin/savePointRepair`, data, json => {

            $(e).prop('disabled', false);
            app.modalLoading(this.modal, 'hide');

            if (this.modal.find('form [name="id"]').length) {
                this.modal.find('form [name="name"]').prop('disabled', true);
                this.modal.find('form [name="norma"]').prop('disabled', true);
                this.modal.find('form [name="master"]').prop('disabled', true);
            }

            if (json.error) {
                $.each(json.inputs, (i,row) => {
                    this.modal.find(`[name="${row}"]`).addClass('is-invalid');
                });
                return app.globalAlert(json.error, json.done, json.code);
            }

            this.modal.modal('hide');

            if (json.data.slave) {

                let pr = $(`#table-data #repair #card-${json.data.project}`),
                    html = this.htmlSubPointRepair(json.data.point);

                if (json.data.update == 1 || json.data.update == 0) {
                    pr.find(`#sub-point-repair-${json.data.point.id}`).replaceWith(html);
                }
                else {
                    pr.find(`#point-repair-${json.data.slave}`).after(html);
                    let count = +pr.find(`#point-repair-${json.data.slave} span.badge`).text()+1;
                    pr.find(`#point-repair-${json.data.slave} span.badge`).text(count);
                }

                $(`#sub-point-repair-${json.data.point.id}`).css('opacity', '.2')
                .animate({opacity: '1'}, 250);

                return;

            }

            let pr = $(`#table-data #repair #card-${json.data.point.type}`),
                html = this.htmlPointRepair(json.data.point);

            if (!pr.find('ul').length)
                pr.find('.card-body').prepend(`<ul class="list-group mb-3"></ul>`);

            if (json.data.update == 1 || json.data.update == 0) {
                $(`#point-repair-${json.data.point.id}`).replaceWith(html);
            }
            else {
                pr.find('ul').append(html);
                let count = +pr.find('.header-titile-card .badge').text()+1;
                pr.find('.header-titile-card .badge').text(count);
            }

            $(`#point-repair-${json.data.point.id}`).css('opacity', '.2')
            .animate({opacity: '1'}, 250);

            if (!pr.find('.header-titile-card .badge').length)
                pr.find('.header-titile-card').append(`<span class="badge badge-primary badge-pill ml-2">0</span>`);

        });

    }

    /** Удаление возврат пункта ремонта */
    this.removeRepairPoint = e => {

        let data = {
            id: $(e).data('id'),
            del: +$(e).data('del'),
            type: +$(e).data('type'),
        }

        $(e).find('i').removeAttr('onclick')
        .removeClass('fa-eye-slash fa-eye fa-for-hover').addClass('fa-spin fa-spinner');

        app.ajax(`/api/token${this.token}/admin/removeRepairPoint`, data, json => {

            if (json.error) {

                $(e).find('i').attr('onclick', 'admin.removeBreakPoint(this);')
                .removeClass('fa-spin fa-spinner')
                .addClass(data.del == 1 ? 'fa-eye-slash fa-for-hover' : 'fa-eye fa-for-hover');

                return app.globalAlert(json.error, json.done, json.code);

            }

            $(`#table-data #repair #point-repair-${json.data.point.id}`)
            .replaceWith(this.htmlPointRepair(json.data.point));

            let count = 0;
            $(`#table-data #repair ul .list-item-slave-${json.data.point.id}`).each(function() {
                if (json.data.point.del == 1)
                    $(this).addClass('disabled');
                else
                    $(this).removeClass('disabled');
                count++;
            });

            if (json.data.point.master == 1)
                $(`#table-data #repair #point-repair-${json.data.point.id} span.badge`).text(count);

        });

    }

    /** Удаление возврат пункта ремонта */
    this.removeRepairSubPoint = e => {

        let data = {
            id: $(e).data('id'),
            del: +$(e).data('del'),
            type: +$(e).data('type'),
        }

        $(e).find('i').removeAttr('onclick')
        .removeClass('fa-eye-slash fa-eye fa-for-hover').addClass('fa-spin fa-spinner');

        app.ajax(`/api/token${this.token}/admin/subPointRepairShow`, data, json => {

            if (json.error) {

                $(e).find('i').attr('onclick', 'admin.removeBreakPoint(this);')
                .removeClass('fa-spin fa-spinner')
                .addClass(data.del == 1 ? 'fa-eye-slash fa-for-hover' : 'fa-eye fa-for-hover');

                return app.globalAlert(json.error, json.done, json.code);
            }

            $(`#table-data #repair #sub-point-repair-${json.data.point.id}`)
            .replaceWith(this.htmlSubPointRepair(json.data.point));

        });

    }

    /** Открытие окна настроек данных заказчика */
    this.settingClient = e => {

        $(e).removeAttr('onclick')
        .removeClass('fa-for-hover').addClass('fa-spin');

        let data = {
            id: $(e).data('id'),
        };

        this.modal = $('#modal-settings');
        this.modal.find('#save-data').prop('disabled', true);

        app.ajax(`/api/token${this.token}/admin/getProjectsData`, data, json => {

            $(e).attr('onclick', 'admin.settingClient(this);')
            .removeClass('fa-spin').addClass('fa-for-hover');

            $.each(json.data.project, (i,row) => {

                if (i == "access" || i == "listpoints")
                    this.modal.find(`[name="${i}"]`).prop('checked', row == "1" ? true : false);
                else
                    this.modal.find(`[name="${i}"]`).val(row);

            });

            this.modal.modal('show');
            app.loading(this.modal, 'hide');

        });

    }

    /** Сохранение настроек данных заказчика */
    this.saveSettings = e => {

        $(e).prop('disabled', true);
        app.loading(this.modal, 'show');

        let data = this.modal.find('form').serializeArray();

        app.ajax(`/api/token${this.token}/admin/saveSettingsProject`, data, json => {

            app.loading(this.modal, 'hide');

            if (json.error) {
                $(e).prop('disabled', false);
                return app.globalAlert(json.error, json.done, json.code);
            }

            // Сворачивание/Разворачивание списка
            $('#table-data .card').each(function() {
                let open = $(this).find('.card-header > div').data('open');
                if (open != json.data.updated.listpoints)
                    $(this).find('.card-header > div').trigger('click');
            });

            // Замена заголовка
            $('h5#name-client').find('span').text(json.data.updated.name);
            // Замена индикатора активности
            $('h5#name-client').find('.fa-circle').removeClass('text-success text-danger').addClass(json.data.updated.access == 1 ? 'text-success' : 'text-danger');

            this.modal.modal('hide');

        });

    }


    /** Список подвижного состава */
    this.getBusList = () => {

        app.scrollDoit(this.getBusList);

        let data = app.getQuery();
        data.page = app.page;

        if (data.sort)
            $('#filter-sort').val(data.sort);

        if (app.page == 0)
            $('#rows-data').empty();

        $('#no-data-info').remove();

        $('#dataloader').show();        
        app.progress = true;

        app.ajax(`/api/token${app.token}/admin/getBusList`, data, json => {

            $('#dataloader').hide();
            app.progress = false;
            app.page = json.data.next;

            json.data.rows.forEach(row => {

                row.search = json.data.search;
                row.name = (row.mark ? row.mark : ``)+(row.model ? ` `+row.model : ``);
                
                let html = this.getRowBus(row);
                $('#rows-data').append(html);

            });

            if (json.data.rows.length == 0)
                $('#global-data').append('<div class="opacity-50 my-4 mx-auto" id="no-data-info">Ничего не найдено</div>');

            if (json.data.next > json.data.last)
                app.progressEnd = true;

        });

    }
    
    this.getRowBus = row => {

        return `<tr class="border-top">
            <td class="py-1 px-2">
                <a href="/bus${row.id}" target="_blank">${String(row.garage).replace(row.search, `<mark class="p-0" style="background: #fff2a4;">${row.search}</mark>`)}</a>
            </td>
            <td class="py-1 px-2">${String(row.name).replace(row.search, `<mark class="p-0" style="background: #fff2a4;">${row.search}</mark>`)}</td>
            <td class="py-1 px-2">${String(row.number).replace(row.search, `<mark class="p-0" style="background: #fff2a4;">${row.search}</mark>`)}</td>
            <td class="py-1 px-2">${String(row.vin).replace(row.search, `<mark class="p-0" style="background: #fff2a4;">${row.search}</mark>`)}</td>
            <td class="py-1 px-2">${String(row.client).replace(row.search, `<mark class="p-0" style="background: #fff2a4;">${row.search}</mark>`)}</td>
            <td class="py-1 px-2">
                <div class="btn-group dropleft">
                    <i class="fas fa-ellipsis-v fa-for-hover text-center" style="width: 20px;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></i>
                    <div class="dropdown-menu py-1 text-left">
                        <button class="dropdown-item px-3" type="button"><i class="fas fa-pen mr-2"></i>Изменить</button>
                    </div>
                </div>
            </td>
        </tr>`;

    }

    this.filterBus = e => {

        let type = $(e).data('type'),
            val = $(e).val(),
            data = app.getQuery();

        data[type] = val;

        if (val == "0")
            delete data[type];

        let search = app.getQueryUrl(data);
        window.history.pushState(null, null, `/admin/bus${search}`);

        app.page = 0;
        this.getBusList();

    }

    this.getSearchBus = (request, responce) => {

        let data = app.getQuery();
        data.search = String(request.term).trim();

        let search = app.getQueryUrl(data);
        window.history.pushState(null, null, `/admin/bus${search}`);

        app.page = 0;
        this.getBusList();

    }

    this.getDeviceList = () => {

        $('#loading-rows').show();

        app.ajax(`/api/token${app.token}/admin/getDeviceList`, json => {

            $('#loading-rows').hide();

            if (json.data.devices.length == 0)
                return $('#list-data').append('<div class="my-4 text-muted" id="no-devices">Данных еще нет</div>');

            let html;
            json.data.devices.forEach(row => {

                html = this.getDeviceHtmlRow(row);
                $('#list-data').append(html);

            });

        });

    }

    this.getDeviceHtmlRow = row => {
        return `<button type="button" class="list-group-item list-group-item-action text-left" id="device-${row.id}" data-id="${row.id}" onclick="admin.getDeviceRow(this);">
            <div class="font-weight-bold">${row.name}</div>
            ${row.groupName ? `<small class="text-muted">${row.groupName}</small>` : ''}
        </button>`;
    }

    this.getDeviceRow = e => {

        $(e).prop('disabled', true).blur();

        app.modal = $('#modal-device');
        let data = {
            id: $(e).data('id') ? $(e).data('id') : false,
        };

        app.modal.find('form')[0].reset();
        app.modal.find('form [name="id"]').val('');

        app.modal.modal('show');
        app.modalLoading(app.modal, 'show');

        app.ajax(`/api/token${app.token}/admin/getDeviceRow`, data, json => {

            $(e).prop('disabled', false);
            app.modalLoading(app.modal, 'hide');

            app.modal.find('form #group').html('<option selected value="">Выберите группу...</option>');
            json.data.groups.forEach(row => {
                app.modal.find('form #group').append(`<option value="${row.id}">${row.name}</option>`);
            });

            if (json.data.device.groupId)
                app.modal.find('form #group').val(json.data.device.groupId);

            if (json.data.device.id)
                app.modal.find('form [name="id"]').val(json.data.device.id);

            if (json.data.device.name)
                app.modal.find('form #name').val(json.data.device.name);

        }, err => {
            $(e).prop('disabled', false);
            app.modalLoading(app.modal, 'hide');
        });

    }

    this.saveDevice = e => {

        $(e).prop('disabled', true);
        app.modalLoading(app.modal, 'show');

        let data = app.modal.find('form').serializeArray();

        app.ajax(`/api/token${app.token}/admin/saveDevice`, data, json => {

            $(e).prop('disabled', false);
            app.modalLoading(app.modal, 'hide');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            app.modal.modal('hide');

            let text = app.modal.find(`form #group option[value=${json.data.device.groupId}]`).text();
            json.data.device.groupName = text;

            let html = this.getDeviceHtmlRow(json.data.device);

            if ($('#device-'+json.data.device.id).length)
                $('#device-'+json.data.device.id).replaceWith(html);
            else
                $('#list-data').prepend(html);

            $('#device-'+json.data.device.id).css('opacity', '.2')
            .animate({opacity: '1'}, 250);

            $('#no-devices').remove();

        }, err => {
            $(e).prop('disabled', false);
            app.modalLoading(app.modal, 'hide');
        });

    }

}
const admin = new Admin;