@extends('admin.layouts.app')

@section('title', 'Usuarios')
@section('header', 'Usuarios')

@section('content')
    <div class="bg-white rounded-lg border border-ink-150 shadow-sm">
        <div class="px-4 py-3 border-b border-ink-150">
            <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nombre, apellido o email…"
                       class="flex-1 rounded-md border border-ink-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition">
                <button type="submit"
                        class="px-4 py-2 rounded-md bg-brand-500 text-white text-sm font-medium hover:bg-brand-600 transition">
                    Buscar
                </button>
                @if ($q !== '')
                    <a href="{{ route('admin.usuarios.index') }}"
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
                        <th class="text-left px-4 py-3 font-medium">Usuario</th>
                        <th class="text-left px-4 py-3 font-medium">Email</th>
                        <th class="text-left px-4 py-3 font-medium">Teléfono</th>
                        <th class="text-left px-4 py-3 font-medium">Rol</th>
                        <th class="text-right px-4 py-3 font-medium">Membresías</th>
                        <th class="text-left px-4 py-3 font-medium">Alta</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($usuarios as $u)
                        <tr class="hover:bg-ink-25 transition">
                            <td class="px-4 py-3">
                                <p class="font-medium text-ink-900">{{ trim(($u->nombre ?? '') . ' ' . ($u->apellido ?? '')) ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-ink-700">{{ $u->email }}</td>
                            <td class="px-4 py-3 text-ink-700">{{ $u->telefono ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php $roleName = $u->roles->first()?->name; @endphp
                                @if ($roleName)
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $roleName === 'super_admin' ? 'bg-brand-100 text-brand-700' : 'bg-ink-100 text-ink-600' }}">
                                        {{ $roleName }}
                                    </span>
                                @else
                                    <span class="text-ink-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-ink-700 font-medium">{{ $u->memberships_count }}</td>
                            <td class="px-4 py-3 text-ink-700">
                                {{ optional($u->fecha_alta)->format('d/m/Y') ?? optional($u->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.usuarios.show', $u) }}"
                                   class="text-sm text-brand-600 hover:text-brand-700 font-medium">Ver →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-ink-400">
                                No se encontraron usuarios.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-ink-150">
            {{ $usuarios->links() }}
        </div>
    </div>
@endsection
