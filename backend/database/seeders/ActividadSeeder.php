<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Club;
use App\Models\ClubUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Crea actividades para todos los clubes aprobados y actividades personales.
 *
 * Por cada club aprobado (10 clubes):
 *   - 3 programadas (futuras, con track dibujado y cupo parcialmente cubierto)
 *   - 1 en_curso   (empezó hoy, socios inscritos)
 *   - 3 finalizadas (pasadas, con métricas completas y track; 2 publicadas en feed)
 *   - 1 cancelada  (con motivo)
 *   Total: 8 × 10 = 80 actividades de club
 *
 * Actividades personales (sin club, para el feed): 25 actividades finalizadas.
 *
 * También crea:
 *   - inscripciones (tabla inscripciones, sin timestamps)
 *   - asignaciones de guías (tabla actividad_guia)
 */
class ActividadSeeder extends Seeder
{
    // ─── 6 tracks GeoJSON reales de rutas españolas ───────────────────────
    // Formato LineString: [[lng, lat], ...]

    private const TRACK_GUADARRAMA = [
        [-3.950, 40.720], [-3.947, 40.724], [-3.943, 40.729], [-3.940, 40.733],
        [-3.937, 40.737], [-3.934, 40.742], [-3.931, 40.748], [-3.929, 40.754],
        [-3.926, 40.759], [-3.924, 40.765], [-3.922, 40.771], [-3.921, 40.777],
        [-3.920, 40.783], [-3.921, 40.789], [-3.923, 40.795], [-3.925, 40.800],
        [-3.928, 40.804], [-3.931, 40.808], [-3.934, 40.811], [-3.937, 40.814],
    ];

    private const TRACK_MONTSERRAT = [
        [1.820, 41.583], [1.823, 41.587], [1.826, 41.592], [1.830, 41.596],
        [1.834, 41.600], [1.837, 41.604], [1.840, 41.609], [1.843, 41.613],
        [1.847, 41.617], [1.850, 41.621], [1.854, 41.625], [1.857, 41.629],
        [1.861, 41.633], [1.865, 41.636], [1.869, 41.639], [1.873, 41.642],
        [1.877, 41.645], [1.880, 41.647], [1.884, 41.649], [1.888, 41.651],
    ];

    private const TRACK_SIERRA_NEVADA = [
        [-3.398, 37.053], [-3.392, 37.058], [-3.386, 37.063], [-3.380, 37.068],
        [-3.374, 37.073], [-3.368, 37.078], [-3.362, 37.083], [-3.356, 37.088],
        [-3.350, 37.093], [-3.344, 37.097], [-3.338, 37.101], [-3.332, 37.105],
        [-3.326, 37.109], [-3.320, 37.112], [-3.314, 37.115], [-3.308, 37.118],
        [-3.302, 37.120], [-3.296, 37.122], [-3.290, 37.124], [-3.284, 37.126],
    ];

    private const TRACK_PICOS = [
        [-4.820, 43.195], [-4.815, 43.199], [-4.810, 43.204], [-4.805, 43.208],
        [-4.800, 43.212], [-4.796, 43.217], [-4.792, 43.221], [-4.788, 43.225],
        [-4.784, 43.229], [-4.780, 43.233], [-4.776, 43.237], [-4.772, 43.240],
        [-4.768, 43.243], [-4.764, 43.246], [-4.760, 43.248], [-4.756, 43.250],
        [-4.752, 43.252], [-4.748, 43.253], [-4.744, 43.254], [-4.740, 43.255],
    ];

    private const TRACK_CAMINO_SANTIAGO = [
        [-8.544, 42.875], [-8.538, 42.878], [-8.532, 42.880], [-8.526, 42.882],
        [-8.520, 42.884], [-8.514, 42.886], [-8.508, 42.888], [-8.502, 42.890],
        [-8.496, 42.892], [-8.490, 42.894], [-8.484, 42.896], [-8.478, 42.898],
        [-8.472, 42.899], [-8.466, 42.900], [-8.460, 42.901], [-8.454, 42.902],
        [-8.448, 42.903], [-8.442, 42.904], [-8.436, 42.905], [-8.430, 42.906],
    ];

    private const TRACK_TRAMUNTANA = [
        [2.640, 39.780], [2.646, 39.784], [2.652, 39.789], [2.658, 39.794],
        [2.664, 39.798], [2.670, 39.802], [2.676, 39.806], [2.682, 39.810],
        [2.688, 39.814], [2.694, 39.817], [2.700, 39.820], [2.706, 39.823],
        [2.712, 39.826], [2.718, 39.828], [2.724, 39.830], [2.730, 39.832],
        [2.736, 39.833], [2.742, 39.834], [2.748, 39.835], [2.754, 39.836],
    ];

