@extends('layouts.app')

@section('title', __('Editar producto'))

@section('extra_css')
<style>
    body { background-color: #f2f3f8; }

    /* Sidebar */
    .dashboard-sidebar .profile-header { background: linear-gradient(135deg, #679941, #4e7a2e); border-radius: 0.75rem 0.75rem 0 0; }
    .verified-badge { background: linear-gradient(135deg, #43e97b, #38f9d7); color:#fff; border-radius:20px; padding:3px 12px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px; }
    .avatar-placeholder { width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,.25); border:3px solid rgba(255,255,255,.5); display:flex; align-items:center; justify-content:center; font-size:2rem; color:#fff; margin:0 auto; overflow:hidden; }
    .avatar-placeholder img { width:100%; height:100%; object-fit:cover; }
    .aiz-side-nav-link { border-radius:8px; transition:background .15s,color .15s; font-size:14px; color:#555 !important; }
    .aiz-side-nav-link.active { background-color:rgba(103,153,65,.12)!important; color:#679941!important; font-weight:600; }
    .aiz-side-nav-link:hover { background-color:#f0f1f3!important; color:#333!important; }

</style>
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row gutters-10">

            {{-- SIDEBAR --}}
            <div class="col-lg-3 mb-4">
                <div class="dashboard-sidebar">
                    <div class="profile-header p-4 text-center text-white">
                        <div class="avatar-placeholder mb-3">
                            <img src="{{ asset('assets/img/avatar-place.png') }}"
                                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=679941&color=fff&size=80'"
                                 alt="{{ Auth::user()->name }}">
                        </div>
                        <h4 class="h5 fw-600 fs-18 mb-1">{{ strtoupper(Auth::user()->name) }}</h4>
                        <p class="mb-2 text-truncate opacity-80 fs-13">{{ Auth::user()->email }}</p>
                        <span class="verified-badge"><i class="las la-check-circle"></i> {{ __('dashboard.verified') }}</span>
                    </div>
                    <div class="bg-white shadow-sm rounded-bottom p-3">
                        <ul class="aiz-side-nav-list list-unstyled mb-0">
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/dashboard') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-home mr-2 fs-16"></i><span>{{ __('dashboard.nav.dashboard') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/seller/products') }}" class="aiz-side-nav-link active d-flex align-items-center p-2">
                                    <i class="las la-box mr-2 fs-16"></i><span>{{ __('dashboard.nav.products') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ route('seller.orders.index') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-shopping-cart mr-2 fs-16"></i><span>{{ __('dashboard.nav.orders') }}</span>
                                </a>
                            </li>
                            <li class="aiz-side-nav-item mb-1">
                                <a href="{{ url('/profile') }}" class="aiz-side-nav-link d-flex align-items-center p-2">
                                    <i class="las la-user-cog mr-2 fs-16"></i><span>{{ __('dashboard.nav.manage_profile') }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- FORMULARIO --}}
            <div class="col-lg-9">

                <div class="d-flex align-items-center mb-4">
                    <a href="{{ url('/seller/products') }}" class="btn btn-sm btn-outline-secondary mr-3">
                        <i class="las la-arrow-left mr-1"></i> Volver
                    </a>
                    <h3 class="h4 fw-700 mb-0">{{ __('Editar producto') }}</h3>
                </div>

                @include('partials.product-form', [
                    'formAction' => route('seller.products.update', $product->id),
                    'cancelUrl' => url('/seller/products'),
                ])
            </div>

        </div>
    </div>
</section>
@endsection

