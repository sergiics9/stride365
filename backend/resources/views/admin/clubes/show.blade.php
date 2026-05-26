@extends('admin.layouts.app')

@section('title', $club->nombre)
@section('header', $club->nombre)

@section('actions')
    <a href="{{ route('admin.clubes.index') }}"
       class="px-3 py-2 rounded-md border border-ink-150 text-sm text-ink-700 hover:bg-ink-50 transition">
        ← Volver
    </a>

    @if ($club->application_status === \App\Models\Club::STATUS_APPROVED)
        <form method="POST" action="{{ route('admin.clubes.toggle-active', $club) }}">
            @csrf
            <button type="submit"
                    class="px-3 py-2 rounded-md text-sm font-medium text-white transition {{ $club->active ? 'bg-danger hover:opacity-90' : 'bg-success hover:opacity-90' }}">
                {{ $club->active ? 'Desactivar' : 'Activar' }}
            </button>
        </form>
    @endif
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-ink-150 shadow-sm p-6">
            <div class="flex items-start gap-4 mb-6">
                @if ($club->logo_url)
                    <img src="{{ $club->logo_url }}" alt="" class="h-16 w-16 rounded object-cover border border-ink-150">
                @else
                    <div class="h-16 w-16 rounded bg-ink-100"></div>
                @endif
                <div>
                    <h2 class="text-lg font-semibold text-ink-900">{{ $club->nombre }}</h2>
                    <p class="text-sm text-ink-400">{{ $club->slug }}</p>
                </div>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Email</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $club->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Teléfono</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $club->telefono ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Dirección</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $club->direccion ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Descripción</dt>
                    <dd class="text-ink-900 mt-0.5 whitespace-pre-line">{{ $club->descripcion ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-ink-900 mb-3">Estado</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Solicitud</dt>
                        <dd class="font-medium text-ink-900">{{ ucfirst($club->application_status) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Activo</dt>
                        <dd class="font-medium text-ink-900">{{ $club->active ? 'Sí' : 'No' }}</dd>
                    </div>
                    @if ($club->approved_at)
                        <div class="flex justify-between">
                            <dt class="text-ink-400">Aprobado</dt>
                            <dd class="font-medium text-ink-900">{{ $club->approved_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    @if ($club->rejection_reason)
                        <div>
                            <dt class="text-ink-400 mt-2">Motivo rechazo</dt>
                            <dd class="font-medium text-danger mt-1">{{ $club->rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-ink-900 mb-3">Resumen</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Admins</dt>
                        <dd class="font-medium text-ink-900">{{ $adminsCount }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Socios activos</dt>
                        <dd class="font-medium text-ink-900">{{ $sociosCount }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Actividades</dt>
                        <dd class="font-medium text-ink-900">{{ $actividadesCount }}</dd>
                    </div>
                </dl>
            </div>

            @if ($club->requester)
                <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-ink-900 mb-3">Solicitante</h3>
                    <p class="text-sm text-ink-900">{{ $club->requester->name }}</p>
                    <p class="text-xs text-ink-400">{{ $club->requester->email }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
