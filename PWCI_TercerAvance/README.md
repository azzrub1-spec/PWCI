# KONNEKT — Tercer avance, cambio 2: cursos del instructor

Esta versión conserva las categorías del cambio 1 y agrega el formulario del
instructor para crear, editar y dar de baja sus propios cursos.
No es todavía la entrega final completa. Los niveles, las inscripciones,
el progreso, los reportes y los demás módulos pendientes
se trabajarán en cambios posteriores. PayPal conserva la versión anterior;
queda pendiente adaptarlo al formulario propio solicitado por el profesor.

## Aplicar la actualización 02 sin borrar lo anterior

El ZIP `KONNEKT_TercerAvance_02_Cursos_Actualizacion.zip` contiene únicamente
los archivos nuevos o modificados. Necesitas tener instalada la versión 01
con categorías y su `bootstrap.php`, que ya se probó en XAMPP.

1. Descomprime la actualización fuera de `htdocs`.
2. Copia el contenido de su carpeta `PWCI_TercerAvance` dentro de tu carpeta
   existente `C:\xampp\htdocs\PWCI_TercerAvance`, aceptando reemplazar los
   archivos repetidos. **No borres la carpeta existente.**
3. Conserva `config/config.php`, `bootstrap.php`, tus imágenes y la base de datos.
   Esta actualización no modifica el esquema ni necesita importar SQL.
4. Recarga con Ctrl+F5 y entra como instructor. Abre **Mis cursos** en el menú.

Para otro integrante que todavía no tenga la versión 01, primero debe instalar
el ZIP corregido de categorías y después aplicar esta actualización.

## Prueba del cambio 02

1. Entra como instructor, abre **Mis cursos** y registra un curso con título,
   descripción, una categoría existente, costo y una imagen JPG o PNG de hasta
   2 MB. El costo 0 significa que el curso completo es gratuito.
2. Verifica que aparezca en Mis cursos, en Catálogo y en la tabla `curso`.
   Debe tener el `id_instructor` de la cuenta con la que iniciaste sesión.
   La imagen se copia a `public/uploads/cursos` con un nombre aleatorio.
   La carpeta se crea automáticamente. MySQL guarda la ruta, no un BLOB.
3. Edita el título, descripción, categoría o costo y guarda. Debe conservar
   su instructor y fecha de publicación originales.
4. Guarda otra edición sin elegir imagen: conserva la anterior.
   Después selecciona otra JPG/PNG y verifica que cambie en el catálogo.
5. Prueba campos obligatorios vacíos, costo negativo, más de dos decimales
   o un archivo que no sea JPG/PNG. Deben rechazarse. La API también valida
   los datos aunque se envíen desde Postman.
6. Prueba **Dar de baja**: primero cancela y verifica que siga activo.
   Después confirma: conserva su fila con `activo=0`, desaparece del catálogo
   y permanece en Mis cursos con la leyenda “Dado de baja”. No se borra el curso.
7. Como estudiante o administrador, abre directamente
   `public/index.php?r=mis-cursos`: debe negar el acceso.
   Sin sesión debe dirigir al inicio de sesión.
8. Si tienes otra cuenta de instructor, verifica que su lista contenga solo
   sus cursos. En Postman intenta modificar un ID del otro instructor: debe
   responder 404 y conservar el registro sin cambios.

En este cambio se registra el curso general. Los niveles y su contenido se
agregarán en la siguiente actualización, no están terminados en este paquete.

## API de cursos

Ruta base: `/PWCI_TercerAvance/api/index.php`.
Todas las respuestas son JSON.

| Método y ruta | Acceso | Resultado correcto |
| --- | --- | --- |
| `GET /cursos` | Público | 200, cursos activos y filtros `titulo`, `categoria` |
| `GET /cursos/{id}` | Público | 200, curso activo; 404 si está dado de baja |
| `GET /cursos?propios=1` | Instructor | 200, sus cursos activos y dados de baja, `csrf_token` |
| `GET /cursos/{id}?propios=1` | Instructor propietario | 200, datos de su curso |
| `POST /cursos` | Instructor | 201, `id` y mensaje de creación |
| `PUT /cursos/{id}` | Instructor propietario | 200, `id` y mensaje de actualización |
| `DELETE /cursos/{id}` | Instructor propietario | 200, baja lógica |

