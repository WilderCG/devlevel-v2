# DevLevel v2

Aplicación web desarrollada en **PHP 8.2+** y **MariaDB/MySQL** con **PDO** para la gestión de misiones técnicas, acumulación de experiencia (XP) por niveles y administración de tareas según roles de usuario.

## Características principales

- **Autenticación y roles (RBAC):** Acceso diferenciado para desarrolladores y perfiles de gestión (`PROJECT_MANAGER`).
- **Catálogo de misiones filtrado y paginado:** Los desarrolladores visualizan únicamente las misiones activas acordes a su nivel que todavía no han completado, con paginación en servidor.
- **Progreso persistente:** Al completar una misión, se registra en el historial del usuario y se actualizan sus puntos de experiencia de forma atómica.
- **Panel de gestión:** Interfaz exclusiva para el *Project Manager* que permite dar de alta nuevas misiones y vincularlas con múltiples habilidades técnicas (relación N:M).

## Tecnologías utilizadas

- **Backend:** PHP 8.2+ con tipado estricto (`declare(strict_types=1)`) y programación orientada a objetos.
- **Base de datos:** MariaDB / MySQL con integridad referencial (claves foráneas y restricciones únicas).
- **Acceso a datos:** Extensión nativa PHP Data Objects (**PDO**).
- **Frontend:** HTML5 y CSS3.

## Arquitectura y estructura del proyecto

El proyecto separa la configuración, el acceso a datos, la lógica de negocio y la presentación para facilitar el mantenimiento y evitar código acoplado:

```text
devlevel-v2/
├── config/
│   └── database.php              # Configuración de conexión a la base de datos
├── sql/
│   └── devlevel_pdo.sql          # Esquema relacional y datos iniciales de prueba
├── src/
│   ├── Database.php              # Conexión centralizada PDO (patrón Singleton)
│   ├── helpers.php               # Funciones auxiliares (escape HTML y gestión de sesión)
│   ├── Repositories/
│   │   ├── UserRepository.php    # Consultas de usuarios
│   │   ├── TaskRepository.php    # Filtrado, conteo y listado paginado de misiones
│   │   └── SkillRepository.php   # Consulta de habilidades técnicas
│   └── Services/
│       ├── AuthService.php       # Login, logout y control de estado de sesión
│       └── LevelService.php      # Lógica de negocio al completar misiones
└── public/
    ├── login.php                 # Inicio de sesión
    ├── index.php                 # Catálogo de misiones y registro de progreso
    ├── manager.php               # Panel de creación de misiones (solo PROJECT_MANAGER)
    └── logout.php                # Cierre de sesión
```

## Decisiones técnicas y seguridad

- **Conexión y manejo de errores:** La conexión PDO se gestiona desde una única instancia en `src/Database.php` con excepciones activas (`ERRMODE_EXCEPTION`), recuperación en modo asociativo (`FETCH_ASSOC`) y sentencias preparadas nativas (`EMULATE_PREPARES => false`). Los fallos internos se registran en el log del servidor sin exponer información técnica al usuario.
- **Prevención de inyección SQL:** Todas las consultas utilizan sentencias preparadas con parámetros nombrados y vinculación explícita de enteros (`PDO::PARAM_INT`) en la paginación (`LIMIT` y `OFFSET`).
- **Transacciones ACID:** Operaciones críticas que modifican varias tablas —como completar una misión (`progreso_tareas` + `usuarios`) o crear una tarea con sus habilidades (`tareas` + `tarea_skill`)— se ejecutan dentro de transacciones con `beginTransaction()`, `commit()` y `rollBack()`.
- **Sesiones y protección XSS:** Las contraseñas se validan mediante hashes BCRYPT con `password_verify()`, se regenera el identificador de sesión tras cada inicio de sesión exitoso (`session_regenerate_id(true)`) y todas las salidas dinámicas en las vistas pasan por el helper `h()` (`htmlspecialchars` con `ENT_QUOTES` y `UTF-8`).

## Instalación y puesta en marcha

1. Copia la carpeta `devlevel-v2` en el directorio raíz de tu servidor web local (en XAMPP: `C:\xampp\htdocs\devlevel-v2`).
2. Inicia los servicios de **Apache** y **MySQL / MariaDB**.
3. Crea la base de datos `devlevel_db` desde phpMyAdmin e importa el archivo `sql/devlevel_pdo.sql`.
4. Comprueba que las credenciales de `config/database.php` coinciden con tu entorno local:
   ```php
   return [
       'host' => 'localhost',
       'dbname' => 'devlevel_db',
       'user' => 'root',
       'password' => ''
   ];
   ```
5. Abre el navegador y accede a:
   ```text
   http://localhost/devlevel-v2/public/login.php
   ```

## Cuentas de prueba

El script SQL incluye cuentas preconfiguradas para probar ambos flujos de la aplicación:

| Rol | Usuario | Contraseña | Ruta principal |
| --- | --- | --- | --- |
| Desarrollador Junior | `junior` | `Junior2026!` | `public/index.php` |
| Project Manager | `manager` | `Manager2026!` | `public/manager.php` |