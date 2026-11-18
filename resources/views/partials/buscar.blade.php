<form class="d-flex gap-2 mb-3 flex-wrap" method="GET">
    <div class="input-group" style="max-width:380px">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ $placeholder ?? 'Buscar…' }}">
    </div>
    {{ $slot ?? '' }}
    <button class="btn btn-outline-secondary">Buscar</button>
    @if(request()->query())<a href="{{ url()->current() }}" class="btn btn-link">Limpiar</a>@endif
</form>