    private function allTracks(): array
    {
        return [
            self::TRACK_GUADARRAMA,
            self::TRACK_MONTSERRAT,
            self::TRACK_SIERRA_NEVADA,
            self::TRACK_PICOS,
            self::TRACK_CAMINO_SANTIAGO,
            self::TRACK_TRAMUNTANA,
        ];
    }

    private function geojson(array $coords): array
    {
        return ['type' => 'LineString', 'coordinates' => $coords];
    }

    // ─── Datos de actividades según modalidad del club ────────────────────

    private const MODALITIES = [
        'trail running', 'ciclismo MTB', 'senderismo',
        'running', 'senderismo de largo recorrido', 'triatlón',
        'escalada', 'piragüismo', 'ciclismo de carretera', 'atletismo',
    ];

    private const ROUTE_NAMES = [
        'La Cima del Águila', 'Collado Ventoso', 'Valle del Silencio', 'Cresta Norte',
        'Barranco del Agua', 'Cumbre del Espino', 'Paso de los Lobos', 'La Fuente Fría',
        'Alto del Perdón', 'Sendero de la Bruma', 'Cañada Real', 'Pico del Tremedal',
        'Los Caños del Río', 'Sierra Alta', 'El Mirador Escondido', 'Ruta de los Álamos',
        'Desfiladero Sur', 'La Majada Alta', 'Peñas Blancas', 'Laguna Fría',
        'Cañón del Torbes', 'Hayedo de Montejo', 'La Dehesa Grande', 'Pinar del Rey',
        'El Collado Viejo', 'Páramo Herido', 'El Portillo Nuevo', 'Bosque Mágico',
        'Puerto de la Cruz', 'Glaciar del Nordeste', 'Las Hoces del Río', 'El Cotorredondo',
    ];

    private const CANCEL_REASONS = [
        'Previsión de lluvia intensa y posibles tormentas eléctricas en la zona. La seguridad de los participantes es lo primero.',
        'El número de inscritos no alcanzó el mínimo necesario para realizar la actividad con las garantías requeridas.',
        'Obras en el tramo principal de la ruta que impiden el paso seguro. La actividad se reprogramará cuando estén concluidas.',
        'Problemas logísticos de última hora con el transporte concertado. Nos disculpamos y la reagendaremos próximamente.',
    ];

    public function run(): void
    {
        $tracks        = $this->allTracks();
        $approvedClubs = Club::where('application_status', Club::STATUS_APPROVED)
            ->orderBy('id')
            ->get();

        foreach ($approvedClubs as $idx => $club) {
            $track    = $tracks[$idx % count($tracks)];
            $modalidad = self::MODALITIES[$idx % count(self::MODALITIES)];

            $adminMembership = ClubUser::where('club_id', $club->id)
                ->where('role', ClubUser::ROLE_ADMIN)
                ->first();
            $creatorId = $adminMembership?->user_id ?? 1;

            $activeSocios = ClubUser::where('club_id', $club->id)
                ->where('role', ClubUser::ROLE_SOCIO)
                ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
                ->get();

            $guideIds = $activeSocios->where('is_guide', true)->pluck('user_id')->values();

            $this->seedClubActividades($club, $idx, $creatorId, $track, $modalidad, $activeSocios, $guideIds);
        }

        $this->command?->info('Actividades de club creadas: ' . ($approvedClubs->count() * 8));

        $this->seedPersonalActividades();
    }

    // ─── Actividades de club (8 por club) ────────────────────────────────

