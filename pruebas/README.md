# Backend Plataforma Educativa ICFES (Golang)

Backend de alto rendimiento construido en **Golang (Gin + GORM)** siguiendo principios de **Clean Architecture**, diseñado para dar soporte unificado a la **App Móvil (React Native)** y al **Panel Administrativo Web (Next.js)**.

---

## 🏛 Arquitectura del Proyecto

```
app-web/
├── cmd/api/main.go            # Entrypoint del servidor y configuración de rutas
├── internal/
│   ├── config/                # Carga de variables de entorno (.env)
│   ├── domain/                # Modelos y entidades (User, Course, Contract, Payment, Document, Exam)
│   ├── repository/            # Capa de persistencia en base de datos (GORM)
│   ├── service/               # Lógica de negocio y casos de uso
│   ├── handler/               # Controladores REST (Gin HTTP Handlers)
│   └── middleware/            # Autenticación JWT, RBAC y CORS
├── pkg/
│   ├── database/              # Conexión DB, automigración y seeder inicial
│   ├── response/              # Estructura uniforme de respuestas JSON
│   ├── storage/               # Adaptador de almacenamiento de archivos (Local / S3)
│   └── token/                 # Generación y validación de tokens JWT
├── docker-compose.yml         # Contenedor de PostgreSQL listo para producción/local
├── Makefile                   # Comandos rápidos de compilación y ejecución
└── .env                       # Variables de entorno
```

---

## 🚀 Inicio Rápido

### 1. Requisitos
- Go 1.21+
- (Opcional) Docker para PostgreSQL

### 2. Ejecución Local Inmediata
El proyecto viene preconfigurado para arrancar con MySQL o SQLite:
```bash
go run cmd/api/main.go
```

O usando el Makefile:
```bash
make run
```

