function Montage() {

    this.subfolders = [];

    this.getDataForStart = () => {

        $('#worktapeload').removeClass('d-none').addClass('d-flex');

        app.ajax(`/api/token${app.token}/montage/getDataForStart`, json => {

            $('#worktapeload').removeClass('d-flex').addClass('d-none');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            let options = '<option selected value="0">Выберите филиал...</option>';
            json.data.mains.forEach(row => {
                options += `<option value="${row.id}">${row.name}</option>`;
            });
            $('#bus-filial').html(options);

            this.subfolders = json.data.subfolders;

            // Список монтажей
            this.allMontagesList();

        });

    }

    this.selectedFilial = e => {

        let filial = +$(e).val();

		if (filial > 0) {

            // Поиск площадок филиала
            let options = '<option selected value="0">Выберите площадку...</option>';
            options += '<option value="add">Указать площадку вручную...</option>';

            let count = 0;
            this.subfolders.forEach(row => {
                if (row.main == filial) {
                    options += `<option value="${row.id}">${row.name}</option>`;
                    count++;
                }
            });

			$('#bus-place').html(options);
			$('#place-select').removeClass('d-none');
			$('#input-place').hide();

			if (count == 0) {
				$('#bus-place').val('add').trigger('change');
				$('#input-place').show();
				$('#bus-place-edit').focus();
            }
            
		}
		else {
            $('#place-select').addClass('d-none');
            $('#input-place').hide();
        }

    }

    this.selectedPlace = e => {

        let val = $(e).val();

        if (val == "add")
            $('#input-place').show();
        else
            $('#input-place').hide();

    }

    this.start = e => {
        
        $(e).prop('disabled', true);
        $('#worktapeload').removeClass('d-none').addClass('d-flex');
        app.formValidRemove($('#start-montage'));

        let data = $('form#start-montage').serializeArray();

        app.ajax(`/api/token${app.token}/montage/start`, data, json => {

            if (json.error) {

                app.formValidErrors($('#start-montage'), json.inputs);

                $(e).prop('disabled', false);
                $('#worktapeload').removeClass('d-flex').addClass('d-none');

                return app.globalAlert(json.error, json.done, json.code);

            }

            window.location.href = "/montage"+json.data.id;

        });

    }

    this.montageId = 0;

    this.getOneMontage = id => {

        $('#loading-global').addClass('d-flex');
        this.montageId = id;

        app.ajax(`/api/token${app.token}/montage/getOneMontage`, {id}, json => {

            $('#loading-global').removeClass('d-flex');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            // Список избранных пользователей
            json.data.fav.forEach(row => {
                let html = application.getHtmlRowCheckboxCollegue(row);
                $('#users-selected').append(html);
            });

            // Список машин
            json.data.bus.forEach(row => {
                $('select#busName').append(`<option value="${row}">${row}</option>`);
            });

            // Заполнение существующих данных
            json.data.montage.inputs.forEach(row => {

                if (row.name == "serialNum")
                    row.value = String(row.value).replace("WM19120177S", "");

                $(`#data-montage [name="${row.name}"]`).val(row.value);

                if (row.name == "busName" && row.value == "add")
                    $('#edit-busName').removeClass('d-none');

            });

            $(`#data-montage [name="subBus"]`).val(json.data.montage.subBus);

            this.getHtmlAllFiles(json.data.montage.files);

            // Ссылка на автоматический акт
            app.fileList.push({
                link: "/montage/act" + id,
                name: json.data.montage.bus + "_Акт_автоматический.jpg"
            });
            let idfilelist = app.fileList.length - 1;
            $('#link-auto-act').replaceWith(`<span class="btn-link cursor-pointer" onclick="app.showImg(this);" data-id="${idfilelist}">Фото акта <i class="fas fa-external-link-alt ml-2"></i></span>`);

            if (json.data.montage.completed)
                this.completedMontage(json.data.montage);

            this.addCommentsRows(json.data.montage.comments);

        });

    }

    this.completedMontage = montage => {

        $('#search-users-block').html('<div class="font-weight-bold mb-2">Выполняли</div>');

        let count = 1;
        montage.users.forEach(row => {
            $('#search-users-block').append(`<div class="text-left px-2"><strong>${count}.</strong> ${row.fio}</div>`);
            count++;
        });

        $('#sub-title').text('Данные монтажа');

        $('#data-montage .btn-add-file').each(function() {
            $(this).remove();
        });

        $('#data-montage .delete-button').each(function() {
            $(this).remove();
        });

        $('#data-montage select, #data-montage input').each(function() {
            $(this).prop('disabled', true);
        });

        $('#completed-button').remove();

    }

    this.searchResultUsers = {};
    this.searchCollegue = (request, responce) => {

        let data = {
            search: String(request.term).trim(),
        }

        app.ajax(`/api/token${app.token}/montage/searchCollegue`, data, json => {

            $('#search-collegue-block').dropdown('show');
            $('#search-result').empty();

            this.searchResultUsers = json.data.users;

            $.each(json.data.users, (i,row) => {
                $('#search-result').append(`<button class="dropdown-item" type="button" onclick="montage.selectUserAddFromSearch(this);" data-key="${i}">${String(row.fio).replace(data.search, `<mark class="p-0">${data.search}</mark>`)} <b>@${String(row.login).replace(data.search, `<mark class="p-0">${data.search}</mark>`)}</b>${row.favorit > 0 ? ` <i class="fas fa-star text-warning"></i>` : ``}</button>`);
            });

            if (!json.data.users.length)
                $('#search-result').append(`<p class="text-muted mb-0 px-3">По запросу "<b>${data.search}</b>" ничего не найдено</p>`);

        });

    }
    
    this.selectUserAddFromSearch = e => {

        let key = $(e).data('key');
        $('#search-collegue-block').dropdown('hide');

        if (!$(`#users-selected #checkbox-line-user-${this.searchResultUsers[key].id}`).length) {
            let html = application.getHtmlRowCheckboxCollegue(this.searchResultUsers[key]);
            $('#users-selected').append(html);
        }
        
        $(`input#user-add-${this.searchResultUsers[key].id}`).prop('checked', true);

        $('#search-collegue').val('');
        $('#search-result').html(`<p class="text-muted mb-0 px-3">Начните поиск по ФИО или логину</p>`);


    }

    this.changeInput = e => {

        $(e).prop('disabled', true);

        let data = {
            id: this.montageId,
            name: $(e).attr('name'),
            value: $(e).val(),
        };

        app.ajax(`/api/token${app.token}/montage/changeInput`, data, json => {

            $(e).prop('disabled', false);

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $(e).val(json.data.input.value);

        });

    }

    this.selectBusName = e => {

        let val = $(e).val();

        if (val == "add")
            $('#edit-busName').removeClass('d-none').find('input').val('');
        else
            $('#edit-busName').addClass('d-none');

    }

    this.getHtmlAllFiles = files => {

        files.forEach(row => {

            app.fileList.push(row);
            let imgId = app.fileList.length - 1;

            $('#files-'+row.type).append(`<div class="d-flex align-items-center mx-auto my-2 px-2" style="max-width: 400px;" id="file-block-${row.id}">
                <div class="card h-100 cursor-pointer hover-link" data-id="${imgId}" onclick="app.showImg(this);" style="width: 100px;">
                    <div class="item-responsive item-16by9">
                        <div class="item-responsive-content"></div>
                        <img src="${row.link}" class="img-fluid" alt="${row.name}" onload="$(this).removeClass('d-none');">
                    </div>
                    <input type="hidden" name="photos[]" value="${row.type}" />
                </div>
                <div class="flex-grow-1 text-truncate px-2 text-left">${row.name}</div>
                <div class="delete-button"><i class="fas fa-trash hover-link" onclick="montage.deleteFile(this);" data-id="${row.id}" data-montage="${row.montageId}" title="Удалить"></i></div>
            </div>`);

        });

        return this;

    }

    this.fileForm = "";

    this.openAddFile = e => {
        this.fileForm = $(e).data('content');
        $('#file-select').trigger('click');
    }

    this.uploadFile = e => {

        let formData = new FormData(),
            files = $(e).prop('files'),
            mainblock = $('#cont-file-'+this.fileForm),
            type = this.fileForm;

        // Пройти в цикле по всем файлам
	    for (var i = 0; i < files.length; i++)
            formData.append('images[]', files[i]);

        formData.append('id', this.montageId);
        formData.append('fileForm', this.fileForm);

        $(e).val('');

        app.file(`/api/token${app.token}/montage/uploadFile`, formData, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            setTimeout(() => {
                mainblock.find('.image-loading').remove();
                mainblock.find('button').prop('disabled', false).removeClass('btn-danger').addClass('btn-primary');

                if (type == "comment")
                    this.addCommentsRows(json.data.files);
                else
                    this.getHtmlAllFiles(json.data.files);

            }, 600);

        }, () => {

            let loading = this.getHtmlLoadingDiv();
            mainblock.append(loading);
            mainblock.find('button').blur().prop('disabled', true);

        }, percent => {

            mainblock.find('.progress-bar').css('width', percent+'%').attr('aria-valuemin', percent);

            if (percent >= 99.9) {
                setTimeout(() => {
                    mainblock.find('.progress-bar').removeClass('bg-dark').addClass('bg-success progress-bar-animated');
                }, 600);
            }

        }, err => {

        }, true);

    }

    this.getHtmlLoadingDiv = () => {

        return `<div class="d-flex justify-content-center align-items-center image-loading" style="z-index: 15;">
            <div class="progress w-100">
                <div class="progress-bar progress-bar-striped bg-dark" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>`;

    }

    this.deleteFile = e => {

        $(e).removeClass('fa-trash').addClass('fa-spin fa-spinner');

        let id = $(e).data('id'),
            montage = $(e).data('montage');

        app.ajax(`/api/token${app.token}/montage/deleteFile`, {id, montage}, json => {

            $(e).addClass('fa-trash').removeClass('fa-spin fa-spinner');

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            $('#file-block-'+id).remove();

        });

    }

    this.doneMontage = e => {

        $(e).prop('disabled', true);
        $('#loading-global').removeClass('d-none').addClass('d-flex');

        let data = $('#data-montage').serializeArray();
        app.formValidRemove($('#data-montage'));

        $('#loading-global .btn-add-file').each(function() {
            $(this).removeClass('btn-danger').addClass('btn-primary');
        });

        app.ajax(`/api/token${app.token}/montage/doneMontage`, data, json => {

            $(e).prop('disabled', false);
            $('#loading-global').addClass('d-none').removeClass('d-flex');

            if (json.inputs && json.code == 5002)
                app.formValidErrors($('#data-montage'), json.inputs);

            if (json.inputs && json.code == 5003) {
                json.inputs.forEach(row => {
                    $(`#cont-file-${row} button`).addClass('btn-danger').removeClass('btn-primary');
                });
            }

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.completedMontage(json.data.montage);

        });

    }

    this.sendComment = () => {

        let butt = $('#cont-file-comment .i-send-message');
        let data = {
            text: String($('#comment-text').val()).trim(),
            id: this.montageId,
        };

        if (data.text == "" || !data.text) {

            butt.addClass('text-danger');

            butt.animate({right: '7px'}, 50)
            .animate({right: '15px'}, 50)
            .animate({right: '7px'}, 50)
            .animate({right: '15px'}, 50)
            .animate({right: '10px'}, 50);

            return false;

        }

        $('#comment-text').blur();
        $('#cont-file-comment').append('<div class="looooo" style="z-index: 7;"></div>')

        butt.removeAttr('onclick').removeClass('fa-paper-plane hover-link text-danger').addClass('fa-spin fa-spinner');

        app.ajax(`/api/token${app.token}/montage/sendComment`, data, json => {

            $('#cont-file-comment .looooo').remove();

            butt.attr('onclick', 'montage.sendComment();').removeClass('fa-spin fa-spinner').addClass('fa-paper-plane hover-link');

            if (json.error) {

                butt.addClass('text-danger');

                butt.animate({right: '7px'}, 50)
                .animate({right: '15px'}, 50)
                .animate({right: '7px'}, 50)
                .animate({right: '15px'}, 50)
                .animate({right: '10px'}, 50);

                return app.globalAlert(json.error, json.done, json.code);

            }
            
            this.addCommentsRows(json.data.comment);

        });

    }

    this.sendCommentAuto = e => {

        if (event.keyCode == 13)
            this.sendComment();

    }

    this.addCommentsRows = rows => {

        let html = "";
        rows.forEach(row => {

            if (row.link)
                html = this.addCommentsRowPhoto(row);
            else
                html = this.addCommentsRowText(row);

            $('#comments-list').prepend(html);

        });

        if (rows.length)
            $('#no-comments-rows').remove();

    }

    this.addCommentsRowPhoto = row => {

        app.fileList.push({
            link: row.link,
            name: row.name,
        });
        let fileid = app.fileList.length - 1;

        return `<div class="my-3 px-3">
            <div class="text-left">
                <span class="font-weight-bold">${row.fio}</span>
                <span class="ml-2 opacity-40">${row.dateAdd}</span>
            </div>
            <div class="card h-100 cursor-pointer hover-link mt-1" data-id="${fileid}" onclick="app.showImg(this);">
                <div class="item-responsive item-16by9">
                    <div class="item-responsive-content"></div>
                    <img src="${row.link}" class="img-fluid" alt="${row.name}" onload="$(this).removeClass('d-none');">
                </div>
            </div>
        </div>`;

    }

    this.addCommentsRowText = row => {

        return `<div class="my-3 px-3 text-left">
            <div>
                <span class="font-weight-bold">${row.fio}</span>
                <span class="ml-2 opacity-40">${row.dateAdd}</span>
            </div>
            <div>${row.comment}</div>
        </div>`;

    }

    this.typeTableForScroll = false;
    this.allMontagesList = (type = false) => {

        if (type && !this.typeTableForScroll)
            this.typeTableForScroll = true;

        let data = {
            page: app.page,
        };
        let html;

        app.progress = true;
        $('#loading-table').show();

        app.ajax(`/api/token${app.token}/montage/allMontagesList`, data, json => {

            app.scrollDoit(this.allMontagesList);
            $('#loading-table').hide();

            json.data.rows.forEach(row => {

                html = this.typeTableForScroll ? this.getHtmlMoreRowTablethis(row) : this.getHtmlRowTable(row);
                $('#all-montages').append(html);

            });

            if (json.data.next > json.data.last) {

                app.progressEnd = true;
                let colspan = $('#content-table thead tr th').length;

                $('#all-montages').append(`<tr id="all-montages-no-data">
                    <td class="text-center no-data-info" colspan="${colspan}"><small class="d-block my-2 opacity-40">Это все данные</small></td>
                </tr>`);

            }

            $('[data-toggle="tooltip"]').tooltip();
            
            app.page = json.data.next;
            app.progress = false;

        });

    }

    this.getHtmlRowTable = row => {
        return `<tr class="align-self-center ${row.completed === null ? 'table-warning' : ''}">
            <th class="align-middle" scope="col" data-th="Монтаж">${row.id}</th>
            <td class="align-middle" data-th="Дата">${row.dateAdd}</td>
            <td class="align-middle" data-th="Машина">${row.bus}</td>
            <td class="align-middle" data-th="Филиал">${row.filial}</td>
            <td class="align-middle" data-th="Площадка">${row.place}</td>
            <td class="align-middle" data-th="Завершил">${row.fio}${row.countUsers > 0 ? ' +'+row.countUsers : ''}</td>
            <td class="align-middle" data-th="Страница монтажа">
                <a class="btn btn-link p-0" target="_blanck" href="/montage${row.id}" role="button">
                    <span class="table-adaptive-minim mr-2">Перейти</span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </td>
        </tr>`;
    }

    this.getHtmlMoreRowTablethis  = row => {

        return `<tr class="align-self-center ${row.completed === null ? 'table-warning' : ''}" id="tr-montage-${row.id}">
            <th class="align-middle" scope="col" data-th="Монтаж">
                <a href="/montage${row.id}" target="_blanck">#${row.id}<i class="fas fa-external-link-alt ml-1"></i></a>
            </th>
            <td class="align-middle" data-th="Дата">${row.dateAdd}</td>
            <td class="align-middle" data-th="Машина"><i class="fas fa-bus fa-for-hover${row.busGarage ? '' : ' text-danger'} mr-1" onclick="montage.addBusData(this);" data-montage="${row.id}"></i><a href="/admin/bus?search=${row.busInt}" target="_search">${row.bus}</a></td>
            <td class="align-middle" data-th="Марка">${row.inputs.busName ? row.inputs.busName : ''}</td>
            <td class="align-middle" data-th="Гос. номер">${row.inputs.busNum ? row.inputs.busNum : ''}</td>
            <td class="align-middle" data-th="Филиал">${row.filial}</td>
            <td class="align-middle" data-th="Площадка">${row.place}</td>
            <td class="align-middle" data-th="Завершил">${row.fio}${row.countUsers > 0 ? ` <strong>+${row.countUsers}</strong>` : ''}</td>
            <td class="align-middle" data-th="Комментарии">
                <span class="${row.comments == 0 ? 'opacity-30' : ''}"><i class="far fa-comments mr-1"></i><b>${row.comments}</b></span>            
            </td>
            <td class="align-middle">
                <div class="btn-group btn-group-sm" role="group">
                    ${row.completed ? `
                    <button type="button" class="btn btn-warning" target="_blanck" title="Скачать архив с файлами" onclick="montage.zip(this);" data-id="${row.id}">
                        <i class="far fa-file-archive" style="width: 14px;"></i>
                    </button>` : ``}
                    <!--<a class="btn btn-primary" target="_blanck" href="/montage${row.id}" role="button" title="Перейти на страницу монтажа" data-toggle="tooltip">
                        <i class="fas fa-external-link-alt"></i>
                    </a>-->
                </div>
            </td>
        </tr>`;

    }

    this.excel = e => {

        let data = {
            start: $('#start-excel').val(),
            stop: $('#stop-excel').val(),
        };

        $(e).prop('disabled', true).find('i').removeClass('fa-file-excel').addClass('fa-spin fa-spinner');
        $('#start-excel, #stop-excel').prop('disabled', true);

        app.ajax(`/api/token${app.token}/montage/excel`, data, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            app.globalAlert("Файл сформирован, сейчас начнётся его скачивание", json.done);
            location.href = json.data.link;
            
            setTimeout(function() {
                $(e).prop('disabled', false).find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-excel');
                $('#start-excel, #stop-excel').prop('disabled', false);
            }, 2000);

        }, err => {
            $(e).prop('disabled', false).find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-excel');
        });

    }

    this.docx = e => {

        let data = {
            start: $('#start-excel').val(),
            stop: $('#stop-excel').val(),
        };

        $(e).prop('disabled', true).find('i').removeClass('fa-file-word').addClass('fa-spin fa-spinner');
        $('#start-excel, #stop-excel').prop('disabled', true);

        app.ajax(`/api/token${app.token}/montage/docx`, data, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            app.globalAlert("Файл сформирован, сейчас начнётся его скачивание", json.done);
            location.href = json.data.link;
            
            setTimeout(function() {
                $(e).prop('disabled', false).find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-word');
                $('#start-excel, #stop-excel').prop('disabled', false);
            }, 2000);

        }, err => {
            $(e).prop('disabled', false).find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-word');
        });

    }

    this.zip = e => {

        let data = {
            id: $(e).data('id'),
        };

        $(e).prop('disabled', true).find('i').removeClass('far fa-file-archive').addClass('fas fa-spin fa-spinner');

        app.ajax(`/api/token${app.token}/montage/zip`, data, json => {

            app.globalAlert("Файл сформирован, сейчас начнётся его скачивание", json.done);
            location.href = json.data.link;

            setTimeout(function() {
                $(e).prop('disabled', false).find('i').removeClass('fas fa-spin fa-spinner').addClass('far fa-file-archive');
            }, 2000);

        });

    }

    this.addBusData = e => {

        $(e).removeClass('fa-bus').addClass('fa-spin fa-spinner').removeAttr('onclick');

        app.modal = $('#modal-add-bus');

        let data = {
            id: $(e).data('montage'),
            newBus: true,
        };

        app.ajax(`/api/token${app.token}/montage/getOneMontage`, data, json => {

            if (json.error)
                return app.globalAlert(json.error, json.done, json.code);

            this.idMontage = data.id;

            app.modal.modal('show');
            $(e).addClass('fa-bus').removeClass('fa-spin fa-spinner').attr('onclick', 'montage.addBusData(this);');

            app.modal.find('h5.modal-title').text('Добавить машину '+json.data.montage.bus)

            app.modal.find('#client').html('<option selected value="">Для справки...</option>');
            json.data.clients.forEach(row => {
                app.modal.find('#client').append(`<option value="${row.id}">${row.name}</option>`);
            });

            app.modal.find('#garage').val(json.data.montage.bus);

            this.checkSearchedBus(json.data.busGarage);

            let input;
            json.data.montage.inputs.forEach(row => {

                input = false;

                if (row.name == "busName")
                    input = "#mark";
                else if (row.name == "vinNum")
                    input = "#vin";
                else if (row.name == "busNum")
                    input = "#number";

                if (input)
                    app.modal.find(input).val(row.value);

            });

        });

    }

    this.checkSearchedBus = data => {

        app.modal.find('form .alert').remove();

        let html = "", count = 0;
        data.forEach(row => {

            count++;

            html += `<div class="my-1">
                <span class="font-weight-bold">${count}.</span>
                <span class="ml-2">${row.mark ? row.mark : ''}${row.model ? ` ${row.model}` : ``}</span>
                ${row.name ? `<span class="font-weight-bold ml-2">${row.name}</span>` : ``}
                <span class="ml-2">${row.number ? row.number : ''}</span>
                <span class="ml-2">${row.vin ? row.vin : ''}</span>
            </div>`;

        });

        if (count)
            app.modal.find('form').prepend(`<div class="alert alert-info" style="font-size: 80%;">
                <div class="font-weight-bold">Найдены совпадения по гаражному номеру:</div>
                ${html}
            </div>`);

    }

    this.idMontage = 0;

    this.addNewBus = e => {

        $(e).prop('disabled', true);
        app.modalLoading(app.modal, 'show');

        let data = app.modal.find('form').serializeArray();

        app.ajax(`/api/token${app.token}/admin/addNewBus`, data, json => {

            $(e).prop('disabled', false);
            app.modalLoading(app.modal, 'hide');

            $('#tr-montage-'+this.idMontage).find(`i[data-montage="${this.idMontage}"]`).removeClass('text-danger');

            app.modal.modal('hide');

        }, err => {
            $(e).prop('disabled', false);
            app.modalLoading(app.modal, 'hide');
        });

    }

}
const montage = new Montage;