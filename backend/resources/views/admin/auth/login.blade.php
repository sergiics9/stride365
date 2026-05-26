<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Acceso · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: {
                            25:'#f9fafc',50:'#f4f6fa',100:'#eceef3',150:'#dde1e8',200:'#c2c8d2',
                            300:'#8a93a1',400:'#5a6371',500:'#3d4651',600:'#2a313b',700:'#1f242c',
                            800:'#14171c',900:'#0b0d10',950:'#05070a',
                        },
                        brand: { 100:'#e3ecff',300:'#8aaaff',400:'#5c8bff',500:'#2f6bff',600:'#1e54e6',700:'#1843c2' },
                        danger: '#e0364f',
                    },
                    fontFamily: { sans: ['Inter','ui-sans-serif','system-ui','sans-serif'] },
                    borderRadius: { DEFAULT: '0.625rem', sm: '0.4rem', lg: '1rem' },
                },
            },
        };
    </script>
    <style>body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="bg-ink-900 min-h-screen flex items-center justify-center px-4 antialiased">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(47,107,255,0.18),transparent_50%),radial-gradient(circle_at_bottom_right,rgba(47,107,255,0.10),transparent_50%)] pointer-events-none"></div>

    <div class="relative w-full max-w-md">
        <div class="bg-white shadow-xl rounded-lg p-8 border border-ink-150">
            <div class="text-center mb-7">
                <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto mx-auto mb-4">
                <p class="text-sm text-ink-400">Panel super admin</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-danger/10 border border-danger/30 px-4 py-3 text-sm text-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-ink-700 mb-1.5">Email</label>
                    <input id="email" name="email" type="email" required autofocus
                           value="{{ old('email') }}"
                           class="w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-ink-700 mb-1.5">Contraseña</label>
                    <input id="password" name="password" type="password" required
                           class="w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition">
                </div>

                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" value="1"
                           class="rounded border-ink-200 text-brand-500 focus:ring-brand-500">
                    <label for="remember" class="ml-2 text-sm text-ink-500">Recordarme</label>
                </div>

                <button type="submit"
                        class="w-full bg-brand-500 text-white py-2.5 rounded-md text-sm font-semibold hover:bg-brand-600 transition">
                    Entrar
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-ink-300 mt-6">
            © {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
