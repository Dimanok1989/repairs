<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\Auth\User;
use App\Http\Controllers\Main;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // Прверка токена
        if (!$user = User::checkToken($request, true))
            return Main::error("Ошибка аутентификации", 9000);

        $request->__user = $user;

        \App\Models\UserModel::setLastTime($user->id);

        return $next($request);

    }
}
