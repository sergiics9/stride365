<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Crea 15 clubes:
 *   - 10 aprobados + activos (con admin y socios en tabla club_user)
 *   - 3 pendientes de aprobación (solicitudes)
 *   - 2 rechazados (con motivo)
 *
 * Estados de membresía incluidos: active, grace, cancelled, pending.
 */
class ClubSeeder extends Seeder
{
    public function run(): void
    {
        $super = User::where('email', 'superadmin@stride.local')->firstOrFail();
        $now   = now();

        // ─────────────────────────────────────────────────────────────────────
        // 10 CLUBES APROBADOS
        // Cada entrada: nombre, slug, descripción, dirección, teléfono, email,
        //   admin email, socios [[email, status?, is_guide?]]
        // ─────────────────────────────────────────────────────────────────────
        $approved = [
            [
                'nombre'      => 'Trail Running Sierra de Guadarrama',
                'slug'        => 'trail-sierra-guadarrama',
                'descripcion' => 'Club dedicado al trail running en los senderos de la Sierra de Guadarrama. Organizamos salidas semanales, entrenamientos por grupos de nivel y carreras de montaña durante todo el año.',
                'direccion'   => 'Calle del Pinar 4, Cercedilla, Madrid',
                'telefono'    => '91 234 5678',
                'email'       => 'contacto@trailguadarrama.es',
                'admin'       => 'admin1@stride.local',
                'socios'      => [
                    ['email' => 'socio1@stride.local', 'is_guide' => true],
                    ['email' => 'socio2@stride.local'],
                    ['email' => 'socio3@stride.local', 'is_guide' => true],
                    ['email' => 'socio4@stride.local', 'status' => ClubUser::STATUS_GRACE],
                    ['email' => 'socio5@stride.local', 'status' => ClubUser::STATUS_CANCELLED],
                ],
            ],
            [
                'nombre'      => 'Club Ciclista Barceloneta',
                'slug'        => 'ciclista-barceloneta',
                'descripcion' => 'Ciclismo de montaña y carretera por el Parque Natural de Collserola y los alrededores de Barcelona. Salidas todos los fines de semana y entrenamiento de rodillo en invierno.',
                'direccion'   => 'Passeig Marítim 45, Barcelona',
                'telefono'    => '93 456 7890',
                'email'       => 'info@ciclista-barceloneta.cat',
                'admin'       => 'admin2@stride.local',
                'socios'      => [
                    ['email' => 'socio2@stride.local'],
                    ['email' => 'socio5@stride.local'],
                    ['email' => 'socio6@stride.local', 'is_guide' => true],
                    ['email' => 'socio7@stride.local'],
                    ['email' => 'socio8@stride.local'],
                ],
            ],
            [
                'nombre'      => 'Club Montañero Sierra Nevada',
                'slug'        => 'montanero-sierra-nevada',
                'descripcion' => 'Senderismo, alpinismo y esquí de travesía en Sierra Nevada y las cordilleras béticas granadinas. Expediciones anuales a los Alpes y el Atlas marroquí.',
                'direccion'   => 'Calle Alhambra 8, Granada',
                'telefono'    => '95 876 5432',
                'email'       => 'club@montanerogranada.es',
                'admin'       => 'admin3@stride.local',
                'socios'      => [
                    ['email' => 'socio3@stride.local'],
                    ['email' => 'socio9@stride.local', 'is_guide' => true],
                    ['email' => 'socio10@stride.local'],
                    ['email' => 'socio11@stride.local', 'status' => ClubUser::STATUS_INACTIVE],
                ],
            ],
            [
                'nombre'      => 'Running Costasol Málaga',
                'slug'        => 'running-costasol-malaga',
                'descripcion' => 'Club de atletismo popular y running urbano en la Costa del Sol. Entrenamientos matutinos en el Paseo Marítimo y participación en carreras populares de la provincia.',
                'direccion'   => 'Paseo del Parque 3, Málaga',
                'telefono'    => '95 234 1234',
                'email'       => 'hola@runningcostasol.com',
                'admin'       => 'admin4@stride.local',
                'socios'      => [
                    ['email' => 'socio1@stride.local'],
                    ['email' => 'socio4@stride.local', 'is_guide' => true],
                    ['email' => 'socio8@stride.local'],
                    ['email' => 'socio12@stride.local', 'is_guide' => true],
                    ['email' => 'socio13@stride.local'],
                ],
            ],
            [
                'nombre'      => 'Senderistas del Camino de Santiago',
                'slug'        => 'senderistas-camino-santiago',
                'descripcion' => 'Club gallego especializado en rutas de larga distancia por el Camino de Santiago, las Rías Baixas y el litoral atlántico. Hospedaje concertado en albergues privados.',
                'direccion'   => 'Rúa do Franco 7, Santiago de Compostela',
                'telefono'    => '98 156 7890',
                'email'       => 'peregrinos@senderistascamino.gal',
                'admin'       => 'admin5@stride.local',
                'socios'      => [
                    ['email' => 'socio5@stride.local', 'is_guide' => true],
                    ['email' => 'socio10@stride.local'],
                    ['email' => 'socio13@stride.local'],
                    ['email' => 'socio14@stride.local'],
                ],
            ],
            [
                'nombre'      => 'Triatlón Valencia Club',
                'slug'        => 'triatlon-valencia',
                'descripcion' => 'Club de triatlón con entrenamientos de natación en la piscina municipal, ciclismo por la Albufera y running por el Jardín del Turia. Sede en el Puerto Deportivo de Valencia.',
                'direccion'   => 'Marina Real Juan Carlos I, Valencia',
                'telefono'    => '96 345 6789',
                'email'       => 'tri@triatlon-valencia.es',
                'admin'       => 'admin6@stride.local',
                'socios'      => [
                    ['email' => 'socio2@stride.local'],
                    ['email' => 'socio6@stride.local'],
                    ['email' => 'socio11@stride.local', 'is_guide' => true],
                    ['email' => 'socio14@stride.local'],
                ],
            ],
            [
                'nombre'      => 'Club Escalada Pirenaica',
                'slug'        => 'escalada-pirenaica',
                'descripcion' => 'Escalada deportiva, alpinismo y barrancos en el Pirineo Aragonés. Cursos de iniciación cada primavera y otoño. Expedición anual al Himalaya para socios avanzados.',
                'direccion'   => 'Plaza Mayor 1, Jaca, Huesca',
                'telefono'    => '97 435 7654',
                'email'       => 'cumbre@escalada-pirenaica.com',
                'admin'       => 'admin7@stride.local',
                'socios'      => [
                    ['email' => 'socio3@stride.local'],
                    ['email' => 'socio7@stride.local', 'is_guide' => true],
                    ['email' => 'socio9@stride.local', 'is_guide' => true],
                    ['email' => 'socio12@stride.local'],
                ],
            ],
            [
                'nombre'      => 'Club Kayak Cantábrico',
                'slug'        => 'kayak-cantabrico',
                'descripcion' => 'Piragüismo de mar y aguas bravas en la costa cantábrica y los ríos asturianos y cántabros. Kayak de travesía, surf ski y descenso de cañones.',
                'direccion'   => 'Puerto Chico s/n, Santander',
                'telefono'    => '94 223 4567',
                'email'       => 'info@kayakcantabrico.com',
                'admin'       => 'admin8@stride.local',
                'socios'      => [
                    ['email' => 'socio4@stride.local', 'is_guide' => true],
                    ['email' => 'socio6@stride.local'],
                    ['email' => 'socio8@stride.local'],
                    ['email' => 'socio10@stride.local'],
                ],
            ],
            [
                'nombre'      => 'Club Ciclismo Extremeño',
                'slug'        => 'ciclismo-extremeno',
                'descripcion' => 'Rutas cicloturistas por la Vía de la Plata y las dehesas extremeñas. Marchas populares, cicloturismo familiar y bicicleta de montaña. Club federado en la RFEC.',
                'direccion'   => 'Calle Trujillo 14, Mérida, Badajoz',
                'telefono'    => '92 430 1234',
                'email'       => 'pedales@ciclistasextremenos.es',
                'admin'       => 'admin9@stride.local',
                'socios'      => [
                    ['email' => 'socio5@stride.local', 'is_guide' => true],
                    ['email' => 'socio7@stride.local'],
                    ['email' => 'socio11@stride.local'],
                    ['email' => 'socio13@stride.local'],
                ],
            ],
            [
                'nombre'      => 'Club Atletismo Hispalense',
                'slug'        => 'atletismo-hispalense',
                'descripcion' => 'Atletismo de pista y campo a través en Sevilla. Secciones de velocidad, fondo, lanzamientos y marcha atlética. Campeones regionales de categoría absoluta en los últimos tres años.',
                'direccion'   => 'Avenida de la Constitución 5, Sevilla',
                'telefono'    => '95 456 7891',
                'email'       => 'atletismo@hispalense.es',
                'admin'       => 'admin10@stride.local',
                'socios'      => [
                    ['email' => 'socio1@stride.local'],
                    ['email' => 'socio2@stride.local'],
                    ['email' => 'socio8@stride.local'],
                    ['email' => 'socio14@stride.local', 'is_guide' => true],
                ],
            ],
        ];

        foreach ($approved as $data) {
            $admin     = User::where('email', $data['admin'])->firstOrFail();
            $approvedAt = $now->copy()->subMonths(rand(3, 14));

            $club = Club::create([
                'nombre'             => $data['nombre'],
                'slug'               => $data['slug'],
                'descripcion'        => $data['descripcion'],
                'direccion'          => $data['direccion'],
                'telefono'           => $data['telefono'],
                'email'              => $data['email'],
                'active'             => true,
                'application_status' => Club::STATUS_APPROVED,
                'requested_by'       => $admin->id,
                'approved_by'        => $super->id,
                'approved_at'        => $approvedAt,
            ]);

            // Admin membership
            ClubUser::create([
                'user_id'             => $admin->id,
                'club_id'             => $club->id,
                'role'                => ClubUser::ROLE_ADMIN,
                'is_guide'            => false,
                'status'              => ClubUser::STATUS_ACTIVE,
                'subscription_name'  => ClubUser::buildSubscriptionName('club', $club->id),
                'subscribed_at'      => $approvedAt,
                'current_period_end' => $approvedAt->copy()->addYear(),
                'joined_at'          => $approvedAt->toDateString(),
            ]);

            // Socios memberships
            foreach ($data['socios'] as $s) {
                $socio    = User::where('email', $s['email'])->firstOrFail();
                $isGuide  = $s['is_guide'] ?? false;
                $status   = $s['status']   ?? ClubUser::STATUS_ACTIVE;
                $joinedAt = $now->copy()->subMonths(rand(1, 11));

                $periodEnd  = $joinedAt->copy()->addYear();
                $endsAt     = null;

                if ($status === ClubUser::STATUS_GRACE) {
                    // Canceló hace poco; aún en gracia
                    $periodEnd = $now->copy()->subDays(rand(5, 20));
                    $endsAt    = $now->copy()->addDays(rand(5, 20));
                } elseif ($status === ClubUser::STATUS_CANCELLED || $status === ClubUser::STATUS_INACTIVE) {
                    $periodEnd = $now->copy()->subDays(rand(30, 90));
                    $endsAt    = $now->copy()->subDays(rand(10, 30));
                }

                ClubUser::create([
                    'user_id'             => $socio->id,
                    'club_id'             => $club->id,
                    'role'                => ClubUser::ROLE_SOCIO,
                    'is_guide'            => $isGuide,
                    'status'              => $status,
                    'subscription_name'  => ClubUser::buildSubscriptionName('socio', $club->id),
                    'subscribed_at'      => $joinedAt,
                    'current_period_end' => $periodEnd,
                    'ends_at'            => $endsAt,
                    'joined_at'          => $joinedAt->toDateString(),
                    'left_at'            => in_array($status, [ClubUser::STATUS_CANCELLED, ClubUser::STATUS_INACTIVE])
                                            ? $endsAt?->toDateString()
                                            : null,
                    'left_reason'        => in_array($status, [ClubUser::STATUS_CANCELLED, ClubUser::STATUS_INACTIVE])
                                            ? 'Baja voluntaria del socio.'
                                            : null,
                ]);
            }
        }

        // ─────────────────────────────────────────────────────────────────────
        // 3 CLUBES PENDIENTES (solicitudes sin aprobar)
        // ─────────────────────────────────────────────────────────────────────
        $pending = [
            [
                'nombre'      => 'Club BTT Costera Valenciana',
                'slug'        => 'btt-costera-valenciana',
                'descripcion' => 'Club de Bicicleta de Montaña orientado a los parajes naturales de la Comunitat Valenciana: Tinença de Benifassà, Penyagolosa y la Serra Mariola.',
                'email'       => 'btt@bttvaldena.es',
                'requester'   => 'libre1@stride.local',
            ],
            [
                'nombre'      => 'Asociación Deportiva Madrileña',
                'slug'        => 'asociacion-deportiva-madrilena',
                'descripcion' => 'Asociación multideporte orientada a fomentar la actividad física en el área metropolitana de Madrid. Secciones de running, ciclismo, senderismo y natación.',
                'email'       => 'info@admadrile.es',
                'requester'   => 'libre2@stride.local',
            ],
            [
                'nombre'      => 'Club Surf Atlántico Huelva',
                'slug'        => 'surf-atlantico-huelva',
                'descripcion' => 'Surf, paddle surf y bodyboard en las playas del Atlántico onubense. Escuela federada con instructores certificados por la Real Federación Española de Surf.',
                'email'       => 'olas@surfatlantico.es',
                'requester'   => 'libre1@stride.local',
            ],
        ];

        foreach ($pending as $data) {
            $requester = User::where('email', $data['requester'])->firstOrFail();

            Club::create([
                'nombre'             => $data['nombre'],
                'slug'               => $data['slug'],
                'descripcion'        => $data['descripcion'],
                'email'              => $data['email'],
                'active'             => false,
                'application_status' => Club::STATUS_PENDING,
                'requested_by'       => $requester->id,
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // 2 CLUBES RECHAZADOS (con motivo de rechazo)
        // ─────────────────────────────────────────────────────────────────────
        $rejected = [
            [
                'nombre'           => 'Club Montaña Ibérica',
                'slug'             => 'montana-iberica',
                'descripcion'      => 'Senderismo y senderismo de largo recorrido por el Sistema Ibérico castellano.',
                'email'            => 'info@montanaiberica.es',
                'requester'        => 'socio12@stride.local',
                'rejection_reason' => 'La documentación aportada está incompleta. Falta el certificado de inscripción en el registro autonómico de asociaciones deportivas y el acta fundacional firmada por los socios fundadores. Subsana la situación y vuelve a presentar la solicitud.',
            ],
            [
                'nombre'           => 'Deportes Alpinos Pirineos',
                'slug'             => 'deportes-alpinos-pirineos',
                'descripcion'      => 'Escalada, alpinismo y esquí de alta montaña en el Pirineo aragonés y catalán.',
                'email'            => 'alpinos@dap.es',
                'requester'        => 'socio7@stride.local',
                'rejection_reason' => 'Se han detectado datos inconsistentes en la solicitud: el número de socios fundadores indicado (12) no coincide con el número de firmas adjuntas (7). Además, el email de contacto no responde. Corrige las irregularidades antes de presentar una nueva solicitud.',
            ],
        ];

        foreach ($rejected as $data) {
            $requester = User::where('email', $data['requester'])->firstOrFail();

            Club::create([
                'nombre'             => $data['nombre'],
                'slug'               => $data['slug'],
                'descripcion'        => $data['descripcion'],
                'email'              => $data['email'],
                'active'             => false,
                'application_status' => Club::STATUS_REJECTED,
                'requested_by'       => $requester->id,
                'approved_by'        => $super->id,
                'approved_at'        => $now->copy()->subMonths(rand(1, 3)),
                'rejection_reason'   => $data['rejection_reason'],
            ]);
        }

        $this->command?->info('Clubes creados: 10 aprobados, 3 pendientes, 2 rechazados');
    }
}
