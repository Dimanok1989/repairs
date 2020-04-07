@extends('index')

@section('title', 'Заявки')

@section('content')

@include('application.search')

<div class="mt-4 mb-3 mx-auto" style="max-width: 800px;" id="applications-list" data-client="{{ request()->client ?? 0 }}" data-project="{{ request()->project ?? 0 }}">

    <ul class="list-group" id="list-application"></ul>

    <div class="py-3 px-2 d-none text-center" id="loading-applications">
        <div class="spinner-border ml-auto" role="status" aria-hidden="true"></div>
    </div>

</div>

@endsection

@section('script')
<script src="/libs/app-application.js?{{ config('app.version') }}"></script>
<script>
    application.getApplicationsList();
</script>
@endsection