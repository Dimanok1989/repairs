@extends('index')

@section('title', 'Новая заявка')

@section('content')

<div class="text-center pt-4">

    <h4 class="mb-0">{{ $data->name }}</h4>
    <p class="mb-5">Новая заявка</p>

    <div id="select-project" class="w-100 mx-auto {{ $count == 1 ? 'd-none' : '' }}" style="max-width: 500px;">

        @if ($count)
        <p class="font-weight-bold">Выберите проект</p>
        @endif

        @forelse($projects as $key => $row)
            <button type="button" class="btn btn-primary btn-lg btn-block my-3" onclick="application.selectedProject(this);" data-project="{{ $key }}">{{ $row }}</button>
        @empty
            <div class="alert alert-warning text-left" role="alert">
                <h4 class="alert-heading">Внимание!</h4>
                <p class="mb-0">К сожалению завести новую заявку не получится, так как не произведена настройка проектов и вариантов возможных неисправностей</p>
                <hr class="my-2">
                <small class="mb-0">Обратитесь к администрации сайта для исправления данной ситуации</small>
            </div>
        @endforelse

    </div>

    <form class="{{ $count == 1 ? '' : 'd-none' }} mx-auto" id="form-break" style="max-width: 500px;" data-valid="0" onchange="app.changeForm(this);" enctype="multipart/form-data">

        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-bus"></i></span>
            </div>
            <input type="number" class="form-control" name="number" placeholder="Гаражный номер" aria-label="Гаражный номер" onchange="application.checkNumber(this);">
        </div>

        <p class="font-weight-bold mb-2">Выберите неисправность</p>

        @foreach($projects as $project => $name)

            <div id="selected-project-{{ $project }}" class="w-100 mx-auto text-left {{ $count == 1 ? '' : 'd-none' }} px-2">

            @php $nopoint = false; @endphp

            @forelse($data->break->$project as $row)                
                @if ($row->del == 0)

                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input" name="break[]" value="{{ $row->id }}" id="break-checkbox-{{ $row->id }}">
                        <label class="custom-control-label" for="break-checkbox-{{ $row->id }}">{{ $row->name }}</label>
                    </div>
                    
                    @php $nopoint = true; @endphp
                @endif
            @empty
                <div class="text-muted">Пукнты выбора неисправностей по проекту не настроены</div>
            @endforelse

            @if (!$nopoint) <div class="text-muted">Пукнты выбора неисправностей по проекту не настроены</div> @endif

            </div>

        @endforeach

        <input type="hidden" name="project" value="{{ $count == 1 ? $projectskey : '0' }}"/>
        <input type="hidden" name="client" value="{{ $data->id }}" />

        <button type="button" class="btn btn-secondary btn-sm btn-block mt-4 position-relative" onclick="$('#imagesform').trigger('click');" id="imagesformbutton">
            <span><i class="far fa-image mr-2"></i>Добавить фото</span>
            <div class="progress position-absolute d-none" style="top: 5px; left: 5px; bottom: 5px; right: 5px; height: auto;" id="imagesformprogress">
                <div class="progress-bar progress-bar-striped bg-dark progress-bar-animated" role="progressbar" style="width: 0;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </button>

        <p class="font-weight-bold mt-3 mb-2">Комментарий к заявке</p>
        <div class="input-group mb-4">
            <textarea class="form-control" name="comment" rows="5"></textarea>
        </div>

        @if ($__user)
            <div class="custom-control custom-switch mx-2 mt-2 mb-3 text-left">
                <input type="checkbox" class="custom-control-input" name="priority" id="priority-checkbox">
                <label class="custom-control-label" for="priority-checkbox">Высокий приоритет</label>
            </div>
        @endif

        <div id="images-data" class="row row-cols-1 row-cols-md-3 text-center"></div>

        <button type="button" class="btn btn-outline-secondary mb-3" onclick="application.addNewApplication(this);" id="button-add-application">Добавить</button>

        <input type="file" class="d-none" id="imagesform" accept="image/*" multiple="true" onchange="application.uploadFiles(this);" />

    </form>

</div>

<div class="d-none justify-content-center align-items-center position-fixed text-center" style="top: 0px; left: 0; right: 0; bottom: 0px; background: #ffffffc4;" id="loading-add-application">
    <div class="spinner-grow text-success" role="status" style="width: 3rem; height: 3rem;">
        <span class="sr-only">Загрузка...</span>
    </div>
</div>

@endsection

@section('script')

<script src="/libs/app-application.js?{{ config('app.version') }}"></script>
<script>
    $(function() {
        // let top = $('#head').outerHeight();
        // $('#loading-add-application').css('top', top+'px');
    });    
</script>

@endsection