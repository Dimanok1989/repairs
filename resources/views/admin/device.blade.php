@extends('index')

@section('title', 'Список оборудования')

@section('content')

<div>

    <h4 class="my-3">Оборудование</h4>

    <div id="list-data" class="list-group mx-auto mt-4 content-block-width"></div>

    <div class="text-center" id="loading-rows">
        <div class="spinner-border spinner-border-sm" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

    <div class="global-button-add">
        <button type="button" class="btn btn-success rounded-circle" onclick="admin.getDeviceRow(this);"><i class="fas fa-plus"></i></button>
    </div>

</div>

<div class="modal" id="modal-device" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-device-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-device-label">Оборудование</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body text-left">

                <div class="form-group">
                    <label for="name">Наименование*</label>
                    <input type="text" class="form-control" id="name" aria-describedby="deviceName"  name="name" required>
                    <small id="deviceName" class="form-text text-muted">Это наименование будет использовано в акте и в процессе приёмки</small>
                </div>

                <div class="form-group mb-0">
                    <label for="group">Группа оборудования</label>
                    <select class="form-control" id="group" name="group"></select>
                </div>

                <input type="hidden" name="id" />

            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-data" onclick="admin.saveDevice(this);">Сохранить</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        admin.getDeviceList();
    });
</script>
@endsection