@php($cls = ['pendiente' => 'text-bg-secondary', 'listo' => 'text-bg-info', 'entregado' => 'text-bg-success', 'cancelado' => 'text-bg-dark'][$estado] ?? 'text-bg-light')
<span class="badge {{ $cls }}">{{ ucfirst($estado) }}</span>
