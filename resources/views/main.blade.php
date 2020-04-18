@extends('index')

@section('content')

@include('application.search')

<div class="mt-4 mb-3 mx-auto content-block-width">
@forelse($applications as $application)

    <div class="card mt-3 cursor-default">
        <div class="card-body py-2">
            <div class="d-flex justify-content-start align-items-center mb-1">
                <h5 class="card-title mb-0">{{ $application->name }}</h5>
                <span class="badge badge-primary ml-2">{{ $application->counts }}</span>
            </div>
            <div class="mx-auto">
                @foreach($application->projects as $key => $project)
                    <a href="{{ route('applicatioslist') }}{{ $application->id }}?project={{ $key }}" class="btn btn-light-main text-center p-0 m-2 text-dark">
                        <div class="pt-2 pb-0 px-3"><i class="fas {{ $application->projectsIcon[$key] }} fa-2x"></i></span></div>
                        <div class="text-danger font-weight-bold">{{ $application->applications[$key] ?? 0 }}</div>
                        {{-- <div class="badge badge-primary mt-1">{{ $application->applications[$key] ?? 0 }}</div> --}}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

@empty
{{-- <div class="mt-4 mb-3 mx-auto" style="max-width: 600px;">
    <h3>Активных заказчиков не найдено</h3>
</div> --}}
@endforelse
</div>

{{-- @forelse($applications as $application)
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
<div class="mt-4 mb-3 mx-auto" style="max-width: 600px;">
    <h3>Активных заказчиков не найдено</h3>
</div>
@endforelse --}}

@endsection