<?php

namespace App\Http\Middleware;

use Closure;
use Session;
use App\Http\Controllers\Auth\User;

class CheckUserSession
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

        // Обновление данных сессии, если они есть
        if ($old = Session::get('user')) {

            $user = User::getUserData($old->id, $old->token);
            Session::put('user', $user);

            \App\Models\UserModel::setLastTime($user->id);

            \Illuminate\Support\Facades\View::share('__user', $user);

        }
        else
            \Illuminate\Support\Facades\View::share('__user', false);

        return $next($request);
    }
}
