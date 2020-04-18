@extends('index')

@section('content')

<div>

    <h4 class="my-3">Заказчики</h4>

    <div id="list-data" class="list-group mx-auto mt-4 content-block-width"></div>

    <div class="text-center" id="loading-rows">
        <div class="spinner-border spinner-border-sm" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

    <div class="global-button-add">
        <button type="button" class="btn btn-success rounded-circle" onclick="admin.projectData(this);"><i class="fas fa-plus"></i></button>
    </div>

</div>

<div class="modal fade" id="modal-client-add" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-point-break-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новый заказчик</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="text-left">

                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Наименование заказчика" aria-label="Наименование заказчика" name="name">
                    </div>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">{{ env('APP_URL') }}/</span>
                        </div>
                        <input type="text" class="form-control" placeholder="Логин заказчика" aria-label="Логин заказчика" name="login">
                    </div>

                    <p class="text-info mt-3 mb-0">Это первоначальные данные заказчика, все остальные данные указываются непосредственно на персональной конфигурационной странице. <strong>НЕЗАБУДЬТЕ</strong> также настроить доступ к заявкам заказчика на странице настроек группы сотрудников</p>

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
                        <button type="button" class="btn btn-primary" id="save-data" onclick="admin.projectDataSave(this);">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        admin.getProjectsList();
    });
</script>
@endsection