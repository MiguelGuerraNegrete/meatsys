@php($u = auth()->user())
@php($rol = $u->rol->nombre)
@php($alertasNav = in_array($rol, ['administrador','inventario']) ? \App\Models\AlertaVencimiento::where('estado','pendiente')->count() : 0)
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') · MeatSys</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/meatsys.css') }}">
    @stack('head')
</head>
<body>
<div class="ms-shell">
    <aside class="ms-sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="ms-brand"><span class="ms-brand-mark"><i class="bi bi-shop"></i></span> MeatSys</a>
        <nav class="ms-nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Panel principal</a>

            @if(in_array($rol, ['administrador','cajero']))
                <div class="ms-nav-title">Ventas</div>
                <a href="{{ route('ventas.create') }}" class="{{ request()->routeIs('ventas.create') ? 'active' : '' }}"><i class="bi bi-cart-plus"></i> Nueva venta</a>
                <a href="{{ route('ventas.index') }}" class="{{ request()->routeIs('ventas.index','ventas.show') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Ventas y facturas</a>
                <a href="{{ route('caja.index') }}" class="{{ request()->routeIs('caja.*') ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Caja</a>
                <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Clientes</a>
                <a href="{{ route('pedidos.index') }}" class="{{ request()->routeIs('pedidos.*') ? 'active' : '' }}"><i class="bi bi-bag-check"></i> Pedidos</a>
            @endif

            <div class="ms-nav-title">Inventario</div>
            <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos.*') ? 'active' : '' }}"><i class="bi bi-box-seam"></i> Productos</a>
            @if(in_array($rol, ['administrador','inventario']))
                <a href="{{ route('categorias.index') }}" class="{{ request()->routeIs('categorias.*') ? 'active' : '' }}"><i class="bi bi-tags"></i> Categorías</a>
                <a href="{{ route('inventario.index') }}" class="{{ request()->routeIs('inventario.*') ? 'active' : '' }}"><i class="bi bi-clipboard-data"></i> Niveles de inventario</a>
                <a href="{{ route('compras.index') }}" class="{{ request()->routeIs('compras.*') ? 'active' : '' }}"><i class="bi bi-truck"></i> Compras / ingresos</a>
                <a href="{{ route('alertas.index') }}" class="{{ request()->routeIs('alertas.*') ? 'active' : '' }}"><i class="bi bi-alarm"></i> Alertas de vencimiento
                    @if($alertasNav)<span class="badge rounded-pill text-bg-warning">{{ $alertasNav }}</span>@endif</a>
            @endif

            @if($rol === 'administrador')
                <div class="ms-nav-title">Administración</div>
                <a href="{{ route('proveedores.index') }}" class="{{ request()->routeIs('proveedores.*') ? 'active' : '' }}"><i class="bi bi-building"></i> Proveedores</a>
                <a href="{{ route('pagos.index') }}" class="{{ request()->routeIs('pagos.*') ? 'active' : '' }}"><i class="bi bi-credit-card-2-back"></i> Pagos a proveedores</a>
                <a href="{{ route('reportes.index') }}" class="{{ request()->routeIs('reportes.*') ? 'active' : '' }}"><i class="bi bi-bar-chart-line"></i> Reportes</a>
                <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> Usuarios</a>
            @endif
        </nav>
        <div class="ms-user">
            <div class="text-white fw-semibold">{{ $u->nombre_completo }}</div>
            <div class="small mb-2" style="color:#8d817b">{{ $u->rol->etiqueta() }}</div>
            <button class="btn btn-sm btn-outline-light w-100" data-bs-toggle="modal" data-bs-target="#modalLogout"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
            <a href="{{ route('creditos') }}" class="d-block text-center small mt-2" style="color:#8d817b">Créditos de imágenes</a>
        </div>
    </aside>

    <div class="ms-main">
        <div class="ms-topbar">
            <button class="btn btn-sm btn-light d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menú"><i class="bi bi-list fs-5"></i></button>
            <div class="flex-grow-1">
                <h1 class="ms-page-title">@yield('title', 'Inicio')</h1>
                @hasSection('subtitle')<div class="ms-subtitle">@yield('subtitle')</div>@endif
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-end">@yield('actions')</div>
        </div>
        <main class="ms-content">
            @if(session('ok'))
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i> {{ session('ok') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    @if($errors->count() === 1) {{ $errors->first() }} @else
                        Revise los siguientes datos:<ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<div class="modal fade" id="modalLogout" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="bi bi-box-arrow-right fs-1 text-primary"></i>
                <p class="mt-2 mb-0 fw-semibold">¿Desea cerrar la sesión?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-primary">Sí, salir</button></form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalConfirmar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4"><i class="bi bi-question-circle fs-1 text-primary"></i><p class="mt-2 mb-0" id="confirmarTexto"></p></div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="confirmarOk">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/meatsys.js') }}"></script>
@stack('scripts')
</body>
</html>
