@extends('index')

@section('content')

@include('application.search')

@forelse($applications as $application)
<div class="mt-4 mb-3 mx-auto" style="max-width: 600px;">
    <div class="d-flex justify-content-center align-items-center">
        <div class="font-weight-bold">{{ $application->name }}</div>
        <div class="badge badge-primary ml-2">{{ $application->counts }}</div>
    </div>

    <ul class="list-group mt-2">
    @foreach($application->projects as $key => $project)
        <a href="{{ route('applicatioslist') }}{{ $application->id }}?project={{ $key }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span>{{ $project }}</span>
            <span class="badge badge-primary badge-pill">{{ $application->applications[$key] ?? 0 }}</span>
        </a>      
    @endforeach
    </ul>

</div>
@empty
{{-- <div class="mt-4 mb-3 mx-auto" style="max-width: 600px;">
    <h3>Активных заказчиков не найдено</h3>
</div> --}}
@endforelse

@endsection