### 3. Configurar MySQL de Hostinger
1. En **Hostinger hPanel**, ve a **Bases de datos MySQL** y crea tu base de datos y usuario.
2. Si vas a conectarte desde tu máquina local hacia Hostinger, ve a **MySQL Remoto** en hPanel y agrega tu IP pública (o `%` para permitir cualquier IP).
3. En [.env](file:///Applications/MAMP/htdocs/app-web/.env), configura:
   ```env
   DB_DRIVER=mysql
   DB_DSN=usuario_hostinger:contraseña@tcp(servidor_mysql_hostinger:3306)/nombre_bd_hostinger?charset=utf8mb4&parseTime=True&loc=Local
   ```
4. Si el backend corre directamente dentro del VPS o hosting de Hostinger, el host será `localhost`:
   ```env
   DB_DSN=usuario_hostinger:contraseña@tcp(localhost:3306)/nombre_bd_hostinger?charset=utf8mb4&parseTime=True&loc=Local
   ```
5. Al iniciar el backend, **GORM creará automáticamente todas las tablas, relaciones y cargará los datos iniciales (Admin, curso $700.000, materias ICFES y simulacro inicial)**.

### 4. Usar con PostgreSQL (Docker)
1. Iniciar contenedor de PostgreSQL:
   ```bash
   make docker-up
   ```
2. En `.env`, cambiar `DB_DRIVER=postgres` y su correspondiente `DB_DSN`.

---

## 🔐 Credenciales y Semillero Inicial (Seeding Automático)

Al iniciar el servidor por primera vez, se generan automáticamente:
- **Usuario Administrador:**
  - **Email:** `admin@icfes.com`
  - **Contraseña:** `Admin123456!`
- **Curso Oficial:** "Curso de Preparación Académica ICFES Saber 11°" por un valor de **$700.000 COP**.
- **Contrato Digital:** Términos y condiciones del servicio educativo activos.
- **5 Materias ICFES:** Matemáticas, Lectura Crítica, Ciencias Naturales, Sociales y Ciudadanas, Inglés.
- **Preguntas de Prueba y Simulacro Diagnóstico inicial** listo para responder.

---

## 📡 Catálogo de Endpoints de la API

### Formato de Respuesta Estándar
```json
{
  "success": true,
  "message": "Operación exitosa",
  "data": { ... },
  "error": null
}
```

---

### 1. Autenticación (Público)
| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/api/v1/auth/register` | Registro de nuevo estudiante (nombre, email, teléfono, password) |
| `POST` | `/api/v1/auth/login` | Login para estudiantes y administradores |
| `GET` | `/api/v1/auth/me` | Obtener perfil del usuario autenticado (requiere Bearer token) |

#### Ejemplo Registro:
```json
POST /api/v1/auth/register
{
  "full_name": "Juan Pérez",
  "email": "juan@correo.com",
  "phone": "3101234567",
  "password": "MiPassword123"
}
```

---

### 2. Módulo Estudiante (App Móvil - React Native)
> **Nota:** Todos los endpoints de estudiante requieren el header `Authorization: Bearer <TOKEN>`.

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/v1/student/contract` | Consultar contrato activo y si ya fue firmado |
| `POST` | `/api/v1/student/contract/accept` | Firmar/Aceptar contrato digital con checkbox |
| `GET` | `/api/v1/student/course-summary` | Resumen financiero: $700.000, pagado, pendiente y porcentaje |
| `GET` | `/api/v1/student/payments` | Historial de abonos registrados |
| `POST` | `/api/v1/student/document` | Subir foto de documento/cédula (multipart/form-data: `document`, `document_type`, `document_number`) |
| `GET` | `/api/v1/student/document` | Consultar estado de validación (Pendiente, Aprobado, Rechazado) |
| `GET` | `/api/v1/student/exams` | Listar simulacros ICFES publicados |
| `POST` | `/api/v1/student/exams/:id/start` | Iniciar presentación de simulacro |
| `POST` | `/api/v1/student/exams/sessions/:session_id/submit` | Enviar respuestas del simulacro |
| `GET` | `/api/v1/student/exams/sessions/:session_id/results` | Ver puntaje, aciertos, errores y desempeño por materia |
| `GET` | `/api/v1/student/exams/history` | Historial de simulacros realizados |

#### Ejemplo Firma de Contrato:
```json
POST /api/v1/student/contract/accept
{
  "contract_id": "uuid-del-contrato",
  "accept_terms": true
}
```

---

### 3. Módulo Administrativo (Web - Next.js)
> **Nota:** Requiere `Authorization: Bearer <TOKEN>` con rol `admin`.

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/v1/admin/dashboard` | KPIs: total estudiantes, ingresos totales, cédulas pendientes, simulacros completados |
| `GET` | `/api/v1/admin/students` | Listado paginado de estudiantes con buscador |
| `GET` | `/api/v1/admin/students/:id` | Detalle del estudiante, contrato y saldo de pagos |
| `POST` | `/api/v1/admin/payments` | Registrar abono manual para un estudiante |
| `GET` | `/api/v1/admin/documents/pending` | Bandeja de cédulas pendientes de revisión |
| `POST` | `/api/v1/admin/documents/:id/review` | Aprobar o rechazar cédula con motivo opcional |
| `GET` | `/api/v1/admin/subjects` | Listar las 5 materias oficiales ICFES |
| `POST` | `/api/v1/admin/questions` | Crear pregunta en el banco (opciones A, B, C, D, respuesta correcta) |
| `GET` | `/api/v1/admin/questions` | Consultar banco de preguntas (filtro por materia) |
| `POST` | `/api/v1/admin/questions/upload-image`| Subir diagrama/imagen para una pregunta |
| `POST` | `/api/v1/admin/exams` | Crear nuevo simulacro y asignarle preguntas |
| `GET` | `/api/v1/admin/exams` | Listar todos los simulacros |
| `PUT` | `/api/v1/admin/exams/:id/publish` | Publicar o despublicar simulacro |

#### Ejemplo Registro de Abono:
```json
POST /api/v1/admin/payments
{
  "user_id": "uuid-del-estudiante",
  "amount": 280000,
  "payment_method": "transfer",
  "receipt_number": "TRF-98234",
  "notes": "Abono cuota 1"
}
```
*El sistema recalcula automáticamente el saldo pendiente ($420.000) y el porcentaje pagado (40%).*
