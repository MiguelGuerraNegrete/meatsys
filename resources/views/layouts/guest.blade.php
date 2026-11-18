<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · MeatSys</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/meatsys.css') }}">
</head>
<body>
<div class="login-split">
    <div class="login-foto" style="background-image:url('{{ asset('img/portada/login.jpg') }}')" role="img" aria-label="Carnicero cortando carne en un mercado">
        <div class="credito">Foto: Adrian Snood, <a href="https://creativecommons.org/licenses/by/2.0/" target="_blank" rel="noopener">CC BY 2.0</a></div>
        <div class="lema">
            <h2>Carne fresca, cuentas claras.</h2>
            <p>Inventario por lotes, ventas por peso, vencimientos y caja diaria en un solo lugar.</p>
        </div>
    </div>
    <div class="login-form">
        <div class="inner">
            <div class="d-flex align-items-center gap-2 mb-4">
                <span class="ms-brand-mark text-white" style="width:46px;height:46px;font-size:1.35rem;border-radius:12px"><i class="bi bi-shop"></i></span>
                <div>
                    <div class="h4 fw-bold mb-0">MeatSys</div>
                    <div class="text-muted small">Gestión de carnicería</div>
                </div>
            </div>
            <h1 class="h5 fw-bold mb-3">@yield('title')</h1>
            @if(session('ok'))<div class="alert alert-success py-2 small">{{ session('ok') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </div>
</div>
</body>
</html>
