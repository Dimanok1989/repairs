@extends('index')

@section('content')

<div style="max-width: 800px;" class="mx-auto">

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
                        <label for="name">Наименование заказчика</label>
                        <input type="text" class="form-control" name="name" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                    </div>

                    <div class="custom-control custom-switch mt-3">
                        <input type="checkbox" class="custom-control-input" name="access" id="access-clietn" onchange="admin.changeDataModal(this);">
                        <label class="custom-control-label" for="access-clietn">Доступ к заявкам заказчика открыт</label>
                    </div>
                    <small class="form-text text-muted">Настройка отображения заявок заказчика, доступ к странице заведения заявок</small>

                    <div class="form-group mt-3">
                        <label for="name">Ссылка станицы подачи заявок</label>

                        <div class="input-group">
                            <input type="text" class="form-control" name="link" disabled id="copy-link-data" value="{{ env('APP_URL') }}/{{ $data->login }}" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                            {{-- <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="copy-link" onclick="app.copy(this);" data-copy="{{ env('APP_URL') }}/{{ $data->login }}" disabled><i class="fas fa-copy"></i></button>
                            </div> --}}
                        </div>                        
                    </div>
                    
                    <div class="form-group mt-3">
                        <label for="name">Идентификатор Telegram</label>

                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="basic-addon1"><i class="fab fa-telegram"></i></span>
                            </div>
                            <input type="text" class="form-control" name="telegram" onchange="admin.changeDataModal(this);" onkeyup="admin.changeDataModal(this);">
                        </div>                        
                        <small class="form-text text-muted">Используйте цифровой идентификатор группы (обычно большое отрицательное число -10235654232453) или логин группы @telegramgroup</small>
                    </div>

                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" name="listpoints" id="listpoints" onchange="admin.changeDataModal(this);">
                        <label class="custom-control-label" for="listpoints">Раскрывать список пунктов</label>
                    </div>

                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="save-data" onclick="admin.saveSettings(this);" disabled>Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-add-point-break" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-point-break-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-point-break-label">Новый пункт неисправности</h5>
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
                        <input type="text" class="form-control" placeholder="Наименование пункта" aria-label="Наименование пункта" name="name">
                    </div>
                    <small class="text-info">Наименование пункта будет использовано при формировании закрывающих документов</small>

                    <p class="text-info mt-3 mb-0">Следует помнить, что не стоит редактировать данные пункта, так как это может повлиять на статистику по уже выполненным заявкам и может изменить закрывающий документ, поэтому для изменения какого либо пункта можно удалить требуемую позицию и заменить её новой</p>

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
                        <button type="button" class="btn btn-primary" id="save-data" onclick="admin.savePointBreak(this);">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-add-point-repair" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-add-point-repair-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-point-repair-label">Новый пункт ремонта</h5>
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
                    {{-- <small class="text-info">Наименование пункта будет использовано при формировании закрывающих документов</small> --}}

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

                        <div class="custom-control custom-switch text-left mt-3">
                            <input type="checkbox" class="custom-control-input" id="forchanged" name="forchanged">
                            <label class="custom-control-label" for="forchanged">Смена оборудования</label>
                        </div>
                        <small class="text-info">При выборе данного пункта в момент завршения заявки, нужно будет загрузить фотографии старого и ногового оборудования</small>

                        <div class="custom-control custom-switch text-left mt-3">
                            <input type="checkbox" class="custom-control-input" id="forchangedfond" name="forchangedfond">
                            <label class="custom-control-label" for="forchangedfond">Подменный фонд</label>
                        </div>
                        <small class="text-info">Если будет выбан данный пункт, то заявка поменяет свой статус на подменный фонд</small>

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
                        <button type="button" class="btn btn-primary" id="save-data" onclick="admin.savePointRepair(this);">Сохранить</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')

<script src="/libs/app-admin.js?{{ config('app.version') }}"></script>
<script>
    $(function() {
        admin.getProjectsData({{ $id }});
    });    
</script>

@endsection