<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\PublicacionFeed;
use Illuminate\Database\Seeder;

/**
 * Crea publicaciones en el feed a partir de:
 *   1. Actividades personales (club_id = null, publicada_en_feed = true)  → ~25
 *   2. Actividades de club finalizadas con publicada_en_feed = true       → ~20
 *
 * Total esperado: ~45 publicaciones → 3 páginas de paginado (15/página).
 */
class PublicacionFeedSeeder extends Seeder
{
    public function run(): void
    {
        $total = 0;

        // ── Actividades personales ────────────────────────────────────────
        $personal = Actividad::whereNull('club_id')
            ->where('publicada_en_feed', true)
            ->where('estado', Actividad::ESTADO_FINALIZADA)
            ->get();

        foreach ($personal as $act) {
            if (PublicacionFeed::where('actividad_id', $act->id)->exists()) {
                continue;
            }

            PublicacionFeed::create([
                'user_id'           => $act->user_id,
                'actividad_id'      => $act->id,
                'titulo'            => $act->titulo,
                'resumen'           => $this->resumen($act),
                'contenido'         => $this->contenido($act),
                'tipo'              => 'actividad',
                'visibilidad'       => 'publica',
                'fecha_publicacion' => $act->finalizada_at ?? $act->fecha_inicio,
                'estado'            => 'activo',
            ]);

            $total++;
        }

        // ── Actividades de club finalizadas y publicadas ──────────────────
        $deClub = Actividad::whereNotNull('club_id')
            ->where('publicada_en_feed', true)
            ->where('estado', Actividad::ESTADO_FINALIZADA)
            ->get();

        foreach ($deClub as $act) {
            if (PublicacionFeed::where('actividad_id', $act->id)->exists()) {
                continue;
            }

            PublicacionFeed::create([
                'user_id'           => $act->user_id,
                'actividad_id'      => $act->id,
                'titulo'            => $act->titulo,
                'resumen'           => $this->resumen($act),
                'contenido'         => $this->contenido($act),
                'tipo'              => 'actividad',
                'visibilidad'       => 'publica',
                'fecha_publicacion' => $act->finalizada_at ?? $act->fecha_inicio,
                'estado'            => 'activo',
            ]);

            $total++;
        }

        $this->command?->info("Publicaciones de feed creadas: $total");
    }

    private function resumen(Actividad $act): string
    {
        $km       = $act->distancia ? round((float) $act->distancia, 1) . ' km' : '— km';
        $desnivel = $act->desnivel_positivo_m ? $act->desnivel_positivo_m . 'm D+' : '';
        $duracion = $act->duracion_segundos
            ? gmdate('H:i', $act->duracion_segundos) . 'h'
            : '';
        $ritmo = $act->ritmo_segundos_por_km
            ? floor($act->ritmo_segundos_por_km / 60)
              . ':'
              . str_pad((string) ($act->ritmo_segundos_por_km % 60), 2, '0', STR_PAD_LEFT)
              . ' min/km'
            : '';

        $partes = array_filter([$km, $desnivel, $duracion, $ritmo]);

        return implode(' | ', $partes);
    }

    private function contenido(Actividad $act): string
    {
        $km      = $act->distancia ? round((float) $act->distancia, 1) : '?';
        $desnivel = $act->desnivel_positivo_m ?? 0;
        $duracion = $act->duracion_segundos ? gmdate('H:i', $act->duracion_segundos) : '—';
        $ritmo   = $act->ritmo_segundos_por_km
            ? floor($act->ritmo_segundos_por_km / 60)
              . ':'
              . str_pad((string) ($act->ritmo_segundos_por_km % 60), 2, '0', STR_PAD_LEFT)
            : '—';
        $tipoTexto = $act->modo_creacion === Actividad::MODO_IMPORTADA
            ? 'importada desde GPX'
            : 'grabada en vivo con la app';

        $frases = [
            "¡Gran jornada completada! {$km} km disfrutando del entorno. Ritmo medio: {$ritmo} min/km. El cuerpo lo agradece.",
            "Sesión {$tipoTexto}. {$km} km y {$desnivel}m de desnivel después, el esfuerzo merece la pena. Tiempo total: {$duracion}h.",
            "Ruta espectacular. El track habla por sí solo: {$km} km, {$desnivel}m D+ y unas vistas que no tienen precio.",
            "Entrenamiento registrado. Distancia: {$km} km | Desnivel: {$desnivel}m | Tiempo: {$duracion}h. Progresando semana a semana.",
            "Esta ruta ya era un clásico pendiente. Hoy por fin la he completado: {$km} km y {$desnivel}m de D+. ¡Merece la pena cada metro!",
            "Actividad {$tipoTexto}. {$km} km a {$ritmo} min/km de media. Las piernas aguantan, la cabeza también. A por la siguiente.",
        ];

        // Selección determinista según el id para que sea reproducible
        return $frases[$act->id % count($frases)];
    }
}
