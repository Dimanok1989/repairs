@extends('index')

@section('title', 'Приёмка ' . $inspection->busGarage)

@section('content')

<div class="mt-4 mb-3 mx-auto content-block-width">

    <h4 class="mb-0">Приёмка {{ $inspection->busGarage }}</h4>

    <div id="done-inspect" class="px-2"></div>

    <div id="bus-data" class="mt-3"></div>

    <div id="inspection-data" class="my-3">
        <div class="spinner-border" role="status"></div>
    </div>

</div>

<div class="modal" id="device-info" tabindex="-1" role="dialog" data-backdrop="static" >
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="modal-body text-left pb-0"></form>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="data-save" onclick="inspection.save(this);">Сохранить</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    $(document).ready(() => {
        inspection.open({{ $inspection->id }});
        inspection.getButtonsColorInterval = setInterval(() => {
            inspection.getButtonsColor({{ $inspection->id }});
        }, 10000);
    });
</script>
@endsection