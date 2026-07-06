@extends('layouts.app')

@section('title', 'Admin — Gestión de Wallet')

@section('extra_css')
<style>
    /* ── Variables ── */
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

    .admin-wallet-wrapper {
        background: var(--gray);
        min-height: 90vh;
        padding: 32px 0 60px;
    }

    /* ── Encabezado ── */
    .aw-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .aw-header h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--text);
        margin: 0;
    }
    .aw-header h1 span {
        color: var(--green);
    }

    /* ── Tabs ── */
    .aw-tabs {
        display: flex;
        gap: 6px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 5px;
        margin-bottom: 24px;
    }
    .aw-tab {
        flex: 1;
        text-align: center;
        padding: 9px 16px;
        border-radius: 7px;
        font-size: .88rem;
        font-weight: 600;
        color: var(--muted);
        cursor: pointer;
        border: none;
        background: transparent;
        transition: background .2s, color .2s;
    }
    .aw-tab.active {
        background: var(--green);
        color: #fff;
    }

    /* ── Stats cards ── */
    .aw-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }
    .aw-stat {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px 18px;
    }
    .aw-stat .stat-val {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1.1;
    }
    .aw-stat .stat-lbl {
        font-size: .76rem;
        color: var(--muted);
        margin-top: 3px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .aw-stat.green .stat-val { color: var(--green); }
    .aw-stat.red   .stat-val { color: var(--red);   }
    .aw-stat.yellow .stat-val{ color: var(--yellow); }

    /* ── Tabla ── */
    .aw-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .aw-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        font-size: .95rem;
        font-weight: 700;
        color: var(--text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .aw-card-header .dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--green);
        display: inline-block;
    }

    .aw-table { width: 100%; border-collapse: collapse; }
    .aw-table thead th {
        background: #fafbfc;
        font-size: .76rem;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 10px 16px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    .aw-table tbody td {
        padding: 12px 16px;
        font-size: .86rem;
        color: var(--text);
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    .aw-table tbody tr:last-child td { border-bottom: none; }
    .aw-table tbody tr:hover { background: #fafcf8; }

    /* Mono texto (hash, address) */
    .mono {
        font-family: monospace;
        font-size: .8rem;
        color: #444;
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
    }

    /* ── Badges ── */
    .badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
    }
    .badge-pending  { background: var(--yellow-s); color: var(--yellow); }
    .badge-approved { background: var(--green-s);  color: var(--green);  }
    .badge-rejected { background: var(--red-s);    color: var(--red);    }

    /* ── Botones acción ── */
    .btn-approve, .btn-reject {
        border: none;
        border-radius: 7px;
        padding: 5px 13px;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        transition: opacity .15s, transform .1s;
    }
    .btn-approve:hover, .btn-reject:hover { opacity: .85; transform: translateY(-1px); }
    .btn-approve { background: var(--green-s); color: var(--green); }
    .btn-reject  { background: var(--red-s);   color: var(--red);   }

    /* ── Avatar usuario ── */
    .user-cell {
        display: flex;
        align-items: center;
        gap: 9px;
    }
    .user-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--green-s);
        color: var(--green);
        font-weight: 700;
        font-size: .8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .user-name  { font-weight: 600; font-size: .85rem; }
    .user-email { font-size: .75rem; color: var(--muted); }

    /* ── Comprobante ── */
    .proof-link {
        color: var(--green);
        font-size: .8rem;
        text-decoration: none;
        font-weight: 600;
    }
    .proof-link:hover { text-decoration: underline; }

    /* ── Empty ── */
    .aw-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--muted);
        font-size: .9rem;
    }
    .aw-empty i { font-size: 2.5rem; margin-bottom: 10px; display: block; opacity: .4; }

    /* ── Alertas flash ── */
    .aw-alert {
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 18px;
        font-size: .88rem;
        font-weight: 600;
    }
    .aw-alert-success { background: var(--green-s); color: var(--green); }
    .aw-alert-warning { background: var(--yellow-s); color: var(--yellow); }
    .aw-alert-error   { background: var(--red-s);   color: var(--red);   }

    /* ── Responsive ── */
    @media (max-width: 767px) {
        .aw-table thead { display: none; }
        .aw-table tbody tr {
            display: block;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin: 10px 12px;
            padding: 12px;
        }
        .aw-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border: none;
            font-size: .82rem;
        }
        .aw-table tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--muted);
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .mono { max-width: 120px; }
    }
</style>
@endsection

