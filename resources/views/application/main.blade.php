@extends('index')

@section('title', 'Заявка #'.$application->id)

@section('content')

<div class="mt-3 mx-auto" id="content-application" style="max-width: 800px;"></div>

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

<div class="modal fade" id="modal-delete-application" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content text-center">
            <div class="modal-body text-center">
                <h6>Вы действительно хотите удалить эту заявку?</h6>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Нет</button>
                    <button type="button" class="btn btn-danger" id="save-data" onclick="application.applicationDeleteSave(this);">Да</button>
                </div>
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
            <form class="modal-body text-left">
                
                
                
            </form>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-data" onclick="application.applicationCombine(this);" disabled>Объединить</button>
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
<script src="/libs/app-application.js?{{ config('app.version') }}"></script>
<script>
    application.getOneApplicationData('{{ request()->link }}');
</script>
@endsection

