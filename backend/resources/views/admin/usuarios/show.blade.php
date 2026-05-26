@extends('admin.layouts.app')

@section('title', $usuario->name ?: $usuario->email)
@section('header', $usuario->name ?: $usuario->email)

@section('actions')
    <a href="{{ route('admin.usuarios.index') }}"
       class="px-3 py-2 rounded-md border border-ink-150 text-sm text-ink-700 hover:bg-ink-50 transition">
        ← Volver
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-ink-150 shadow-sm p-6">
            <div class="flex items-start gap-4 mb-6">
                @if ($usuario->foto_url)
                    <img src="{{ $usuario->foto_url }}" alt="" class="h-16 w-16 rounded-full object-cover border border-ink-150">
                @else
                    <div class="h-16 w-16 rounded-full bg-ink-100 flex items-center justify-center text-ink-400 font-semibold">
                        {{ strtoupper(substr($usuario->nombre ?? $usuario->email, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-lg font-semibold text-ink-900">{{ $usuario->name ?: '—' }}</h2>
                    <p class="text-sm text-ink-400">{{ $usuario->email }}</p>
                </div>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Teléfono</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $usuario->telefono ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Sexo</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $usuario->sexo ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Fecha nacimiento</dt>
                    <dd class="text-ink-900 mt-0.5">{{ optional($usuario->fecha_nacimiento)->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Fecha alta</dt>
                    <dd class="text-ink-900 mt-0.5">{{ optional($usuario->fecha_alta)->format('d/m/Y') ?? optional($usuario->created_at)->format('d/m/Y') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Dirección</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $usuario->direccion ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Estado</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $usuario->estado ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-ink-400">Rol</dt>
                    <dd class="text-ink-900 mt-0.5">{{ $usuario->roles->pluck('name')->join(', ') ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">Clubes</h3>
            @if ($usuario->memberships->isEmpty())
                <p class="text-sm text-ink-400">Sin membresías.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($usuario->memberships as $m)
                        <li class="text-sm pb-3 border-b border-ink-100 last:border-0 last:pb-0">
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ $m->club ? route('admin.clubes.show', $m->club) : '#' }}"
                                   class="font-medium text-ink-900 hover:text-brand-600 transition">
                                    {{ $m->club->nombre ?? '—' }}
                                </a>
                                @php
                                    $statusColors = [
                                        'active'    => 'bg-success/15 text-success',
                                        'grace'     => 'bg-warning/15 text-warning',
                                        'pending'   => 'bg-ink-100 text-ink-600',
                                        'cancelled' => 'bg-danger/15 text-danger',
                                        'inactive'  => 'bg-ink-100 text-ink-500',
                                    ];
                                    $c = $statusColors[$m->status] ?? 'bg-ink-100 text-ink-600';
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $c }}">{{ $m->status }}</span>
                            </div>
                            <p class="text-xs text-ink-400 mt-0.5">
                                {{ $m->role }}{{ $m->is_guide ? ' · guía' : '' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
