@extends('mail.layout')

@section('title', $esAdmin ? 'Suscripción activa' : 'Bienvenido al club')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? 'usuario' }},</p>

    @if ($esAdmin)
        <p>Tu suscripción de administrador para el club <strong>{{ $club->nombre }}</strong> está activa.</p>
        <p>Ya puedes gestionar todas las funciones del club desde la plataforma.</p>
    @else
        <p>¡Ya eres socio/a de <strong>{{ $club->nombre }}</strong>!</p>
        <p>Tu suscripción está activa. Puedes ver las actividades y toda la información del club desde la plataforma.</p>
    @endif
@endsection
