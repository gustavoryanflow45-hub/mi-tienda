{{-- Aviso de aprobación/rechazo de tienda (se muestra una vez) --}}
@if(isset($shopUpdates) && $shopUpdates->isNotEmpty())
    @foreach($shopUpdates as $update)
        @php $approved = (bool) ($update->data['approved'] ?? false); @endphp
        <div style="border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; gap:10px;
                    background:{{ $approved ? '#eefbf1' : '#fdeeee' }};
                    border:1px solid {{ $approved ? '#b7e4c7' : '#f5c2c2' }};">
            <i class="las {{ $approved ? 'la-store' : 'la-store-slash' }}"
               style="font-size:1.6rem; color:{{ $approved ? '#2f7a4d' : '#c0392b' }};"></i>
            <div style="flex:1;">
                <div style="font-size:.88rem; font-weight:700; color:{{ $approved ? '#2f7a4d' : '#c0392b' }};">
                    {{ $update->data['message'] }}
                </div>
                <div style="font-size:.75rem; color:#8a8a8a; margin-top:2px;">
                    {{ $update->created_at->diffForHumans() }}
                </div>
            </div>
            @if($approved)
                <a href="{{ route('seller.products.index') }}" class="btn btn-sm btn-primary fw-600">
                    Ir a mis productos
                </a>
            @endif
        </div>
    @endforeach
@endif
