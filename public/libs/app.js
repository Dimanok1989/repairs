function App() {

    /** Токен пользователя для запросов */
    this.token = $('meta[name="token"]').attr('content');

    /** Данные авторизированного пользователя */
    this.user = {};

    /** Функция вызова для подгрузки данных при прокрутке страницы */
    this.funcForScroll = false;
    this.page = 0;

    this.constructor = function() {
        this.checkToken();
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

        console.log(">>", data);

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            dataType: "JSON",
            success: json => {

                console.log("<<", json);

                if (typeof callback == "function")
                    callback(json);

            },
            error: err => {

                console.log("<<", err);

                if (typeof error == "function")
                    return error(err);

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
            title += ' '+code;

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

    this.search = function() {

    }

    /** Индикация загрузки модального окна */
    this.modalLoading = function(e, type = false) {

        let t = $(e).find('.modal-header').outerHeight(),
            b = $(e).find('.modal-footer').outerHeight();

        $(e).find('.modal-loading').remove();

        if (type == "show")
            $(e).find('.modal-content').append(`<div class="modal-loading d-flex justify-content-center align-items-center" style="top: ${t}px; bottom: ${b}px;"><div class="spinner-grow text-success" role="status"><span class="sr-only">Загрузка...</span></div></div>`);

    }

    this.getQuery = function(str = "") {

        let arr = {};

        if (str == "")
            return arr;
        
        str = str.replace("?", "").split("&");
        
        $.each(str, (i,row) => {
            row = row.split("=");
            arr[row[0]] = row[1];
        });

        return arr;

    }

    this.getQueryUrl = function(arr) {

        let newArr = [];

        $.each(arr, (i,row) => {
            newArr.push(i+"="+row);
        });

        return "?" + newArr.join("&");

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

}
const app = new App;