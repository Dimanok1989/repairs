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

    @if ($__user->access->montage == 1 OR $__user->access->admin == 1)
    <div class="my-4 position-relative">
        <h4>
            <i class="fas fa-angle-left fa-for-hover" onclick="app.chartMontageChange(this);" data-step="1"></i>
            <a href="{{ route('montage') }}" class="mx-3">Монтаж</a>
            <i class="fas fa-angle-right fa-for-hover" onclick="app.chartMontageChange(this);" data-step="-1"></i>
        </h4>
        <div class="mt-2 card p-3" id="chart_montage" style="height: 234px;"></div>
    </div>
    @endif

</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        @if ($__user->access->montage == 1 OR $__user->access->admin == 1) app.chartMontage(); @endif
    });
</script>
@endsection