@extends('layouts.app')

@section('title', 'Mi Cartera')

@section('extra_css')
<style>
    /* ── Wallet page ── */
    .wallet-wrapper {
        background: #f5f6fa;
        min-height: 80vh;
        padding: 24px 0 40px;
    }

    /* Balance card */
    .wallet-balance-card {
        background: linear-gradient(135deg, #c850c0 0%, #f97794 55%, #fcc5e4 100%);
        border-radius: 16px;
        padding: 32px 24px 28px;
        text-align: center;
        color: #fff;
        box-shadow: 0 8px 24px rgba(200, 80, 192, .30);
        margin-bottom: 20px;
    }
    .wallet-balance-card .wallet-icon {
        font-size: 2rem;
        margin-bottom: 8px;
        opacity: .9;
    }
    .wallet-balance-card .balance-amount {
        font-size: 2.6rem;
        font-weight: 700;
        letter-spacing: 1px;
        line-height: 1.1;
    }
    .wallet-balance-card .balance-label {
        font-size: .9rem;
        opacity: .85;
        margin-top: 4px;
    }

    /* Action cards */
    .wallet-action-card {
        background: #fff;
        border-radius: 16px;
        padding: 28px 24px;
        text-align: center;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        margin-bottom: 20px;
        cursor: pointer;
        transition: box-shadow .2s, transform .15s;
    }
    .wallet-action-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,.12);
        transform: translateY(-2px);
    }
    .wallet-action-card .action-circle {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #d1d5db;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: #fff;
        margin-bottom: 12px;
        transition: background .2s;
    }
    .wallet-action-card:hover .action-circle {
        background: var(--primary);
    }
    .wallet-action-card .action-label {
        color: var(--primary);
        font-size: 1rem;
        font-weight: 600;
    }

    /* History table card */
    .wallet-history-card {
        background: #fff;
        border-radius: 16px;
        padding: 0;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .wallet-history-card .history-header {
        padding: 16px 20px;
        font-size: 1rem;
        font-weight: 600;
        border-bottom: 1px solid #f0f0f0;
        background: #fff;
    }
    .wallet-history-card .table {
        margin-bottom: 0;
    }
    .wallet-history-card .table thead th {
        background: #fafafa;
        font-size: .82rem;
        font-weight: 700;
        color: #555;
        border-top: none;
        padding: 10px 16px;
    }
    .wallet-history-card .table tbody td {
        font-size: .87rem;
        color: #333;
        padding: 10px 16px;
        vertical-align: middle;
    }
    .wallet-history-card .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Empty state */
    .wallet-empty {
        text-align: center;
        padding: 28px 16px;
        color: #aaa;
        font-size: .9rem;
    }

    /* Modals */
    .wallet-modal .modal-header {
        background: linear-gradient(135deg, #c850c0, #f97794);
        color: #fff;
        border-radius: 12px 12px 0 0;
    }
    .wallet-modal .modal-header .close {
        color: #fff;
        opacity: .8;
    }
    .wallet-modal .modal-title {
        font-weight: 700;
    }

    /* Badge status */
    .badge-pending   { background: #ffc107; color: #333; }
    .badge-approved  { background: #28a745; color: #fff; }
    .badge-rejected  { background: #dc3545; color: #fff; }
</style>
@endsection

@section('content')
<div class="wallet-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">

                {{-- Balance Card --}}
                <div class="wallet-balance-card">
                    <div class="wallet-icon">
                        <i class="las la-dollar-sign"></i>
                    </div>
                    <div class="balance-amount">
                        ${{ number_format(auth()->user()->balance ?? 0, 2) }}
                    </div>
                    <div class="balance-label">{{ __('Saldo de Wallet') }}</div>
                </div>

                {{-- Offline Recharge --}}
                <div class="wallet-action-card" data-toggle="modal" data-target="#offlineRechargeModal">
                    <div class="action-circle">
                        <i class="las la-plus"></i>
                    </div>
                    <div class="action-label">{{ __('Offline Recharge Wallet') }}</div>
                </div>

                {{-- Withdrawal Request --}}
                <div class="wallet-action-card" data-toggle="modal" data-target="#withdrawalModal">
                    <div class="action-circle">
                        <i class="las la-plus"></i>
                    </div>
                    <div class="action-label">{{ __('Enviar solicitud de retiro') }}</div>
                </div>

                {{-- Recharge History --}}
                <div class="wallet-history-card">
                    <div class="history-header">{{ __('Wallet Recharge History') }}</div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Cantidad') }}</th>
                                    <th>{{ __('Aprobación') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recharges ?? [] as $key => $recharge)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>${{ number_format($recharge->amount, 2) }}</td>
                                    <td>
                                        @if($recharge->approval == 1)
                                            <span class="badge badge-approved px-2 py-1 rounded">{{ __('Aprobado') }}</span>
                                        @elseif($recharge->approval == 0)
                                            <span class="badge badge-pending px-2 py-1 rounded">{{ __('Pendiente') }}</span>
                                        @else
                                            <span class="badge badge-rejected px-2 py-1 rounded">{{ __('Rechazado') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="wallet-empty">{{ __('No hay recargas registradas.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Withdrawal History --}}
                <div class="wallet-history-card">
                    <div class="history-header">{{ __('Wallet Withdrawal History') }}</div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Cantidad') }}</th>
                                    <th>{{ __('Estado') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($withdrawals ?? [] as $key => $withdrawal)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>${{ number_format($withdrawal->amount, 2) }}</td>
                                    <td>
                                        @if($withdrawal->status == 'approved')
                                            <span class="badge badge-approved px-2 py-1 rounded">{{ __('Aprobado') }}</span>
                                        @elseif($withdrawal->status == 'pending')
                                            <span class="badge badge-pending px-2 py-1 rounded">{{ __('Pendiente') }}</span>
                                        @else
                                            <span class="badge badge-rejected px-2 py-1 rounded">{{ __('Rechazado') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="wallet-empty">{{ __('No hay retiros registrados.') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Modal: Offline Recharge --}}
<style>
    .network-card-inner {
        border: 2px solid #e5e7eb !important;
        border-radius: 12px !important;
        transition: border-color .2s, box-shadow .2s;
        background: #fff;
    }
    .network-card-inner:hover {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(103,153,65,.15);
    }
    .network-card input:checked + .network-card-inner {
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(103,153,65,.15);
    }
    /* caja dirección */
    #wallet-address-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: .83rem;
        word-break: break-all;
        color: #333;
    }
    #wallet-address-box .addr-label {
        font-weight: 700;
        color: var(--primary);
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 4px;
    }
    #wallet-address-box .addr-text {
        font-family: monospace;
        font-size: .82rem;
        color: #222;
        line-height: 1.5;
    }
    #copy-addr-btn {
        margin-top: 8px;
        font-size: .8rem;
        padding: 4px 14px;
        border-radius: 6px;
        border: 1px solid var(--primary);
        background: transparent;
        color: var(--primary);
        cursor: pointer;
        transition: background .2s, color .2s;
    }
    #copy-addr-btn:hover, #copy-addr-btn.copied {
        background: var(--primary);
        color: #fff;
    }
</style>

<div class="modal fade" id="offlineRechargeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">

            <div class="modal-header border-0 pb-1 pt-4 px-4">
                <h5 class="modal-title" style="font-size:1.15rem;font-weight:700;">
                    {{ __('Offline Recharge Wallet') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="font-size:1.4rem;opacity:.6;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('wallet.recharge') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 pb-2">

                    {{-- Grid de redes --}}
                    <div class="row mb-3" style="row-gap:12px;">

                        {{-- BEP20 --}}
                        <div class="col-6">
                            <label class="network-card d-block" style="cursor:pointer;margin:0;">
                                <input type="radio" name="network" value="BEP20" class="d-none network-radio">
                                <div class="network-card-inner text-center py-3 px-2">
                                    <img src="{{ asset('assets/images/binance-coin-bnb-logo.png') }}"
                                         alt="BEP20"
                                         style="width:68px;height:68px;object-fit:contain;border-radius:50%;background:#1a1a1a;padding:6px;">
                                    <div class="mt-2" style="font-size:.88rem;font-weight:600;color:#222;">BEP20-USDT</div>
                                </div>
                            </label>
                        </div>

                        {{-- TRC20 --}}
                        <div class="col-6">
                            <label class="network-card d-block" style="cursor:pointer;margin:0;">
                                <input type="radio" name="network" value="TRC20" class="d-none network-radio">
                                <div class="network-card-inner text-center py-3 px-2">
                                    <img src="{{ asset('assets/images/tron-trx-logo.png') }}"
                                         alt="TRC20"
                                         style="width:68px;height:68px;object-fit:contain;border-radius:50%;background:#1a1a1a;padding:6px;">
                                    <div class="mt-2" style="font-size:.88rem;font-weight:600;color:#222;">TRC20-USDT</div>
                                </div>
                            </label>
                        </div>

                        {{-- ERC20 --}}
                        <div class="col-6">
                            <label class="network-card d-block" style="cursor:pointer;margin:0;">
                                <input type="radio" name="network" value="ERC20" class="d-none network-radio">
                                <div class="network-card-inner text-center py-3 px-2">
                                    <img src="{{ asset('assets/images/ethereum-eth-logo.png') }}"
                                         alt="ERC20"
                                         style="width:68px;height:68px;object-fit:contain;border-radius:50%;background:#1a1a1a;padding:6px;">
                                    <div class="mt-2" style="font-size:.88rem;font-weight:600;color:#222;">ERC20-USDT</div>
                                </div>
                            </label>
                        </div>

                    </div>

                    {{-- Caja de dirección (aparece al seleccionar) --}}
                    <div id="wallet-address-box"
                         style="display:none;align-items:center;justify-content:space-between;
                                background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;
                                padding:10px 12px;gap:8px;margin-bottom:12px;">
                        <span id="addr-value"
                              style="font-size:.82rem;color:#333;word-break:break-all;flex:1;
                                     font-family:monospace;line-height:1.4;"></span>
                        <button type="button" id="copy-addr-btn" onclick="copyWalletAddress()"
                                title="Copiar dirección"
                                style="flex-shrink:0;background:none;border:none;padding:2px 4px;
                                       cursor:pointer;color:#888;font-size:1.1rem;line-height:1;
                                       transition:color .2s;">
                            <i class="las la-copy" id="copy-icon"></i>
                        </button>
                    </div>

                    {{-- Formulario --}}
                    <div class="border rounded p-3 mb-1" style="border-radius:10px!important;">
                        <div class="form-group mb-3">
                            <label class="mb-1" style="font-size:.88rem;">
                                {{ __('Cantidad') }} <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="amount" class="form-control" min="1" step="0.01" required
                                   placeholder="{{ __('Cantidad') }}" style="border-radius:8px;">
                        </div>
                        <div class="form-group mb-3">
                            <label class="mb-1" style="font-size:.88rem;">
                                {{ __('ID de transacción') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="transaction_id" class="form-control" required
                                   placeholder="{{ __('ID de transacción') }}" style="border-radius:8px;">
                        </div>
                        <div class="form-group mb-0">
                            <label class="mb-1" style="font-size:.88rem;">{{ __('Foto') }}</label>
                            <div class="input-group">
                                <label class="input-group-text" for="payment_proof_input"
                                       style="background:#e9ecef;cursor:pointer;border-radius:8px 0 0 8px;font-size:.84rem;">
                                    {{ __('Vistazo') }}
                                </label>
                                <input type="file" name="payment_proof" id="payment_proof_input"
                                       class="form-control" accept="image/*"
                                       style="border-radius:0 8px 8px 0;">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-2">
                    <button type="submit" class="btn w-100"
                            style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;font-size:1rem;padding:.7rem;">
                        {{ __('Confirmar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Withdrawal --}}
<div class="modal fade wallet-modal" id="withdrawalModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:16px;border:none;">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="font-size:1.2rem;">{{ __('Enviar solicitud de retiro') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size:1.5rem;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('wallet.withdraw') }}" method="POST">
                @csrf
                <div class="modal-body pt-2">

                    <div class="form-group mb-3">
                        <label class="mb-1" style="font-size:.9rem;">{{ __('Cantidad') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" min="1" step="0.01" required
                               placeholder="{{ __('Cantidad') }}" style="border-radius:8px;">
                    </div>

                    <div class="form-group mb-3">
                        <label class="mb-1" style="font-size:.9rem;">{{ __('Nombre completo') }} <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required
                               placeholder="{{ __('Nombre completo') }}" style="border-radius:8px;">
                    </div>

                    <div class="form-group mb-3">
                        <label class="mb-1" style="font-size:.9rem;">
                            {{ __('Nombre del banco') }} <span class="text-danger">*</span>
                            <small class="text-muted">{{ __('Complete la criptomoneda (TRC20, ERC20, BEP20)') }}</small>
                        </label>
                        <input type="text" name="bank_name" class="form-control" required
                               placeholder="{{ __('Nombre del banco') }}" style="border-radius:8px;">
                    </div>

                    <div class="form-group mb-3">
                        <label class="mb-1" style="font-size:.9rem;">
                            {{ __('Número de cuenta bancaria') }} <span class="text-danger">*</span>
                            <small class="text-muted">{{ __('Por favor ingrese el precio del producto') }}</small>
                        </label>
                        <input type="text" name="account_number" class="form-control" required
                               placeholder="{{ __('Número de cuenta bancaria') }}" style="border-radius:8px;">
                    </div>

                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" class="btn btn-block w-100"
                            style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;font-size:1rem;padding:.65rem;">
                        {{ __('Confirmar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('extra_js')
<script>
// ── Configura aquí tus direcciones reales ──────────────────────────────
var walletAddresses = {
    BEP20: '0x8WCssl45hjghhjjdjSBEP20',
    TRC20: 'TEkbFw2Gc6JAgZtKPb9KcmuyvojVCTtk',
    ERC20: '0xaA2e45D14346c191541Db631d5A0F25cF14E018B',
};
// ───────────────────────────────────────────────────────────────────────

$(document).on('change', '.network-radio', function() {
    var net  = $(this).val();
    var addr = walletAddresses[net] || '';

    $('#addr-value').text(addr);
    $('#copy-icon').attr('class', 'las la-copy');
    $('#copy-addr-btn').css('color', '#888');
    $('#wallet-address-box').css('display', 'flex');
});

function copyWalletAddress() {
    var addr = document.getElementById('addr-value').textContent;
    if (!addr) return;

    function showCopied() {
        $('#copy-icon').attr('class', 'las la-check');
        $('#copy-addr-btn').css('color', 'var(--primary)');
        setTimeout(function() {
            $('#copy-icon').attr('class', 'las la-copy');
            $('#copy-addr-btn').css('color', '#888');
        }, 2500);
    }

    if (navigator.clipboard) {
        navigator.clipboard.writeText(addr).then(showCopied).catch(function() {
            fallbackCopy(addr); showCopied();
        });
    } else {
        fallbackCopy(addr); showCopied();
    }
}

function fallbackCopy(text) {
    var tmp = document.createElement('textarea');
    tmp.value = text;
    tmp.style.position = 'fixed';
    tmp.style.opacity  = '0';
    document.body.appendChild(tmp);
    tmp.select();
    document.execCommand('copy');
    document.body.removeChild(tmp);
}
</script>
@endsection