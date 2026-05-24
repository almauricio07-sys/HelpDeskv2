# 🎫 Sistema de Mesa de Ayuda — Los Bélicos

> **Help Desk System** desarrollado con PHP nativo en arquitectura **MVC**, MySQL (PDO), Bootstrap 5 y diseño **Dark Mode** absoluto.

---

## 📋 Tabla de Contenidos

1. [Descripción del Proyecto](#descripción-del-proyecto)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Arquitectura MVC](#arquitectura-mvc)
4. [Estructura del Proyecto](#estructura-del-proyecto)
5. [Base de Datos](#base-de-datos)
6. [Instalación en XAMPP](#instalación-en-xampp)
7. [Usuarios de Prueba](#usuarios-de-prueba)
8. [Requerimientos Funcionales por Rol](#requerimientos-funcionales-por-rol)
9. [Seguridad Implementada](#seguridad-implementada)
10. [Paleta de Diseño UI/UX](#paleta-de-diseño-uiux)
11. [Convenciones del Equipo](#convenciones-del-equipo)

---

## Descripción del Proyecto

El **Sistema de Mesa de Ayuda** es una aplicación web integral para la gestión de tickets de soporte técnico. Permite registrar incidencias, asignarlas a técnicos, dar seguimiento mediante notas internas y generar reportes estadísticos en tiempo real.

**Equipo:** Los Bélicos  
**Entorno:** XAMPP (Windows) / PHP 8.1+ / MySQL 8.0+

---

## Stack Tecnológico

| Capa       | Tecnología                                  |
|------------|---------------------------------------------|
| Backend    | PHP 8.1+ (nativo, MVC)                      |
| Base de Datos | MySQL 8.0+ con PDO + consultas preparadas |
| Frontend   | HTML5, CSS3, JavaScript ES6 puro            |
| UI Library | Bootstrap 5.3 + Bootstrap Icons 1.11        |
| Gráficas   | Chart.js 4.4                                |
| Fuentes    | Google Fonts (Inter, Roboto Mono)           |
| Servidor   | Apache (XAMPP)                              |

---

## Arquitectura MVC

El sistema sigue el patrón **Modelo-Vista-Controlador** con un **Front Controller** único (`index.php`) que enruta todas las peticiones.

```
Petición HTTP
     │
     ▼
index.php  (Front Controller / Router)
     │
     ├── Lee ?controller=X&action=Y
     ├── Valida y sanitiza parámetros
     ├── Carga el Controlador via Autoloader
     │
     ▼
XxxController.php
     │
     ├── Verifica autenticación ($_SESSION)
     ├── Verifica autorización (rol)
     ├── Instancia el Modelo necesario
     ├── Llama al método del Modelo (lógica de negocio + BD)
     │
     ▼
Modelo.php  (Encapsula PDO + SQL preparado)
     │
     ▼
Controlador recibe datos → carga Vista
     │
     ▼
Vista.php  (HTML + PHP para presentación)
     │
     include header.php + footer.php
```

### Responsabilidades

| Capa          | Archivo tipo         | Responsabilidad                                 |
|---------------|----------------------|-------------------------------------------------|
| **Model**     | `app/models/*.php`   | Consultas PDO, lógica de datos, validaciones BD |
| **View**      | `app/views/**/*.php` | HTML, presentación, sin lógica de negocio       |
| **Controller**| `app/controllers/*.php` | Orquestación, validación HTTP, sesiones      |
| **Config**    | `config/Database.php`| Conexión PDO Singleton                          |
| **Router**    | `index.php`          | Front Controller, autoloader, despacho          |

---

## Estructura del Proyecto

```
Mesa/
├── index.php                          # Front Controller (router principal)
│
├── config/
│   └── Database.php                   # Clase PDO Singleton
│
├── app/
│   ├── controllers/
│   │   ├── AuthController.php         # Login / Logout
│   │   ├── DashboardController.php    # Dashboard por rol
│   │   ├── TicketController.php       # CRUD tickets + notas + asignación
│   │   └── UsuarioController.php      # CRUD usuarios (solo Coordinador)
│   │
│   ├── models/
│   │   ├── Ticket.php                 # Modelo tickets, solicitantes, notas
│   │   └── Usuario.php                # Modelo usuarios, roles, auth
│   │
│   └── views/
│       ├── layouts/
│       │   ├── header.php             # Navbar dinámica por rol
│       │   └── footer.php             # Footer + CDN scripts
│       ├── auth/
│       │   └── login.php              # Formulario de acceso
│       ├── dashboard/
│       │   └── index.php              # Dashboard adaptado por rol
│       ├── tickets/
│       │   ├── index.php              # Tablero general + filtros
│       │   ├── create.php             # Formulario nuevo ticket
│       │   ├── show.php               # Detalle + notas + acciones
│       │   └── mis_tickets.php        # Vista técnico (RF_06)
│       ├── usuarios/
│       │   ├── index.php              # Catálogo usuarios (RF_13)
│       │   ├── create.php             # Nuevo usuario (RF_11)
│       │   └── edit.php               # Editar usuario (RF_12)
│       └── errors/
│           ├── 403.php                # Acceso denegado
│           └── 404.php                # No encontrado
│
├── public/
│   ├── css/
│   │   └── style.css                  # Sistema de diseño Dark Mode
│   └── js/
│       └── main.js                    # Scripts globales
│
├── database/
│   └── helpdesk_db.sql                # Esquema + datos de prueba
│
└── scripts/
    └── generar_hash.php               # Utilidad BCrypt (solo local)
```

---

## Base de Datos

### Diagrama Entidad-Relación (simplificado)

```
roles (1) ──────────< usuarios (N)
departamentos (1) ──< solicitantes (N)
solicitantes (1) ───< tickets (N)
canales_contacto (1)< tickets (N)
estatus_tickets (1) < tickets (N)
usuarios (técnico) (1) < tickets (N)
tickets (1) ────────< notas_internas (N)
usuarios (técnico) (1) < notas_internas (N)
```

### Tablas

| Tabla               | Descripción                                       |
|---------------------|---------------------------------------------------|
| `roles`             | Catálogo: Coordinador, Técnico, Mesa de Ayuda     |
| `departamentos`     | Catálogo de áreas organizacionales                |
| `estatus_tickets`   | Abierto, En Proceso, Cerrado, Pendiente           |
| `canales_contacto`  | Teléfono, WhatsApp, Correo, Presencial, Portal    |
| `solicitantes`      | Personas que reportan problemas                   |
| `usuarios`          | Personal del sistema con rol y autenticación      |
| `tickets`           | Incidencias con folio único auto-generado         |
| `notas_internas`    | Bitácora de acciones por ticket                   |

---

## Instalación en XAMPP

### Prerequisitos

- ✅ XAMPP instalado (PHP 8.1+ y MySQL 8.0+)
- ✅ Apache y MySQL en ejecución

### Paso 1 — Clonar/Copiar el Proyecto

```
Coloca la carpeta Mesa/ en:
C:\xampp\htdocs\Mesa\
```

### Paso 2 — Importar la Base de Datos

**Opción A — phpMyAdmin (recomendado):**

1. Abre `http://localhost/phpmyadmin` en tu navegador
2. Haz clic en **"Nueva"** para crear una base de datos (si no existe)
3. Ve a la pestaña **"Importar"**
4. Selecciona el archivo `database/helpdesk_db.sql`
5. Clic en **"Continuar"**

**Opción B — Línea de comandos:**

```bash
# Desde la terminal de XAMPP o Windows PowerShell
C:\xampp\mysql\bin\mysql.exe -u root -p < C:\xampp\htdocs\Mesa\database\helpdesk_db.sql
```

> El script SQL crea automáticamente la base de datos `helpdesk_db`, todas las tablas e inserta datos de prueba.

### Paso 3 — Verificar Configuración de Conexión

Edita `config/Database.php` si tu configuración difiere:

```php
private string $host     = 'localhost';
private string $dbName   = 'helpdesk_db';
private string $username = 'root';
private string $password = '';       // Cambia si tienes contraseña en MySQL
```

### Paso 4 — Verificar el Hash de Contraseñas

> ⚠️ **IMPORTANTE:** El hash incluido en el SQL fue generado con PHP 8.1. Si tu versión difiere, puede que no funcione.

Para generar un hash válido para tu entorno:

1. Abre: `http://localhost/Mesa/scripts/generar_hash.php`
2. Escribe la contraseña `Admin1234!`
3. Copia el hash resultante
4. En phpMyAdmin, actualiza la columna `password_hash` en la tabla `usuarios` con ese nuevo hash

### Paso 5 — Acceder al Sistema

```
URL: http://localhost/Mesa/
```

El sistema te redirigirá automáticamente al formulario de login.

---

## Usuarios de Prueba

| Clave de Acceso | Contraseña    | Rol            | Estado |
|-----------------|---------------|----------------|--------|
| `coord01`       | `Admin1234!`  | Coordinador    | Activo |
| `tec01`         | `Admin1234!`  | Técnico        | Activo |
| `tec02`         | `Admin1234!`  | Técnico        | Activo |
| `mesa01`        | `Admin1234!`  | Mesa de Ayuda  | Activo |

---

## Requerimientos Funcionales por Rol

### 🎫 Mesa de Ayuda (Rol 3)

| RF     | Descripción                                                           | Implementado |
|--------|-----------------------------------------------------------------------|:---:|
| RF_01  | Captura de ticket vía Teléfono/WhatsApp                               | ✅  |
| RF_02  | Insertar solicitante si no existe; reutilizar si ya existe            | ✅  |
| RF_03  | Folio único auto-generado: `HD-YYYYMMDD-XXXX`                        | ✅  |
| RF_04  | Tablero general con todos los tickets                                 | ✅  |
| RF_05  | Filtros por folio y nombre del solicitante                            | ✅  |
| RF_07  | Asignar técnico al ticket                                             | ✅  |
| RF_10  | Botón para cerrar ticket (con modal de confirmación)                  | ✅  |

### 🔧 Técnico (Rol 2)

| RF     | Descripción                                                           | Implementado |
|--------|-----------------------------------------------------------------------|:---:|
| RF_06  | Vista "Mis Folios Asignados" filtrada por su ID de sesión             | ✅  |
| RF_08  | Interfaz para agregar notas internas en el detalle del ticket         | ✅  |
| RF_09  | Actualizar el estatus de su ticket asignado                           | ✅  |

### 👑 Coordinador (Rol 1)

| RF     | Descripción                                                           | Implementado |
|--------|-----------------------------------------------------------------------|:---:|
| RF_11  | Registrar nuevo usuario con rol                                       | ✅  |
| RF_12  | Modificar datos y estado de usuario                                   | ✅  |
| RF_13  | Catálogo de usuarios con filtros                                      | ✅  |
| RF_14  | Dashboard: gráfica Tickets Activos vs Cerrados (Doughnut)             | ✅  |
| RF_15  | Dashboard: gráfica Tickets por Técnico (Bar Chart)                    | ✅  |

---

## Seguridad Implementada

| Medida                        | Descripción                                                      |
|-------------------------------|------------------------------------------------------------------|
| **PDO + Prepared Statements** | 100% de consultas son preparadas. Sin concatenación de SQL.      |
| **BCrypt (cost=12)**          | Contraseñas hasheadas con `password_hash()` + `password_verify()`|
| **Session Regeneration**      | `session_regenerate_id(true)` al autenticar (Anti-Session Fixation) |
| **Role-Based Access Control** | Cada controlador valida el `rol_id` de sesión antes de ejecutar  |
| **Output Escaping**           | `htmlspecialchars()` en todas las salidas hacia HTML             |
| **Input Sanitization**        | `trim()`, `filter_var()`, `FILTER_VALIDATE_EMAIL` en todos los POST |
| **Timing Attack Mitigation**  | `usleep(random_int(...))` en login fallido                       |
| **Route Sanitization**        | `preg_replace('/[^a-zA-Z0-9]/', '', ...)` en parámetros GET      |
| **Error Logging**             | `display_errors = 0` + `log_errors = 1` en producción           |

---

## Paleta de Diseño UI/UX

```css
--bg-page:       #0a0a0a   /* Fondo de página        */
--bg-card:       #141414   /* Fondo de contenedores  */
--border-subtle: rgba(255, 255, 255, 0.08)
--accent:        #0d6efd   /* Azul eléctrico (único color de acento) */
--text-primary:  #f0f0f0
--text-secondary:#9ca3af
--success:       #10b981
--warning:       #f59e0b
--danger:        #ef4444
```

**Principios de diseño aplicados:**
- 🌑 Dark Mode absoluto — sin fondo blanco en ninguna sección
- 🎯 Minimalismo — solo un color de acento (azul `#0d6efd`)
- ✨ Micro-animaciones (`fadeInUp`) en todos los componentes
- 📱 Diseño 100% responsivo — vista tabla en desktop, tarjetas en mobile
- 🔤 Tipografía Inter (sans) + Roboto Mono (folios/código)
- 💎 Glassmorphism sutil en navbar con `backdrop-filter: blur()`

---

## Convenciones del Equipo

### Nomenclatura

- **Controladores:** `NombreController.php` — PascalCase
- **Modelos:** `Nombre.php` — PascalCase  
- **Vistas:** `nombre_vista.php` — snake_case
- **Métodos públicos:** camelCase (`obtenerTodosLosTickets`)
- **Variables PHP:** camelCase (`$idTicket`, `$nombreCompleto`)
- **Columnas BD:** snake_case (`id_solicitante`, `fecha_creacion`)

### Commits sugeridos

```
feat: agregar RF_08 notas internas al ticket
fix: corregir validación de email en TicketController
style: ajustar padding en tabla de dashboard
refactor: extraer lógica de generación de folio al modelo
```

### Flujo de trabajo

```
1. Crear rama feature/RF_XX
2. Implementar cambios
3. Probar en XAMPP local
4. Pull Request → Revisión → Merge
```

---

## Créditos

**Proyecto:** Sistema de Mesa de Ayuda  
**Equipo:** Los Bélicos  
**Stack:** PHP MVC · MySQL PDO · Bootstrap 5 · Chart.js  
**Licencia:** Uso académico / institucional

---

> 💡 Para soporte técnico del proyecto, contacta al equipo de desarrollo Los Bélicos.
