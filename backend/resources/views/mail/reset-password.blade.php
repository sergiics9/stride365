@extends('mail.layout')

@section('title', 'Restablecer contraseña')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? 'usuario' }},</p>
    <p>Has recibido este correo porque solicitaste restablecer la contraseña de tu cuenta.</p>
    <p style="margin: 24px 0;">
        <a href="{{ $url }}" style="display: inline-block; padding: 12px 24px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            Restablecer contraseña
        </a>
    </p>
    <p>Este enlace caduca en {{ $expire }} minutos.</p>
    <p>Si no solicitaste el cambio, puedes ignorar este mensaje.</p>
@endsection
