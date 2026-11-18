<?php
$title = 'Mi panel';

$accesos = match ($usuario['rol']) {
    'estudiante' => [
        [
            'titulo' => 'Mis cursos',
            'descripcion' => 'Continúa tus niveles y consulta tu avance y certificados.',
            'ruta' => 'mis-inscripciones',
            'boton' => 'Ver mis cursos',
        ],
        [
            'titulo' => 'Explorar cursos',
            'descripcion' => 'Encuentra cursos y elige lo que quieres aprender.',
            'ruta' => 'catalogo',
            'boton' => 'Explorar catálogo',
        ],
        [
            'titulo' => 'Mensajes',
            'descripcion' => 'Consulta tus conversaciones con los instructores.',
            'ruta' => 'mensajes',
            'boton' => 'Ver mensajes',
        ],
    ],
    'instructor' => [
        [
            'titulo' => 'Mis cursos',
            'descripcion' => 'Administra tus cursos y organiza sus niveles y materiales.',
            'ruta' => 'mis-cursos',
            'boton' => 'Administrar cursos',
        ],
        [
            'titulo' => 'Reporte de ventas',
            'descripcion' => 'Consulta las inscripciones, el avance y los ingresos de tus cursos.',
            'ruta' => 'ventas-instructor',
            'boton' => 'Consultar reporte',
        ],
        [
            'titulo' => 'Mensajes',
            'descripcion' => 'Consulta y responde las preguntas de tus estudiantes.',
            'ruta' => 'mensajes',
            'boton' => 'Ver mensajes',
        ],
    ],
    'administrador' => [
        [
            'titulo' => 'Usuarios',
            'descripcion' => 'Consulta las cuentas y administra su estado de acceso.',
            'ruta' => 'usuarios-admin',
            'boton' => 'Administrar usuarios',
        ],
        [
            'titulo' => 'Categorías',
            'descripcion' => 'Organiza las categorías disponibles para los cursos.',
            'ruta' => 'categorias',
            'boton' => 'Administrar categorías',
        ],
        [
            'titulo' => 'Reporte de usuarios',
            'descripcion' => 'Consulta la información de estudiantes e instructores.',
            'ruta' => 'reporte-usuarios',
            'boton' => 'Consultar reporte',
        ],
    ],
    default => [],
};

require dirname(__DIR__) . '/layout/header.php';
?>

<section class="pagina panel-inicio">
  <div class="panel-bienvenida">
    <div class="titulo">
      <span class="etiqueta">
        <?= htmlspecialchars(
            strtoupper($usuario['rol']),
            ENT_QUOTES,
            'UTF-8'
        ) ?>
      </span>

      <h1>
        Bienvenido, <?= htmlspecialchars(
            $usuario['nombre'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
      </h1>

      <p>Desde aquí puedes acceder a las opciones de tu cuenta.</p>
    </div>

    <a
      class="boton-secundario"
      href="<?= BASE_URL ?>/index.php?r=perfil"
    >
      Ver mi perfil
    </a>
  </div>

  <div class="panel-seccion">
    <h2>¿Qué quieres hacer hoy?</h2>
    <p class="muted">Selecciona una opción para continuar.</p>
  </div>

  <div class="rejilla panel-accesos">
    <?php foreach ($accesos as $acceso): ?>
      <article class="tarjeta panel-acceso">
        <h3>
          <?= htmlspecialchars(
              $acceso['titulo'],
              ENT_QUOTES,
              'UTF-8'
          ) ?>
        </h3>

        <p>
          <?= htmlspecialchars(
              $acceso['descripcion'],
              ENT_QUOTES,
              'UTF-8'
          ) ?>
        </p>

        <a
          class="boton"
          href="<?= BASE_URL ?>/index.php?r=<?= htmlspecialchars(
              $acceso['ruta'],
              ENT_QUOTES,
              'UTF-8'
          ) ?>"
        >
          <?= htmlspecialchars(
              $acceso['boton'],
              ENT_QUOTES,
              'UTF-8'
          ) ?>
        </a>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="panel-sesion">
    <p class="muted">Puedes cerrar tu sesión al terminar.</p>

    <form method="post" action="<?= BASE_URL ?>/index.php?r=logout">
      <button class="boton-secundario" type="submit">
        Cerrar sesión
      </button>
    </form>
  </div>
</section>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>