@extends('layouts.app')
@section('title', 'Créditos de imágenes')
@section('subtitle', 'Fotografías de Wikimedia Commons usadas bajo licencias libres')
@section('content')
<div class="row g-3">
    @foreach($creditos as $archivo => $c)
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <div class="card h-100 overflow-hidden">
                <img src="{{ asset('img/'.$archivo) }}" alt="" style="aspect-ratio:4/3;object-fit:cover;width:100%" loading="lazy">
                <div class="card-body small">
                    <div class="fw-semibold text-truncate" title="{{ $c['titulo'] }}">{{ $c['titulo'] }}</div>
                    <div class="text-muted">{{ $c['autor'] }}</div>
                    <div class="mt-1"><span class="badge badge-soft">{{ $c['licencia'] }}</span> <a href="{{ $c['url'] }}" target="_blank" rel="noopener">Ver original</a></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<p class="small text-muted mt-3">Las fotos se recortaron y redimensionaron para la aplicación. Las fotos que suban los usuarios son responsabilidad de quien las sube.</p>
@endsection
