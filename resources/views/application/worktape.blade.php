@extends('index')

@section('title', 'Лента работ')

@section('content')

    <div id="worktape" class="mt-4 mx-auto" style="max-width: 700px;"></div>
    <div class="text-center" id="worktapeload">
        <div class="spinner-grow spinner-grow-sm" role="status">
            <span class="sr-only">Загрузка...</span>
        </div>
    </div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        service.getWorkTape();
    });
</script>
@endsection