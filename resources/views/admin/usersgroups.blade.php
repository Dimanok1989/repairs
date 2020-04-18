@extends('index')

@section('content')

<div>

    <h4 class="my-3">Группы пользователей</h4>

    <ul id="list-groups" class="list-group mx-auto mt-4 content-block-width"></ul>

    <div class="text-center" id="loading-rows">
        <div class="spinner-border spinner-border-sm" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

    <div class="global-button-add">
        <button type="button" class="btn btn-success rounded-circle" onclick="admin.usersGroupData(this);"><i class="fas fa-plus"></i></button>
    </div>

    <div class="modal fade" id="modal-group" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-title" aria-hidden="true">

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
                            <label for="namegroup" class="col-sm-3 col-form-label">Наименование*</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" placeholder="Укажите наименование..." aria-label="Укажите наименование..." name="namegroup" id="namegroup" required />
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="descriptgroup" class="col-sm-3 col-form-label">Описание</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" placeholder="Краткое описание группы..." aria-label="Краткое описание группы..." name="descriptgroup" id="descriptgroup" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="colorgroup" class="col-sm-3 col-form-label">Цвет</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="colorgroup" id="colorgroup" required>
                                    <option selected value="">Выберите цвет...</option>
                                    <option value="primary" class="text-primary font-weight-bold">Синий</option>
                                    <option value="success" class="text-success font-weight-bold">Зелёный</option>
                                    <option value="danger" class="text-danger font-weight-bold">Красный</option>
                                    <option value="warning" class="text-warning font-weight-bold">Желтый</option>
                                    <option value="info" class="text-info font-weight-bold">Бирюзовый</option>
                                </select>
                            </div>
                            <small class="form-text text-muted px-4">Можно присвоить цвет, чтобы различать сотрудников в общем списке, относящихся к данной группе</small>
                        </div>

                    </form>

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
                            <button type="button" class="btn btn-primary" id="save-group-data" onclick="admin.saveGroup(this);">Сохранить</button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div class="modal fade" id="modal-group-access" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-title" aria-hidden="true">

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

                        

                    </form>

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
                            <button type="button" class="btn btn-primary" id="save-group-access-data" onclick="admin.saveGroupAccess(this);">Сохранить</button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@section('script')
<script>admin.getUsersGroupsList();</script>
@endsection