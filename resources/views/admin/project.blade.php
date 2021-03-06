@extends('index')

@section('title', 'Заказчик ' . $data->name)

@section('content')

<div class="mx-auto content-block-width">

    <h4 class="mt-3 mb-0">Заказчик</h4>
    <h5 class="mb-3 d-flex align-items-center justify-content-center" id="name-client">
        <i class="fas fa-circle fa-xs mr-2 text-{{ $data->access ? 'success' : 'danger' }}" style="opacity: .7;"></i>
        <span>{{ $data->name }}</span>
        <i class="fas fa-cog fa-for-hover ml-2" data-id="{{ $id }}" onclick="admin.settingClient(this);"></i>
    </h5>

    <div id="table-data">

        <div class="tab-content mt-4" id="break">
            <h5>Виды неисправностей</h5>
        </div>

        <div class="tab-content mt-5" id="repair">
            <h5>Виды ремонта</h5>
        </div>

        <div class="tab-content mt-5" id="canseled">
            <h5>Причины отмены заявки</h5>
        </div>
        
        <p class="text-info">Следует помнить, что не стоит редактировать данные пункта и подпунктов, так как это может повлиять на статистику по уже выполненным заявкам и может изменить закрывающий документ, поэтому для изменения какого либо пункта можно удалить требуемую позицию и заменить её новой</p>

        <div class="text-center py-3" id="loading-rows">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="sr-only">Загрузка...</span>
            </div>
        </div>

    </div>

</div>

