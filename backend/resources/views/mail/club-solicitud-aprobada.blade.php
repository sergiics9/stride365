<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Club aprobado</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222;">
    <p>Hola {{ $notifiable->nombre ?? 'usuario' }},</p>
    <p>Tu solicitud para crear el club <strong>{{ $club->nombre }}</strong> ha sido <strong>aprobada</strong>.</p>
    <p>Ya puedes gestionar tu club en la plataforma. Si aún no has completado el pago de la cuota de administrador, hazlo desde la sección de suscripciones para activar todos los servicios.</p>
    @if (!empty($clubUrl))
        <p><a href="{{ $clubUrl }}" style="color: #0d6efd;">Ver tu club</a></p>
    @endif
    <p style="color: #666; font-size: 0.875rem;">{{ config('app.name') }}</p>
</body>
</html>
