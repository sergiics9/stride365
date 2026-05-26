@extends('admin.layouts.app')

@section('title', 'Solicitudes de club')
@section('header', 'Solicitudes de club')

@section('content')
    @php
        $tabs = [
            'pending'  => 'Pendientes',
            'approved' => 'Aprobadas',
            'rejected' => 'Rechazadas',
        ];
    @endphp

    <div class="bg-white rounded-lg border border-ink-150 shadow-sm">
        <div class="px-4 pt-3 border-b border-ink-150">
            <nav class="flex gap-1 -mb-px">
                @foreach ($tabs as $key => $label)
                    @php $active = $status === $key; @endphp
                    <a href="{{ route('admin.solicitudes.index', ['status' => $key]) }}"
                       class="px-4 py-2.5 text-sm font-medium border-b-2 transition {{ $active ? 'border-brand-500 text-brand-600' : 'border-transparent text-ink-400 hover:text-ink-700' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-ink-50 text-ink-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Club</th>
                        <th class="text-left px-4 py-3 font-medium">Solicitante</th>
                        <th class="text-left px-4 py-3 font-medium">Fecha</th>
                        <th class="text-left px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($solicitudes as $club)
                        <tr class="hover:bg-ink-25 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($club->logo_url)
                                        <img src="{{ $club->logo_url }}" alt="" class="h-8 w-8 rounded object-cover border border-ink-150">
                                    @else
                                        <div class="h-8 w-8 rounded bg-ink-100"></div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-ink-900">{{ $club->nombre }}</p>
                                        <p class="text-xs text-ink-400">{{ $club->email ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink-700">
                                @if ($club->requester)
                                    <p>{{ $club->requester->name }}</p>
                                    <p class="text-xs text-ink-400">{{ $club->requester->email }}</p>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $club->created_at?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'pending'  => 'bg-warning/15 text-warning',
                                        'approved' => 'bg-success/15 text-success',
                                        'rejected' => 'bg-danger/15 text-danger',
                                    ];
                                    $c = $statusColors[$club->application_status] ?? 'bg-ink-100 text-ink-600';
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $c }}">
                                    {{ ucfirst($club->application_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.solicitudes.show', $club) }}"
                                   class="text-sm text-brand-600 hover:text-brand-700 font-medium">Revisar →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-ink-400">
                                No hay solicitudes {{ strtolower($tabs[$status]) }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-ink-150">
            {{ $solicitudes->links() }}
        </div>
    </div>
@endsection
