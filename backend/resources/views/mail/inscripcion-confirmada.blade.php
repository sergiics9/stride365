@extends('mail.layout')

@section('title', 'Inscripción confirmada')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? 'socio' }},</p>
    <p>Tu inscripción a la actividad <strong>{{ $actividad->titulo }}</strong> ha sido confirmada.</p>
    <p>Fecha: {{ optional($actividad->fecha_inicio)->format('d/m/Y H:i') }}</p>
    @if ($actividad->lugar)
        <p>Lugar: {{ $actividad->lugar }}</p>
    @endif
    <p>¡Nos vemos allí!</p>
@endsection
