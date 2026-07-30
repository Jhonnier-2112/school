# IA MEMORY - Backend | Proyecto FemTribe Runner

> **Archivo de Memoria y Arquitectura del Backend**
> Este documento contiene el contexto completo, estructura real de archivos, modelos de datos y flujo funcional del **backend** del proyecto para el desarrollo continuo con la IA.

---

## 1. Visión General del Proyecto

- **Nombre del Proyecto**: FemTribe Runner (`femtribe.com.co`)
- **Carpeta Raíz del Backend**: `/Applications/MAMP/htdocs/backend/`
- **Propósito**: Backend PHP MVC que expone la lógica de negocio, autenticación, pagos e inscripciones de la plataforma de running FemTribe.
- **Objetivos Principales**:
  1. **Autenticación (Login, Registro, Google OAuth 2.0)**: Datos personales y de entrega, vinculación con `google_id`.
  2. **Tokens HMAC-SHA256 + Refresh Tokens (1 hora)**: `TokenAuthService` emite Access Token + Refresh Token almacenado en `user_tokens`, cookies HTTP-Only de 3600s, endpoint `/auth/refresh-token`.
  3. **Roles RBAC con UUID**: Tabla `roles` con semillas fijas para `cliente` y `administrador`. Campo `role_id` en `users`.
  4. **Log de Accesos (`user_access_logs`)**: IP, URL, método HTTP, User-Agent, usuario autenticado.
  5. **Historial de Compras (`orders`)**: Pedidos y estado de pago en perfil del usuario.
  6. **Carrito Persistente en BD (`user_cart_items`)**: Sync `/cart/sync`, consulta `/cart/get`.
  7. **Inscripciones por Etapas**: Adulto multietapa, Niño con acudiente, Mascota (nombre y raza).
  8. **Pasarela Bancolombia / Wompi**: Firma SHA-256, checkout, órdenes, webhook.

---

## 2. Stack Tecnológico

- **Lenguaje**: PHP 8.x
- **Arquitectura**: MVC nativo PHP
- **Enrutamiento**: `App\Core\Router` (clase personalizada) + `routes.php` (central, en frontend/)
- **Configuración**: `frontend/config/config.php` parsea `.env` nativo (sin librería externa)
- **Base de Datos**: MySQL / MariaDB vía PDO (`App\Config\Database`)
- **Seguridad**: HMAC-SHA256 tokens, Refresh Tokens en BD, BCRYPT para contraseñas
- **Google OAuth 2.0**: Integración nativa cURL (`GoogleAuthService.php`)
- **Pagos**: API Bancolombia / Wompi REST + Webhooks (`BancolombiaPaymentService.php`)
- **Email**: PHPMailer (`EmailService.php`) vía SMTP Hostinger
- **Dependencias**: `frontend/composer.json` → `phpmailer/phpmailer ^6.11`

---

## 3. Estructura Real de Directorios del Backend

> ⚠️ IMPORTANTE: Los archivos están directamente en `backend/controllers/`, `backend/models/`, etc. (SIN prefijo `app/`).
> El autoloader del frontend busca en esas rutas usando `require_once`.

