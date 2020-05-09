<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Сервис@hasSection('title') - @yield('title')@endif</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="token" content="{{ isset($_COOKIE['token']) ? $_COOKIE['token'] : 0 }}">
        <link rel="stylesheet" href="/libs/bootstrap-4.4.1-dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="/css/style.css?{{ config('app.version') }}">
        <link href="/libs/fontawesome-free-5.12.0-web/css/all.css" rel="stylesheet">
    </head>

    <body class="bg-light cursor-default">

        <div id="head" style="{{ isset($__user) ? 'padding-top: 63px;' : '' }}">
            @include('header')
        </div>

        <div id="content" class="text-center p-1">
            @yield('content')
        </div>

        <script src="/libs/jquery/jquery-3.4.1.min.js"></script>
        <script src="/libs/jquery/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>        
        <script src="/libs/jquery/jquery.cookie.js"></script>
        <script src="/libs/jquery/jquery.maskedinput.min.js"></script>        
        
        <script src="/libs/popper.min.js"></script>
        <script src="/libs/bootstrap-4.4.1-dist/js/bootstrap.min.js"></script>
        <script src="/libs/google.charts.loader.min.js"></script>

        <script src="/app/app.js?{{ config('app.version') }}"></script>
        <script src="/app/app-admin.js?{{ config('app.version') }}"></script>
        <script src="/app/app-application.js?{{ config('app.version') }}"></script>
        <script src="/app/app-service.js?{{ config('app.version') }}"></script>
        <script src="/app/app-montage.js?{{ config('app.version') }}"></script>
        <script src="/app/app-inspection.js?{{ config('app.version') }}"></script>
        @yield('script')
        <script>
            $(() => {
                setTimeout(() => {
                    if ($('#left-bar-menu .new-data-menu').length == 0)
                        $('#header-open-menu .new-data').remove();
                }, 1000);   
            });
        </script>

    </body>

</html>