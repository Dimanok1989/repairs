@extends('index')

@section('content')

<div class="d-flex justify-content-center align-items-center login-body">

    <form class="p-2" id="login-form" style="max-width: 400px; width: 100%;" onsubmit="return false;">
        
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="far fa-user"></i></span>
            </div>
            <input type="text" name="login" class="form-control" placeholder="Имя пользователя" required>
        </div>

        <div class="input-group mb-3">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
            </div>
            <input type="password" name="password" class="form-control" placeholder="Пароль" required>
        </div>

        <button type="submit" class="btn btn-primary" onclick="app.login(this);"><i class="fas fa-sign-in-alt"></i> Вход</button>

    </form>

</div>

@endsection