```
/Applications/MAMP/htdocs/backend/
├── IA_MEMORY.md                        ← Este archivo
├── controllers/
│   ├── AdminController.php             # Ejecución de migraciones SQL (/admin/run-db)
│   ├── AuthController.php              # Login, Registro, Google OAuth, Refresh Token, Perfil
│   ├── CartController.php              # Gestión y sincronización del carrito en BD
│   ├── EventController.php             # Detalles e información de carreras/eventos
│   ├── HomeController.php              # Página de inicio / Landing
│   ├── PaymentController.php           # Checkout, pago Bancolombia, respuesta, webhook
│   ├── ProductController.php           # Catálogo de productos, filtros y detalle
│   ├── RegistrationController.php      # Inscripciones por etapas (Adulto, Niño, Mascota)
│   └── UserController.php             # Gestión administrativa de usuarios
├── core/
│   ├── Controller.php                  # Clase base: validación de Tokens y Cookies 1 hora
│   ├── Model.php                       # Clase base para modelos
│   └── Router.php                      # Dispatcher de rutas HTTP → controladores
├── models/
│   ├── Category.php                    # Categorías y relaciones con productos
│   ├── Order.php                       # Órdenes de compra, items, pagos e historial
│   ├── Product.php                     # Consultas, paginación y filtros de productos
│   ├── Registration.php                # CRUD e inscripciones por etapas
│   ├── Role.php                        # Roles con UUID (cliente/administrador) + helpers estáticos
│   ├── User.php                        # Usuarios, Google Sign-In y autenticación
│   └── UserCart.php                    # Carrito persistente en BD
├── services/
│   ├── AccessLogService.php            # Registro automático de visitas en BD
│   ├── BancolombiaPaymentService.php   # Integración API Bancolombia / Wompi + firma SHA-256
│   ├── EmailService.php                # Envío de correos vía PHPMailer
│   ├── GoogleAuthService.php           # Autenticación nativa Google OAuth 2.0
│   └── TokenAuthService.php            # Generación Access Tokens + Refresh Tokens (1 hora)
└── views/
    ├── admin/
    │   └── users.php                   # Tabla administrativa de usuarios registrados
    ├── auth/
    │   ├── login.php                   # Formulario login (con botón Google)
    │   ├── profile.php                 # Perfil de usuario + Historial de Compras
    │   └── register.php                # Formulario completo de registro
    ├── layouts/
    │   ├── header.php                  # Navegación superior + Modal + Google Sign-In
    │   └── footer.php                  # Pie de página, redes sociales, soporte
    ├── blog.php
    ├── carrito.php
    ├── checkout.php
    ├── consulta_inscripcion.php
    ├── event.php
    ├── home.php
    ├── nosotros.php
    ├── payment_response.php
    ├── producto_detalle.php
    ├── productos.php
    ├── registration_closed.php
    ├── registration_form.php
    └── registration_success.php
```

> **SEPARACIÓN**: Las vistas PHP activas están en `frontend/public_html/views/` (si se movieron).
> El frontend (`frontend/`) aloja: `routes.php`, `config/`, `sql/`, `vendor/`, `public_html/`, `composer.json`.

---

## 4. Base de Datos (`runner_db` en desarrollo, `u266057107_femtribe_bd` en producción)

### Tabla `users`
- `id` INT AUTO_INCREMENT PK
- `nombres`, `apellidos`, `tipo_documento`, `numero_documento`
- `email` UNIQUE, `password` (BCRYPT)
- `telefono`, `direccion`, `municipio`, `departamento`
- `eps`, `grupo_sanguineo`, `rh`
- `google_id`, `avatar_url`
- `role_id` CHAR(36) NULL FK → `roles.id`
- `created_at`, `updated_at`

### Tabla `roles` (RBAC — UUID)
- `id` CHAR(36) PK (UUID), `name`, `slug`, `description`, `is_active`, `created_at`, `updated_at`
- **Semillas fijas**:
  - `a1b2c3d4-0001-0001-0001-000000000001` → `Cliente`
  - `a1b2c3d4-0002-0002-0002-000000000002` → `Administrador`

### Tabla `user_tokens` (Access + Refresh Tokens)
- `id`, `user_id` FK, `token` (Access Token HMAC-SHA256), `refresh_token`, `expires_at` (1 hora), `created_at`

### Tabla `user_access_logs`
- `id`, `user_id` NULL FK, `ip_address`, `page_url`, `method`, `user_agent`, `referer`, `created_at`

### Tabla `user_cart_items`
- `id`, `user_id`, `product_id`, `product_slug`, `product_name`, `price`, `quantity`, `color`, `gender`, `size`, `created_at`

### Tabla `categories`
- `id`, `name`, `slug`, `description`, `created_at`

### Tabla `products`
- `id`, `name`, `slug`, `description`, `price`, `stock`, `sku`, `image_url`, `created_at`

### Tabla `category_product` (Pivote)
- `category_id`, `product_id`

### Tabla `orders`
- `id`, `user_id`, `reference`, `status` (`PENDING`/`APPROVED`/`DECLINED`), `total_amount`, `created_at`

### Tabla `order_items`
- `id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`

### Tabla `payments`
- `id`, `order_id`, `wompi_transaction_id`, `payment_method`, `status`, `amount`, `created_at`

### Tabla `race_stages`
- `id`, `name`, `slug`, `category_type` (`adulto`/`nino`/`mascota`), `distance`, `price`, `description`, `is_active`

