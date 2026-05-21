@extends('mail.layout')

@section('title', 'Inscripción confirmada')

@section('content')
    <p>Hola {{ $notifiable->nombre ?? 'socio' }},</p>

    <p>Tu inscripción a la siguiente actividad ha sido confirmada:</p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600; width: 35%; border-radius: 4px 0 0 0;">Actividad</td>
            <td style="padding: 10px 12px; background: #f0f4ff; border-radius: 0 4px 0 0;">{{ $actividad->titulo }}</td>
        </tr>
        @if ($actividad->club)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600;">Club</td>
            <td style="padding: 10px 12px;">{{ $actividad->club->nombre }}</td>
        </tr>
        @endif
        @if ($actividad->fecha_inicio)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600;">Fecha y hora</td>
            <td style="padding: 10px 12px; background: #fafafa;">{{ $actividad->fecha_inicio->format('d/m/Y \a \l\a\s H:i') }}</td>
        </tr>
        @endif
        @if ($actividad->lugar)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600;">Lugar</td>
            <td style="padding: 10px 12px;">{{ $actividad->lugar }}</td>
        </tr>
        @endif
        @if ($actividad->punto_encuentro)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600;">Punto de encuentro</td>
            <td style="padding: 10px 12px; background: #fafafa;">{{ $actividad->punto_encuentro }}</td>
        </tr>
        @endif
        @if ($actividad->modalidad)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600;">Modalidad</td>
            <td style="padding: 10px 12px;">{{ ucfirst($actividad->modalidad) }}</td>
        </tr>
        @endif
        @if ($actividad->dificultad)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600;">Dificultad</td>
            <td style="padding: 10px 12px; background: #fafafa;">{{ ucfirst($actividad->dificultad) }}</td>
        </tr>
        @endif
        @if ($actividad->distancia)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600;">Distancia</td>
            <td style="padding: 10px 12px;">{{ number_format((float) $actividad->distancia, 1, ',', '.') }} km</td>
        </tr>
        @endif
        @if ($actividad->material_necesario)
        <tr>
            <td style="padding: 10px 12px; background: #f8f9fa; font-weight: 600; border-radius: 0 0 0 4px;">Material necesario</td>
            <td style="padding: 10px 12px; background: #fafafa; border-radius: 0 0 4px 0;">{{ $actividad->material_necesario }}</td>
        </tr>
        @endif
    </table>

    <p>¡Nos vemos allí!</p>
@endsection