Las escrituras requieren la cookie de sesión y `X-CSRF-Token`. Inicia sesión
como instructor y consulta `GET /cursos?propios=1` para obtener el token.
Las peticiones de Postman incluidas guardan ese token y el `curso_id` nuevo.
Se conservan las rutas de cursos del segundo avance; el alta ahora exige
la imagen que pide la FE y se agrega PUT para edición.

Para crear, usa Body > form-data: `titulo` (máximo 150 caracteres),
`descripcion` (obligatoria, máximo 65535 bytes), `categoria_id` (existente),
`costo` (de 0 a 99999999.99, hasta dos decimales) e `imagen` (tipo File).
No escribas manualmente Content-Type en form-data: Postman o el navegador
establece el encabezado multipart con su separador.

Para editar sin cambiar la imagen, PUT recibe `Content-Type: application/json`:

```json
{
  "titulo": "Curso editado",
  "descripcion": "Descripción actualizada.",
  "categoria_id": 2,
  "costo": "249.50"
}
```

Para editar y reemplazar la imagen, el formulario usa POST multipart a
`/cursos/{id}` con los mismos campos y `_method=PUT`. PHP recibe archivos
por POST; el punto de entrada interpreta ese campo y ejecuta la actualización
con las mismas comprobaciones de sesión, rol, CSRF y propiedad del curso.
La API no permite cambiar el instructor, la fecha o el estado mediante los
campos del formulario. La baja lógica solo se realiza con DELETE.

Errores: 400 por datos o archivo inválidos; 401 sin sesión; 403 por rol, origen
o token inválido; 404 si el curso no existe o pertenece a otro instructor;
409 si ya está dado de baja; 500 por un error interno.
Ejemplo: `{"success":false,"message":"Selecciona una categoría existente."}`.
En un alta correcta responde 201:
`{"success":true,"id":1,"message":"Curso creado correctamente."}`.

En la colección de Postman: inicia sesión como instructor y ejecuta “Consultar
mis cursos y obtener token”. En “Registrar curso (elige una imagen)”, selecciona
una JPG/PNG local en el campo `imagen`. Cambia `categoria_id` si el ID 2 no
corresponde a una categoría que tengas. La edición conserva la imagen y la
baja se prueba al final. Las contraseñas de la colección se dejan vacías.

El controlador recibe `CursoRepository` y `CategoriaRepository` por constructor.
El punto de entrada les entrega sus modelos MySQL. Las consultas quedan en
`app/models/Curso.php`; no se agregan consultas SQL a las vistas.

## Instalación inicial de la versión 01

1. Descomprime y copia `PWCI_TercerAvance` dentro de `xampp/htdocs`.
   Conserva las carpetas del primer y segundo avance.
2. Enciende Apache y MySQL en XAMPP.
3. Si ya tienes la base `konnekt` del segundo avance, úsala tal como está.
   **No vuelvas a importar el SQL ni borres tus usuarios o cursos.**
   `database/konnekt.sql` solo sirve para una instalación nueva.
4. Revisa `config/config.php`: conexión MySQL y `BASE_URL`.
   Con la carpeta directamente en `htdocs`, la URL es
   `http://localhost/PWCI_TercerAvance/public/index.php`.
   Si Apache usa 8080, agrega `:8080` después de `localhost`.
   Si colocas el proyecto dentro de otra carpeta, ajusta también `BASE_URL`.
5. Usa los correos y contraseñas que ya registraste. No se crean cuentas nuevas.
   Las fotografías incluidas son las que venían en el segundo avance; copia
   también tus demás fotos locales de `public/uploads/perfiles` si tienes otras.
6. No se incluye `config/paypal.local.php` porque contiene credenciales privadas.
   No se necesita para probar categorías. No subas ese archivo a GitHub.

## Qué probar

1. Inicia sesión como administrador y abre **Categorías** en el menú.
2. Crea una categoría con nombre y descripción. Debe aparecer con el nombre
   del administrador y la fecha y hora de creación.
3. Edita su nombre o descripción. El creador y la fecha originales se conservan.
4. Intenta repetir un nombre existente: debe aparecer un error visible.
5. Intenta guardar un nombre vacío o compuesto de espacios: debe rechazarse.
6. Elimina una categoría de prueba sin cursos: primero pide confirmación.
7. Intenta eliminar una categoría utilizada por un curso: debe rechazarse
   y conservar tanto la categoría como el curso.
