@extends('index')

@section('title', 'Поиск')

@section('content')
@include('application.search')

<div class="mt-3 mx-auto content-block-width">

    <div class="nav nav-pills mx-auto justify-content-center d-none" id="v-pills-tab" role="tablist">
        <a class="nav-link py-1 px-2 active" id="v-pills-applications-tab" data-toggle="pill" href="#v-pills-applications" role="tab" aria-controls="v-pills-applications" aria-selected="true" data-id="applications">Заявки</a>
        <a class="nav-link py-1 px-2" id="v-pills-bus-tab" data-toggle="pill" href="#v-pills-bus" role="tab" aria-controls="v-pills-bus" aria-selected="false" data-id="bus">Авто</a>
        <a class="nav-link py-1 px-2" id="v-pills-device-tab" data-toggle="pill" href="#v-pills-device" role="tab" aria-controls="v-pills-device" aria-selected="false" data-id="device">Оборудование</a>
    </div>

    <div class="tab-content mt-3" id="v-pills-tabContent">
        <div class="tab-pane fade show active" id="v-pills-applications" role="tabpanel" aria-labelledby="v-pills-applications-tab">Здесь будут результаты поиска по заявкам</div>
        <div class="tab-pane fade" id="v-pills-bus" role="tabpanel" aria-labelledby="v-pills-bus-tab">Здесь будут результаты поиска по авто</div>
        <div class="tab-pane fade" id="v-pills-device" role="tabpanel" aria-labelledby="v-pills-device-tab">Здесь будут результаты поиска по оборудованию</div>
    </div>

    <div class="text-center" id="loading-data">
        <div class="spinner-grow spinner-grow-sm" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

</div>

@endsection


@section('script')
<script>
$(() => {

    app.noRedir = true;
    app.searchData();

    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        app.activeSearche = $(e.target).data('id');
    });

});
</script>
@endsection