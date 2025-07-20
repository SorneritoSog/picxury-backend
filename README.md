# API de Proyecto de Fotografías

Una API REST desarrollada con Laravel para la gestión de fotógrafos, sesiones fotográficas, clientes y portafolios.

## Requisitos del Sistema

Antes de instalar el proyecto, asegúrate de tener instalado lo siguiente:

- **PHP 8.2 o superior**
- **Composer** (gestor de dependencias de PHP)
- **Node.js 18 o superior** y **npm**
- **XAMPP** (para Apache y MySQL)
- **Git** (para clonar el repositorio)

### Configurar XAMPP

1. **Instalar XAMPP** desde [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. **Iniciar servicios** en el panel de control de XAMPP:
   - ✅ **Apache** - Servidor web
   - ✅ **MySQL** - Base de datos

### Verificar Requisitos

```bash
php --version
composer --version
node --version
npm --version
```

## Instalación

Sigue estos pasos para configurar el proyecto en tu computador:

### 1. Clonar el Repositorio

```bash
git clone https://github.com/Swithsere094/laravel-api.git
cd laravel-api
```

### 2. Instalar Dependencias de PHP

```bash
composer install
```

### 3. Instalar Dependencias de Node.js

```bash
npm install
```

### 4. Configurar Variables de Entorno

```bash
# Copiar el archivo de configuración
cp .env.example .env

# Generar la clave de aplicación
php artisan key:generate
```

### 5. Configurar Base de Datos

Edita el archivo `.env` y configura tu base de datos MySQL de XAMPP:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_base_de_datos
DB_USERNAME=root
DB_PASSWORD=
```

**Nota:** En XAMPP, el usuario por defecto es `root` y la contraseña suele estar vacía.

### 6. Ejecutar Migraciones

```bash
# Crear las tablas en la base de datos
php artisan migrate
```

### 7. Insertar Datos Iniciales Requeridos

Después de ejecutar las migraciones, es **OBLIGATORIO** insertar estos registros específicos en las siguientes tablas:

#### Tabla `services`
Ejecuta estas consultas SQL en tu base de datos:

```sql
INSERT INTO services (id, name, type) VALUES 
(1, 'Sin editar', 'Edición'),
(2, 'Edición básica', 'Edición'),
(3, 'Edición intermedia', 'Edición'),
(4, 'Edición avanzada', 'Edición'),
(5, 'Foto profesional', 'Foto');
```

#### Tabla `photo_session_types`
Ejecuta estas consultas SQL en tu base de datos:

```sql
INSERT INTO photo_session_types (id, name, description) VALUES 
(1, 'Retrato', 'Individual, profesional, etc.'),
(2, 'Boda', 'Preboda, ceremonia, postboda...'),
(3, 'Familiar', 'Padres e hijos, Embarazo, etc.'),
(4, 'Evento', 'Conciertos, conferencias, etc.');
```

**Nota:** Estos registros son esenciales para el funcionamiento correcto de la aplicación. Sin ellos, el sistema no funcionará adecuadamente.

### 8. Poblar Base de Datos (Opcional)

```bash
# Poblar la base de datos con datos de prueba adicionales
php artisan db:seed
```

### 9. Configurar Almacenamiento

```bash
# Crear enlace simbólico para archivos públicos
php artisan storage:link
```

### 10. Construir Assets Frontend

```bash
# Para desarrollo
npm run dev

# Para producción
npm run build
```

## Ejecutar el Proyecto

### Servidor de Desarrollo

```bash
# Iniciar el servidor Laravel
php artisan serve

# En otra terminal, ejecutar el worker de colas (OBLIGATORIO)
php artisan queue:work

# En una tercera terminal, navegar al frontend React y ejecutar Vite
cd ../frontend-react  # o la carpeta donde esté tu proyecto React
npm run dev
```

El proyecto estará disponible en: 
- **Backend (API):** `http://localhost:8000`
- **Frontend (React):** `http://localhost:3000` (o el puerto que configure Vite)

**Importante:** El comando `php artisan queue:work` es **OBLIGATORIO** para que funcionen correctamente las notificaciones y otros trabajos en segundo plano del sistema.

### Scripts Disponibles

El proyecto incluye varios scripts útiles:

- `run-worker.sh` - Ejecutar workers de cola
- `run-cron.sh` - Ejecutar tareas programadas
- `build-app.sh` - Script de construcción

## Estructura del Proyecto

- **app/Models/** - Modelos Eloquent (Photographer, PhotoSession, Client, etc.)
- **app/Http/Controllers/** - Controladores de la API
- **database/migrations/** - Migraciones de base de datos
- **routes/api.php** - Rutas de la API
- **public/images/** - Archivos de imágenes

## Funcionalidades Principales

- Gestión de fotógrafos y sus servicios
- Manejo de sesiones fotográficas
- Sistema de clientes
- Portafolios con categorías
- Notificaciones
- Movimientos financieros
- Autenticación con Laravel Sanctum

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development/)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
