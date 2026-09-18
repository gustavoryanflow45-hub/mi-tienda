@extends('layouts.app')

@section('title', __('Registra tu tienda'))

@section('meta_description', __('Registra tu tienda y empieza a vender en nuestra plataforma.'))

@section('extra_css')
<style>
    .page-hero {
        background: #f0f2f5;
        padding: 18px 0 10px;
        border-bottom: 1px solid #e2e6ea;
    }
    .page-hero h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #222;
        margin: 0;
    }
    .breadcrumb {
        background: transparent;
        padding: 0;
        margin: 4px 0 0;
        font-size: 0.82rem;
    }
    .breadcrumb-item a {
        color: #679941;
        text-decoration: none;
    }
    .breadcrumb-item.active {
        color: #888;
    }
    .breadcrumb-item + .breadcrumb-item::before {
        content: "/";
        color: #bbb;
    }

    /* Main layout */
    .register-shop-page {
        background: #eef0f3;
        min-height: calc(100vh - 200px);
        padding: 30px 0 50px;
    }

    /* Cards */
    .form-card {
        background: #fff;
        border-radius: 6px;
        border: 1px solid #e0e4e8;
        margin-bottom: 20px;
        overflow: hidden;
    }
    .form-card-header {
        background: #fff;
        padding: 14px 20px;
        border-bottom: 1px solid #e8ecef;
    }
    .form-card-header h5 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #333;
        margin: 0;
        letter-spacing: 0.01em;
    }
    .form-card-body {
        padding: 20px 20px 10px;
    }

    /* Form fields */
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        font-size: 0.82rem;
        font-weight: 600;
        color: #444;
        margin-bottom: 5px;
        display: block;
    }
    .form-group label .req {
        color: #e74c3c;
        margin-left: 2px;
    }
    .form-control {
        border: 1px solid #d8dde3;
        border-radius: 4px;
        height: 38px;
        font-size: 0.85rem;
        color: #333;
        background: #fafbfc;
        padding: 6px 12px;
        transition: border-color 0.2s, box-shadow 0.2s;
        width: 100%;
        box-sizing: border-box;
    }
    .form-control:focus {
        border-color: #679941;
        background: #fff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(103,153,65,0.12);
    }
    .form-control::placeholder {
        color: #b0b8c2;
        font-size: 0.84rem;
    }

    /* File input */
    .file-input-wrapper {
        display: flex;
        align-items: center;
        border: 1px solid #d8dde3;
        border-radius: 4px;
        background: #fafbfc;
        height: 38px;
        overflow: hidden;
    }
    .file-input-wrapper .file-btn {
        background: #e8eaed;
        border: none;
        border-right: 1px solid #d8dde3;
        padding: 0 12px;
        height: 100%;
        font-size: 0.8rem;
        color: #333;
        cursor: pointer;
        white-space: nowrap;
        display: flex;
        align-items: center;
        font-weight: 500;
    }
    .file-input-wrapper .file-btn:hover {
        background: #dde0e4;
    }
    .file-input-wrapper .file-label {
        padding: 0 12px;
        font-size: 0.82rem;
        color: #999;
        flex: 1;
    }
    .file-input-wrapper input[type="file"] {
        display: none;
    }

    /* Submit button */
    .btn-register-shop {
        background: #679941;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 10px 28px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
        display: inline-block;
        text-align: center;
        letter-spacing: 0.02em;
    }
    .btn-register-shop:hover {
        background: #578533;
        color: #fff;
        transform: translateY(-1px);
    }
    .btn-register-shop:active {
        transform: translateY(0);
        background: #4e7a2e;
    }

    .submit-row {
        padding: 10px 0 20px;
        text-align: right;
    }

    /* Alert messages */
    .alert {
        border-radius: 4px;
        font-size: 0.85rem;
        padding: 10px 16px;
        margin-bottom: 18px;
    }
</style>
@endsection

@section('content')

{{-- Page title bar --}}
<div class="page-hero">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1>{{ __('Registra tu tienda') }}</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Inicio') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Registra tu tienda') }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

{{-- Main content --}}
<div class="register-shop-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('shops.create') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    {{-- Personal Info --}}
                    <div class="form-card">
                        <div class="form-card-header">
                            <h5>{{ __('Datos personales') }}</h5>
                        </div>
                        <div class="form-card-body">

                            <div class="form-group">
                                <label>{{ __('Tu nombre') }} <span class="req">*</span></label>
                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       placeholder="{{ __('Nombre') }}"
                                       value="{{ old('name') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Tu correo electrónico') }} <span class="req">*</span></label>
                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       placeholder="{{ __('Correo electrónico') }}"
                                       value="{{ old('email') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Tu contraseña') }} <span class="req">*</span></label>
                                <input type="password"
                                       name="password"
                                       class="form-control"
                                       placeholder="{{ __('Contraseña') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Repite la contraseña') }} <span class="req">*</span></label>
                                <input type="password"
                                       name="password_confirmation"
                                       class="form-control"
                                       placeholder="{{ __('Confirmar contraseña') }}"
                                       required>
                            </div>

                        </div>
                    </div>

                    {{-- Basic Info --}}
                    <div class="form-card">
                        <div class="form-card-header">
                            <h5>{{ __('Datos de la tienda') }}</h5>
                        </div>
                        <div class="form-card-body">

                            <div class="form-group">
                                <label>{{ __('Nombre de la tienda') }} <span class="req">*</span></label>
                                <input type="text"
                                       name="shop_name"
                                       class="form-control"
                                       placeholder="{{ __('Nombre de la tienda') }}"
                                       value="{{ old('shop_name') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Correo de la tienda') }} <span class="req">*</span></label>
                                <input type="email"
                                       name="shop_email"
                                       class="form-control"
                                       placeholder="{{ __('Correo de la tienda') }}"
                                       value="{{ old('shop_email') }}"
                                       required>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Frente del documento de identidad') }} <span class="req">*</span></label>
                                <div class="file-input-wrapper" onclick="document.getElementById('id_front').click()">
                                    <span class="file-btn">{{ __('Elegir archivo') }}</span>
                                    <span class="file-label" id="id_front_label">{{ __('Ningún archivo seleccionado') }}</span>
                                    <input type="file"
                                           id="id_front"
                                           name="id_front"
                                           accept="image/*"
                                           onchange="updateFileLabel(this, 'id_front_label')"
                                           required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Reverso del documento de identidad') }} <span class="req">*</span></label>
                                <div class="file-input-wrapper" onclick="document.getElementById('id_back').click()">
                                    <span class="file-btn">{{ __('Elegir archivo') }}</span>
                                    <span class="file-label" id="id_back_label">{{ __('Ningún archivo seleccionado') }}</span>
                                    <input type="file"
                                           id="id_back"
                                           name="id_back"
                                           accept="image/*"
                                           onchange="updateFileLabel(this, 'id_back_label')"
                                           required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Dirección') }} <span class="req">*</span></label>
                                <input type="text"
                                       name="address"
                                       class="form-control"
                                       placeholder="{{ __('Dirección') }}"
                                       value="{{ old('address') }}"
                                       required>
                            </div>

                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="submit-row">
                        <button type="submit" class="btn-register-shop">
                            {{ __('Registra tu tienda') }}
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@section('extra_js')
<script>
    function updateFileLabel(input, labelId) {
        var label = document.getElementById(labelId);
        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
            label.style.color = '#444';
        } else {
            label.textContent = @json(__('Ningún archivo seleccionado'));
            label.style.color = '#999';
        }
    }
</script>
@endsection