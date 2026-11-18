<!doctype html>
<html><head><meta charset="utf-8">@include('pdf._estilos')</head>
<body>
    <h1>{{ config('meatsys.negocio.nombre') }} — {{ $titulo }}</h1>
    <div class="muted">
        @if($tipo !== 'inventario')Periodo: {{ $filtros['desde']->format('d/m/Y') }} al {{ $filtros['hasta']->format('d/m/Y') }} · @endif
        Generado el {{ now()->format('d/m/Y h:i a') }} por {{ auth()->user()->nombre_completo }}
    </div>
    <div style="margin-top:12px">@include('reportes._tabla')</div>
</body></html>
