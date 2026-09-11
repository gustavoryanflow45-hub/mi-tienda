@extends('layouts.app')

@section('title', 'Admin — Aprobación de Tiendas')

@section('extra_css')
<style>
    :root {
        --green:   #679941;
        --green-s: rgba(103,153,65,.12);
        --red:     #e53e3e;
        --red-s:   rgba(229,62,62,.10);
        --yellow:  #d69e2e;
        --yellow-s:rgba(214,158,46,.12);
        --gray:    #f7f8fa;
        --border:  #e8eaed;
        --text:    #1a202c;
        --muted:   #718096;
    }

    .admin-shops-wrapper { background: var(--gray); min-height: 90vh; padding: 32px 0 60px; }

    .as-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
    .as-header h1 { font-size: 1.45rem; font-weight: 700; color: var(--text); margin: 0; }
    .as-header h1 span { color: var(--green); }

    .as-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .as-stat { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 16px 18px; }
    .as-stat .stat-val { font-size: 1.5rem; font-weight: 700; color: var(--text); line-height: 1.1; }
    .as-stat .stat-lbl { font-size: .76rem; color: var(--muted); margin-top: 3px; text-transform: uppercase; letter-spacing: .4px; }
    .as-stat.green .stat-val  { color: var(--green); }
    .as-stat.red .stat-val    { color: var(--red); }
    .as-stat.yellow .stat-val { color: var(--yellow); }

    .as-filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
    .as-filters input, .as-filters select {
        border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px;
        font-size: .85rem; background: #fff; color: var(--text);
    }
    .as-filters .btn-filter {
        border: none; border-radius: 8px; padding: 8px 18px; font-size: .82rem;
        font-weight: 700; background: var(--green); color: #fff; cursor: pointer;
    }
    .as-filters .btn-clear { align-self: center; font-size: .8rem; color: var(--muted); text-decoration: none; }

    .as-card { background: #fff; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 24px; }
    .as-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: .95rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; }
    .as-card-header .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); display: inline-block; }

    .as-table { width: 100%; border-collapse: collapse; }
    .as-table thead th {
        background: #fafbfc; font-size: .76rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .5px; padding: 10px 16px;
        border-bottom: 1px solid var(--border); white-space: nowrap;
    }
    .as-table tbody td { padding: 12px 16px; font-size: .86rem; color: var(--text); border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .as-table tbody tr:last-child td { border-bottom: none; }
    .as-table tbody tr:hover { background: #fafcf8; }

    .badge-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
    .badge-pending  { background: var(--yellow-s); color: var(--yellow); }
    .badge-approved { background: var(--green-s);  color: var(--green); }
    .badge-rejected { background: var(--red-s);    color: var(--red); }

    .btn-approve, .btn-reject {
        border: none; border-radius: 7px; padding: 5px 13px; font-size: .78rem;
        font-weight: 700; cursor: pointer; transition: opacity .15s, transform .1s;
    }
    .btn-approve:hover, .btn-reject:hover { opacity: .85; transform: translateY(-1px); }
    .btn-approve { background: var(--green-s); color: var(--green); }
    .btn-reject  { background: var(--red-s);   color: var(--red); }

    .user-cell { display: flex; align-items: center; gap: 9px; }
    .user-avatar {
        width: 32px; height: 32px; border-radius: 50%; background: var(--green-s);
        color: var(--green); font-weight: 700; font-size: .8rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .user-name  { font-weight: 600; font-size: .85rem; }
    .user-email { font-size: .75rem; color: var(--muted); }

    .id-thumb { width: 42px; height: 30px; object-fit: cover; border-radius: 5px; border: 1px solid var(--border); margin-right: 4px; }

    .as-empty { text-align: center; padding: 40px 20px; color: var(--muted); font-size: .9rem; }
    .as-empty i { font-size: 2.5rem; margin-bottom: 10px; display: block; opacity: .4; }

    .as-alert { border-radius: 10px; padding: 12px 18px; margin-bottom: 18px; font-size: .88rem; font-weight: 600; }
    .as-alert-success { background: var(--green-s);  color: var(--green); }
    .as-alert-warning { background: var(--yellow-s); color: var(--yellow); }
    .as-alert-error   { background: var(--red-s);    color: var(--red); }

    @media (max-width: 767px) {
        .as-table thead { display: none; }
        .as-table tbody tr { display: block; border: 1px solid var(--border); border-radius: 10px; margin: 10px 12px; }
        .as-table tbody td { display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px solid #f4f4f4; }
        .as-table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--muted); font-size: .75rem; text-transform: uppercase; }
    }
</style>
@endsection

@section('content')
<div class="admin-shops-wrapper">
<div class="container">

    <div class="as-header">
        <h1>Aprobación de <span>Tiendas</span></h1>
        <span style="font-size:.82rem;color:var(--muted);">Panel de administración</span>
    </div>

    @if(session('success'))
        <div class="as-alert as-alert-success"><i class="las la-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="as-alert as-alert-warning"><i class="las la-exclamation-circle"></i> {{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="as-alert as-alert-error"><i class="las la-times-circle"></i> {{ session('error') }}</div>
    @endif

    <div class="as-stats">
        <div class="as-stat">
            <div class="stat-val">{{ $counts['total'] }}</div>
            <div class="stat-lbl">Tiendas</div>
        </div>
        <div class="as-stat yellow">
            <div class="stat-val">{{ $counts['pending'] }}</div>
            <div class="stat-lbl">Pendientes</div>
        </div>
        <div class="as-stat green">
            <div class="stat-val">{{ $counts['approved'] }}</div>
            <div class="stat-lbl">Aprobadas</div>
        </div>
        <div class="as-stat red">
            <div class="stat-val">{{ $counts['rejected'] }}</div>
            <div class="stat-lbl">Rechazadas</div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.shops') }}" class="as-filters">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar tienda o vendedor">
        <select name="status" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="0" @selected(request('status') === '0')>Pendientes</option>
            <option value="1" @selected(request('status') === '1')>Aprobadas</option>
            <option value="2" @selected(request('status') === '2')>Rechazadas</option>
        </select>
        <button type="submit" class="btn-filter">Filtrar</button>
        @if(request('search') || request('status') !== null)
            <a href="{{ route('admin.shops') }}" class="btn-clear">Limpiar</a>
        @endif
    </form>

    <div class="as-card">
        <div class="as-card-header">
            <span class="dot"></span> Solicitudes de registro de tienda
        </div>
        <div class="table-responsive">
            <table class="as-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tienda</th>
                        <th>Vendedor</th>
                        <th>Dirección</th>
                        <th>Documento</th>
                        <th>Estado</th>
                        <th>Registrada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shops as $shop)
                        <tr>
                            <td data-label="#">{{ $shop->id }}</td>
                            <td data-label="Tienda">
                                <div class="user-name">{{ $shop->name }}</div>
                                <div class="user-email">{{ $shop->email }}</div>
                            </td>
                            <td data-label="Vendedor">
                                <div class="user-cell">
                                    <div class="user-avatar">{{ strtoupper(substr($shop->user->name ?? 'U', 0, 1)) }}</div>
                                    <div>
                                        <div class="user-name">{{ $shop->user->name ?? '—' }}</div>
                                        <div class="user-email">
                                            {{ $shop->user->email ?? '' }}
                                            @if($shop->user && ! $shop->user->isVerified())
                                                <span class="badge-pill badge-pending" style="margin-left:4px;">correo sin verificar</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Dirección">{{ $shop->address }}</td>
                            <td data-label="Documento">
                                <a href="{{ uploaded_asset($shop->id_front_image) }}" target="_blank" rel="noopener">
                                    <img src="{{ uploaded_asset($shop->id_front_image) }}" alt="Frente del documento" class="id-thumb">
                                </a>
                                <a href="{{ uploaded_asset($shop->id_back_image) }}" target="_blank" rel="noopener">
                                    <img src="{{ uploaded_asset($shop->id_back_image) }}" alt="Reverso del documento" class="id-thumb">
                                </a>
                            </td>
                            <td data-label="Estado">
                                @if((int) $shop->status === 1)
                                    <span class="badge-pill badge-approved"><i class="las la-check-circle"></i> Aprobada</span>
                                @elseif((int) $shop->status === 2)
                                    <span class="badge-pill badge-rejected"><i class="las la-times-circle"></i> Rechazada</span>
                                @else
                                    <span class="badge-pill badge-pending"><i class="las la-clock"></i> Pendiente</span>
                                @endif
                            </td>
                            <td data-label="Registrada">{{ $shop->created_at?->format('d/m/Y') }}</td>
                            <td data-label="Acciones">
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    @if((int) $shop->status !== 1)
                                        <form method="POST" action="{{ route('admin.shops.approve', $shop->id) }}">
                                            @csrf
                                            <button type="submit" class="btn-approve">
                                                <i class="las la-check"></i> Aprobar
                                            </button>
                                        </form>
                                    @endif
                                    @if((int) $shop->status !== 2)
                                        <form method="POST" action="{{ route('admin.shops.reject', $shop->id) }}"
                                              onsubmit="return confirm('¿Rechazar la tienda «{{ $shop->name }}»? El vendedor perderá el acceso a su panel.');">
                                            @csrf
                                            <button type="submit" class="btn-reject">
                                                <i class="las la-times"></i> Rechazar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="as-empty">
                                    <i class="las la-store-slash"></i>
                                    No hay tiendas que coincidan con el filtro.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $shops->links() }}

</div>
</div>
@endsection
