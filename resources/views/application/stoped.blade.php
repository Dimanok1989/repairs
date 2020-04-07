@extends('index')

@section('content')

<div class="d-flex justify-content-center align-items-center position-absolute text-center" style="top: 47px; left: 0; right: 0; bottom: 47px;">
    
    <div>
        <h1>{{ $data->name }}</h1>
        <p class="lead">Подача заявок временно приостановлена</p>
    </div>

</div>

@endsection