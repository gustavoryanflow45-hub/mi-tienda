@extends('layouts.app')

@section('title', 'Programa de Afiliados')

@section('content')
<div style="background:#f2f3f8; padding:30px 0 60px; min-height:60vh;">
    <div class="container" style="max-width:800px;">

        <div style="background:linear-gradient(135deg,#679941,#4e7a2e); border-radius:12px; padding:40px; color:#fff; text-align:center; margin-bottom:24px;">
            <i class="las la-hand-holding-usd" style="font-size:3rem; margin-bottom:16px; display:block;"></i>
            <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:10px;">Programa de Afiliados</h1>
            <p style="opacity:.9; font-size:.95rem;">Gana comisiones recomendando nuestros productos a tus amigos y familia.</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
            <div style="background:#fff; border-radius:10px; box-shadow:0 1px 8px rgba(0,0,0,.07); padding:24px; text-align:center;">
                <i class="las la-link" style="font-size:2rem; color:#679941; margin-bottom:12px; display:block;"></i>
                <h3 style="font-size:.95rem; font-weight:700; margin-bottom:8px;">1. Comparte tu enlace</h3>
                <p style="font-size:.83rem; color:#888;">Registra tu cuenta y obtén tu enlace de referido único.</p>
            </div>
            <div style="background:#fff; border-radius:10px; box-shadow:0 1px 8px rgba(0,0,0,.07); padding:24px; text-align:center;">
                <i class="las la-users" style="font-size:2rem; color:#679941; margin-bottom:12px; display:block;"></i>
                <h3 style="font-size:.95rem; font-weight:700; margin-bottom:8px;">2. Atrae compradores</h3>
                <p style="font-size:.83rem; color:#888;">Cuando alguien compra usando tu enlace, ganas una comisión.</p>
            </div>
            <div style="background:#fff; border-radius:10px; box-shadow:0 1px 8px rgba(0,0,0,.07); padding:24px; text-align:center;">
                <i class="las la-wallet" style="font-size:2rem; color:#679941; margin-bottom:12px; display:block;"></i>
                <h3 style="font-size:.95rem; font-weight:700; margin-bottom:8px;">3. Cobra tus ganancias</h3>
                <p style="font-size:.83rem; color:#888;">Retira tus comisiones directo a tu cuenta bancaria.</p>
            </div>
        </div>

        <div style="background:#fff; border-radius:10px; box-shadow:0 1px 8px rgba(0,0,0,.07); padding:28px; text-align:center;">
            @auth
                <p style="font-size:.9rem; color:#555; margin-bottom:16px;">Tu enlace de referido:</p>
                <code style="background:#f5f6f8; padding:10px 20px; border-radius:6px; font-size:.85rem; color:#333;">
                    {{ url('/') }}?ref={{ auth()->user()->referral_code ?? 'N/A' }}
                </code>
            @else
                <p style="font-size:.9rem; color:#555; margin-bottom:16px;">Regístrate para obtener tu enlace de afiliado.</p>
                <a href="{{ route('register') }}"
                   style="background:#679941; color:#fff; border-radius:8px; padding:12px 28px; font-size:.9rem; font-weight:700; text-decoration:none; display:inline-block;">
                    Comenzar ahora
                </a>
            @endauth
        </div>

    </div>
</div>
@endsection
