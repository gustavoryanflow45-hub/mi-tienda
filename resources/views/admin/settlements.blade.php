@extends('layouts.app')

@section('title', __('Admin — Liquidación de ventas'))

@section('extra_css')
<style>
    :root {
        --green:   #679941;
        --green-s: rgba(103,153,65,.12);
        --red:     #e53e3e;
        --red-s:   rgba(229,62,62,.10);
        --yellow:  #d69e2e;
        --yellow-s:rgba(214,158,46,.12);
        --blue:    #3182ce;
        --blue-s:  rgba(49,130,206,.12);
        --gray:    #f7f8fa;
        --border:  #e8eaed;
        --text:    #1a202c;
        --muted:   #718096;
    }

    .admin-settle-wrapper { background: var(--gray); min-height: 90vh; padding: 32px 0 60px; }

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
    .as-stat.blue .stat-val   { color: var(--blue); }

    .as-filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
    .as-filters input {
        border: 1px solid var(--border); border-radius: 8px; padding: 8px 12px;
        font-size: .85rem; background: #fff; color: var(--text);
    }
    .as-filters .btn-filter {
        border: none; border-radius: 8px; padding: 8px 18px; font-size: .82rem;
        font-weight: 700; background: var(--green); color: #fff; cursor: pointer;
    }
    .as-filters .btn-clear { align-self: center; font-size: .8rem; color: var(--muted); text-decoration: none; }

    .as-card { background: #fff; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 24px; }
    .as-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: .95rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .as-card-header .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); display: inline-block; }
    .as-card-header .hint { margin-left: auto; font-size: .78rem; font-weight: 400; color: var(--muted); }

    .as-table { width: 100%; border-collapse: collapse; }
    .as-table thead th {
        background: #fafbfc; font-size: .76rem; font-weight: 700; color: var(--muted);
        text-transform: uppercase; letter-spacing: .5px; padding: 10px 16px;
        border-bottom: 1px solid var(--border); white-space: nowrap;
    }
    .as-table tbody td { padding: 12px 16px; font-size: .86rem; color: var(--text); border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .as-table tbody tr:last-child td { border-bottom: none; }
    .as-table tbody tr:hover { background: #fafcf8; }
    .as-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .as-table .num.net { font-weight: 700; color: var(--green); }
    .as-table .num.fee { color: var(--red); }
    .as-table tr.settled-zero td { color: var(--muted); }

    .btn-settle {
        border: none; border-radius: 7px; padding: 6px 14px; font-size: .78rem;
        font-weight: 700; cursor: pointer; transition: opacity .15s, transform .1s;
        background: var(--green); color: #fff; white-space: nowrap;
    }
    .btn-settle:hover { opacity: .88; transform: translateY(-1px); }
    .btn-settle:disabled { background: var(--border); color: var(--muted); cursor: not-allowed; transform: none; }

    .user-cell { display: flex; align-items: center; gap: 9px; }
    .user-avatar {
        width: 32px; height: 32px; border-radius: 50%; background: var(--green-s);
        color: var(--green); font-weight: 700; font-size: .8rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .user-name  { font-weight: 600; font-size: .85rem; }
    .user-email { font-size: .75rem; color: var(--muted); }

    .badge-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700; }
    .badge-pending  { background: var(--yellow-s); color: var(--yellow); }
    .badge-approved { background: var(--green-s);  color: var(--green); }
    .badge-rejected { background: var(--red-s);    color: var(--red); }

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
        .as-table .num { text-align: left; }
    }
</style>
@endsection

@section('content')
<div class="admin-settle-wrapper">
<div class="container">

    <div class="as-header">
        <h1>{!! __('Liquidación de <span>ventas</span>') !!}</h1>
        <span style="font-size:.82rem;color:var(--muted);">{{ __('Panel de administración') }} · {{ __('comisión del :rate%', ['rate' => round($commissionRate * 100)]) }}</span>
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
        <div class="as-stat yellow">
            <div class="stat-val">{{ $totals['sellers_pending'] }}</div>
            <div class="stat-lbl">{{ __('Tiendas por liquidar') }}</div>
        </div>
        <div class="as-stat blue">
            <div class="stat-val">${{ number_format($totals['total_sales'], 2) }}</div>
            <div class="stat-lbl">{{ __('Ventas pendientes') }}</div>
        </div>
        <div class="as-stat red">
            <div class="stat-val">${{ number_format($totals['commission'], 2) }}</div>
            <div class="stat-lbl">{{ __('Comisión') }} ({{ round($commissionRate * 100) }}%)</div>
        </div>
        <div class="as-stat green">
            <div class="stat-val">${{ number_format($totals['net_amount'], 2) }}</div>
            <div class="stat-lbl">{{ __('Neto a acreditar') }}</div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.settlements') }}" class="as-filters">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Buscar tienda o vendedor') }}">
        <button type="submit" class="btn-filter">{{ __('Filtrar') }}</button>
        @if(request('search'))
            <a href="{{ route('admin.settlements') }}" class="btn-clear">{{ __('Limpiar') }}</a>
        @endif
    </form>

    <div class="as-card">
        <div class="as-card-header">
            <span class="dot"></span> {{ __('Ventas pendientes por tienda') }}
            <span class="hint">{{ __('Solo cuentan los pedidos cobrados y ya entregados. Liquidar acredita el neto en la billetera del vendedor y deja sus ventas y ganancias en cero.') }}</span>
        </div>
        <div class="table-responsive">
            <table class="as-table">
                <thead>
                    <tr>
                        <th>{{ __('Tienda') }}</th>
                        <th>{{ __('Vendedor') }}</th>
                        <th>{{ __('Estado') }}</th>
                        <th class="num">{{ __('Líneas') }}</th>
                        <th class="num">{{ __('Ventas') }}</th>
                        <th class="num">{{ __('Comisión') }}</th>
                        <th class="num">{{ __('A acreditar') }}</th>
                        <th>{{ __('Última liquidación') }}</th>
                        <th>{{ __('Acción') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $seller  = $row['seller'];
                            $pending = $row['pending'];
                            $last    = $row['last'];
                            $shop    = $seller->shop;
                            $label   = $shop->name ?? $seller->name;
                            $confirm = sprintf(
                                __("¿Liquidar «%s»?\n\nVentas: $%s\nComisión (%d%%): -$%s\nA acreditar en su billetera: $%s\n\nSus ventas y ganancias quedarán en cero."),
                                $label,
                                number_format($pending['total_sales'], 2),
                                round($pending['commission_rate'] * 100),
                                number_format($pending['commission'], 2),
                                number_format($pending['net_amount'], 2),
                            );
                        @endphp
                        <tr class="{{ $pending['lines_count'] === 0 ? 'settled-zero' : '' }}">
                            <td data-label="{{ __('Tienda') }}">
                                <div class="user-name">{{ $shop->name ?? '— sin tienda —' }}</div>
                                <div class="user-email">{{ $shop->email ?? '' }}</div>
                            </td>
                            <td data-label="{{ __('Vendedor') }}">
                                <div class="user-cell">
                                    <div class="user-avatar">{{ strtoupper(substr($seller->name ?? 'U', 0, 1)) }}</div>
                                    <div>
                                        <div class="user-name">{{ $seller->name }}</div>
                                        <div class="user-email">{{ $seller->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('Estado') }}">
                                @if($shop && (int) $shop->status === 1)
                                    <span class="badge-pill badge-approved"><i class="las la-check-circle"></i> {{ __('Aprobada') }}</span>
                                @elseif($shop && (int) $shop->status === 2)
                                    <span class="badge-pill badge-rejected"><i class="las la-times-circle"></i> {{ __('Rechazada') }}</span>
                                @else
                                    <span class="badge-pill badge-pending"><i class="las la-clock"></i> {{ __('Pendiente') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Líneas') }}" class="num">{{ $pending['lines_count'] }}</td>
                            <td data-label="{{ __('Ventas') }}" class="num">${{ number_format($pending['total_sales'], 2) }}</td>
                            <td data-label="{{ __('Comisión') }}" class="num fee">-${{ number_format($pending['commission'], 2) }}</td>
                            <td data-label="{{ __('A acreditar') }}" class="num net">${{ number_format($pending['net_amount'], 2) }}</td>
                            <td data-label="{{ __('Última liquidación') }}">
                                @if($last)
                                    <div class="user-name">{{ $last->settled_at->format('d/m/Y H:i') }}</div>
                                    <div class="user-email">${{ number_format($last->net_amount, 2) }} {{ __('acreditados') }}</div>
                                @else
                                    <span class="user-email">{{ __('Nunca') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Acción') }}">
                                <form method="POST" action="{{ route('admin.settlements.settle', $seller->id) }}"
                                      data-confirm="{{ $confirm }}"
                                      onsubmit="return confirm(this.dataset.confirm);">
                                    @csrf
                                    <button type="submit" class="btn-settle" @disabled($pending['lines_count'] === 0)>
                                        <i class="las la-hand-holding-usd"></i> {{ __('Liquidar') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="as-empty">
                                    <i class="las la-store-slash"></i>
                                    {{ __('No hay vendedores que coincidan con el filtro.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="as-card">
        <div class="as-card-header">
            <span class="dot" style="background:var(--blue);"></span> {{ __('Historial de liquidaciones') }}
        </div>
        <div class="table-responsive">
            <table class="as-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Fecha') }}</th>
                        <th>{{ __('Tienda') }}</th>
                        <th class="num">{{ __('Líneas') }}</th>
                        <th class="num">{{ __('Ventas') }}</th>
                        <th class="num">{{ __('Comisión') }}</th>
                        <th class="num">{{ __('Acreditado') }}</th>
                        <th>{{ __('Liquidó') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $settlement)
                        <tr>
                            <td data-label="#">{{ $settlement->id }}</td>
                            <td data-label="{{ __('Fecha') }}">{{ $settlement->settled_at->format('d/m/Y H:i') }}</td>
                            <td data-label="{{ __('Tienda') }}">
                                <div class="user-name">{{ $settlement->seller?->shop?->name ?? $settlement->seller?->name ?? '—' }}</div>
                                <div class="user-email">{{ $settlement->seller?->email }}</div>
                            </td>
                            <td data-label="{{ __('Líneas') }}" class="num">{{ $settlement->lines_count }}</td>
                            <td data-label="{{ __('Ventas') }}" class="num">${{ number_format($settlement->total_sales, 2) }}</td>
                            <td data-label="{{ __('Comisión') }}" class="num fee">-${{ number_format($settlement->commission, 2) }} ({{ round($settlement->commission_rate * 100) }}%)</td>
                            <td data-label="{{ __('Acreditado') }}" class="num net">${{ number_format($settlement->net_amount, 2) }}</td>
                            <td data-label="{{ __('Liquidó') }}">{{ $settlement->admin?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="as-empty">
                                    <i class="las la-file-invoice-dollar"></i>
                                    {{ __('Todavía no se ha liquidado ninguna tienda.') }}
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $history->links() }}

</div>
</div>
@endsection
