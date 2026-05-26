<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubUser;
use App\Models\Comunicado;
use Illuminate\Database\Seeder;

/**
 * Crea 6 comunicados por cada club aprobado (60 en total).
 * Contenidos variados: bienvenida, cambios de horario, resultados,
 * asamblea, equipamiento, inscripciones y convocatorias especiales.
 */
class ComunicadoSeeder extends Seeder
{
    public function run(): void
    {
        $approvedClubs = Club::where('application_status', Club::STATUS_APPROVED)
            ->orderBy('id')
            ->get();

        $totalCreated = 0;

        foreach ($approvedClubs as $idx => $club) {
            $admin = ClubUser::where('club_id', $club->id)
                ->where('role', ClubUser::ROLE_ADMIN)
                ->first();

            if (! $admin) {
                continue;
            }

            foreach ($this->comunicados($club, $idx) as $data) {
                Comunicado::create([
                    'club_id'           => $club->id,
                    'user_id'           => $admin->user_id,
                    'titulo'            => $data['titulo'],
                    'contenido'         => $data['contenido'],
                    'fecha_publicacion' => $data['fecha'],
                ]);
                $totalCreated++;
            }
        }

        $this->command?->info("Comunicados creados: $totalCreated");
    }

    /** Devuelve los 6 comunicados para un club dado. */
    private function comunicados(Club $club, int $idx): array
    {
        $nombre = $club->nombre;

        return [
            // 1 — Bienvenida de temporada
            [
                'titulo'    => '¡Bienvenidos a la nueva temporada!',
                'contenido' => "Estimados socios de {$nombre}:\n\n"
                    . "Con la llegada de la nueva temporada, queremos recordaros que el calendario de actividades ya está disponible en el apartado correspondiente de la plataforma. Hemos preparado una programación cargada de retos para todos los niveles, desde rutas familiares hasta itinerarios de alta montaña.\n\n"
                    . "Os animamos a inscribiros cuanto antes, ya que las plazas son limitadas y se asignan por riguroso orden de inscripción.\n\n"
                    . "Recordad que la cuota anual debe estar al corriente para poder participar. ¡Nos vemos en la naturaleza!",
                'fecha' => now()->subMonths(5)->subDays(rand(1, 10)),
            ],

            // 2 — Cambio de horario
            [
                'titulo'    => 'Cambio de horario en los entrenamientos de los martes',
                'contenido' => "Estimados socios:\n\n"
                    . "Os comunicamos que, a partir del próximo martes " . now()->subMonths(4)->format('d/m/Y') . ", los entrenamientos semanales pasarán a realizarse a las 19:30 h en lugar de las 18:30 h habituales.\n\n"
                    . "El cambio se debe a la nueva disponibilidad del monitor y a que la mayoría de los socios encuestados prefería el horario más tardío para poder acudir después del trabajo.\n\n"
                    . "El lugar permanece igual. Si tenéis cualquier duda o sugerencia, no dudéis en contactar con la junta directiva a través del correo del club.\n\n"
                    . "Gracias por vuestra comprensión.",
                'fecha' => now()->subMonths(4)->subDays(rand(1, 5)),
            ],

            // 3 — Resultados de la última salida
            [
                'titulo'    => 'Resumen y resultados — Salida de primavera',
                'contenido' => "¡Gran jornada la del pasado fin de semana!\n\n"
                    . "Completamos la ruta con " . rand(20, 38) . " participantes, un número magnífico que demuestra la vitalidad de " . $nombre . ". El tiempo acompañó, las vistas fueron espectaculares y el grupo demostró una preparación física excelente.\n\n"
                    . "Desde la junta directiva queremos agradecer especialmente la labor de los guías, sin los cuales esta jornada no habría sido posible, y a todos los voluntarios que ayudaron en la organización.\n\n"
                    . "El álbum de fotos ya está disponible en el apartado de la actividad. ¡Hasta la próxima!",
                'fecha' => now()->subMonths(3)->subDays(rand(1, 7)),
            ],

            // 4 — Convocatoria de asamblea
            [
                'titulo'    => 'Convocatoria de Asamblea General Ordinaria',
                'contenido' => "Se convoca a todos los socios de {$nombre} a la Asamblea General Ordinaria, que se celebrará el próximo "
                    . now()->subMonths(2)->addDays(rand(3, 7))->format('l d \d\e F \d\e Y')
                    . " a las 20:00 h en la sede del club.\n\n"
                    . "Orden del día:\n"
                    . "1. Lectura y aprobación del acta de la reunión anterior.\n"
                    . "2. Informe de actividades y resultados del ejercicio.\n"
                    . "3. Informe económico y aprobación de presupuesto.\n"
                    . "4. Renovación parcial de la junta directiva.\n"
                    . "5. Propuestas y ruegos de los socios.\n\n"
                    . "Se ruega puntualidad y asistencia de todos los socios con derecho a voto. Los socios que no puedan asistir pueden delegar su voto en otro socio mediante documento escrito.",
                'fecha' => now()->subMonths(2)->subDays(rand(5, 12)),
            ],

            // 5 — Nuevo equipamiento
            [
                'titulo'    => 'Nuevo equipamiento disponible en préstamo para socios',
                'contenido' => "El club ha adquirido nuevo material disponible en régimen de préstamo para todos los socios que tengan la cuota anual al corriente.\n\n"
                    . "El nuevo equipamiento incluye:\n"
                    . "• 6 mochilas de hidratación (20L) con sistema de agua integrado.\n"
                    . "• 10 pares de bastones de trekking de carbono.\n"
                    . "• 4 botiquines completos de primeros auxilios.\n"
                    . "• 3 brújulas de precisión para orientación.\n\n"
                    . "Para reservar cualquiera de estos materiales, poneos en contacto con la secretaría con al menos 48 horas de antelación a través del correo del club o en los horarios de atención en la sede.\n\n"
                    . "¡Aprovechadlo!",
                'fecha' => now()->subMonths(1)->subDays(rand(10, 20)),
            ],

            // 6 — Apertura de inscripciones / actualidad
            [
                'titulo'    => 'Apertura del plazo de inscripción — Actividades de verano',
                'contenido' => "Ya está abierto el plazo de inscripción para todas las actividades programadas para el periodo estival.\n\n"
                    . "Este año hemos ampliado el número de plazas en las rutas más demandadas y hemos añadido varias actividades nocturnas por petición de los socios. También incorporamos por primera vez una ruta adaptada para socios con movilidad reducida.\n\n"
                    . "Recordad que los socios con más de un año de antigüedad en el club tienen prioridad de inscripción durante los primeros siete días. Pasado ese plazo, las plazas restantes se abren al público general.\n\n"
                    . "Consultad el calendario completo en el apartado de Actividades y no os quedéis sin plaza. ¡El verano promete!",
                'fecha' => now()->subDays(rand(5, 25)),
            ],
        ];
    }
}