    private function seedClubActividades(
        Club $club,
        int $idx,
        int $creatorId,
        array $track,
        string $modalidad,
        $activeSocios,
        $guideIds
    ): void {
        $lugar = $club->direccion ?? 'Sede del club';

        // ── 3 PROGRAMADAS ────────────────────────────────────────────────
        $programadas = [
            [
                'titulo'             => "Salida grupal — " . self::ROUTE_NAMES[$idx % count(self::ROUTE_NAMES)],
                'descripcion'        => "Salida grupal de $modalidad. Ruta circular de dificultad media. Imprescindible llevar agua (mínimo 1,5L), snacks energéticos y ropa de abrigo por si cambia el tiempo.",
                'fecha_inicio'       => now()->addDays(rand(4, 10)),
                'lugar'              => $lugar,
                'punto_encuentro'    => 'Aparcamiento del punto de inicio a las 8:00h. Confirmar asistencia con 24h de antelación.',
                'material_necesario' => 'Calzado técnico, mochila pequeña, agua, bastones opcionales, protección solar.',
                'dificultad'         => 'Media',
                'cupo_maximo'        => rand(16, 28),
                'costo'              => 0,
                'modo_creacion'      => Actividad::MODO_DIBUJADA,
                'track_geojson'      => $this->geojson($track),
                'inscribir'          => rand(3, 5),
                'assign_guide'       => true,
            ],
            [
                'titulo'             => "Ruta técnica — " . self::ROUTE_NAMES[($idx + 5) % count(self::ROUTE_NAMES)],
                'descripcion'        => "Itinerario exigente de nivel avanzado para $modalidad. Requiere experiencia previa. La inscripción es obligatoria y el cupo es muy limitado.",
                'fecha_inicio'       => now()->addDays(rand(11, 25)),
                'lugar'              => $lugar,
                'punto_encuentro'    => 'Bar de la Fuente a las 7:30h en punto.',
                'material_necesario' => 'Equipo completo de montaña, botiquín personal, mapa topográfico 1:25.000.',
                'dificultad'         => 'Alta',
                'cupo_maximo'        => rand(10, 18),
                'costo'              => rand(5, 20),
                'modo_creacion'      => Actividad::MODO_DIBUJADA,
                'track_geojson'      => $this->geojson(array_reverse($track)),
                'inscribir'          => rand(2, 4),
                'assign_guide'       => true,
            ],
            [
                'titulo'             => "Excursión familiar — " . now()->addMonths(2)->format('F') . " " . now()->addMonths(2)->year,
                'descripcion'        => "Actividad abierta a familias y principiantes absolutos. Sin dificultad técnica. ¡Anímate aunque sea tu primera salida con el club!",
                'fecha_inicio'       => now()->addDays(rand(30, 55)),
                'lugar'              => $lugar,
                'punto_encuentro'    => 'Aparcamiento del parque a las 9:00h.',
                'material_necesario' => 'Calzado cómodo de campo, protección solar, agua y comida para el mediodía.',
                'dificultad'         => 'Baja',
                'cupo_maximo'        => 40,
                'costo'              => 0,
                'modo_creacion'      => Actividad::MODO_DIBUJADA,
                'track_geojson'      => $this->geojson(array_slice($track, 0, 12)),
                'inscribir'          => rand(2, 3),
                'assign_guide'       => false,
            ],
        ];

        foreach ($programadas as $d) {
            $act = Actividad::create([
                'club_id'            => $club->id,
                'user_id'            => $creatorId,
                'titulo'             => $d['titulo'],
                'descripcion'        => $d['descripcion'],
                'fecha_inicio'       => $d['fecha_inicio'],
                'lugar'              => $d['lugar'],
                'punto_encuentro'    => $d['punto_encuentro'],
                'material_necesario' => $d['material_necesario'],
                'modalidad'          => $modalidad,
                'dificultad'         => $d['dificultad'],
                'cupo_maximo'        => $d['cupo_maximo'],
                'costo'              => $d['costo'],
                'estado'             => Actividad::ESTADO_PROGRAMADA,
                'modo_creacion'      => $d['modo_creacion'],
                'track_geojson'      => $d['track_geojson'],
            ]);

            if ($d['assign_guide'] && $guideIds->isNotEmpty()) {
                $this->assignGuide($act->id, $guideIds->first());
            }

            $this->inscribe($act->id, $activeSocios->pluck('user_id')->take($d['inscribir'])->all());
        }

        // ── 1 EN CURSO ──────────────────────────────────────────────────
        $enCurso = Actividad::create([
            'club_id'            => $club->id,
            'user_id'            => $creatorId,
            'titulo'             => "En marcha ahora — " . self::ROUTE_NAMES[($idx + 12) % count(self::ROUTE_NAMES)],
            'descripcion'        => "Actividad en curso. El grupo salió esta mañana y está completando la ruta en este momento. Seguimiento en tiempo real disponible.",
            'fecha_inicio'       => now()->subHours(rand(2, 4)),
            'lugar'              => $lugar,
            'modalidad'          => $modalidad,
            'dificultad'         => 'Media',
            'cupo_maximo'        => 20,
            'costo'              => 0,
            'estado'             => Actividad::ESTADO_EN_CURSO,
            'modo_creacion'      => Actividad::MODO_DIBUJADA,
            'track_geojson'      => $this->geojson($track),
        ]);

        if ($guideIds->isNotEmpty()) {
            $this->assignGuide($enCurso->id, $guideIds->first());
        }
        $this->inscribe($enCurso->id, $activeSocios->pluck('user_id')->take(rand(4, 5))->all());

        // ── 3 FINALIZADAS ───────────────────────────────────────────────
        $finalizadas = [
            [
                'titulo'           => self::ROUTE_NAMES[($idx + 18) % count(self::ROUTE_NAMES)] . ' — Completada',
                'descripcion'      => "Gran jornada de $modalidad completada con éxito. El tiempo acompañó y el grupo llegó al completo.",
                'fecha_inicio'     => now()->subDays(rand(7, 20)),
                'distancia'        => rand(8, 22) + (rand(0, 9) / 10),
                'desnivel'         => rand(350, 1400),
                'duracion'         => rand(7200, 18000),
                'ritmo'            => rand(320, 580),
                'fc_media'         => rand(130, 158),
                'fc_max'           => rand(163, 182),
                'modo'             => Actividad::MODO_DIBUJADA,
                'publicada'        => true,
                'inscribir'        => rand(4, min(5, $activeSocios->count())),
                'assign_guide'     => true,
            ],
            [
                'titulo'           => self::ROUTE_NAMES[($idx + 22) % count(self::ROUTE_NAMES)] . ' — ' . now()->subMonths(1)->format('M Y'),
                'descripcion'      => "Salida mensual de $modalidad. Participación excelente. El desnivel acumulado superó todas las expectativas.",
                'fecha_inicio'     => now()->subMonths(1)->subDays(rand(2, 10)),
                'distancia'        => rand(15, 35) + (rand(0, 9) / 10),
                'desnivel'         => rand(800, 2200),
                'duracion'         => rand(14400, 28800),
                'ritmo'            => rand(350, 600),
                'fc_media'         => rand(140, 163),
                'fc_max'           => rand(168, 188),
                'modo'             => Actividad::MODO_IMPORTADA,
                'publicada'        => true,
                'inscribir'        => rand(3, min(4, $activeSocios->count())),
                'assign_guide'     => true,
            ],
            [
                'titulo'           => "Apertura de temporada — " . now()->subMonths(4)->format('M Y'),
                'descripcion'      => "Actividad inaugural de temporada. Rutas pensadas para retomar el ritmo y encontrarse con los compañeros.",
                'fecha_inicio'     => now()->subMonths(4),
                'distancia'        => rand(5, 14) + (rand(0, 9) / 10),
                'desnivel'         => rand(150, 600),
                'duracion'         => rand(3600, 10800),
                'ritmo'            => rand(280, 420),
                'fc_media'         => rand(118, 142),
                'fc_max'           => rand(152, 172),
                'modo'             => Actividad::MODO_DIBUJADA,
                'publicada'        => false,
                'inscribir'        => rand(3, min(5, $activeSocios->count())),
                'assign_guide'     => false,
            ],
        ];

        foreach ($finalizadas as $d) {
            $finAt = (clone $d['fecha_inicio'])->addSeconds($d['duracion']);
            $act = Actividad::create([
                'club_id'               => $club->id,
                'user_id'               => $creatorId,
                'titulo'                => $d['titulo'],
                'descripcion'           => $d['descripcion'],
                'fecha_inicio'          => $d['fecha_inicio'],
                'fecha_fin'             => $finAt,
                'lugar'                 => $lugar,
                'modalidad'             => $modalidad,
                'dificultad'            => 'Media',
                'cupo_maximo'           => rand(15, 25),
                'costo'                 => 0,
                'estado'                => Actividad::ESTADO_FINALIZADA,
                'modo_creacion'         => $d['modo'],
                'track_geojson'         => $this->geojson($track),
                'distancia'             => $d['distancia'],
                'desnivel_positivo_m'   => $d['desnivel'],
                'duracion_segundos'     => $d['duracion'],
                'ritmo_segundos_por_km' => $d['ritmo'],
                'pulsaciones_media'     => $d['fc_media'],
                'pulsaciones_max'       => $d['fc_max'],
                'finalizada_at'         => $finAt,
                'publicada_en_feed'     => $d['publicada'],
            ]);

            if ($d['assign_guide'] && $guideIds->isNotEmpty()) {
                $this->assignGuide($act->id, $guideIds->first());
            }
            $this->inscribe($act->id, $activeSocios->pluck('user_id')->take($d['inscribir'])->all());
        }

        // ── 1 CANCELADA ─────────────────────────────────────────────────
        Actividad::create([
            'club_id'             => $club->id,
            'user_id'             => $creatorId,
            'titulo'              => self::ROUTE_NAMES[($idx + 28) % count(self::ROUTE_NAMES)] . ' — CANCELADA',
            'descripcion'         => "Esta actividad fue cancelada. Pedimos disculpas por los inconvenientes.",
            'fecha_inicio'        => now()->addDays(rand(3, 12)),
            'lugar'               => $lugar,
            'modalidad'           => $modalidad,
            'dificultad'          => 'Media',
            'cupo_maximo'         => 15,
            'costo'               => 0,
            'estado'              => Actividad::ESTADO_CANCELADA,
            'modo_creacion'       => Actividad::MODO_DIBUJADA,
            'motivo_cancelacion'  => self::CANCEL_REASONS[$idx % count(self::CANCEL_REASONS)],
        ]);
    }

