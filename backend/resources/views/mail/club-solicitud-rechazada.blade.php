<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitud de club</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222;">
    <p>Hola {{ $notifiable->nombre ?? 'usuario' }},</p>
    <p>Lamentamos informarte de que tu solicitud para crear el club <strong>{{ $club->nombre }}</strong> ha sido <strong>rechazada</strong>.</p>
    <p><strong>Motivo indicado por el administrador:</strong></p>
    <p style="white-space: pre-wrap; background: #f8f9fa; padding: 12px; border-radius: 6px;">{{ $reason }}</p>
    <p>Si tienes dudas, puedes contactar con el equipo de la plataforma.</p>
    <p style="color: #666; font-size: 0.875rem;">{{ config('app.name') }}</p>
</body>
</html>
