@extends('index')

@section('title', 'Админка монтажа')

@section('content')

<h3 class="mt-4 mb-4">Монтаж</h3>

<div class="my-4 mx-auto text-center px-3" style="max-width: 500px;">
	<h5>Сформирвоать excel</h5>
	<div class="input-group mt-2">
  		<input type="date" class="form-control" value="{{ date("Y-m-01", time()-60*60*24*3) }}" id="start-excel">
  		<input type="date" class="form-control" value="{{ date("Y-m-t", time()-60*60*24*3) }}" id="stop-excel">
  		<div class="input-group-append">
			<button class="btn btn-outline-secondary" type="button" id="download-exel" onclick="montage.excel(this);" title="Скачать отчет за период в excel"><i class="fas fa-file-excel" aria-hidden="true"></i></button>
            <button class="btn btn-outline-secondary" type="button" id="download-word-acts" onclick="montage.docx(this);" title="Скачать все акты за период"><i class="fas fa-file-word" aria-hidden="true"></i></button>
  		</div>
	</div>
</div>

<table class="table table-sm mt-4 mx-auto table-adaptive" id="content-table" style="font-size: 80%; max-width: 1300px;">
    <thead class="thead-dark d-none">
        <tr>
            <th scope="col">#id</th>
            <th scope="col">Дата</th>
            <th scope="col">Машина</th>
            <th scope="col">Марка</th>
            <th scope="col">Гос. номер</th>
            <th scope="col">Филиал</th>
            <th scope="col">Площадка</th>
            <th scope="col">Завершил</th>
            <th scope="col"></th>
            <th scope="col"></th>
        </tr>
    </thead>
    <tbody id="all-montages">
    </tbody>
</table>

<div class="text-center" id="loading-table" style="display: none;">
    <div class="spinner-border" role="status">
        <span class="sr-only">Загрузка...</span>
    </div>
</div>

<div class="modal" id="modal-add-bus" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-bus-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-bus-label">Добавить машину</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form class="modal-body text-left">

                <div class="form-group mb-2">
                    <label for="client" class="mb-0">Принадлежность заказчику</label>
                    <select  class="form-control" name="client" id="client"></select>
                </div>

                <div class="form-group mb-2">
                    <label for="mark" class="mb-0">Марка</label>
                    <input type="text" class="form-control" id="mark" name="mark" placeholder="ЛиАЗ, Маз, Волжанин...">
                </div>

                <div class="form-group mb-2">
                    <label for="model" class="mb-0">Модель</label>
                    <input type="text" class="form-control" id="model" name="model" placeholder="5265, 6213...">
                </div>

                <div class="form-group mb-2">
                    <label for="modif" class="mb-0">Модификация</label>
                    <input type="text" class="form-control" id="modif" name="modif" placeholder="Описание или полная маркировка...">
                </div>

                <div class="form-group mb-2">
                    <label for="year" class="mb-0">Год выпуска</label>
                    <input type="text" class="form-control" id="year" name="year" placeholder="Год выпуска...">
                </div>

                <div class="form-group mb-2">
                    <label for="vin" class="mb-0">VIN</label>
                    <input type="text" class="form-control" id="vin" name="vin" placeholder="Введите VIN...">
                </div>

                <div class="form-group mb-0">
                    <label for="number" class="mb-0">Гос. номер</label>
                    <input type="text" class="form-control" id="number" name="number" placeholder="Укажите регистрационный номер...">
                </div>

                <input type="hidden" name="garage" id="garage" />

            </form>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="save-data" onclick="montage.addNewBus(this);">Добавить</button>
            </div>

        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        montage.allMontagesList(true);
    });
</script>
@endsection