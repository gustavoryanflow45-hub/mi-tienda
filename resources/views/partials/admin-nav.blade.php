{{--
    Navegación del panel admin. En modo dominio el admin no tiene sesión en
    la tienda, así que /dashboard no le sirve para moverse ni para salir:
    esta barra lo hace desde el propio panel. Se ve igual en móvil (los
    enlaces bajan de línea), no hay nada oculto por breakpoint.
--}}
<nav class="d-flex flex-wrap align-items-center mb-4 p-2 bg-white rounded shadow-sm" aria-label="{{ __('Panel de administración') }}">
    <a href="{{ route('admin.shops') }}" class="btn btn-sm mr-2 mb-1 {{ request()->routeIs('admin.shops') ? 'btn-primary' : 'btn-light' }}">
        <i class="las la-store"></i> {{ __('Tiendas') }}
    </a>
    <a href="{{ route('admin.settlements') }}" class="btn btn-sm mr-2 mb-1 {{ request()->routeIs('admin.settlements') ? 'btn-primary' : 'btn-light' }}">
        <i class="las la-hand-holding-usd"></i> {{ __('Liquidaciones') }}
    </a>
    <a href="{{ route('admin.wallet') }}" class="btn btn-sm mr-2 mb-1 {{ request()->routeIs('admin.wallet') ? 'btn-primary' : 'btn-light' }}">
        <i class="las la-wallet"></i> {{ __('Billetera') }}
    </a>
    <a href="{{ route('warehouse.index') }}" class="btn btn-sm mr-2 mb-1 {{ request()->routeIs('warehouse.index') ? 'btn-primary' : 'btn-light' }}">
        <i class="las la-warehouse"></i> {{ __('Almacén') }}
    </a>
    <form method="POST" action="{{ route('admin.logout') }}" class="ml-auto mb-1">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="las la-sign-out-alt"></i> {{ __('Cerrar sesión del panel') }}
        </button>
    </form>
</nav>