### Tabla `registrations`
- `id`, `user_id`, `categoria_participante`, `etapas_seleccionadas` (JSON), `nombre_mascota`, `raza_mascota`, `acudiente_nombre`, `acudiente_documento`, `nombres`, `apellidos`, `tipo_documento`, `numero_documento`, `fecha_nacimiento`, `edad`, `genero`, `eps`, `grupo_sanguineo`, `rh`, `direccion`, `municipio`, `departamento`, `email`, `telefono`, `parentesco_emergencia`, `celular_emergencia`, `created_at`

---

## 5. Migraciones SQL (ejecutar en orden desde `/admin/run-db`)

| # | Archivo | Contenido |
|---|---|---|
| 1 | `create_users_table.sql` | Tabla `users` base |
| 2 | `create_auth_tokens_and_google.sql` | `user_tokens`, columnas `google_id`, `avatar_url` |
| 3 | `create_roles_and_seeds.sql` | Tabla `roles` UUID + semillas + FK `users.role_id` |
| 4 | `create_ecommerce_and_payments.sql` | `categories`, `products`, `category_product`, `orders`, `order_items`, `payments` |
| 5 | `create_access_logs_cart_and_stages.sql` | `user_access_logs`, `user_cart_items`, `race_stages`, `registrations` |

---

## 6. Servicios Clave

### `TokenAuthService`
- Genera Access Token (HMAC-SHA256, payload: `user_id`, `name`, `email`, `role`, `exp`)
- Genera Refresh Token (random_bytes → hex 64 chars)
- Almacena en `user_tokens` con `expires_at = NOW() + 3600s`
- Cookies HTTP-Only: `access_token`, `refresh_token`
- `verifyToken()`: decodifica y valida expiración
- `refreshToken()`: valida refresh en BD, emite nuevo par

### `GoogleAuthService`
- Intercambia `code` OAuth por `access_token` vía cURL
- Obtiene perfil Google (nombre, email, `sub`→`google_id`, avatar)
- Crea o vincula usuario por `google_id` o `email`

### `BancolombiaPaymentService`
- Firma SHA-256: `reference + amount_in_cents + COP + integrity_secret`
- Crea orden en `orders`, redirige a widget Wompi
- Webhook: valida firma del evento, actualiza `payments` + `orders`

### `AccessLogService`
- `logAccess()` estático: captura IP, URL, método, User-Agent, usuario autenticado
- Se llama en cada request desde `frontend/public_html/index.php`

### `EmailService`
- PHPMailer SMTP Hostinger (puerto 465, SSL)
- Correos de confirmación de inscripción y registro

---

## 7. Flujo de Tokens (HMAC-SHA256)

```
Login/Registro
  └─> AuthController → TokenAuthService::generateTokens($user)
        ├─> Access Token (1 hora, payload: user_id + name + email + role)
        ├─> Refresh Token (hex aleatorio, 1 hora)
        └─> Cookies HTTP-Only + BD: user_tokens

Petición autenticada
  └─> Controller::requireAuth() → TokenAuthService::verifyToken($_COOKIE['access_token'])
        ├─> Válido → continúa
        └─> Expirado → 401 / redirect /login

Renovación
  └─> POST /auth/refresh-token → TokenAuthService::refreshToken($_COOKIE['refresh_token'])
        └─> Valida BD → nuevo par de tokens
```

---

## 8. Reglas y Convenciones

1. **Namespaces**: `App\Controllers\*`, `App\Models\*`, `App\Services\*`, `App\Core\*`, `App\Config\*`
2. **Rutas físicas**: `backend/controllers/`, `backend/models/`, etc. (sin `app/` intermedio)
3. **PDO**: Siempre prepared statements con `bindParam` / `bindValue`
4. **Contraseñas**: `password_hash(..., PASSWORD_BCRYPT)` / `password_verify()`
5. **Tokens**: Solo cookies HTTP-Only, nunca `localStorage`
6. **Google OAuth**: Vinculación por `google_id`, fallback por `email`
7. **Roles**: UUID semilla fija (`Role::CLIENTE_ID`, `Role::ADMIN_ID`)
8. **Inscripciones**: Controladas por `RegistrationConfig::INSCRIPCIONES_ABIERTAS`
9. **SQL Migrations**: Siempre en orden 1→5 desde `AdminController@runDb`
10. **CORS / AJAX**: Respuestas JSON con `Content-Type: application/json`
