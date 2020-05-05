@extends('index')

@section('title', $bus->garage . " " . $bus->mark)

@section('content')

<div class="my-4">
    <h4 class="mb-0">{{ $bus->garage }}</h4>
    <p>{{ $bus->mark }}</p>

    <div class="opacity-40 my-4">Страница в разработке</div>

</div>


@endsection

@section('script')
<script>

$(document).ready(() => {

});

</script>
@endsection