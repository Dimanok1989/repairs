@extends('index')

@section('title', 'Заявки')

@section('content')

{{-- @include('application.search') --}}

<div class="mt-4 mb-3 mx-auto content-block-width" id="applications-list" data-client="{{ request()->client ?? 0 }}" data-project="{{ request()->project ?? 0 }}">

    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
        <h5 id="name-client" class="mb-0">{{ $project->name }}</h5>
        <p class="ml-2 mb-0">{{ $projectName }}</p>
    </div>

    <ul class="list-group" id="list-application"></ul>

    <div class="py-3 px-2 d-none text-center" id="loading-applications">
        <div class="spinner-border ml-auto" role="status" aria-hidden="true"></div>
    </div>

</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        application.getApplicationsList();
    });
</script>
@endsection