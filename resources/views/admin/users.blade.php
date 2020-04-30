@extends('index')

@section('title', 'Сотрудники')

@section('content')

<div class="mx-auto content-block-width" id="sub-content">

    <h4 class="my-3">Сотрудники</h4>

    <div class="input-group">
        <input type="search" class="form-control" id="search-start" placeholder="Поиск..." aria-label="Поиск...">
        <div class="input-group-append">
            <button class="btn btn-outline-secondary" type="button" onclick="app.getUsersList();"><i class="fas fa-search"></i></button>
        </div>
    </div>

    <ul id="list-users" class="list-group mt-4"></ul>

    <div class="text-center" id="loading-rows">
        <div class="spinner-border spinner-border-sm" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

    <div class="global-button-add">
        <button type="button" class="btn btn-success rounded-circle" onclick="admin.userData(this);"><i class="fas fa-plus"></i></button>
    </div>
    
</div>

<div class="modal" id="modal-user" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                    
                <form class="text-left">

                    <div class="form-group row mb-3">
                        <label for="firstname" class="col-sm-3 col-form-label">Фамилия*</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" placeholder="Укажите фамилию" aria-label="Укажите фамилию" name="firstname" id="firstname" required />
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                        <label for="lastname" class="col-sm-3 col-form-label">Имя*</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" placeholder="Укажите имя" aria-label="Укажите имя" name="lastname" id="lastname" required />
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                        <label for="fathername" class="col-sm-3 col-form-label">Отчество</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" placeholder="Укажите отчество" aria-label="Укажите отчество" name="fathername" id="fathername" />
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="login" class="col-sm-3 col-form-label">Логин*</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" placeholder="Укажите логин" aria-label="Укажите логин" name="login" id="login" required />
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label">Телефон</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" placeholder="Укажите номер телефона" aria-label="Укажите номер телефона" name="phone" id="phone" />
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="group" class="col-sm-3 col-form-label">Группа*</label>
                        <div class="col-sm-9">
                            <select class="form-control" name="group" id="group" required>
                                <option selected disabled value="0">Выберите группу...</option>
                            </select>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100 justify-content-between">
                    <div>
                        <div class="spinner-grow" role="status" id="loading-modal">
                            <span class="sr-only">Загразка...</span>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="save-user-data" onclick="admin.saveUser(this);">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modal-user-access" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">                    
                <form class="text-left"></form>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100 justify-content-between">
                    <div>
                        <div class="spinner-grow" role="status" id="loading-modal">
                            <span class="sr-only">Загрузка...</span>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="button" class="btn btn-primary" id="save-data" onclick="admin.saveUserAccess(this);">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modal-user-reset-pass" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-title">Сброс пароля</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">                    
                <p class="mb-1" id="fio-user">&nbsp;</p>
                <div class="d-flex justify-content-between px-4">
                    <div class="font-weight-bold">Пароль</div>
                    <div class="text-muted" id="pass-user">******</div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100 justify-content-between">
                    <div>
                        <div class="spinner-grow" role="status" id="loading-modal">
                            <span class="sr-only">Загрузка...</span>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="save-data" onclick="admin.passResetDone(this);" disabled>Сбросить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>

$(() => {
    admin.getUsersList();

    $('#search-start').autocomplete({
        source: admin.getSearchUsersList,
        minLength: 0,
    });

});

</script>
@endsection