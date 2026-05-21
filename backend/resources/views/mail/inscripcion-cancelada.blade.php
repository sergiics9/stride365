@extends('mail.layout')

@section('title', 'Inscripción cancelada')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? 'socio' }},</p>
    <p>Tu inscripción a la actividad <strong>{{ $actividad->titulo }}</strong> ha sido cancelada.</p>
    @if (!empty($motivo))
        <p>Motivo: {{ $motivo }}</p>
    @endif
    <p>Si crees que es un error, contacta con tu club.</p>
@endsection
