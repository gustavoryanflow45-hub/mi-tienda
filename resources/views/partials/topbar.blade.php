{{-- resources/views/partials/topbar.blade.php --}}
<div class="top-navbar bg-white border-bottom border-soft-secondary z-1035">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 col">
                <ul class="list-inline d-flex justify-content-between justify-content-lg-start mb-0">
                    @php
                        // Solo los idiomas que SetLocale::SUPPORTED sabe servir.
                        $languages = [
                            'es' => ['name' => 'Español', 'flag' => 'es'],
                            'en' => ['name' => 'English', 'flag' => 'gb'],
                        ];
                        $current = $languages[app()->getLocale()] ?? $languages['es'];
                    @endphp
                    <li class="list-inline-item dropdown mr-3" id="lang-change">
                        <a href="javascript:void(0)" class="dropdown-toggle text-reset py-2" data-toggle="dropdown" data-display="static">
                            <img src="https://flagcdn.com/160x120/{{ $current['flag'] }}.png" class="mr-2" alt="{{ $current['name'] }}" height="11">
                            <span class="opacity-60">{{ $current['name'] }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-left">
                            @foreach($languages as $code => $lang)
                                <li>
                                    <a href="javascript:void(0)" data-flag="{{ $code }}"
                                       class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}">
                                        <img src="https://flagcdn.com/160x120/{{ $lang['flag'] }}.png" class="mr-1" alt="{{ $lang['name'] }}" height="11">
                                        <span class="language">{{ $lang['name'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="col-5 text-right d-none d-lg-block">
                <ul class="list-inline mb-0 h-100 d-flex justify-content-end align-items-center">
                    @guest
                        <li class="list-inline-item mr-3 border-right border-left-0 pr-3 pl-0">
                            <a href="{{ route('login') }}" class="text-reset d-inline-block opacity-60 py-2">{{ __('topbar.login') }}</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('register') }}" class="text-reset d-inline-block opacity-60 py-2">{{ __('topbar.register') }}</a>
                        </li>
                    @else
                        <li class="list-inline-item mr-3">
                            <span class="opacity-60">{{ Auth::user()->name }}</span>
                        </li>
                        <li class="list-inline-item">
                            <a href="{{ route('logout') }}" class="text-reset d-inline-block opacity-60 py-2"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('topbar.logout') }}</a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </div>
</div>
