@php($cls = ['pendiente' => 'text-bg-danger', 'parcial' => 'text-bg-warning', 'pagado' => 'text-bg-success'][$estado] ?? 'text-bg-secondary')
<span class="badge {{ $cls }}">{{ ucfirst($estado) }}</span>
