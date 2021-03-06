function App() {

    /** Токен пользователя для запросов */
    this.token = $('meta[name="token"]').attr('content');

    /** Объект открытого модального окна */
    this.modal;

    /** Данные авторизированного пользователя */
    this.user = {};

    /** Функция вызова для подгрузки данных при прокрутке страницы */
    this.funcForScroll = false; // Функция, отвечающая за подгрузку данных
    this.progress = false; // Активный процесс подгрузки данных
    this.progressEnd = false; // Окончание данных дял подгрузки
    this.page = 0; // Страница вывода подгрузки

    this.constructor = function() {
        this.checkToken();
    }

    /**
     * Метод подгрузки страницы при прокрутке
     */
    this.scrollDoit = func => {

        if (this.funcForScroll)
            return this;

        this.funcForScroll = func;

        $(window).scroll(() => {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200 && !this.progress && !this.progressEnd && typeof this.funcForScroll == "function") {
                this.funcForScroll();
            }
        });

        return this;

    }

    /**
     * Метод отправки запроса
     * String url Наименование метода обработки запроса
     * Object data Объект данных
     * Bool token Идентификатор вставки токена в запрос
     * Function callback Функция, срабатывающая после успешной обработки запроса
     * Function error Функция, срабатывающая при возникновении ошибки в запросе
     */
    this.ajax = function(url = false, data = false, callback = false, error = false) {

        if (typeof data == "function") {
            error = callback;
            callback = data;
            data = {};
        }
         
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            }
        });

        console.log(">>", url, data);

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            dataType: "JSON",
            success: json => {

                console.log("<<", json);

                if (json.code == 9000)
                    location.href = "/logout";

                if (typeof callback == "function")
                    callback(json);

            },
            error: err => {

                console.log("<<", err);

                if (typeof error == "function")
                    error(err);

                let jsonerror = typeof err.responseJSON == "object" ? err.responseJSON : false;

                app.globalAlert("Произошла неизвестная ошибка сервера", "error", err.status, false, jsonerror);
                    
            },
        });

    }

    this.file = function(url = false, data = false, done = false, before = false, progress = false, error = false, report = false) {

        if (!url || !data)
            return false;

            $.ajax({ 
            type: 'POST',
            url: url,
            data: data,
            processData: false,
            contentType: false,
            dataType: "JSON",
            beforeSend: function () {

                if (typeof before == "function")
                    before();

                // $(el).parent().find('.loading-in-body').addClass('d-flex');
                // $(el).parent().find('.progress-bar').css('width', 0 + '%').attr('aria-valuenow', 0);
            },
            success: (json) => {

                if (report)
                    console.log(json);
                    
                if (typeof done == "function")
                    done(json);
    
            },
            xhr: function() {
                var xhr = $.ajaxSettings.xhr();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {

                        var percentComplete = Math.ceil(evt.loaded / evt.total * 100);
                            
                        if (typeof progress == "function")
                            progress(percentComplete);
                            
                    }
                }, false);
                return xhr;
            },
            error: err => {

                if (report)
                    console.log(err);

                if (typeof error == "function")
                    error(err);

            }
        });

    }

    /** Удаление файла */
    this.deleteFile = (data = {}, callback = false) => {

        this.ajax("/api/deleteFile", data, json => {

            if (typeof callback == "function")
                callback(json);

        });

    }

    /** Применение ошибочной валидации к формам */
    this.formValidErrors = (e, inputs) => {
        $.each(inputs, (i,row) => {
            $(e).find(`[name="${row}"]`).addClass('is-invalid');
        });
    }
    /** Применение положительной валидации к формам */
    this.formValidOk = (e, inputs) => {
        $.each(inputs, (i,row) => {
            $(e).find(`[name="${row}"]`).addClass('is-valid');
        });
    }
    /** Удаление валидации */
    this.formValidRemove = e => {
        $(e).find('.is-invalid, .is-valid').each(function() {
            $(this).removeClass('is-invalid is-valid');
        });
    }
    this.changeForm = e => {
        let valid = $(e).data('valid');
        if (valid == 1) {
            this.formValidRemove($(e));
            $(e).data('valid', 0);
        }
    }

    this.login = function(e) {

        $(e).prop('disabled', true)
        .find('i').removeClass('fa-sign-in-alt').addClass('fa-spinner fa-spin');

        let data = $('#login-form').serializeArray();

        this.ajax("/api/login", data, json => {

            $(e).prop('disabled', false)
            .find('i').removeClass('fa-spinner fa-spin').addClass('fa-sign-in-alt');

            if (json.error) {
                $('#login-form').addClass('was-validated');
                return this.globalAlert(json.error, "error");
            }

            $.cookie('token', json.data.token, {expires: 7, path: '/' });
            location.reload();

        });

    }

    this.logout = function() {

        var data = {
            token: $('meta[name="token"]').attr('content'),
        }

        this.ajax("/api/logout", data, json => {

            $.removeCookie('token');

            if (window.location.pathname != "/")
                window.location.href = "/";
            else
                location.reload();

        });

    }

    this.checkToken = function() {

        var data = {
            token: $('meta[name="token"]').attr('content'),
        }

        // console.log(window.history);
        // history.pushState({param: 'Value'}, 'dfgdfg', 'myurl.html');
        // console.log(window.history);

        if (data.token != 0) {
            this.ajax("/api/checkToken", data, json => {

                if (json.done == "error")
                    app.logout();
                else
                    app.user = json.data;

                console.log(json);

            });
        }

    }

    this.globalAlert = function(text = "Простое уведомление", type = "error", code = false, close = false, jsonerror = false) {

        var typeClass = "alert-info",
            title = "Уведомление";

        switch (type) {
            case "error":
                typeClass = "alert-danger";
                title = "Ошибка";
                break;
            case "success":
                typeClass = "alert-success";
                title = "Выполнено";
                break;
            case "warning":
                typeClass = "alert-warning";
                title = "Внимание";
                break;
        }

        if (code)
            title += ' <span class="opacity-30">'+code+'</span>';

        $('#global-alert').remove();

        $('body').append(`<div class="mx-auto" id="global-alert" style="max-width: 700px; display: none;">
            <div class="alert ${typeClass} alert-dismissible fade show mt-3 mx-2 shadow" role="alert">
                <button type="button" class="close" aria-label="Close" onclick="$('#global-alert').remove();">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="alert-heading">${title}</h4>
                <p class="m-0">${text}</p>
                ${code ? `` : ``}
                ${typeof jsonerror == "object" ? `<hr><p class="mb-1 text-break"><b>${jsonerror.exception}</b> ${jsonerror.message}</p><p class="mb-1 text-break"><b>File:</b> ${jsonerror.file}</p><p class="my-0 text-break"><b>Line: ${jsonerror.line}</b></p>` : ``}
            </div>
        </div>`);

        $('#global-alert').fadeIn(200);

        if (close)
            setTimeout(() => {
                $('#global-alert').remove();
            }, close);

    }

    this.openMenu = function() {

        $('#nav-bg').show();
        $('nav').css('left', '0px');

    }
    this.closeMenu = function() {

        $('#nav-bg').fadeOut(250);
        $('nav').css('left', '-250px');

    }

    /** Индикация загрузки модального окна */
    this.modalLoading = function(e, type = false) {

        let t = $(e).find('.modal-header').outerHeight(),
            b = $(e).find('.modal-footer').outerHeight();

        $(e).find('.modal-loading').remove();

        if (type == "show")
            $(e).find('.modal-content').append(`<div class="modal-loading d-flex justify-content-center align-items-center" style="top: ${t}px; bottom: ${b}px;"><div class="spinner-grow text-success" role="status"><span class="sr-only">Загрузка...</span></div></div>`);

    }

    this.getQuery = (str = "") => {

        let arr = {};

        if (str == "")
            str = location.search;

        if (str == "")
            return arr;
        
        str = str.replace("?", "").split("&");
        
        $.each(str, (i,row) => {
            row = row.split("=");
            arr[row[0]] = row[1];
        });

        return arr;

    }

    this.getQueryUrl = arr => {

        let newArr = [];

        $.each(arr, (i,row) => {
            newArr.push((typeof row != "undefined") ? i+"="+row : i);
        });

        let search = newArr.join("&");

        if (search == "")
            return "";

        return "?" + search;

    }

    this.copy = e => {
        
        var $temp = $('<input>'),
            val = $(e).data('copy');

        $("body").append($temp);
        $temp.val(val).select();
        try { 
            document.execCommand('copy'); 
          } catch(err) { 
            console.log('Can`t copy, boss'); 
          } 
        $temp.remove();

        $(e).css({opacity: .3})
        .animate({opacity: 1});

        console.log("Скопирвоано:", val);

    }

    /** Отображение/скрытие анимации загрузки модального окна */
    this.loading = (e, hide = false) => {

        let top = $(e).find('.modal-header').outerHeight(),
            bottom = $(e).find('.modal-footer').outerHeight(),
            loading = $(e).find('.modal-loading').length ? true : false;

        hide = hide ? hide : 'hide';
        
        if (hide == "hide")
            $(e).find('.modal-loading').remove();
        else if (!loading && hide == "show")
            $(e).find('.modal-content').append(`<div class="modal-loading position-absolute d-flex justify-content-center align-items-center border-0 w-100" style="background: #ffffffc4; top: ${top}px; bottom: ${bottom}px;">
                <div class="spinner-grow text-dark" role="status">
                    <span class="sr-only">Загрузка...</span>
                </div>
            </div>`);
            
        return hide;

    }

    /** Список файлов для вывода на весь экран */
    this.fileList = [];
    /** Вывод изображений на весь экран */
    this.showImg = e => {

        let id = $(e).data('id');

        console.log(id, this.fileList[id]);

        if (this.fileList[id]) {
            $('#content').append(`<div class="img-content" id="img-content">
                <div class="d-flex justify-content-center align-items-center image-loading">
                    <div class="spinner-grow text-light" role="status">
                        <span class="sr-only text-light">Загрузка...</span>
                    </div>
                </div>
                <img class="d-none" src="${this.fileList[id].link}" onload="$(this).removeClass('d-none');">
                <button type="button" class="btn btn-light rounded-circle shadow" onclick="$('#img-content').remove();"><i class="fa fa-times" aria-hidden="true"></i></button>

                <div class="back d-flex justify-content-start align-items-center hover-link" data-id="${id}" data-step="back" onclick="app.nextPhoto(this);"><i class="fas fa-chevron-left text-light fa-2x"></i></div>
                <div class="next d-flex justify-content-end align-items-center hover-link" data-id="${id}" data-step="next" onclick="app.nextPhoto(this);"><i class="fas fa-chevron-right text-light fa-2x"></i></div>                

            </div>`);
        }

    }

    this.nextPhoto = e => {

        $('#img-content img').remove();

        let id = $(e).data('id'),
            step = $(e).data('step'),
            first = false,
            last = false,
            next = false,
            back = false,
            newid = false;

        $.each(this.fileList, (i,row) => {

            if (!first)
                first = i;

            last = i;

            if (i != id && i < id)
                back = i;

            if (i != id && i > id && next === false)
                next = i;

        });

        if (step == "next" && next)
            newid = next;
        else if (step == "next" && next === false)
            newid = first;
        else if (step == "back" && back)
            newid = back;
        else if (step == "back" && back === false)
            newid = last;

        let img = `<img class="d-none" src="${this.fileList[newid].link}" onload="$(this).removeClass('d-none');">`;

        $('#img-content').append(img);
        $('#img-content .next, #img-content .back').data('id', newid);

    }

    /** Начало поиска */
    this.noRedir = false;
    this.searchStart = (button = false) => {

        let text = $('#search-start').val(),
            query = this.getQuery();

        if (text != "")
            query.text = text;

        if (text == "")
            delete query.text;

        let search = this.getQueryUrl(query);

        if (event.keyCode == 13)
            button = true;
        
        if (button && !this.noRedir)
            return window.location.href = `/search${search}`;

        if (button) {

            this.page = 0;
            window.history.pushState(null, null, `/search${search}`); 

            $('#v-pills-tab a.nav-link').each((i,row) => {

                let a = $(row).data('id');
                this.endSearchData[a] = false;

            });

            return this.searchData();

        }

    }

    /** Главный поиск по заявкам */
    this.endSearchData = {
        applications: false,
        bus: false,
        device: false,
    };
    this.activeSearche = "applications";
    this.searchData = () => {

        if (this.endSearchData[this.activeSearche] === true)
            return this;

        let data = this.getQuery();
        data.page = this.page;

        if (!data.text || data.text == "" || data.text == "0")
            return this.globalAlert("Задан пустой запрос", "error");

        $('#loading-data').removeClass('d-none');
        this.progress = true;

        this.ajax(`/api/token${this.token}/search`, data, json => {

            $('#loading-data').addClass('d-none');
            this.searchText = json.data.text;
  
            this.scrollDoit(this.searchData);
            this.searchDoneApplications(json.data.applications)

            this.page++;
            this.progress = false;

            if (this.endSearchData.applications && this.endSearchData.bus && this.endSearchData.device)
                this.progressEnd = true;

        });

    }
    this.searchText = "";
    this.replaceQuery = text => {
        let searched = String(text).replace(this.searchText, '<mark class="px-0 font-weight-bold bg-warning">'+this.searchText+'</mark>');
        return searched;
    }
    /** Вывод найденных заявок */
    this.searchDoneApplications = rows => {

        if (this.endSearchData.applications)
            return this;

        let html = "",
            count = 0;

        $.each(rows, (i,row) => {

            let serials = '';

            count++;

            $.each(row.serialsData, (key,serial) => {
                serials += `<div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="fas fa-barcode"></i>
                        <div class="mx-2">${this.replaceQuery(serial.serialOld)}</div>
                        <i class="fas fa-long-arrow-alt-right"></i>
                        <div class="ml-2">${this.replaceQuery(serial.serialNew)}</div>
                    </div>
                    <!-- <div class="mr-2 text-muted">${serial.dateAdd}</div> -->
                </div>`;
            });

            html += `<div class="card my-2 text-left">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${this.replaceQuery(row.bus)} ${row.clientName}</strong>
                        <small class="opacity-80">${row.dateAdd}</small>
                    </div>
                    <div class="d-flex justify-content-start align-items-center">
                        <a href="${row.appLink}" class="mr-3" target="_blank">Заявка #${this.replaceQuery(row.ida)}</a>
                        ${application.getHtmlStatusApplication(row)}
                    </div>                    
                    <p class="mt-0 mb-1 font-weight-light">${row.breaksListText}</p>
                    ${row.comment ? `<p class="mb-1"><i class="fas fa-quote-left opacity-50 mr-1"></i>${this.replaceQuery(row.comment)}</p>` : ''}
                    ${application.getHtmlBottomIcons(row)}
                    ${serials != "" ? `<div class="mt-2"><div>Замена оборудования:</div>${serials}</div>` : ''}
                </div>
            </div>`;

            if (row.fulldata)
                this.endSearchData.applications = true;

        });

        if (html == "" && this.page == 0)
            html = '<p class="lead">По Вашему запросу заявок не найдено</p>';

        if ((count == 0 && this.page > 0) || this.endSearchData.applications)
            html += '<small class="my-2 opacity-50 text-center">Это все данные</small>';

        if (count == 0)
            this.endSearchData.applications = true;

        if (this.page > 0)
            $('#v-pills-applications').append(html);
        else
            $('#v-pills-applications').html(html);

        return this;

    }

    this.chartData = [];
    this.chartStep = 1;

    this.chartMontageChange = e => {

        this.chartMontage(e);

    }

    this.chartMontage = (e = false) => {

        this.chartData = [];

        if (e) {
            $(e).removeAttr('onclick');
            let step = +$(e).data('step');
            this.chartStep = this.chartStep + step;
        }

        let data = {
            offset: this.chartStep,
        }

        $('#chart_montage').html(`<div class="d-flex justify-content-center align-items-center looooo">
            <div class="spinner-border" role="status"></div>
        </div>`);

        app.ajax(`/api/token${app.token}/montage/chartMontage`, data, json => {

            this.chartStep = json.data.offset;

            if (e)
                $(e).attr('onclick', 'app.chartMontageChange(this);');

            if (!json.data.count) {
                return $('#chart_montage').html(`<div class="d-flex justify-content-center align-items-center looooo">
                    <div class="opacity-40">
                        <div class="font-weight-bold">${json.data.period}</div>
                        <div>Данных нет</div>
                    </div>
                </div>`);
            }

            google.charts.load('current', {packages: ['bar']});
            google.charts.setOnLoadCallback(this.drawChartMontage);

            this.chartData.push(['', 'За день', 'Ваш монтаж']);

            json.data.rows.forEach(row => {
                this.chartData.push(row);
            });

        });

    }

    this.drawChartMontage = () => {

        var data = google.visualization.arrayToDataTable(this.chartData);
        var options = {
            chart: {
              // title: 'Company Performance',
              // subtitle: 'Sales, Expenses, and Profit: 2014-2017',
            },
            legend: {
                position: 'none',
            }
        };
  
        var chart = new google.charts.Bar(document.getElementById('chart_montage'));
        chart.draw(data, google.charts.Bar.convertOptions(options));

    }

}
const app = new App;