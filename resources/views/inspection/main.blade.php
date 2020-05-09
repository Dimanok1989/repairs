@extends('index')

@section('title', 'Приёмка')

@section('content')

<div class="mt-4 mb-3 mx-auto" style="max-width: 1400px;">

    <h4>
        <span class="mr-2">Приёмка</span>
        <button type="button" class="btn btn-primary btn-sm" onclick="inspection.startData(this);"><i class="fas fa-plus-square mr-2"></i>Начать</button>
    </h4>

    <div id="list-rows"></div>

    <table class="table table-sm mt-4 table-adaptive" id="content-table">
        <thead class="thead-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">Дата</th>
                <th scope="col">Машина</th>
                <th scope="col">Заказчик</th>
                <th scope="col">ФИО</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody id="all-table-rows">
        </tbody>
    </table>

    <div class="py-3 px-2 text-center" id="loading-data">
        <div class="spinner-border ml-auto" role="status" aria-hidden="true"></div>
    </div>

</div>

<div class="modal" id="new-inspection" tabindex="-1" role="dialog" data-backdrop="static" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Начать новую приёмку</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body text-left">
                <div class="form-group">
                    <label class="mb-0" for="client-select">Выбрите заказчика</label>
                    <select class="form-control" name="client" id="client-select"></select>
                </div>
                <div class="form-group mb-0">
                    <label class="mb-0" for="number">Гаражный номер</label>
                    <input type="text" class="form-control" name="number" id="number">
                </div>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="data-save" onclick="inspection.start(this);">Начать</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        inspection.tape();
        setInterval(() => {
            inspection.checkUpdateTable();
        }, 10000);
    });
</script>
@endsection