{{-- Aviso de liquidación acreditada en la billetera (se muestra una vez) --}}
@if(isset($settlementUpdates) && $settlementUpdates->isNotEmpty())
    @foreach($settlementUpdates as $update)
        <div style="border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; gap:10px;
                    background:#eef6fb; border:1px solid #b9d9ee;">
            <i class="las la-hand-holding-usd" style="font-size:1.6rem; color:#2b6cb0;"></i>
            <div style="flex:1;">
                <div style="font-size:.88rem; font-weight:700; color:#2b6cb0;">
                    {{ $update->data['message'] }}
                </div>
                <div style="font-size:.78rem; color:#4a5568; margin-top:2px;">
                    {{ __('Ventas') }} ${{ number_format($update->data['total_sales'] ?? 0, 2) }}
                    · {{ __('Comisión') }} -${{ number_format($update->data['commission'] ?? 0, 2) }}
                    · {{ __('Acreditado') }} <strong>${{ number_format($update->data['net_amount'] ?? 0, 2) }}</strong>
                    <span style="color:#8a8a8a;">— {{ $update->created_at->diffForHumans() }}</span>
                </div>
            </div>
            <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-primary fw-600">
                {{ __('Ver mi billetera') }}
            </a>
        </div>
    @endforeach
@endif
