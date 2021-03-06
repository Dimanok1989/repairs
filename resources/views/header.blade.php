@if (Session::get('user'))

<div class="bg-dark text-light px-3 py-3 fixed-top">

    <div class="mx-auto" style="max-width: 1300px;">

    <button class="btn btn-sm btn-dark align-middle rounded-circle2 fa-lg position-relative" onclick="app.openMenu();" id="header-open-menu">
        <i class="fas fa-bars"></i>
        @if ($__user->newData->services > 0)
            <span class="new-data pulse"></span>
        @endif
    </button>

    {{-- <a href="/" class="ml-1 btn btn-sm btn-dark align-middle rounded-circle2 fa-lg" title="Главная страница"><i class="fas fa-home"></i></a> --}}

    <a href="/" class="mx-1 align-middle hover-main" title="Главная страница">
        <img src="/favicon.ico?{{ config('app.version') }}" width="26" />
    </a>

    @if ($__user->access->applications == 1 OR $__user->access->admin == 1)
        <a href="/add" class="ml-1 btn btn-sm btn-dark align-middle fa-lg" title="Добавить заявку"><i class="fas fa-plus-square"></i></a>
        {{-- <a href="/add" class="mx-1 align-middle hover-main" title="Добавить заявку"><i class="fas fa-plus-square fa-lg"></i></a> --}}
    @endif

    @if ($__user->access->application_comment == 1 OR $__user->access->applications_done == 1 OR $__user->access->admin == 1)
        <a href="/comments" class="ml-1 btn btn-sm btn-dark align-middle rounded-circle2 fa-lg position-relative" title="Комментарии" id="header-comments-link"><i class="far fa-comments"></i>@if ($__user->newData->comments > 0) <span class="new-data pulse"></span> @endif</a>
    @endif

    {{-- @if (Session::get('user'))

        <div class="btn-group float-right" role="group">
        <button id="user-menu" type="button" class="btn btn-sm btn-dark align-middle dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ $__user->login }} </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="user-menu">
                <a href="/user/settings" class="dropdown-item px-3{{ url()->current() == route('usersettings') ? " active" : "" }}" title="Настройки"><i class="fas fa-cogs mr-2"></i>Настройки</a>
                @if ($__user->access->admin == 1) <a href="/admin" class="dropdown-item px-3{{ strripos(url()->current(), "/admin") ? " active" : "" }} disabled" title="Админ панель"><i class="fas fa-user-shield mr-2"></i>Админ панель</a> @endif
                <div class="dropdown-divider"></div>
                <a href="/logout" class="dropdown-item px-3" title="Выход"><i class="fas fa-sign-out-alt mr-2"></i>Выход</a>
            </div>
        </div>

    @else

        <a href="/login" class="btn btn-sm btn-dark align-middle rounded-circle float-right" title="Авторизация"><i class="fas fa-sign-in-alt"></i></a>

    @endif --}}

    </div>

</div>

@if (Session::get('user')) 
    <div id="nav-bg" onclick="app.closeMenu();"></div>
    <nav class="menu" id="left-bar-menu">
        <div class="text-light px-3 py-2">
            <button class="btn btn-sm btn-light align-middle rounded-circle" onclick="app.closeMenu();"><i class="fas fa-chevron-left" style="width: 14px; text-align: center;"></i></button>
        </div>
        <div class="list-group list-group-flush">
            {{-- <li class="list-group-item pt-4 pb-1 list-group-item-action disabled title-nav">Меню</li> --}}
            <a href="/" class="list-group-item py-1 list-group-item-action{{ route('mainpage') == url()->current() ? " active" : "" }}"><i class="fas fa-home mr-1"></i>Главная страница</a>

            @if ($__user->access->applications == 1 OR $__user->access->admin == 1)
                <a href="/add" class="list-group-item py-1 list-group-item-action{{ route('SelectForaddApplication') == url()->current() ? " active" : "" }}"><i class="fas fa-plus-square mr-1"></i>Добавить заявку</a>
                <a href="/service" class="list-group-item py-1 list-group-item-action position-relative{{ strripos(url()->current(), "ru/service") ? " active" : "" }}">
                    <i class="fas fa-tools mr-1"></i>Лента работ
                    @if ($__user->newData->services > 0)
                        <span class="new-data-menu pulse" id="menu-new-data"></span>
                    @endif
                </a>
            @endif  

            @if ($__user->access->inspection == 1 OR $__user->access->admin == 1)
                <a href="/inspection" class="list-group-item py-1 list-group-item-action{{ strripos(url()->current(), "ru/inspection") ? " active" : "" }}"><i class="fas fa-clipboard-list mr-1"></i>Приёмка</a>
            @endif

            @if ($__user->access->montage == 1 OR $__user->access->admin == 1)
                <a href="/montage" class="list-group-item py-1 list-group-item-action{{ strripos(url()->current(), "ru/montage") ? " active" : "" }}"><i class="fas fa-ruler-combined mr-1"></i>Монтаж</a>
            @endif
            <a href="/logout" class="list-group-item py-1 list-group-item-action" title="Выход"><i class="fas fa-sign-out-alt mr-1"></i>Выход</a>

            @if ($__user->access->admin == 1)
                <li class="list-group-item pt-4 pb-1 list-group-item-action disabled title-nav">Админка</li>
                {{-- <a href="/admin" class="list-group-item py-1 mt-4 list-group-item-action{{ route('admin') == url()->current() ? " active" : "" }}"><i class="fas fa-user-shield mr-1"></i>Админ панель</a> --}}
                <a href="/admin/projects" class="list-group-item py-1 list-group-item-action{{ (route('adminprojects') == url()->current() OR strripos(url()->current(), "/admin/projects")) ? " active" : "" }}"><i class="fas fa-project-diagram mr-1"></i>Заказчики</a>
                <a href="/admin/users" class="list-group-item py-1 list-group-item-action{{ route('adminusers') == url()->current() ? " active" : "" }}"><i class="fas fa-user mr-1"></i>Сотрудники</a>
                <a href="/admin/users/groups" class="list-group-item py-1 list-group-item-action{{ route('adminusersgroups') == url()->current() ? " active" : "" }}"><i class="fas fa-users mr-1"></i>Группы</a>                
                <a href="/admin/bus" class="list-group-item py-1 list-group-item-action{{ route('adminbus') == url()->current() ? " active" : "" }}"><i class="fas fa-bus-alt mr-1"></i>Подвижной состав</a>
                <a href="/admin/device" class="list-group-item py-1 list-group-item-action{{ route('admindevice') == url()->current() ? " active" : "" }}"><i class="fas fa-laptop-medical mr-1"></i>Оборудование</a>
                <a href="/admin/insp" class="list-group-item py-1 list-group-item-action disabled"><i class="fas fa-clipboard-list mr-1"></i></i>Приёмка</a>                
                <a href="/admin/montage" class="list-group-item py-1 list-group-item-action{{ strripos(url()->current(), '/admin/montage') ? " active" : "" }}"><i class="fas fa-swatchbook mr-1"></i>Монтаж</a>
            @endif

        </div>
    </nav>
@endif

@endif