@section('content')
<div class="admin-wallet-wrapper">
<div class="container">

    {{-- Header --}}
    <div class="aw-header">
        <h1>Gestión de <span>Wallet</span></h1>
        <span style="font-size:.82rem;color:var(--muted);">
            Panel de administración
        </span>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="aw-alert aw-alert-success">
            <i class="las la-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="aw-alert aw-alert-warning">
            <i class="las la-exclamation-circle"></i> {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="aw-alert aw-alert-error">
            <i class="las la-times-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="aw-tabs">
        <button class="aw-tab active" onclick="switchTab('recharges', this)">
            <i class="las la-arrow-circle-down"></i> Recargas
            @php $pendingR = $recharges->where('approval', 0)->count(); @endphp
            @if($pendingR > 0)
                <span style="background:#e53e3e;color:#fff;border-radius:20px;
                             padding:1px 7px;font-size:.7rem;margin-left:4px;">
                    {{ $pendingR }}
                </span>
            @endif
        </button>
        <button class="aw-tab" onclick="switchTab('withdrawals', this)">
            <i class="las la-arrow-circle-up"></i> Retiros
            @php $pendingW = $withdrawals->where('status', 'pending')->count(); @endphp
            @if($pendingW > 0)
                <span style="background:#e53e3e;color:#fff;border-radius:20px;
                             padding:1px 7px;font-size:.7rem;margin-left:4px;">
                    {{ $pendingW }}
                </span>
            @endif
        </button>
    </div>

    {{-- ══════════════════════════════════════
         TAB 1 — RECARGAS
    ══════════════════════════════════════ --}}
    <div id="tab-recharges">

        {{-- Stats --}}
        <div class="aw-stats">
            <div class="aw-stat">
                <div class="stat-val">{{ $recharges->count() }}</div>
                <div class="stat-lbl">Total solicitudes</div>
            </div>
            <div class="aw-stat yellow">
                <div class="stat-val">{{ $recharges->where('approval', 0)->count() }}</div>
                <div class="stat-lbl">Pendientes</div>
            </div>
            <div class="aw-stat green">
                <div class="stat-val">{{ $recharges->where('approval', 1)->count() }}</div>
                <div class="stat-lbl">Aprobadas</div>
            </div>
            <div class="aw-stat red">
                <div class="stat-val">{{ $recharges->where('approval', -1)->count() }}</div>
                <div class="stat-lbl">Rechazadas</div>
            </div>
            <div class="aw-stat green">
                <div class="stat-val">
                    ${{ number_format($recharges->where('approval', 1)->sum('amount'), 2) }}
                </div>
                <div class="stat-lbl">Total aprobado</div>
            </div>
        </div>

        {{-- Tabla recargas --}}
        <div class="aw-card">
            <div class="aw-card-header">
                <span class="dot"></span> Solicitudes de Recarga Offline
            </div>
            <div class="table-responsive">
                <table class="aw-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Red</th>
                            <th>Monto</th>
                            <th>TX ID</th>
                            <th>Comprobante</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recharges as $r)
                        <tr>
                            <td data-label="#">{{ $r->id }}</td>
                            <td data-label="Usuario">
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($r->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="user-name">{{ $r->user->name ?? '—' }}</div>
                                        <div class="user-email">{{ $r->user->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Red">
                                @if($r->network)
                                    <span class="badge-pill badge-approved">{{ $r->network }}</span>
                                @else
                                    <span style="color:var(--muted)">—</span>
                                @endif
                            </td>
                            <td data-label="Monto">
                                <strong>${{ number_format($r->amount, 2) }}</strong>
                            </td>
                            <td data-label="TX ID">
                                @if($r->transaction_id)
                                    <span class="mono" title="{{ $r->transaction_id }}">
                                        {{ $r->transaction_id }}
                                    </span>
                                @else
                                    <span style="color:var(--muted)">—</span>
                                @endif
                            </td>
                            <td data-label="Comprobante">
                                @if($r->payment_proof)
                                    <a href="{{ asset('storage/' . $r->payment_proof) }}"
                                       target="_blank" class="proof-link">
                                        <i class="las la-image"></i> Ver
                                    </a>
                                @else
                                    <span style="color:var(--muted)">—</span>
                                @endif
                            </td>
                            <td data-label="Estado">
                                @if($r->approval == 1)
                                    <span class="badge-pill badge-approved">
                                        <i class="las la-check"></i> Aprobado
                                    </span>
                                @elseif($r->approval == 0)
                                    <span class="badge-pill badge-pending">
                                        <i class="las la-clock"></i> Pendiente
                                    </span>
                                @else
                                    <span class="badge-pill badge-rejected">
                                        <i class="las la-times"></i> Rechazado
                                    </span>
                                @endif
                            </td>
                            <td data-label="Fecha" style="color:var(--muted);font-size:.78rem;">
                                {{ $r->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td data-label="Acciones">
                                @if($r->approval == 0)
                                <div style="display:flex;gap:6px;">
                                    <form method="POST"
                                          action="{{ route('wallet.approve', $r->id) }}"
                                          onsubmit="return confirm('¿Aprobar esta recarga de ${{ number_format($r->amount,2) }}?')">
                                        @csrf
                                        <button type="submit" class="btn-approve">
                                            <i class="las la-check"></i> Aprobar
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('wallet.reject', $r->id) }}"
                                          onsubmit="return confirm('¿Rechazar esta recarga?')">
                                        @csrf
                                        <button type="submit" class="btn-reject">
                                            <i class="las la-times"></i> Rechazar
                                        </button>
                                    </form>
                                </div>
                                @else
                                    <span style="color:var(--muted);font-size:.8rem;">Sin acción</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                <div class="aw-empty">
                                    <i class="las la-inbox"></i>
                                    No hay solicitudes de recarga.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════
         TAB 2 — RETIROS
    ══════════════════════════════════════ --}}
    <div id="tab-withdrawals" style="display:none;">

        {{-- Stats --}}
        <div class="aw-stats">
            <div class="aw-stat">
                <div class="stat-val">{{ $withdrawals->count() }}</div>
                <div class="stat-lbl">Total solicitudes</div>
            </div>
            <div class="aw-stat yellow">
                <div class="stat-val">{{ $withdrawals->where('status','pending')->count() }}</div>
                <div class="stat-lbl">Pendientes</div>
            </div>
            <div class="aw-stat green">
                <div class="stat-val">{{ $withdrawals->where('status','approved')->count() }}</div>
                <div class="stat-lbl">Aprobados</div>
            </div>
            <div class="aw-stat red">
                <div class="stat-val">{{ $withdrawals->where('status','rejected')->count() }}</div>
                <div class="stat-lbl">Rechazados</div>
            </div>
            <div class="aw-stat red">
                <div class="stat-val">
                    ${{ number_format($withdrawals->where('status','approved')->sum('amount'), 2) }}
                </div>
                <div class="stat-lbl">Total retirado</div>
            </div>
        </div>

        {{-- Tabla retiros --}}
        <div class="aw-card">
            <div class="aw-card-header">
                <span class="dot" style="background:var(--red);"></span>
                Solicitudes de Retiro
            </div>
            <div class="table-responsive">
                <table class="aw-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Monto</th>
                            <th>Nombre completo</th>
                            <th>Red / Banco</th>
                            <th>Dirección / Cuenta</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdrawals as $w)
                        <tr>
                            <td data-label="#">{{ $w->id }}</td>
                            <td data-label="Usuario">
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($w->user->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="user-name">{{ $w->user->name ?? '—' }}</div>
                                        <div class="user-email">{{ $w->user->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Monto">
                                <strong style="color:var(--red);">
                                    ${{ number_format($w->amount, 2) }}
                                </strong>
                            </td>
                            <td data-label="Nombre">{{ $w->full_name }}</td>
                            <td data-label="Red/Banco">
                                <span class="badge-pill badge-pending">{{ $w->bank_name }}</span>
                            </td>
                            <td data-label="Dirección">
                                <span class="mono" title="{{ $w->account_number }}">
                                    {{ $w->account_number }}
                                </span>
                            </td>
                            <td data-label="Estado">
                                @if($w->status == 'approved')
                                    <span class="badge-pill badge-approved">
                                        <i class="las la-check"></i> Aprobado
                                    </span>
                                @elseif($w->status == 'pending')
                                    <span class="badge-pill badge-pending">
                                        <i class="las la-clock"></i> Pendiente
                                    </span>
                                @else
                                    <span class="badge-pill badge-rejected">
                                        <i class="las la-times"></i> Rechazado
                                    </span>
                                @endif
                            </td>
                            <td data-label="Fecha" style="color:var(--muted);font-size:.78rem;">
                                {{ $w->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td data-label="Acciones">
                                @if($w->status == 'pending')
                                <div style="display:flex;gap:6px;">
                                    <form method="POST"
                                          action="{{ route('wallet.withdrawal.approve', $w->id) }}"
                                          onsubmit="return confirm('¿Confirmar retiro de ${{ number_format($w->amount,2) }}?')">
                                        @csrf
                                        <button type="submit" class="btn-approve">
                                            <i class="las la-check"></i> Aprobar
                                        </button>
                                    </form>
                                    <form method="POST"
                                          action="{{ route('wallet.withdrawal.reject', $w->id) }}"
                                          onsubmit="return confirm('¿Rechazar este retiro? El saldo será devuelto.')">
                                        @csrf
                                        <button type="submit" class="btn-reject">
                                            <i class="las la-times"></i> Rechazar
                                        </button>
                                    </form>
                                </div>
                                @else
                                    <span style="color:var(--muted);font-size:.8rem;">Sin acción</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                <div class="aw-empty">
                                    <i class="las la-inbox"></i>
                                    No hay solicitudes de retiro.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>
@endsection

@section('extra_js')
<script>
function switchTab(tab, el) {
    // Ocultar todos
    document.getElementById('tab-recharges').style.display   = 'none';
    document.getElementById('tab-withdrawals').style.display = 'none';
    // Desactivar tabs
    document.querySelectorAll('.aw-tab').forEach(function(t) {
        t.classList.remove('active');
    });
    // Mostrar el seleccionado
    document.getElementById('tab-' + tab).style.display = 'block';
    el.classList.add('active');
}

// Activar tab desde URL hash
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#withdrawals') {
        switchTab('withdrawals', document.querySelectorAll('.aw-tab')[1]);
    }
});
</script>
@endsection