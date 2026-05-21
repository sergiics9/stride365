@extends('mail.layout')

@section('title', 'Factura')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? '' }},</p>
    <p>Adjuntamos la factura correspondiente a tu suscripción.</p>
    <p>Número de factura: <strong>{{ $number }}</strong></p>
    <p>Total: <strong>{{ $total }}</strong></p>
    <p>Gracias por confiar en nosotros.</p>
@endsection
