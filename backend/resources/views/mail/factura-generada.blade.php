<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222;">
    <p>Hola {{ $notifiable->nombre ?? '' }},</p>
    <p>Adjuntamos la factura correspondiente a tu suscripción.</p>
    <p>Número de factura: <strong>{{ $number }}</strong></p>
    <p>Total: <strong>{{ $total }}</strong></p>
    <p>Gracias por confiar en nosotros.</p>
    <p style="color: #666; font-size: 0.875rem;">{{ config('app.name') }}</p>
</body>
</html>
