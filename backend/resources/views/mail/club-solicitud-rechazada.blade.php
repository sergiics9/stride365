@extends('mail.layout')

@section('title', 'Solicitud de club')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? 'usuario' }},</p>
    <p>Lamentamos informarte de que tu solicitud para crear el club <strong>{{ $club->nombre }}</strong> ha sido <strong>rechazada</strong>.</p>
    <p><strong>Motivo indicado por el administrador:</strong></p>
    <p style="white-space: pre-wrap; background: #f8f9fa; padding: 12px; border-radius: 6px;">{{ $reason }}</p>
    <p>Si tienes dudas, puedes contactar con el equipo de la plataforma.</p>
@endsection