8. Abre el catálogo: las categorías aparecen en el selector y permiten filtrar.
9. Como estudiante o instructor, abre
   `public/index.php?r=categorias`: debe responder 403. Sin sesión debe llevar
   al inicio de sesión. Las operaciones de escritura de la API también están
   protegidas, aunque se invoquen desde Postman.

Las categorías iniciales del SQL no tenían creador: muestran
“Sin creador registrado”. Las nuevas sí guardan el administrador autenticado.

## API de categorías

Ruta base: `/PWCI_TercerAvance/api/index.php`.
Todas las respuestas usan `Content-Type: application/json; charset=utf-8`.

| Método y ruta | Acceso | Resultado correcto |
| --- | --- | --- |
| `GET /categorias` | Público | 200, `success` y lista `categorias` |
| `POST /categorias` | Administrador | 201, `success`, `id`, `message` |
| `PUT /categorias/{id}` | Administrador | 200, `success`, `id`, `message` |
| `DELETE /categorias/{id}` | Administrador | 200, `success`, `message` |

POST y PUT reciben un objeto JSON como este:

```json
{
  "nombre": "Fotografía",
  "descripcion": "Cursos de fotografía y edición."
}
```

El nombre es obligatorio y admite hasta 100 caracteres, sin saltos de línea.
La descripción es opcional y admite hasta 65535 bytes UTF-8.
El creador y la fecha los establece el servidor; no se reciben del cliente.

Las escrituras necesitan la cookie de sesión del administrador, los encabezados
`Content-Type: application/json` y `X-CSRF-Token`.
Primero consulta GET `/categorias` con esa misma sesión: devuelve `csrf_token`
solo al administrador. La colección de Postman guarda ese token automáticamente.

Errores: 400 para datos o ID inválidos; 401 sin sesión; 403 por rol u origen
no autorizado o token CSRF inválido; 404 si no existe el recurso o la ruta;
409 por nombre duplicado o categoría con cursos; 500 ante un error interno.
Ejemplo: `{"success":false,"message":"Ya existe una categoría con ese nombre."}`.

En Postman importa la colección incluida y configura `base_url` según tu puerto.
Escribe tus datos de administrador en las variables **solo para la prueba local**,
sin publicar contraseñas. Ejecuta “Iniciar sesión como administrador”, después
“Consultar categorías y obtener token”, y luego las operaciones de categorías.
El alta guarda automáticamente `categoria_id`. El error de nombre vacío debe
probarse antes de eliminar esa categoría. Al salir de sesión o cambiar de cuenta,
vuelve a consultar categorías para renovar el token.

## Código y dependencias

- `public/index.php`: protege la página por rol antes de ejecutar el controlador.
- `api/index.php`: aplica CORS, sesión, rol y CSRF antes de las escrituras.
- `app/controllers/CategoriaController.php`: valida datos y responde en JSON.
- `app/models/Categoria.php`: consultas preparadas a la tabla existente.
- `app/contracts/CategoriaRepository.php`: contrato recibido por el constructor
  del controlador. Permite sustituir el modelo sin modificar el controlador.
- `app/views/categorias/index.php` y `public/js/categorias.js`: formulario y lista,
  consumo con fetch, confirmación de eliminación y mensajes sin recargar.

## Subir el cambio 02 a GitHub

Después de probarlo, sube los archivos de esta actualización dentro de la
carpeta `PWCI_TercerAvance` que ya existe en el repositorio. No crees otra
carpeta del mismo nombre dentro de ella, no borres lo anterior y no subas el ZIP.
La lista exacta de archivos nuevos o modificados es el contenido del ZIP.
No subas las imágenes de pruebas ni `config/paypal.local.php`.

Mensaje sugerido del commit:

`Agregar creación, edición y baja lógica de cursos del instructor`

Cada integrante debe subir sus cambios reales con su propia cuenta.
No se han creado commits ni subido archivos automáticamente a GitHub.

## Verificación del paquete

Se comprobó la sintaxis JavaScript y el flujo de interfaz con respuestas simuladas.
La carga real de imágenes, las sesiones y el guardado en MySQL deben probarse
en XAMPP siguiendo los pasos anteriores. Este entorno de revisión no tiene
PHP ni MySQL instalados; las pruebas simuladas no sustituyen esa comprobación.
