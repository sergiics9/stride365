@extends('admin.layouts.app')

@section('title', 'Solicitud · ' . $club->nombre)
@section('header', 'Solicitud de club')

@section('actions')
    <a href="{{ route('admin.solicitudes.index', ['status' => $club->application_status]) }}"
       class="px-3 py-2 rounded-md border border-ink-150 text-sm text-ink-700 hover:bg-ink-50 transition">
        ← Volver
    </a>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
                <div class="flex items-start gap-4 mb-6">
                    @if ($club->logo_url)
                        <img src="{{ $club->logo_url }}" alt="" class="h-16 w-16 rounded object-cover border border-ink-150">
                    @else
                        <div class="h-16 w-16 rounded bg-ink-100"></div>
                    @endif
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-ink-900">{{ $club->nombre }}</h2>
                        <p class="text-sm text-ink-400">{{ $club->slug }}</p>
                    </div>
                    @php
                        $statusColors = [
                            'pending'  => 'bg-warning/15 text-warning',
                            'approved' => 'bg-success/15 text-success',
                            'rejected' => 'bg-danger/15 text-danger',
                        ];
                        $c = $statusColors[$club->application_status] ?? 'bg-ink-100 text-ink-600';
                    @endphp
                    <span class="px-2.5 py-1 rounded text-xs font-medium {{ $c }}">
                        {{ ucfirst($club->application_status) }}
                    </span>
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

            @if ($club->application_status === \App\Models\Club::STATUS_PENDING)
                <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-ink-900 mb-4">Resolver solicitud</h3>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <form method="POST" action="{{ route('admin.solicitudes.approve', $club) }}"
                              onsubmit="return confirm('¿Aprobar este club? Se activará y se notificará al solicitante.');"
                              class="flex-1">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-2.5 rounded-md bg-success text-white text-sm font-medium hover:opacity-90 transition">
                                Aprobar club
                            </button>
                        </form>

                        <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                                class="flex-1 px-4 py-2.5 rounded-md border border-danger text-danger text-sm font-medium hover:bg-danger/5 transition">
                            Rechazar…
                        </button>
                    </div>

                    <form id="reject-form" method="POST" action="{{ route('admin.solicitudes.reject', $club) }}" class="hidden mt-4">
                        @csrf
                        <label for="reason" class="block text-sm font-medium text-ink-700 mb-1">Motivo del rechazo</label>
                        <textarea id="reason" name="reason" rows="3" required maxlength="1000"
                                  placeholder="Indica el motivo del rechazo…"
                                  class="w-full rounded-md border border-ink-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none"></textarea>
                        @error('reason')
                            <p class="text-xs text-danger mt-1">{{ $message }}</p>
                        @enderror
                        <div class="mt-3 flex justify-end">
                            <button type="submit"
                                    class="px-4 py-2 rounded-md bg-danger text-white text-sm font-medium hover:opacity-90 transition">
                                Confirmar rechazo
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if ($club->requester)
                <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-ink-900 mb-3">Solicitante</h3>
                    <p class="text-sm text-ink-900">{{ $club->requester->name }}</p>
                    <p class="text-xs text-ink-400">{{ $club->requester->email }}</p>
                </div>
            @endif

            <div class="bg-white rounded-lg border border-ink-150 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-ink-900 mb-3">Histórico</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-400">Solicitada</dt>
                        <dd class="font-medium text-ink-900">{{ $club->created_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if ($club->approved_at)
                        <div class="flex justify-between">
                            <dt class="text-ink-400">Resuelta</dt>
                            <dd class="font-medium text-ink-900">{{ $club->approved_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($club->approver)
                        <div class="flex justify-between">
                            <dt class="text-ink-400">Por</dt>
                            <dd class="font-medium text-ink-900">{{ $club->approver->name }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($club->rejection_reason)
                    <div class="mt-4 pt-4 border-t border-ink-100">
                        <dt class="text-xs uppercase tracking-wide text-ink-400 mb-1">Motivo del rechazo</dt>
                        <dd class="text-sm text-ink-900 whitespace-pre-line">{{ $club->rejection_reason }}</dd>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
