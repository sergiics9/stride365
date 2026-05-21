<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', \App\Support\BrandLogo::name())</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #222; margin: 0; padding: 24px 16px;">
    @if ($logo = \App\Support\BrandLogo::dataUri())
        <p style="text-align: center; margin: 0 0 24px;">
            <img src="{{ $logo }}" alt="{{ \App\Support\BrandLogo::name() }}" width="160" style="max-width: 160px; height: auto; display: inline-block;">
        </p>
    @endif

    @yield('content')

    <p style="color: #666; font-size: 0.875rem; margin-top: 32px;">{{ \App\Support\BrandLogo::name() }}</p>
</body>
</html>
