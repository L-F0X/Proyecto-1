# SENA · Directorio de personal

Aplicación inicial para separar y consultar al personal administrativo y a los instructores. Está construida con PHP 8, HTML, CSS, JavaScript y MySQL.

## Requisitos

- PHP 8.1 o superior con las extensiones `pdo_mysql` y `mbstring`.
- MySQL o MariaDB.
- Apache con PHP (por ejemplo, XAMPP).
- PHP disponible en `PATH` para iniciar el servidor integrado desde VS Code.

## Puesta en marcha local

1. En una instalación nueva, importa `schema.sql` en MySQL/MariaDB.
2. Configura las variables de entorno del servidor PHP: `DB_HOST` (opcional; por defecto `127.0.0.1`), `DB_NAME` (opcional; por defecto `sena_personal`), `DB_USER` y `DB_PASS`.
3. En una base que ya usa la versión anterior, aplica `migration_profiles.sql` si aún no la ejecutaste y después `migration_directory_split.sql`, una sola vez cada una. No importes `schema.sql` sobre una base existente.
4. Ingresa desde `http://localhost/Proyecto_1/login.php` con tu cuenta de administración.
5. Abre `http://localhost/Proyecto_1/importar_directorio.php` y carga el libro `.xlsx`.

## Servidor de desarrollo de VS Code

Abre el proyecto en VS Code y ejecuta **Terminal > Ejecutar tarea de compilación** (o `Ctrl+Shift+B`); selecciona `Iniciar servidor local SENA` si se solicita. La tarea sirve el proyecto en `http://127.0.0.1:8000`. Detén el proceso desde su terminal con `Ctrl+C`. Es el servidor integrado de PHP para desarrollo local, no para producción ni para exponer en una red pública. Copia `config.local.example.php` como `config.local.php` y configura allí los datos de tu equipo; este archivo local está excluido de Git y sirve tanto para el servidor integrado como para el cargador demo. En Apache, `SetEnv` debe estar en el contexto correcto y Apache debe reiniciarse al cambiarlo. En desarrollo puedes omitir `DB_PASS` si la cuenta MySQL realmente no tiene contraseña; en otros entornos `DB_PASS` es obligatorio. No guardes credenciales en `tasks.json` ni subas `config.local.php`.

El menú contiene `Principal`, `administrativos.php` e `instructores.php`. El panel Principal muestra el total de personas, los conteos por categoría, sedes registradas, números de contrato, fechas de terminación registradas y fechas futuras o pasadas, además de la distribución por regional y tipo de vinculación. No se usa una tabla separada de contratos; los números y fechas provenientes del Excel se conservan como campos del directorio.

La búsqueda de Administrativos filtra por nombre, número de contrato o documento de identidad. La de Instructores filtra por nombre, documento de identidad o tipo de vinculación. Ambas listas incluyen un rango de fechas de terminación y exportación CSV de las filas visibles; el formato final de exportación se definirá posteriormente.

El importador toma las hojas `Administrativos` e `Instructores`, conserva cada categoría en una tabla separada y omite filas sin rol. Ignora `Listas`, `f` y `Funcionarios Planta`. Las coincidencias se conservan y quedan marcadas para revisión; la importación se puede ejecutar una sola vez para evitar duplicar o reemplazar filas fuente. `Principal` muestra datos agregados; las páginas de Administrativos e Instructores muestran los encabezados y campos del Excel, incluidos documentos, teléfonos, correos personales y direcciones, solo para usuarios con rol de administración o Talento Humano.

En el archivo recibido se identificaron 58 administrativos y 173 instructores con rol explícito. Se omiten 2 filas sin rol; 20 filas tienen coincidencias dentro de su propia hoja y 2 filas coinciden entre categorías, y se marcan para revisión. También se reportan 4 correos institucionales vacíos y 4 fechas de terminación no reconocidas; estas últimas se conservan como texto para corregirlas posteriormente.

Esta etapa solo separa el directorio. Los números de contrato y fechas de terminación del Excel se guardan en los registros de administrativos e instructores; no se crea ni se requiere una tabla independiente de contratos.

El cargador demo descrito a continuación es solo para desarrollo. En una base real no ejecutes ese cargador.

## Datos demo de desarrollo

Para una instalación de desarrollo, se pueden cargar instructores y cuentas ficticias desde PowerShell:

   ```powershell
   $env:APP_ENV = 'development'
   php seed_demo.php --confirm
   ```

Para una instalación sin usuarios, define primero `APP_SETUP_KEY` con una clave aleatoria larga y privada. Abre `http://localhost/Proyecto_1/setup.php` y crea la cuenta administradora inicial; después de crearla, esa página queda deshabilitada.

No se incluye una cuenta ni una contraseña predeterminadas. En Windows, configura las variables de entorno para Apache/PHP desde el entorno en que se inicia XAMPP y reinicia Apache para que las lea. La aplicación busca también las variables configuradas con `SetEnv` en `$_SERVER`, como ocurre en algunas configuraciones de Apache. Para una instalación publicada, sirve el sitio únicamente por HTTPS, restringe el acceso a la base de datos y respalda la información de forma cifrada.

## Perfiles de prueba (solo desarrollo)

El cargador usa nombres y correos ficticios. Las contraseñas de prueba se configuran localmente en `config.local.php`, que está excluido de Git; el cargador solo funciona por CLI, requiere `APP_ENV=development` y exige `--confirm`.

| Perfil | Usuario | Contraseña | Acceso |
|---|---|---|---|
| Administrador | `admin.demo@sena.test` | Configurada localmente | Administración del directorio |
| Talento Humano | `talento.demo@sena.test` | Configurada localmente | Administración del directorio |
| Instructora | `lucia.ramirez@sena.test` | Configurada localmente | Consulta su perfil |
| Instructor | `mateo.gomez@sena.test` | Configurada localmente | Consulta su perfil |
| Instructora | `valentina.cruz@sena.test` | Configurada localmente | Consulta su perfil |

Los perfiles y datos de prueba no deben usarse en producción. Elimina estas cuentas y los registros de ejemplo antes de alojar el sistema con información real.

## Seguridad incluida

- Contraseñas almacenadas con `password_hash()` y verificadas con `password_verify()`.
- Consultas PDO preparadas; escape HTML en la salida; validación del lado del servidor.
- Token CSRF en formularios, regeneración de sesión al iniciar sesión y cookies `HttpOnly`/`SameSite`.
- Encabezados contra clickjacking, MIME sniffing y carga de scripts externos.
- Roles diferenciados para administración, Talento Humano e instructores. Cada instructor solo consulta el perfil vinculado a su cuenta.

El directorio Excel contiene datos personales. Limita la base de datos a usuarios autorizados y cifra los respaldos. El lenguaje por sí solo no garantiza la seguridad; antes de usar datos reales también se requieren controles institucionales de roles, auditoría, políticas de retención y revisión de seguridad.