    // ─── Actividades personales (para el feed) ────────────────────────────

    private function seedPersonalActividades(): void
    {
        $tracks = $this->allTracks();

        $titles = [
            'Trail matutino — Vuelta al castillo', 'Rodaje largo 25 km fondo',
            'Subida al pico del barrio', 'Ruta de los miradores escondidos',
            'Vuelta a la sierra en bici', 'Largo de fondo Z2 — 3 horas',
            'Sprint interval training en pista', 'Ruta de las cascadas olvidadas',
            'Etapa del Camino Norte', 'Bajada técnica al río',
            'Circuito de montaña 360°', 'Ruta costera al amanecer',
            'Travesía del valle central', 'Fondo aeróbico matinal — 90 min',
            'Vuelta ciclista al embalse', 'Trail nocturno luna llena',
            'Gran fondo especial de primavera', 'Ruta de los puertos altos',
            'Maratón de entrenamiento completo', 'Cierre de temporada — Ruta magna',
            'Sesión de recuperación activa', 'GPX importado — Rutas clásicas',
            'Salida con el grupo de amigos', 'Fin de semana en la montaña — Día 1',
            'Cima del Tremedal en solitario',
        ];

        // Coge socios y usuarios libres para distribuir las actividades personales
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'usuario'))
            ->orderBy('id')
            ->take(16)
            ->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No se encontraron usuarios para actividades personales.');
            return;
        }

        foreach ($titles as $i => $titulo) {
            $user     = $users[$i % $users->count()];
            $track    = $tracks[$i % count($tracks)];
            $daysAgo  = rand(2, 180);
            $duracion = rand(2400, 21600);
            $modoCreacion = ($i % 5 === 0)
                ? Actividad::MODO_IMPORTADA
                : Actividad::MODO_VIVO;
            $modalities = ['trail running', 'ciclismo', 'senderismo', 'running'];

            Actividad::create([
                'club_id'               => null,
                'user_id'               => $user->id,
                'titulo'                => $titulo,
                'descripcion'           => 'Actividad personal registrada con la app Stride365.',
                'fecha_inicio'          => now()->subDays($daysAgo)->setHour(rand(6, 9))->setMinute(0),
                'fecha_fin'             => now()->subDays($daysAgo)->setHour(rand(10, 14))->setMinute(30),
                'lugar'                 => 'Zona habitual de entrenamiento',
                'modalidad'             => $modalities[$i % count($modalities)],
                'distancia'             => rand(5, 42) + (rand(0, 9) / 10),
                'desnivel_positivo_m'   => rand(80, 1900),
                'duracion_segundos'     => $duracion,
                'ritmo_segundos_por_km' => rand(278, 610),
                'pulsaciones_media'     => rand(122, 165),
                'pulsaciones_max'       => rand(158, 192),
                'estado'                => Actividad::ESTADO_FINALIZADA,
                'modo_creacion'         => $modoCreacion,
                'track_geojson'         => $this->geojson($track),
                'finalizada_at'         => now()->subDays($daysAgo)->setHour(rand(10, 14)),
                'publicada_en_feed'     => true,
            ]);
        }

        $this->command?->info('Actividades personales creadas: ' . count($titles));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function inscribe(int $actividadId, array $userIds): void
    {
        foreach (array_unique($userIds) as $userId) {
            DB::table('inscripciones')->insertOrIgnore([
                'user_id'           => $userId,
                'actividad_id'      => $actividadId,
                'fecha_inscripcion' => now()->subDays(rand(1, 20)),
            ]);
        }
    }

    private function assignGuide(int $actividadId, int $userId): void
    {
        DB::table('actividad_guia')->insertOrIgnore([
            'actividad_id' => $actividadId,
            'user_id'      => $userId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}
