@extends('index')

@section('title', 'Новая заявка')

@section('content')

<div class="mt-4 mb-3 mx-auto" style="max-width: 500px;">

    <h4 class="mb-0">Новая заявка</h4>
    <p class="mb-4">Выберите заказчика</p>

    @forelse($projects as $project)
        <a href="{{ route('addRequest', $project->login) }}" role="button" class="btn btn-primary btn-lg btn-block mb-4">{{ $project->name }}</a>
    @empty
        <blockquote class="blockquote text-center mt-5">
            <p class="mb-0">К сожалению Вам не разрешается создать новую заявку</p>
            <footer class="blockquote-footer">Администрация</footer>
        </blockquote>
    @endforelse

</div>

@endsection