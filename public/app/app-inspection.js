function Inspection() {

    this.lastTime = false;

    this.tape = () => {

        app.progress = true;
        $('#loading-data').show();

        let data = {
            page: app.page,
        };

        app.ajax(`/api/token${app.token}/inspection/tape`, data, json => {

            $('#loading-data').hide();
            app.progress = false;

            if (json.error) {
                app.progressEnd = true;
                return;
            }

            json.data.inspections.forEach(row => {
                this.echoRowTable(row);
            });

            if (json.data.next > json.data.last)
                app.progressEnd = true;

            app.page = json.data.next;
            this.lastTime = json.data.lastTime;

            let colspan = $('#content-table thead tr th').length;

            if (json.data.inspections.length == 0 && json.data.next == 2)
                $('#all-table-rows').append(`<tr id="no-data-tr-table"><td class="py-2 opacity-60" colspan="${colspan}">Данных ещё нет</div>`);
            else if (json.data.next > json.data.last)
                $('#all-table-rows').append(`<tr><td class="py-2 opacity-60" colspan="${colspan}">Это все данные</div>`);

        });

    }

    this.checkUpdateTable = () => {

        if (!this.lastTime || app.progress)
            return false;

        let data = {
            lastTime: this.lastTime,
        };

        app.ajax(`/api/token${app.token}/inspection/checkUpdateTable`, data, json => {

            this.lastTime = json.data.lastTime;

            if (json.error)
                return false;

            json.data.inspections.forEach(row => {
                this.echoRowTable(row, true);
            });

            if (json.data.inspections.length)
                $('#all-table-rows #no-data-tr-table').remove();

        });

    }

    this.echoRowTable = (row, prepend = false) => {

        let html = this.getHtmlTableRow(row);

        if ($('#table-row-'+row.id).length) {
            $('#table-row-'+row.id).replaceWith(html);
            $('#table-row-'+row.id).css('opacity', '.2').animate({opacity: 1}, 300);
            return;
        }

        if (prepend)
            return $('#all-table-rows').prepend(html);

        $('#all-table-rows').append(html);

    }

    this.getHtmlTableRow = row => {

        let color = "table-warning";
        
        if (row.done !== null)
            color = "table-success";

        return `<tr class="align-self-center ${color}" id="table-row-${row.id}">
            <th class="align-middle" scope="col" data-th="Приёмка">#${row.id}</th>
            <td class="align-middle" data-th="Заказчик">${row.clientName ? row.clientName : ''}</td>
            <td class="align-middle" data-th="Дата">${row.dateAdd}</td>
            <td class="align-middle" data-th="Машина">${row.busMark ? row.busMark+' ' : ''}${row.busModel ? row.busModel+' ' : ''}<i>${row.busGarage}</i></td>
            <td class="align-middle" data-th="ФИО">${row.fio}</td>
            <td class="align-middle">
                <a class="btn btn-link p-0" href="/inspection/${row.id}" role="button">
                    <span class="table-adaptive-minim mr-2">Перейти</span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </td>
        </tr>`;

    }

    this.startData = e => {

        app.modal = $('#new-inspection');

        app.modal.modal('show');
        app.modalLoading(app.modal, 'show');

        app.modal.find('#data-save').prop('disabled', true);
        app.modal.find('form')[0].reset();

        app.ajax(`/api/token${app.token}/inspection/startData`, json => {

            app.modalLoading(app.modal, 'hide');
            app.modal.find('#data-save').prop('disabled', false);

            app.modal.find('#client-select').html('<option selected value="">Укажу позже...</option>');
            json.data.clients.forEach(row => {
                app.modal.find('#client-select').append(`<option value="${row.id}">${row.name}</option>`);
            });
            
        });

    }

    this.start = e => {

        app.modalLoading(app.modal, 'show');
        $(e).prop('disabled', true);

        let data = app.modal.find('form').serializeArray();

        app.ajax(`/api/token${app.token}/inspection/start`, data, json => {

            if (json.error) {
                app.modalLoading(app.modal, 'hide');
                $(e).prop('disabled', false);
                return app.globalAlert(json.error, json.done, json.code);
            }

            location.href = json.data.link;

        });

    }

    this.data = {}

    this.open = id => {

        let data = {id};

        app.ajax(`/api/token${app.token}/inspection/open`, data, json => {

            $('#inspection-data').empty();

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.data = json.data;

            // if (json.data.inspection.clientName)
            //     $('#bus-data').before(`<p>${json.data.inspection.clientName}</p>`);

            $('#bus-data').append(`<p class="font-weight-bold mb-2">Данные машины</p>`);

            let clients = "";
            json.data.clients.forEach(row => {
                clients += `<option value="${row.id}">${row.name}</option>`;
            });

            $('#bus-data').append(`<div class="row mx-1">
                <div class="col-sm p-1">
                    <div class="input-group">
                        <select class="form-control" id="inspClient" name="client">
                            <option value="" selected>Выберите заказчика...</option>
                            ${clients}
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mx-1">
                <div class="col-sm p-1">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Марка" aria-label="Марка" id="inspBusMark" value="${json.data.inspection.busMark ? json.data.inspection.busMark : ''}" name="busMark">
                    </div>
                </div>
                <div class="col-sm p-1">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Модель" aria-label="Модель" id="inspBusModel" value="${json.data.inspection.busModel ? json.data.inspection.busModel : ''}" name="busModel">
                    </div>
                </div>
            </div>
            <div class="row mx-1">
                <div class="col-sm p-1">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Гос. номер" aria-label="Гос. номер" id="inspBusRegNum" value="${json.data.inspection.busRegNum ? json.data.inspection.busRegNum : ''}" name="busRegNum">
                    </div>
                </div>
                <div class="col-sm p-1">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="VIN" aria-label="VIN" id="inspBusVin" value="${json.data.inspection.busVin ? json.data.inspection.busVin : ''}" name="busVin">
                    </div>
                </div>
            </div>`);

            $('#inspClient').val(json.data.inspection.client);

            $('#bus-data').find('input, select').on('change', function() {
                inspection.changeBusData(this);
            }).on('focus', function() {
                $(this).addClass('input-focus');
            }).on('blur', function() {
                $(this).removeClass('input-focus');
            });

            $.each(json.data.buttons, (i,rows) => {

                let buttons = "", count = 1;
                rows.forEach(row => {

                    if (count == 1)
                        buttons += `<div class="row mx-1">`;

                    buttons += `<div class="col-sm p-1">
                        <button class="btn btn-secondary btn-block mx-auto h-100" type="button" onclick="inspection.device(this);" data-id="${row.id}" id="button-type-${row.type}">${row.button}</button>
                    </div>`;

                    if (count == 3) {
                        buttons += `</div>`;
                        count = 0;
                    }

                    count++;

                });

                $('#inspection-data').append(`<div id="dvr-data" class="my-3">
                    <p class="font-weight-bold mb-1">${json.data.projects[i]}</p>
                    ${buttons}
                </div>`);

            });

            if (!json.data.inspection.done) {

                $('#inspection-data').append(`<div id="done-button">
                    <button type="button" class="btn btn-dark position-relative" onclick="inspection.done(this);">Завершить</button>
                </div>`);

            }

            // Замена цвета кнопок
            this.changeButtonsColor(json.data.colors);

            if (json.data.inspection.done) {

                clearInterval(this.getButtonsColorInterval);
                this.getHtmlDoneInspection(json.data);

                if (json.data.inspection.clientName)
                    $('#done-inspect').before(`<p>${json.data.inspection.clientName}</p>`);

            }

        });

    }

    this.changeButtonsColor = colors => {

        colors.forEach(row => {

            $('#button-type-'+row.type)
            .removeClass('btn-secondary btn-success btn-danger')
            .addClass('btn-'+row.color);

        });

    }

    this.names = ['client', 'busMark', 'busModel', 'busRegNum', 'busVin'];

    this.getButtonsColorInterval;

    this.getButtonsColor = id => {

        app.ajax(`/api/token${app.token}/inspection/open`, {id}, json => {

            this.changeButtonsColor(json.data.colors);

            this.data = json.data;

            let value = "",
                hasClass = false;

            this.names.forEach(row => {

                hasClass = $(`#bus-data [name="${row}"]`).hasClass('input-focus');
                value = $(`#bus-data [name="${row}"]`).val();
                
                if (json.data.inspection[row] && json.data.inspection[row] != value && !hasClass)
                    $(`#bus-data [name="${row}"]`).val(json.data.inspection[row]).css('opacity', '.1').animate({opacity: 1}, 300);

            });

            if (json.data.inspection.done) {

                clearInterval(this.getButtonsColorInterval);
                this.getHtmlDoneInspection(json.data);

                if (json.data.inspection.clientName)
                    $('#done-inspect').before(`<p>${json.data.inspection.clientName}</p>`);

            }

        });

    }

    this.getHtmlDoneInspection = data => {

        $('#done-inspect').empty();
        $('#done-inspect').append(`<div class="d-flex justify-content-between">
            <div class="text-left font-weight-bold">${data.inspection.busMark} ${data.inspection.busModel}</div>
            <div class="text-right">${data.inspection.busRegNum}</div>
        </div>
        <div class="d-flex justify-content-between">
            <div class="text-left font-weight-bold"></div>
            <div class="text-right">${data.inspection.busVin}</div>
        </div>`);

        let html = "";
        $.each(data.devices, (i,rows) => {
            html = this.getHtmlDoneInspectionRow(rows);
            $('#done-inspect').append(html);
        });

        $('#done-button').remove();

        if (!data.check)
            $('#bus-data, #inspection-data').remove();

    }

    this.getHtmlDoneInspectionRow = rows => {

        let html = "";

        rows.forEach(row => {

            html += '<hr class="my-2">';

            let firstRight = row.deviceName;

            if (row.noinstall == 1)
                firstRight = "Не установлено";

            let subname = "";
            if (row.multiple) {

                row.buttonSett.deviceList.forEach(dev => {
                    if (dev.id == row.multiple) {
                        subname = `<span class="opacity-50 ml-1" title="Пломбы">${dev.name}</span>`;
                    }
                });

            }

            html += `<div class="d-flex justify-content-between">
                <div class="text-left font-weight-bold">${row.crash == 1 ? '<i class="fas fa-car-crash text-danger mr-2"></i>' : ''}${row.buttonSett.button}${subname}</div>
                <div class="text-right">${firstRight ? firstRight : '<i class="fas fa-check-circle text-success"></i>'}</div>
            </div>`;

            let stamps = row.buttonSett.stamp == 1 ? '<i class="fas fa-stamp mr-1 opacity-40"></i>' + row.stamps.join(' ') : "";

            if (row.buttonSett.serial || stamps)
                html += `<div class="d-flex justify-content-between">
                    <div class="text-left">${stamps}</div>
                    <div class="text-right">${row.serial ? row.serial : ''}</div>
                </div>`;

            if (row.comment)
                html += `<div class="d-flex justify-content-between">
                    <div class="text-right">${row.comment}</div>
                </div>`;

        });

        return html;

    }

    this.changeBusData = e => {

        $(e).prop('disabled', true);

        let data = {
            id: this.data.inspection.id,
            name: $(e).attr('name'),
            value: $(e).val(),
        };

        app.ajax(`/api/token${app.token}/inspection/changeBusData`, data, json => {

            $(e).prop('disabled', false);

        }, err => {
            $(e).prop('disabled', false);
            $(e).val('');
        });

    }

    this.loadingDevice = false;

    this.device = e => {

        if (this.loadingDevice)
            return false;

        this.loadingDevice = true;

        let data = {
            id: $(e).data('id'),
            insp: this.data.inspection.id,
            multiple: $(e).data('multipleRow') ? $(e).data('multipleRow') : false,
        };

        $(e).prop('disabled', true)
        .append(`<div class="d-flex justify-content-center align-items-center image-loading">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>`);

        app.modal = $('#device-info');

        app.modal.find('.modal-header button.close').replaceWith(`<button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>`);

        app.ajax(`/api/token${app.token}/inspection/deviceForm`, data, json => {

            $(e).prop('disabled', false).find('.image-loading').remove();
            app.modalLoading(app.modal, 'hide');

            this.loadingDevice = false;

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            app.modal.find('h5.modal-title').text(json.data.button.button);
            app.modal.find('.modal-footer').show();
            app.modal.modal('show');

            let form = app.modal.find('form');
            form.html(`<div id="device-form"></div>`);

            if (json.data.button.multiple && !data.multiple)
                return this.deviceMultiple(json.data);

            if (json.data.button.multipleTitle)
                app.modal.find('h5.modal-title').text(json.data.button.multipleTitle);

            let device = form.find('#device-form');

            device.append(`<input type="hidden" name="insp" value="${this.data.inspection.id}" />`);
            device.append(`<input type="hidden" name="typeId" value="${json.data.button.type}" />`);

            if (json.data.button.devices)
                device.append(`<input type="hidden" name="devices" value="${json.data.button.devices}" />`);

            if (data.multiple) {

                device.append(`<input type="hidden" name="multiple" value="${data.multiple}" />`);
                device.append(`<input type="hidden" name="button" value="${data.id}" />`);

                app.modal.find('.modal-header button.close').replaceWith(`<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="app.modalLoading(app.modal, 'show'); inspection.device(this);" data-id="${json.data.button.id}">
                    <span aria-hidden="true">×</span>
                </button>`);

            }

            if (json.data.button.device == 1) {

                device.append(`<div class="form-group">
                    <label for="select-name" class="mb-1">Наименование устройства</label>
                    <select id="select-name" class="form-control" name="name">
                        <option selected value="">Выберите наименование...</option>
                        <option value="add" class="font-weight-bold">Нет в списке...</option>
                    </select>
                    <input type="text" class="form-control mt-2 d-none" name="nameAdd" id="name-add" placeholder="Введите наименование вручную..." />
                </div>`);

                device.find('#select-name').on('change', {select: device.find('#select-name')}, this.selectChangeDeviceName);

                let countDevice = 0;
                json.data.devices.forEach(row => {
                    device.find('#select-name').append(`<option value="${row.id}">${row.name}</option>`)
                    countDevice++;
                });

                if (countDevice == 0) {
                    device.find('#select-name').val('add').trigger('change');
                    device.find('#select-name').addClass('d-none');
                    device.find('#name-add').removeClass('mt-2');
                }

            }

            if (json.data.button.onlyAvailability == 1) {
                device.append(`<div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" name="install" class="custom-control-input" id="install-check">
                    <label class="custom-control-label" for="install-check">Не установлено / Отсутствует</label>
                </div>`);
            }

            if (json.data.button.onlyCount == 1) {

                device.append(`<input type="hidden" name="count" id="count-devices" value="0">
                <div class="d-flex justify-content-center align-items-center mb-3" id="count-devices-buttons">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-back" data-step="-1" disabled><i class="fas fa-minus-square"></i></button>
                    <h5 class="mb-0 mx-3" id="counted">0</h5>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-next" data-step="1"><i class="fas fa-plus-square"></i></button>
                </div>`);

                app.modal.find('#count-devices-buttons button').on('click', function() {
                    inspection.countDevice(this);
                });

            }

            if (json.data.button.serial == 1) {
                device.append(`<div class="form-group">
                    <label for="serial-number" class="mb-1">Серийный номер</label>
                    <input id="serial-number" type="text" name="serial" class="form-control" placeholder="Введите серийный номер устройства...">
                </div>`);
            }

            if (json.data.button.onlyCount == 0) {
                device.append(`<div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" name="crash" class="custom-control-input" id="crach-check">
                    <label class="custom-control-label" for="crach-check">Устройтво неисправно</label>
                </div>`);
            }

            device.append(`<div class="form-group">
                <textarea class="form-control" name="comment" id="comment" rows="3" placeholder="Примечание..."></textarea>
            </div>`);

            // Заполнение ранее введенных данных
            if (json.data.device.id) {

                device.append(`<input type="hidden" name="id" value="${json.data.device.id}" />`);

                device.find('#select-name').val(json.data.device.name);
                device.find('#serial-number').val(json.data.device.serial);
                device.find('#comment').val(json.data.device.comment);

                device.find('#crach-check').prop('checked', json.data.device.crash);
                device.find('#install-check').prop('checked', json.data.device.noinstall);

                if (json.data.device.count) {
                    device.find('#btn-back').prop('disabled', Number(json.data.device.count) > 0 ? false : true);
                    device.find('#count-devices').val(json.data.device.count);
                    device.find('#count-devices-buttons #counted').text(json.data.device.count);
                }

            }

            // if (json.data.button.stamp == 1) {
                device.append(`<div class="form-group" id="stamps-rows">
                    <label class="mb-0">Пломбы</label>
                </div>`);

                if (json.data.device.stamps) {
                    let stamps = 0;
                    json.data.device.stamps.forEach(row => {
                        this.addStampRow(row);
                        stamps++;
                    });

                    if (stamps == 0)
                        this.addStampRow();

                }
                else
                    this.addStampRow();

            // }

        }, err => {
            $(e).prop('disabled', false).find('.image-loading').remove();
            this.loadingDevice = false;
        });

    }

    this.deviceList = {};

    this.deviceMultiple = data => {

        let html = "",
            countButton = 1,
            countRow = 1,
            count = 1,
            buttons = [],
            ids = [],
            colors = [];

        // Стандартный набор кнопок
        data.button.defaultList.forEach(row => {
            buttons.push(row);
            ids.push(row.id);
        });

        let newButtons = "";
        data.button.deviceList.forEach(row => {
            newButtons += `<option value="${row.id}">${row.name}</option>`;
            this.deviceList[row.id] = row;
        });

        let buttonArray = {};
        data.device.forEach(row => {

            buttonArray = this.deviceList[row.multiple];
            buttonArray.color = row.color;

            let key = $.inArray(row.multiple, ids);

            if (key < 0)
                buttons.push(buttonArray);
            else
                buttons[key] = buttonArray;

        });

        buttons.forEach(row => {

            if (countButton == 1)
            html += `<div class="row mx-2 buttons-row" id="count-button-row-${countRow}">`;

            html += `<div class="col-sm p-1 buttons-cell">
                <button class="btn btn-${row.color ? row.color : `secondary`} btn-block mx-auto h-100" type="button" onclick="inspection.device(this);" data-id="${data.button.id}" data-multiple-row="${row.id}">${row.name}</button>
            </div>`;

            if (countButton == 3 || count == buttons.length)
                html += `</div>`;

            if (countButton == 3) {
                countButton = 0;
                countRow++;
            }

            countButton++;
            count++;

        });

        app.modal.find('form').append(`<div id="device-buttons" class="mb-3">
            ${html}
        </div>`);

        app.modal.find('form').append(`<input type="hidden" value="${data.button.id}" id="buttonId">`);

        if (newButtons != "") {
            app.modal.find('form').append(`<div class="input-group mb-3">
                <select class="custom-select" id="select-new-device">
                    <option value="" selected>Выберите устройство...</option>
                    ${newButtons}
                </select>
                <div class="input-group-append">
                    <button class="btn btn-outline-info" type="button" onclick="inspection.addMultiDevice(this);"><i class="fas fa-plus-square mr-2"></i>Добавить</button>
                </div>
            </div>`);
        }

        app.modal.find('.modal-footer').hide();

    }

    this.addMultiDevice = e => {

        let select = app.modal.find('#select-new-device'),
            device = select.val(),
            button = app.modal.find('#buttonId').val();

        select.removeClass('is-invalid');

        if (device == "")
            return select.addClass('is-invalid');

        select.val("");
        
        let newButton = `<div class="col-sm p-1 buttons-cell">
            <button class="btn btn-secondary btn-block mx-auto h-100" type="button" onclick="inspection.device(this);" data-id="${button}" data-multiple-row="${device}">${this.deviceList[device].name}</button>
        </div>`;

        let countButton = 0,
            countRow = 0;

        app.modal.find('#device-buttons .buttons-row').each(function() {

            countRow++;
            countButton = 0;

            $(this).find('.buttons-cell').each(function() {
                countButton++;
            });

        });

        if (countButton == 3) {
            countRow++;
            app.modal.find('form #device-buttons').append(`<div class="row mx-2 buttons-row" id="count-button-row-${countRow}"></div>`);
        }

        app.modal.find(`#count-button-row-${countRow}`).append(newButton);

    }

    this.selectChangeDeviceName = e => {

        let selected = e.data.select.val();

        if (selected == "add")
            app.modal.find('form #name-add').removeClass('d-none');
        else
            app.modal.find('form #name-add').addClass('d-none');

    }

    this.addStampRow = (num = false) => {

        app.modal.find('#stamps-rows').append(`<div class="input-group mt-1">
            <input name="stamp[]" type="text" class="form-control" placeholder="Введите номер одной пломбы..." value="${num ? num : ""}">
        </div>`);

        this.countStampRows();

    }

    this.removeStampRow = e => {

        $(e).parents('.input-group').remove();
        this.countStampRows();

    }

    this.countStampRows = () => {

        let rows = app.modal.find('#stamps-rows .input-group').length,
            count = 1;
        
        app.modal.find('#stamps-rows .input-group').each((i,row) => {

            $(row).find('.input-group-append').remove();

            if ((count == 1 && rows == 1) || count > 1)
                $(row).append('<div class="input-group-append"></div>');
        
            if (count > 1)
                $(row).find('.input-group-append').append('<button class="btn btn-outline-danger" type="button" onclick="inspection.removeStampRow(this);" title="Удалить строку с пломбой"><i class="fas fa-trash"></i></button>');

            if (count == rows)
                $(row).find('.input-group-append').append('<button class="btn btn-outline-success" type="button" onclick="inspection.addStampRow();" title="Добавить еще одну пломбу"><i class="fas fa-plus"></i></button>');

            count++;

        });        

    }

    this.countDevice = e => {

        let count = Number(app.modal.find('#count-devices').val()),
            step = Number($(e).data('step'));

        let now = count + step;

        if (now < 0)
            now = 0;

        app.modal.find('#btn-back').prop('disabled', now > 0 ? false : true);
        app.modal.find('#count-devices').val(now);
        app.modal.find('#count-devices-buttons #counted').text(now);

    }

    this.save = e => {

        let data = app.modal.find('form').serializeArray();
        app.modalLoading(app.modal, 'show');

        app.ajax(`/api/token${app.token}/inspection/save`, data, json => {


            if (json.error) {
                app.modal.modal('hide');
                return app.globalAlert(json.error, json.done, json.code);
            }

            if (json.data.button) {
                let button = `<button type="button" data-id="${json.data.button}"></button>`;
                return this.device(button);
            }

            app.modal.modal('hide');
            this.getButtonsColor(this.data.inspection.id);

        });

    }

    this.done = e => {

        $(e).prop('disabled', true)
        .append(`<div class="d-flex justify-content-center align-items-center image-loading">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>`);

        let data = {
            id: this.data.inspection.id,
        };

        app.ajax(`/api/token${app.token}/inspection/done`, data, json => {

            $(e).prop('disabled', false).find('.image-loading').remove();
            app.formValidRemove($('#bus-data'));

            if (json.inputs)
                app.formValidErrors($('#bus-data'), json.inputs);

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            location.href = "/inspection";

        }, err => {
            $(e).prop('disabled', false).find('.image-loading').remove();
        });
        
    }

}
const inspection = new Inspection;