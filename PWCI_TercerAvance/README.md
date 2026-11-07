# KONNEKT — Tercer avance, cambio 1: categorías

Esta carpeta parte del segundo avance y agrega la gestión de categorías.
No es todavía la entrega final completa. Los cursos y niveles del instructor,
las inscripciones, el progreso, los reportes y los demás módulos pendientes
se trabajarán en cambios posteriores. PayPal conserva la versión anterior;
queda pendiente adaptarlo al formulario propio solicitado por el profesor.

## Instalación sin borrar lo anterior

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

## Subir este cambio a GitHub

Agrega únicamente esta carpeta `PWCI_TercerAvance` al repositorio existente.
No reemplaces el primer o segundo avance. Este paquete corresponde a una sola
función, con la preparación necesaria de la carpeta del tercer avance.

Mensaje sugerido del commit:

`Agregar gestión de categorías del administrador y filtro del catálogo`

Cada integrante debe subir sus cambios reales con su propia cuenta.
No se han creado commits ni subido archivos automáticamente a GitHub.

## Verificación del paquete

Se comprobó la sintaxis JavaScript y el flujo de interfaz con respuestas simuladas.
La prueba real con PHP, MySQL y sus usuarios debe realizarse en XAMPP siguiendo
los pasos anteriores; este entorno de revisión no tiene PHP ni MySQL instalados.
