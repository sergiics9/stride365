<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscripción confirmada</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222;">
    <p>Hola {{ $notifiable->nombre ?? 'socio' }},</p>
    <p>Tu inscripción a la actividad <strong>{{ $actividad->titulo }}</strong> ha sido confirmada.</p>
    <p>Fecha: {{ optional($actividad->fecha_inicio)->format('d/m/Y H:i') }}</p>
    @if ($actividad->lugar)
        <p>Lugar: {{ $actividad->lugar }}</p>
    @endif
    <p>¡Nos vemos allí!</p>
    <p style="color: #666; font-size: 0.875rem;">{{ config('app.name') }}</p>
</body>
</html>
