@extends('index')

@section('title', 'Заявка #'.$application->id)

@section('content')

<div class="mt-3 mx-auto content-block-width" id="content-application"></div>

<div class="d-flex justify-content-center align-items-center loading-in-body">
    <div class="spinner-grow text-dark" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>

<div class="modal fade" id="modal-problem-comment" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-problem-comment-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-problem-comment-label">Отметить проблему</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body text-left">
                
                <div class="input-group">
                    <textarea class="form-control" aria-label="Введите комментарий проблемы..." placeholder="Введите комментарий проблемы..." name="comment" rows="4" onkeyup="application.checkComment(this);"></textarea>
                </div>
                <small id="comment-count" class="form-text opacity-60">0/250 символов</small>

            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-data" onclick="application.problemCommentApplicationSave(this);">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-cansel-application" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-cansel-application-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cansel-application-label">Отмена заявки</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body text-left">
                
                <div class="input-group">
                    <textarea class="form-control" aria-label="Можно указать дополнительный комментарий..." placeholder="Можно указать дополнительный комментарий..." name="comment" rows="4" onkeyup="application.checkComment(this);"></textarea>
                </div>
                <small id="comment-count" class="form-text opacity-60">0/250 символов</small>

            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-data" onclick="application.applicationCanselSave(this);">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-delete-application" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cansel-application-label"><i class="fas fa-trash text-danger mr-2"></i>Удаление заявки</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body">
                <h6 class="mb-3">Вы действительно хотите удалить эту заявку?</h6>
                <div class="input-group">
                    <textarea class="form-control" aria-label="Можно указать причину..." placeholder="Можно указать причину..." name="comment" rows="4" onkeyup="application.checkComment(this);"></textarea>
                </div>
                <small id="comment-count" class="form-text opacity-60 text-left">0/250 символов</small>
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Нет</button>
                <button type="button" class="btn btn-danger" id="save-data" onclick="application.applicationDeleteSave(this);">Удалить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-application-combine" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="modal-application-combine-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-application-combine-label">Присоединить к...</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body text-left"></form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-data" onclick="application.applicationCombine(this);" disabled>Объединить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modal-no-useradd" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content text-center">
            <div class="modal-body text-center">
                <h6>Вы не выбрали коллегу для совместного выполнения заявки, продолжить или вернуться?</h6>
                <div class="mt-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-primary mx-2" data-dismiss="modal">Назад</button>
                    <button type="button" class="btn btn-success mx-2" id="save-data" onclick="application.applicationSaveNoUser(this);">Продолжить</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- <div class="text-left">
@php
    dump($application);
@endphp
</div> --}}

@endsection

@section('script')
<script>
    application.getOneApplicationData('{{ request()->link }}');
</script>
@endsection

