@extends('index')

@section('title', 'Подвижной состав')

@section('content')

<div class="mx-auto" style="max-width: 1000px;" id="global-data">

    <div class="mx-auto my-3 d-flex flex-column flex-sm-row justify-content-between align-items-center">

        <div class="input-group m-1">
            <input type="text" class="form-control" placeholder="Поиск..." aria-label="Поиск..." id="search-text" value="{{ request()->search }}">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" onclick="admin.getSearchBus({term: $('#search-text').val()});"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <div class="input-group m-1">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-filter"></i></span>
            </div>
            <select class="custom-select" id="filter-sort" data-type="sort" onchange="admin.filterBus(this);">
                <option selected value="0">Сортировать...</option>
                <option value="date-asc">По дате добавления</option>
                <option value="date-desc">По дате добавления (убывание)</option>
                <option value="garage-asc">По гаражному номеру</option>
                <option value="garage-desc">По гаражному номеру (убывание)</option>
            </select>
        </div>

    </div>

    <table class="table table-hover table-striped mx-auto cursor-default">
        <thead>
            <tr class="d-none d-sm-table-row">
                <th scope="col" class="border-0">Номер</th>
                <th scope="col" class="border-0">Мака, модель</th>
                <th scope="col" class="border-0">Гос. номер</th>
                <th scope="col" class="border-0">VIN</th>
            </tr>
        </thead>
        <tbody id="rows-data"></tbody>
    </table>

</div>

<div class="text-center" id="dataloader">
    <div class="spinner-grow spinner-grow-sm" role="status">
        <span class="sr-only">Загрузка...</span>
    </div>
</div>

@endsection

@section('script')
<script>

$(() => {

    admin.getBusList();

    $('#search-text').autocomplete({
        source: admin.getSearchBus,
        minLength: 0,
    });

});

</script>
@endsection