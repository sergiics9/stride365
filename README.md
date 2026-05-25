# Stride365

Plataforma web SaaS para la gestión integral de clubes deportivos. Permite crear y administrar clubs, gestionar socios, planificar actividades con mapas interactivos, publicar en un feed social, enviar comunicados y cobrar cuotas anuales mediante Stripe.

---

## Tabla de contenidos

- [Descripción](#descripción)
- [Stack tecnológico](#stack-tecnológico)
- [Estructura del repositorio](#estructura-del-repositorio)
- [Requisitos previos](#requisitos-previos)
- [Instalación](#instalación)
  - [Backend (Laravel)](#backend-laravel)
  - [Frontend (Angular)](#frontend-angular)
  - [Escuchar webhooks de Stripe](#escuchar-webhooks-de-stripe)
- [Variables de entorno](#variables-de-entorno)
- [Usuarios de demo](#usuarios-de-demo)
- [Funcionalidades principales](#funcionalidades-principales)
- [API REST](#api-rest)
- [Precios](#precios)

---

## Descripción

Stride365 es una aplicación full-stack que cubre el ciclo completo de un club deportivo:

1. Un usuario solicita crear un club.
2. El super administrador lo aprueba o rechaza.
3. El administrador paga la cuota anual del club mediante Stripe Checkout.
4. Los socios se unen al club con su propia cuota anual.
5. Se gestionan actividades, inscripciones, comunicados y un feed social.
6. Las actividades pueden incluir rutas con mapa, track GPX, distancia, desnivel y métricas.

---

## Stack tecnológico

### Frontend
| Tecnología | Versión |
|---|---|
| Angular | 21 |
| TypeScript | ~5.9 |
| RxJS | ~7.8 |
| Bootstrap | 5 |
| Leaflet + leaflet-gpx | 1.9 / 2.2 |
| SweetAlert2 | 11 |
| Vitest | 4 |

### Backend
| Tecnología | Versión |
|---|---|
| PHP | ^8.2 |
| Laravel | 12 |
| Laravel Sanctum | 4 |
| Laravel Cashier (Stripe) | 16 |
| Spatie Permission | 6 |
| Spatie Media Library | 11 |
| DomPDF | 3 |

### Servicios externos
- **Stripe** — suscripciones, Checkout y webhooks
- **Mailtrap / SMTP** — emails transaccionales
- **OpenTopoData** — elevación de rutas (ASTER 30m)
- **Vercel** — despliegue del frontend

### Base de datos
- MySQL (producción y desarrollo)

---

## Estructura del repositorio

```
Stride365/
├── backend/        # API REST Laravel
│   ├── app/
│   ├── database/
│   ├── resources/views/mail/   # Plantillas de email
│   ├── routes/
│   └── .env.example
└── frontend/       # SPA Angular
    ├── src/
    │   ├── app/features/
    │   └── environments/
    └── vercel.json
```

---

## Requisitos previos

- **PHP** 8.2 o superior + Composer
- **Node.js** 20+ y **npm** 11+
- **MySQL** 8+
- **Angular CLI** (`npm install -g @angular/cli`)
- **Stripe CLI** (para recibir webhooks en local)

---

## Instalación

### Backend (Laravel)

```bash
# 1. Clonar el repositorio
git clone https://github.com/sergiics9/Stride365.git
cd Stride365/backend

# 2. Instalar dependencias PHP
composer install

# 3. Crear el fichero de entorno
cp .env.example .env

# 4. Generar la clave de la aplicación
php artisan key:generate

# 5. Configurar la conexión a base de datos en .env y migrar
php artisan migrate:fresh --seed

# 6. Enlazar el almacenamiento público
# En Windows elimina el enlace anterior si existe:
rm backend/public/storage   # o borra la carpeta manualmente en Windows
php artisan storage:link

# 7. Arrancar el servidor
php artisan serve
```

El backend quedará disponible en `http://127.0.0.1:8000`.

---

### Frontend (Angular)

En otra terminal:

```bash
cd Stride365/frontend
npm install
ng serve
```

La SPA quedará disponible en `http://localhost:4200`.

---

### Escuchar webhooks de Stripe

En una tercera terminal, con la **Stripe CLI** instalada:

```bash
stripe listen --forward-to http://localhost:8000/api/webhook/stripe
```

Copia el webhook secret que muestra la CLI (`whsec_...`) y pégalo en `backend/.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

> Sin este paso las suscripciones no se activarán en local y los emails de factura no se enviarán.

---

## Variables de entorno

Copia `backend/.env.example` a `backend/.env` y ajusta los valores:

```env
APP_NAME=Stride365
APP_URL=http://localhost:8000
APP_FRONTEND_URL=http://localhost:4200

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stride365
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<tu_usuario_mailtrap>
MAIL_PASSWORD=<tu_password_mailtrap>
MAIL_FROM_ADDRESS=hello@stride365.com

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_CLUB=price_...
STRIPE_PRICE_SOCIO=price_...
```

---

## Usuarios de demo

Tras ejecutar `php artisan migrate:fresh --seed` estarán disponibles los siguientes usuarios con contraseña `password`:

| Email | Rol |
|---|---|
| `superadmin@demo.local` | Super administrador |
| `usuario@demo.local` | Usuario sin club |
| `admin@demo.local` | Administrador de club |
| `socio@demo.local` | Socio de club |
| `guia@demo.local` | Guía de club |

---

## Funcionalidades principales

### Gestión de clubes
- Solicitud de creación de club por parte de cualquier usuario
- Aprobación o rechazo por el super administrador (con motivo)
- Panel de administración del club (nombre, logo, descripción, contacto)

### Socios y roles
- Roles: `super_admin`, `admin_club`, `socio`, `guía`
- Cuota anual de socio gestionada con Stripe
- Control de altas, bajas y estado de membresía (`pending`, `active`, `grace`, `cancelled`)

### Actividades
- Creación de actividades programadas con fecha, lugar y punto de encuentro
- Modos: **dibujada** (mapa), **importada** (GPX) o **en vivo** (grabación en tiempo real)
- Cálculo automático de distancia (Haversine), desnivel positivo (OpenTopoData) y métricas
- Inscripciones con control de cupo máximo
- Asignación de guías

### Feed social
- Publicación de actividades finalizadas como posts públicos
- Visualización del track en mapa (Leaflet)
- Edición y eliminación de publicaciones propias

### Comunicados
- El club puede enviar avisos a todos sus socios
- Solo visibles para miembros activos

### Suscripciones y pagos
- Stripe Checkout integrado
- Cuota anual de administrador de club: **129,99 €/año**
- Cuota anual de socio: **39,99 €/año**
- Cancelación y reanudación de suscripciones
- Descarga de facturas en PDF con logo Stride365
- Email automático al activarse la suscripción

### Emails transaccionales
- Inscripción confirmada / cancelada
- Club aprobado / rechazado
- Factura generada
- Restablecimiento de contraseña
- Activación de membresía
- Todos incluyen logo y diseño de Stride365

---

## API REST

Base URL: `http://127.0.0.1:8000/api`

### Autenticación (pública)
| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/auth/register` | Registro |
| `POST` | `/auth/login` | Login |
| `POST` | `/auth/forgot-password` | Solicitar reset de contraseña |
| `POST` | `/auth/reset-password` | Restablecer contraseña |

### Autenticadas (`Authorization: Bearer <token>`)
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/auth/me` | Datos del usuario |
| `PATCH` | `/auth/me` | Actualizar perfil |
| `POST` | `/auth/logout` | Cerrar sesión |
| `GET` | `/feed` | Feed social |
| `GET/POST/PATCH/DELETE` | `/feed/{id}` | Gestión de publicaciones |
| `POST` | `/feed/recordings/start` | Iniciar grabación en vivo |
| `POST` | `/feed/recordings/import-gpx` | Importar GPX |
| `GET` | `/subscription/memberships` | Membresías del usuario |
| `POST` | `/subscription/checkout` | Iniciar pago con Stripe |
| `POST` | `/subscription/cancel` | Cancelar suscripción |
| `GET` | `/subscription/invoices` | Listar facturas |
| `GET` | `/subscription/invoices/{id}` | Descargar factura PDF |
| `GET/POST` | `/clubs/applications` | Solicitudes de club |
| `POST` | `/clubs/applications/{id}/approve` | Aprobar club (super admin) |
| `POST` | `/clubs/applications/{id}/reject` | Rechazar club (super admin) |
| `GET/PATCH/DELETE` | `/clubes/{id}` | Gestión del club |
| `GET/POST/PATCH/DELETE` | `/clubes/{id}/socios` | Gestión de socios |
| `GET/POST/PATCH/DELETE` | `/clubes/{id}/actividades` | Gestión de actividades |
| `POST` | `/clubes/{id}/actividades/{id}/finish` | Finalizar actividad |
| `GET/POST/PATCH/DELETE` | `/clubes/{id}/comunicados` | Comunicados |
| `GET/POST/DELETE` | `/actividades/{id}/inscripciones` | Inscripciones |

### Webhook
| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/webhook/stripe` | Eventos de Stripe |

---

## Precios

| Plan | Precio | Descripción |
|---|---|---|
| Administrador de club | 129,99 €/año | Crea y gestiona un club |
| Socio | 39,99 €/año | Acceso completo a un club |

---

> Proyecto de fin de grado · Stride365 · [Sergi]
