@extends('index')

@section('title', 'Комментарии')

@section('content')

<div class="mx-auto content-block-width" id="comments"></div>

<div class="text-center" id="commentstapeload">
    <div class="spinner-grow spinner-grow-sm" role="status">
        <span class="sr-only">Загрузка...</span>
    </div>
</div>

@endsection

@section('script')
<script>

$(() => {
    service.getLastComments();
    $('#header-comments-link .new-data').remove();
});

</script>
@endsection