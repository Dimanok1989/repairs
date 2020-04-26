@extends('index')

@section('title', 'Монтаж')

@section('content')

    <h3 class="mt-4 mb-0">Монтаж</h3>
    <p class="font-weight-bold"><i class="fas fa-bus mr-2"></i>{{ $montage->bus }}</p>

    <form class="mx-auto position-relative" style="max-width: 500px;" id="data-montage">
        
        <div id="search-users-block">
            <div class="font-weight-bold mb-2">Добавьте коллегу</div>
            <div class="input-group flex-nowrap px-2" id="search-collegue-block" data-toggle="dropdown" aria-expanded="true">
                <input type="text" class="form-control ui-autocomplete-input" placeholder="Поиск коллеги..." aria-label="Поиск коллеги..." aria-describedby="addon-wrapping" id="search-collegue" autocomplete="off">
            </div>
            <div class="dropdown-menu mt-1 w-100 shadow" id="search-result">
                <p class="text-muted mb-0 px-3">Начните ввод ФИО или логин коллеги...</p>
            </div>

            <div id="users-selected" class="px-2"></div>
        </div>

        <hr>

        <div class="font-weight-bold mb-2" id="sub-title">Заполните данные и загрузите фотографии</div>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-act">
			<p class="m-0 pt-1" id="link-auto-act">Фото акта</p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="act">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-act"></div>

        <hr>

        <div class="input-group input-group-sm px-2">
 			<input type="text" class="form-control for-changed" placeholder="Серийный номер sim-карты*" name="iccid" id="iccid" required onchange="montage.changeInput(this);">
		</div>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-sim">
			<p class="m-0 pt-1">Фото сим-карты<b class="text-danger">*</b></p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="sim">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-sim"></div>

        <hr>

        <div class="input-group input-group-sm mt-3 px-2">
			<select class="custom-select for-changed" name="busName" id="busName" onchange="montage.changeInput(this); montage.selectBusName(this);">
                <option selected value="0">Марка и модель...</option>
                <option value="add" class="font-weight-bold">Указать вручную...</option>
                <option value="Неизвестная модель">Неизвестная модель</option>
            </select>
		</div>

        <div class="input-group input-group-sm mt-2 px-2 d-none" id="edit-busName">
			<input type="text" class="form-control for-changed" placeholder="Укажите марку и модель авто..." name="busNameEdit" onchange="montage.changeInput(this);">
		</div>

        <div class="input-group input-group-sm mt-2 px-2">
			<input type="text" class="form-control for-changed" placeholder="Гос. номер" name="busNum" id="busNum" onchange="montage.changeInput(this);">					
		</div>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-bus">
			<p class="m-0 pt-1">Фото передка машины<b class="text-danger">*</b></p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="bus">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-bus"></div>

        <hr>

        <div class="input-group input-group-sm px-2">
			<div class="input-group-prepend">
				<span class="input-group-text">WM19120177S</span>
			</div>
 			<input type="text" class="form-control for-changed" placeholder="Серийный номер*" name="serialNum" onchange="montage.changeInput(this);">
		</div>
        <div class="px-2">
            <small class="form-text text-muted text-left mt-0">Укажите только <strong>последние 4 цифры номера</strong></small>
        </div>

        <div class="input-group input-group-sm mt-2 px-2">
 			<input type="text" class="form-control for-changed" placeholder="MAC-адрес" name="macAddr" onchange="montage.changeInput(this);">
		</div>
        <div class="px-2">
            <small class="form-text text-muted text-left mt-0">При отсутствии данных, просто укажите <strong>нет</strong> в соответствующем поле</small>
        </div>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-serialn">
			<p class="m-0 pt-1">Фото серийного номера и MAC-адреса<b class="text-danger">*</b></p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="serialn">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-serialn"></div>

        <hr>

        <div class="input-group input-group-sm mt-2 px-2">
 			<input type="text" class="form-control for-changed" placeholder="VIN" name="vinNum" onchange="montage.changeInput(this);">
		</div>
        <div class="px-2">
            <small class="form-text text-muted text-left mt-0">Если табличка с номером отсутствует, то просто укажите <strong>нет</strong></small>
        </div>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-vin">
			<p class="m-0 pt-1">Фото таблички с VIN<b class="text-danger">*</b></p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="vin">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-vin"></div>

        <hr>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-cam">
			<p class="m-0 pt-1">Фото камеры и монитора<b class="text-danger">*</b></p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="cam">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-cam"></div>

        <hr>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-electr">
			<p class="m-0 pt-1">Фото электрощитка<b class="text-danger">*</b></p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="electr">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-electr"></div>

        <hr>

        <div class="d-flex justify-content-between align-self-center position-relative my-3 px-2" id="cont-file-indic">
			<p class="m-0 pt-1">Фото индикации работающего устройства<b class="text-danger">*</b></p>
			<button type="button" class="btn btn-primary btn-sm btn-add-file" onclick="montage.openAddFile(this);" data-content="indic">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
		</div>

        <div id="files-indic"></div>

        <input type="hidden" name="id" value="{{ $montage->id }}">

        <hr>

        @if ($montage->completed === NULL)
        <div id="completed-button">
            <button type="button" class="btn btn-success" onclick="montage.doneMontage(this);">
                <i class="fas fa-check-circle"></i>
                <span class="ml-2">Завершить монтаж</span>
            </button>
            <hr>
        </div>
        @endif

    </form>

    <input type="file" class="d-none" accept="image/*" id="file-select" onchange="montage.uploadFile(this);" multiple>

    <div class="my-2 mx-auto" style="max-width: 500px;">

        <div class="font-weight-bold mb-2">Комментарии</div>

        <div class="position-relative comment-form px-2" id="cont-file-comment">
            <div class="position-relative d-flex align-items-center">
                <input type="text" class="form-control" placeholder="Ваш комментарий..." aria-label="Ваш комментарий..." style="padding-left: 35px; padding-right: 35px;" id="comment-text" onkeyup="montage.sendCommentAuto(this);">
                <i class="fa fa-paperclip fa-lg position-absolute i-add-file hover-link" aria-hidden="true" onclick="montage.openAddFile(this);" data-content="comment"></i>
                <i class="fa fa-paper-plane fa-lg position-absolute i-send-message hover-link" aria-hidden="true" onclick="montage.sendComment();"></i>
            </div>
        </div>

        <div class="mt-3 mb-4" id="comments-list"><span class="opacity-50" id="no-comments-rows">Комментариев нет</span></div>

    </div>

    <div class="d-flex justify-content-center align-items-center loading-in-body" id="loading-global">
        <div class="spinner-grow text-dark" role="status">
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

        montage.getOneMontage({{ $montage->id ?? 0 }});

        $('#search-collegue').autocomplete({
            source: montage.searchCollegue,
            minLength: 0,
        });

        $('#data-montage select, #data-montage input').on('change', function() {
            $(this).removeClass('is-invalid');
        });
        
    });
</script>
@endsection