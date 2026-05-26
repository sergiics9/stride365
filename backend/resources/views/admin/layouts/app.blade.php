<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>@yield('title', 'Panel') · {{ config('app.name') }}</title>
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
                            25:  '#f9fafc',
                            50:  '#f4f6fa',
                            100: '#eceef3',
                            150: '#dde1e8',
                            200: '#c2c8d2',
                            300: '#8a93a1',
                            400: '#5a6371',
                            500: '#3d4651',
                            600: '#2a313b',
                            700: '#1f242c',
                            800: '#14171c',
                            900: '#0b0d10',
                            950: '#05070a',
                        },
                        brand: {
                            100: '#e3ecff',
                            300: '#8aaaff',
                            400: '#5c8bff',
                            500: '#2f6bff',
                            600: '#1e54e6',
                            700: '#1843c2',
                        },
                        success: '#16a06b',
                        danger:  '#e0364f',
                        warning: '#e6a23c',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    borderRadius: {
                        DEFAULT: '0.625rem',
                        sm: '0.4rem',
                        lg: '1rem',
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-ink-25 text-ink-900 min-h-screen antialiased">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-ink-900 text-ink-100 flex flex-col border-r border-ink-800">
            <div class="px-6 py-5 border-b border-ink-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto">
                    <span class="block text-[11px] text-ink-300 mt-0.5 uppercase tracking-wider">Super admin</span>
                </a>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                @php
                    $navItems = [
                        ['route' => 'admin.dashboard',          'label' => 'Dashboard',   'match' => ['admin.dashboard']],
                        ['route' => 'admin.solicitudes.index',  'label' => 'Solicitudes', 'match' => ['admin.solicitudes.*']],
                        ['route' => 'admin.clubes.index',       'label' => 'Clubes',      'match' => ['admin.clubes.*']],
                        ['route' => 'admin.usuarios.index',     'label' => 'Usuarios',    'match' => ['admin.usuarios.*']],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs(...$item['match']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="block px-3 py-2 rounded-md transition {{ $active ? 'bg-brand-500 text-white' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="px-4 py-4 border-t border-ink-800 text-xs">
                <p class="text-ink-100 truncate font-medium">{{ auth()->user()->name ?? auth()->user()->email }}</p>
                <p class="text-ink-400 truncate">{{ auth()->user()->email }}</p>
                <form method="POST" action="{{ route('admin.logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full text-left text-ink-300 hover:text-white transition">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <header class="bg-white border-b border-ink-150">
                <div class="px-8 py-4 flex items-center justify-between gap-4">
                    <h1 class="text-xl font-semibold text-ink-900">@yield('header', 'Dashboard')</h1>
                    @hasSection('actions')
                        <div class="flex items-center gap-2">@yield('actions')</div>
                    @endif
                </div>
            </header>

            <div class="px-8 py-6">
                @if (session('status'))
                    <div class="mb-4 rounded-md bg-success/10 border border-success/30 px-4 py-3 text-sm text-success">
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-md bg-danger/10 border border-danger/30 px-4 py-3 text-sm text-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
