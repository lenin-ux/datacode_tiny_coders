# DATACODE — Sistema Administrativo de la Jornada Académica

Sistema web para organizar y llevar el control de la Jornada Académica de la carrera de Ingeniería de Software: talleres, torneos, hackathones, eventos generales y el foro de propuestas de los alumnos. Cada persona ve y usa solo lo que le corresponde según su rol, para que administrar la jornada sea simple tanto para el Coordinador como para un alumno que solo quiere inscribirse a una actividad.

## ¿Para quién es y qué puede hacer cada quien?

El sistema tiene seis tipos de cuenta. Todas inician sesión con su matrícula y contraseña desde la misma página principal.

**Alumno.** Puede ver e inscribirse a talleres, torneos, hackathones y eventos, revisar el foro de propuestas y votar por las que le interesen, proponer un taller nuevo o pedir unirse al Comité, y administrar su perfil (foto, contraseña, credencial imprimible).

**Alumno miembro de Comité.** Tiene todo lo anterior, y además puede proponer torneos, hackathones y temas generales (no solo talleres), y participar más activamente en el foro de propuestas.

**Docente.** Tiene su propio panel donde ve los talleres, torneos y hackathones que tiene asignados como responsable, con la lista de alumnos inscritos en cada uno.

**Coordinador.** Es quien administra la jornada: crea jornadas, aulas y disponibilidades; publica y modifica talleres, torneos, hackathones y eventos; da de alta a Docentes y a personal de Jefatura; integra alumnos al sistema (uno por uno o cargando un Excel); revisa y aprueba o rechaza las propuestas del foro; y genera los horarios e imprimibles generales.

**Departamento de Ingeniería y Jefatura de Academia.** Ambos puestos funcionan igual en el sistema: solo consultan. Pueden ver el horario general de la jornada y los listados imprimibles, pero no pueden crear ni modificar nada.

## Funciones principales

- **Talleres, Torneos y Hackathones**: publicar, ver disponibles, inscribirse y modificar. Los Hackathones además incluyen rúbrica de evaluación y lista de entregables.
- **Eventos generales**: actividades de la jornada que no son taller, torneo ni hackathon (por ejemplo una conferencia o inauguración).
- **Foro de propuestas**: cualquier alumno puede proponer ideas, la comunidad comenta y vota, y el Coordinador aprueba o rechaza. Si se aprueba una propuesta para unirse al Comité, esa persona pasa a tener el rol de Comité automáticamente.
- **Perfil**: cada usuario ve sus datos, puede cambiar su foto y su contraseña, e imprimir su gafete/credencial.
- **Horario general e imprimibles**: una vista que junta talleres, torneos, hackathones y eventos en un solo calendario, y avisa si dos actividades chocan en la misma aula y horario.

## Tecnologías utilizadas

- **PHP** (sin framework) para toda la lógica del servidor.
- **MySQL / MariaDB** como base de datos.
- **Bootstrap 5** para el diseño y que se vea bien en computadora, tablet y celular.
- **JavaScript con AJAX** para las partes que no necesitan recargar la página completa, como iniciar sesión, votar en el foro o comentar una propuesta.

No usa frameworks de PHP (como Laravel) ni gestores de paquetes (Composer o npm); todo corre con PHP y MySQL directamente, lo que lo hace fácil de instalar en casi cualquier hospedaje.

## Requisitos para instalarlo

Para correr este sistema necesitas:

- Un servidor con **PHP 7.4 o superior** (recomendado 8.0+), con las extensiones `PDO MySQL`, `mbstring` y `ZipArchive` habilitadas (las tres son estándar en la mayoría de instalaciones de PHP, incluyendo XAMPP/WAMP/Laragon y casi cualquier hospedaje Linux).
- **MySQL o MariaDB** (versión 5.7 o superior).
- Conexión a internet en el navegador de quien lo usa, porque Bootstrap y los íconos se cargan desde internet (no hace falta internet en el servidor).
- Cualquier forma común de correr PHP: XAMPP, WAMP, Laragon en una computadora, o un servidor Linux con Apache/Nginx para producción.

No hay dependencias que instalar por separado (no se necesita Composer ni npm); el proyecto funciona tal cual se descarga.

## Guía de instalación

### 1. Descarga el proyecto

Copia la carpeta del proyecto a donde vayas a correrlo (por ejemplo, dentro de `htdocs` si usas XAMPP).

### 2. Crea la base de datos

Con phpMyAdmin (o el gestor que prefieras) crea una base de datos vacía y, en este orden, importa:

1. `datacode_db.sql` — la estructura original de tablas.
2. `database/migracion_v2_mvp.sql` — agrega lo que faltaba (rol Docente, columnas nuevas, etc.).
3. `database/seed_datos_prueba.sql` — llena el sistema con datos de ejemplo para poder probarlo de inmediato (opcional, pero muy recomendable la primera vez).

**Atajo:** si solo quieres probar el sistema rápido, `database/instalacion_completa_ejemplo.sql` junta los tres archivos anteriores en uno solo — impórtalo una sola vez y ya tienes la base de datos lista con datos de ejemplo. Úsalo solo sobre una base de datos vacía nueva (no lo corras si ya tienes información que quieras conservar).

### 3. Configura la conexión a la base de datos

Copia el archivo `.env.example` y renómbralo a `.env`. Ábrelo y coloca los datos de tu servidor de base de datos:

```
DB_HOST=localhost
DB_PORT=
DB_NAME=datacode.db
DB_USER=root
DB_PASS=tu_contraseña
DB_CHARSET=utf8mb4
```

Este archivo `.env` es solo tuyo, no se sube al repositorio (ver `.gitignore`), así que cada quien pone ahí sus propias credenciales sin riesgo de compartirlas por accidente.

### 4. Levanta el sistema

Si usas XAMPP/WAMP: enciende Apache y MySQL desde el panel de control, y entra a `http://localhost/nombre-de-la-carpeta/index.php`.

Si prefieres probarlo rápido sin configurar Apache: con MySQL encendido, abre una terminal en la carpeta del proyecto y ejecuta:

```
php -S localhost:8000
```

y entra a `http://localhost:8000/index.php` en tu navegador.

### 5. Inicia sesión

Si cargaste los datos de ejemplo (`seed_datos_prueba.sql`), puedes entrar con cualquiera de los usuarios ahí incluidos, o con estas cuentas de personal creadas para probar cada rol nuevo:

| Puesto | Matrícula | Contraseña |
|---|---|---|
| Docente | 00060001 | 2706AADD |
| Departamento de Ingeniería | 00060002 | 2706AADE |
| Jefatura de Academia | 00060003 | 2706AADF |

## Despliegue en un VPS Linux (producción)

El sistema es 100% compatible con un servidor Linux (Ubuntu, Debian, etc.), ya que está hecho en PHP puro sin nada específico de Windows: no usa rutas con diagonal invertida, ni funciones ni mayúsculas/minúsculas que solo funcionen en Windows. Estos son los pasos generales para ponerlo en un VPS:

1. **Instala los paquetes necesarios**: un servidor web (Apache o Nginx), PHP 8.x con las extensiones `php-mysql`, `php-mbstring` y `php-zip`, y MySQL o MariaDB. En Ubuntu, por ejemplo: `sudo apt install apache2 php php-mysql php-mbstring php-zip mysql-server`.
2. **Sube el proyecto** a una carpeta del servidor (por ejemplo `/var/www/datacode`).
3. **Apunta el sitio (virtual host) a la raíz del proyecto**, no a una subcarpeta. El sistema usa rutas absolutas como `/src/css/style.css`, así que debe ser la raíz del dominio o subdominio (por ejemplo `https://datacode.tudominio.com/`) y no algo como `https://tudominio.com/datacode/`.
4. **Da permisos de escritura** al usuario del servidor web (normalmente `www-data`) sobre las carpetas donde se guardan las fotos de perfil: `src/img/usuarios/` y `src/img/anuncios/`.
5. **Crea la base de datos y el archivo `.env`** siguiendo los mismos pasos de la guía de instalación de arriba, con los datos del MySQL del servidor.
6. **Activa HTTPS** (por ejemplo con Certbot/Let's Encrypt, gratuito) para que el sistema no viaje sin cifrar por internet.
7. Si quieres que la carga de alumnos por Excel envíe correo real con la contraseña generada (función `mail()`), el servidor necesita tener configurado un agente de correo (Postfix, Sendmail) o cambiar esa función por un envío por SMTP; si no, el sistema sigue funcionando igual y simplemente muestra la contraseña en pantalla para copiarla manualmente, como ya pasa hoy en desarrollo local.

## Sobre esta versión

Este sistema está en su primera versión funcional (producto mínimo viable): todas las funciones descritas arriba ya trabajan de principio a fin y fueron probadas con el sistema corriendo de verdad, no solo revisando el código. Quedan pendientes para una siguiente etapa, ya planeada, un repaso más a fondo del diseño visual en pantallas muy pequeñas y un reforzamiento de seguridad (como guardar las contraseñas de forma cifrada en vez de texto plano). Ninguno de esos pendientes afecta que el sistema funcione hoy para administrar la jornada.