<div class="modal" id="modal-settings" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-point-break-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-point-break-label">Настройки</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <form class="text-left">

                    <input type="hidden" name="id" />

                    <div class="form-group">
                        <label for="name" class="mb-0">Наименование заказчика</label>
                        <input type="text" class="form-control" name="name" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                    </div>

                    <div class="custom-control custom-switch mt-3">
                        <input type="checkbox" class="custom-control-input" name="access" id="access-clietn" onchange="admin.changeDataModal(this);">
                        <label class="custom-control-label" for="access-clietn">Доступ к заявкам заказчика открыт</label>
                    </div>
                    <small class="form-text text-muted">Настройка отображения заявок заказчика, доступ к странице заведения заявок</small>

                    <div class="form-group mt-3 mb-0">
                        <label for="name" class="mb-0">Ссылка станицы подачи заявок</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="link" disabled id="copy-link-data" value="{{ env('APP_URL') }}/{{ $data->login }}" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                            {{-- <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="copy-link" onclick="app.copy(this);" data-copy="{{ env('APP_URL') }}/{{ $data->login }}" disabled><i class="fas fa-copy"></i></button>
                            </div> --}}
                        </div>                        
                    </div>

                    <hr>
                    
                    <div class="form-group mb-0">
                        <label for="bottoken" class="mb-0">Токен Telegram-бота</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-robot"></i></span>
                            </div>
                            <input type="text" class="form-control" name="bottoken" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                        </div>
                    </div>

                    <div class="form-group mt-2 mb-0">
                        <label for="telegram" class="mb-0">Идентификатор Telegram группы</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fab fa-telegram"></i></span>
                            </div>
                            <input type="text" class="form-control" name="telegram" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                        </div>                        
                        <small class="form-text text-muted">Используйте цифровой идентификатор группы (обычно большое отрицательное число -10235654232453) или логин группы @telegramgroup</small>
                    </div>

                    <hr>

                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="listpoints" id="listpoints" onchange="admin.changeDataModal(this);">
                        <label class="custom-control-label" for="listpoints">Раскрывать список пунктов</label>
                    </div>

                    <hr>

                    <div class="form-group mt-2 mb-0">
                        <label for="place" class="mb-0">Место проведения работ</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="place" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                        </div>
                        <small class="form-text text-muted">Будет использовано в акте</small>
                    </div>

                    <div class="form-group mt-2 mb-0">
                        <label for="templateNum" class="mb-0">Шаблон номера акта</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                            </div>
                            <input type="text" class="form-control" name="templateNum" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                        </div>                        
                    </div>

                    <div class="alert alert-info mt-2 mb-0 p-2" role="alert" style="font-size: 80%;">
                        <span>Если оставить поле пустым, номер в акте будет порядковым номером заявки. Используйте переменные ниже для составления шаблона:</span>
                        <div>
                            <code>${num}</code>
                            <span>порядковый номер заявки</span>
                        </div>
                        <div>
                            <code>${year}</code> или <code>${y}</code>
                            <span>год подачи заявки</span>
                        </div>
                        <div>
                            <code>${busNum}</code>
                            <span>гаражный номер машины</span>
                        </div>
                        <div>
                            <code>${clientId}</code>
                            <span>порядковый номер заказчика</span>
                        </div>
                    </div>

                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="save-data" onclick="admin.saveSettings(this);" disabled>Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modal-add-point-break" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-point-break-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-point-break-label">Пункт неисправности</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="text-left">

                    <input type="hidden" name="type" value="" />
                    <input type="hidden" name="project" value="" />
                    <input type="hidden" name="razdel" value="{{ $id }}" />

                    <div class="form-group mb-0">
                        <label for="name" class="mb-0">Наименование пункта</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="name">
                        </div>
                        <small class="form-text text-muted">Наименование пункта будет использовано при формировании закрывающих документов</small>
                    </div>

                    {{-- <p class="text-info mt-3 mb-0">Следует помнить, что не стоит редактировать данные пункта, так как это может повлиять на статистику по уже выполненным заявкам и может изменить закрывающий документ, поэтому для изменения какого либо пункта можно удалить требуемую позицию и заменить её новой</p> --}}

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-data" onclick="admin.savePointBreak(this);">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modal-add-point-repair" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-point-repair-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-point-repair-label">Пункт ремонта</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="text-left">

                    <input type="hidden" name="type" value="" />
                    <input type="hidden" name="project" value="" />
                    <input type="hidden" name="razdel" value="{{ $id }}" />

                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Наименование пункта" aria-label="Наименование пункта" name="name" />
                    </div>

                    <div id="master-select">
                        <div class="custom-control custom-switch text-left mt-3">
                            <input type="checkbox" class="custom-control-input" id="master" name="master" onchange="admin.selectMasterPointRepair(this);">
                            <label class="custom-control-label" for="master">Сделать разделом пунктов</label>
                        </div>
                        <small class="text-info">Если новый пункт сделать разделом, то для него можно будет создать свои подпункты с индивидуальными нормами, в противном случае пункт будет учитываться в отчете как обычный</small>
                    </div>

                    <div id="slave-form">

                        <div class="form-group row mt-3">
                            <label for="norma" class="col-sm-3 col-form-label text-left">Норма/час</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="norma" id="norma" step="0.1" min="0" />
                            </div>
                        </div>

                        <hr>

                        <div class="custom-control custom-switch text-left mt-3">
                            <input type="checkbox" class="custom-control-input" id="forchanged" name="forchanged" onchange="admin.selectPointRepairEnterSerials(this);">
                            <label class="custom-control-label" for="forchanged">Смена оборудования</label>
                        </div>
                        <small class="text-info">При выборе данного пункта в момент завршения заявки, нужно будет загрузить фотографии старого и ногового оборудования</small>

                        <div class="form-group mt-3 mb-0">
                            <label for="device" class="mb-0">Наименование оборудования</label>
                            <select class="form-control form-control-sm" name="device" id="device" onchange="admin.changeDataModal(this);" disabled></select>
                        </div>

                        <div class="alert alert-info mt-2 mb-0 p-2" role="alert" style="font-size: 80%;">Можно выбрать оборудование, наименование которого автоматически будет подставлено в талицу акта. Если выбрать <strong>Группу оборудований</strong>, то при завершении заявки с заменой оборудования этой группы необходимо будет выбрать соответсвеющее устройство, а при отсутствии такового, можно будет ввести наименование вручную. Ввести вручную - будет предложено указать наименование заменяемого устройства вручную</div>

                        <div class="custom-control custom-switch text-left mt-3">
                            <input type="checkbox" class="custom-control-input" id="forchangedserials" name="forchangedserials" disabled>
                            <label class="custom-control-label" for="forchangedserials">Указать серийные номера</label>
                        </div>

                        <div class="custom-control custom-switch text-left mt-3">
                            <input type="checkbox" class="custom-control-input" id="forchangedfond" name="forchangedfond">
                            <label class="custom-control-label" for="forchangedfond">Подменный фонд</label>
                        </div>
                        <small class="text-info">Если будет выбан данный пункт, то после завершения, заявка поменяет свой статус на подменный фонд</small>

                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-data" onclick="admin.savePointRepair(this);">Сохранить</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    $(function() {
        admin.getProjectsData({{ $id }});
    });    
</script>
@endsection