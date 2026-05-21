@extends('mail.layout')

@section('title', 'Club aprobado')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? 'usuario' }},</p>
    <p>Tu solicitud para crear el club <strong>{{ $club->nombre }}</strong> ha sido <strong>aprobada</strong>.</p>
    <p>Ya puedes gestionar tu club en la plataforma. Si aún no has completado el pago de la cuota de administrador, hazlo desde la sección de suscripciones para activar todos los servicios.</p>
    @if (!empty($clubUrl))
        <p><a href="{{ $clubUrl }}" style="color: #0d6efd;">Ver tu club</a></p>
    @endif
@endsection
