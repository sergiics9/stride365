@extends('admin.layouts.app')

@section('title', 'Clubes')
@section('header', 'Clubes')

@section('content')
    <div class="bg-white rounded-lg border border-ink-150 shadow-sm">
        <div class="px-4 py-3 border-b border-ink-150">
            <form method="GET" action="{{ route('admin.clubes.index') }}" class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nombre o email…"
                       class="flex-1 rounded-md border border-ink-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition">
                <button type="submit"
                        class="px-4 py-2 rounded-md bg-brand-500 text-white text-sm font-medium hover:bg-brand-600 transition">
                    Buscar
                </button>
                @if ($q !== '')
                    <a href="{{ route('admin.clubes.index') }}"
                       class="px-4 py-2 rounded-md border border-ink-150 text-sm text-ink-700 hover:bg-ink-50 transition">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-ink-50 text-ink-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium">Club</th>
                        <th class="text-left px-4 py-3 font-medium">Email</th>
                        <th class="text-left px-4 py-3 font-medium">Solicitud</th>
                        <th class="text-left px-4 py-3 font-medium">Estado</th>
                        <th class="text-right px-4 py-3 font-medium">Socios</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($clubes as $club)
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
                                        <p class="text-xs text-ink-400">{{ $club->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $club->email ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $appColors = [
                                        'pending'  => 'bg-warning/15 text-warning',
                                        'approved' => 'bg-success/15 text-success',
                                        'rejected' => 'bg-danger/15 text-danger',
                                    ];
                                    $color = $appColors[$club->application_status] ?? 'bg-ink-100 text-ink-600';
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                    {{ ucfirst($club->application_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $club->active ? 'bg-success/15 text-success' : 'bg-ink-100 text-ink-500' }}">
                                    {{ $club->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-ink-700 font-medium">{{ $club->socios_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.clubes.show', $club) }}"
                                   class="text-sm text-brand-600 hover:text-brand-700 font-medium">Ver →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-ink-400">
                                No se encontraron clubes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-ink-150">
            {{ $clubes->links() }}
        </div>
    </div>
@endsection
