@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    @php
        $cards = [
            ['label' => 'Clubes activos',          'value' => $stats['clubes_activos'],         'accent' => 'bg-brand-500'],
            ['label' => 'Solicitudes pendientes', 'value' => $stats['solicitudes_pendientes'], 'accent' => 'bg-warning', 'link' => route('admin.solicitudes.index')],
            ['label' => 'Usuarios totales',        'value' => $stats['usuarios_totales'],        'accent' => 'bg-ink-700'],
            ['label' => 'Actividades totales',     'value' => $stats['actividades_totales'],     'accent' => 'bg-ink-400'],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($cards as $card)
            @php $tag = isset($card['link']) ? 'a' : 'div'; @endphp
            <{{ $tag }} @if(isset($card['link'])) href="{{ $card['link'] }}" @endif
                class="block bg-white rounded-lg border border-ink-150 shadow-sm p-5 hover:border-ink-200 transition relative overflow-hidden">
                <span class="absolute left-0 top-0 bottom-0 w-1 {{ $card['accent'] }}"></span>
                <p class="text-xs uppercase tracking-wide text-ink-400 font-medium">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-semibold text-ink-900">{{ number_format($card['value']) }}</p>
            </{{ $tag }}>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
            <h2 class="text-base font-semibold text-ink-900">Accesos rápidos</h2>
            <p class="text-sm text-ink-400 mt-0.5">Tareas habituales del panel.</p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <a href="{{ route('admin.solicitudes.index') }}"
                   class="inline-flex items-center justify-between px-4 py-3 rounded-md border border-ink-150 text-sm text-ink-900 hover:border-brand-500 hover:bg-brand-100/20 transition">
                    <span class="font-medium">Revisar solicitudes</span>
                    <span class="text-ink-300">→</span>
                </a>
                <a href="{{ route('admin.clubes.index') }}"
                   class="inline-flex items-center justify-between px-4 py-3 rounded-md border border-ink-150 text-sm text-ink-900 hover:border-brand-500 hover:bg-brand-100/20 transition">
                    <span class="font-medium">Gestionar clubes</span>
                    <span class="text-ink-300">→</span>
                </a>
                <a href="{{ route('admin.usuarios.index') }}"
                   class="inline-flex items-center justify-between px-4 py-3 rounded-md border border-ink-150 text-sm text-ink-900 hover:border-brand-500 hover:bg-brand-100/20 transition">
                    <span class="font-medium">Ver usuarios</span>
                    <span class="text-ink-300">→</span>
                </a>
            </div>
        </div>

        <div class="bg-ink-900 rounded-lg p-6 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(47,107,255,0.30),transparent_55%)] pointer-events-none"></div>
            <div class="relative">
                <p class="text-xs uppercase tracking-wide text-ink-300">Sesión actual</p>
                <p class="mt-2 text-lg font-semibold">{{ auth()->user()->name ?? auth()->user()->email }}</p>
                <p class="text-sm text-ink-300">{{ auth()->user()->email }}</p>
                <p class="mt-4 text-xs text-ink-400">
                    Rol activo: <span class="text-brand-300 font-medium">super_admin</span>
                </p>
            </div>
        </div>
    </div>
@endsection
