{{--
    Layout del panel de administración. No depende de layouts.app ni de los
    parciales de la tienda (cabecera, carrito, buscador, pie): el panel debe
    poder extraerse a otra app copiando resources/views/admin/ y este archivo.

    Secciones: title, extra_css, content, extra_js. Una vista que ya pinta
    sus propios avisos (success / warning / error) declara
    @section('flash_in_page', '1') y el layout no los repite.

    $adminNav (AppServiceProvider) trae el admin autenticado, si ya pasó el
    segundo factor y los contadores de la barra.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- El panel no se indexa nunca. --}}
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('Panel de administración')) | {{ config('app.name', 'guStore') }}</title>

    <link rel="icon" href="{{ asset('assets/img/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700&display=swap" rel="stylesheet">

    {{-- Solo el CSS del tema (Bootstrap 4, utilidades aiz e iconos). --}}
    <link rel="stylesheet" href="{{ asset('assets/css/vendors.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/aiz-core.css') }}">

    <style>
        :root {
            --primary: #679941;
            --hov-primary: #557f35;
            --soft-primary: rgba(103, 153, 65, 0.15);
        }
        body {
            font-family: 'Open Sans', sans-serif;
            background: #f4f6f3;
            color: #1f2a1b;
        }
        img { max-width: 100%; }

        .admin-topbar {
            background: #1f2a1b;
            color: #fff;
        }
        .admin-topbar .container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem 1rem;
            padding-top: .6rem;
            padding-bottom: .6rem;
        }
        .admin-brand {
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            white-space: nowrap;
        }
        .admin-brand:hover { color: #fff; text-decoration: none; }
        .admin-brand .badge-panel {
            background: var(--primary);
            border-radius: 4px;
            padding: 1px 6px;
            margin-left: 4px;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* La navegación baja de línea en pantallas estrechas: nada se oculta
           por breakpoint, así que desde un teléfono se llega a todo. */
        .admin-nav {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin: 0;
            padding: 0;
            list-style: none;
            flex: 1 1 auto;
        }
        .admin-nav a {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            color: rgba(255, 255, 255, .75);
            padding: .35rem .6rem;
            border-radius: 6px;
            font-size: .85rem;
            font-weight: 600;
        }
        .admin-nav a:hover { color: #fff; background: rgba(255, 255, 255, .08); text-decoration: none; }
        .admin-nav a.active { color: #fff; background: var(--primary); }
        .admin-nav .count {
            background: #e74c3c;
            color: #fff;
            border-radius: 10px;
            padding: 0 6px;
            font-size: .7rem;
            line-height: 1.4;
        }

        .admin-tools {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
            margin-left: auto;
            font-size: .8rem;
        }
        .admin-tools .who {
            color: rgba(255, 255, 255, .65);
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .admin-tools form { margin: 0; }
        .admin-tools .btn-lang {
            color: rgba(255, 255, 255, .65);
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 4px;
            padding: 1px 7px;
            font-size: .75rem;
        }
        .admin-tools .btn-lang.active { color: #fff; border-color: #fff; }
        .admin-tools .btn-logout {
            color: #fff;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 6px;
            padding: .25rem .6rem;
            font-size: .8rem;
        }
        .admin-tools .btn-logout:hover { background: #c0392b; border-color: #c0392b; }

        .admin-main { flex: 1 0 auto; }
        .admin-footer { color: #8a9585; font-size: .75rem; }
    </style>

    @yield('extra_css')
</head>
<body>
<div class="d-flex flex-column min-vh-100">

    <header class="admin-topbar">
        <div class="container">
            <a href="{{ $adminNav['ready'] ? route('admin.home') : route('admin.login') }}" class="admin-brand">
                {{ config('app.name', 'guStore') }}<span class="badge-panel">{{ __('Panel') }}</span>
            </a>

            @if($adminNav['ready'])
                <nav aria-label="{{ __('Panel de administración') }}" class="flex-grow-1">
                    <ul class="admin-nav">
                        <li>
                            <a href="{{ route('admin.shops') }}" class="{{ request()->routeIs('admin.shops*') ? 'active' : '' }}">
                                <i class="las la-store"></i> {{ __('Tiendas') }}
                                @if($adminNav['pending_shops'] > 0)
                                    <span class="count" title="{{ __('Tiendas pendientes de aprobación') }}">{{ $adminNav['pending_shops'] }}</span>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.settlements') }}" class="{{ request()->routeIs('admin.settlements*') ? 'active' : '' }}">
                                <i class="las la-hand-holding-usd"></i> {{ __('Liquidaciones') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.wallet') }}" class="{{ request()->routeIs('admin.wallet*') ? 'active' : '' }}">
                                <i class="las la-wallet"></i> {{ __('Billetera') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.warehouse.index') }}" class="{{ request()->routeIs('admin.warehouse.*') ? 'active' : '' }}">
                                <i class="las la-warehouse"></i> {{ __('Almacén') }}
                                @if($adminNav['arrivals'] > 0)
                                    <span class="count" title="{{ __('Nuevas llegadas al almacén') }}">{{ $adminNav['arrivals'] }}</span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </nav>
            @endif

            <div class="admin-tools">
                {{-- El idioma se guarda en la sesión del panel, no en la de la tienda. --}}
                <form method="POST" action="{{ route('admin.language') }}" class="d-inline-flex" style="gap:.25rem;">
                    @csrf
                    @foreach(\App\Http\Middleware\SetLocale::SUPPORTED as $code)
                        <button type="submit" name="locale" value="{{ $code }}"
                                class="btn-lang {{ app()->getLocale() === $code ? 'active' : '' }}"
                                aria-pressed="{{ app()->getLocale() === $code ? 'true' : 'false' }}">{{ strtoupper($code) }}</button>
                    @endforeach
                </form>

                @if($adminNav['admin'])
                    <span class="who" title="{{ $adminNav['admin']->email }}">
                        <i class="las la-user-shield"></i> {{ $adminNav['admin']->name }}
                    </span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <i class="las la-sign-out-alt"></i> {{ __('Cerrar sesión del panel') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </header>

    <main class="admin-main">
        @unless(View::hasSection('flash_in_page'))
            @include('admin.partials.flash')
        @endunless

        @yield('content')
    </main>

    <footer class="admin-footer py-3">
        <div class="container text-center">
            {{ config('app.name', 'guStore') }} · {{ __('Panel de administración') }}
        </div>
    </footer>
</div>

{{-- jQuery + Bootstrap del tema: solo para cerrar avisos y componentes Bootstrap. --}}
<script src="{{ asset('assets/js/vendors.js') }}"></script>
@yield('extra_js')
</body>
</html>
