@extends('index')

@section('title', 'Монтаж')

@section('content')

    <h3 class="mt-4 mb-4">Монтаж</h3>

    <form id="start-montage" class="mx-auto" style="max-width: 500px;">

        <div class="input-group" id="filial-select">
            <select class="custom-select" name="filial" id="bus-filial" onchange="montage.selectedFilial(this);">
                <option selected value="0">Выберите филиал...</option>
            </select>
        </div>

        <div class="input-group mt-2 d-none" id="place-select">
		    <select class="custom-select" name="place" id="bus-place" onchange="montage.selectedPlace(this);">
                <option selected value="0">Выберите площадку...</option>
			    <option value="add">Указать площадку вручную...</option>
            </select>
	    </div>

        <div class="input-group mt-2" style="display: none;" id="input-place">
		    <input type="text" class="form-control" name="newplace" id="bus-place-edit" placeholder="Укажите наименование площадки" autocomplete="false" required>
  	    </div>

        <div class="input-group mt-2">
		    <input type="text" class="form-control" name="bus" id="bus-number" placeholder="Гаражный номер" autocomplete="false" required>
        </div>
        <small class="form-text text-muted text-left">Укажите гаражный номер машины в формате 010100</small>

        <button class="btn btn-outline-primary mt-3" type="button" id="start-button" onclick="montage.start(this);">Начать</button>

    </form>

    <table class="table table-sm mt-4">
        <thead class="thead-dark">
            <tr>
                <th scope="col">#</th>
                <th scope="col">Дата</th>
                <th scope="col">Машина</th>
                <th scope="col">Филиал</th>
                <th scope="col">Площадка</th>
                <th scope="col">Завершил</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody id="all-montages">
        </tbody>
    </table>

    <div class="text-center" id="loading-table">
        <div class="spinner-border" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

    <div class="d-flex justify-content-center align-items-center looooo" id="worktapeload">
        <div class="spinner-grow" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        $('#start-montage input, #start-montage select').on('change', function() {
            $(this).removeClass('is-invalid');
        });
        montage.getDataForStart();
    });
</script>
@endsection