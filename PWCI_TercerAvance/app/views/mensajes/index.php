<?php
$title = 'Mensajes';
require dirname(__DIR__) . '/layout/header.php';
?>

<section class="pagina pagina-mensajes">
  <div class="titulo">
    <span class="etiqueta">CONVERSACIONES</span>
    <h1>Mensajes privados</h1>

    <?php if ($esEstudiante): ?>
      <p>Para hacer una pregunta, abre un curso del catálogo y elige «Preguntar al instructor».</p>
    <?php else: ?>
      <p>Consulta y responde las preguntas de tus estudiantes.</p>
    <?php endif; ?>
  </div>

  <?php if ($conversaciones === []): ?>
    <div class="panel mensajes-vacio">
      <h2>Todavía no tienes conversaciones</h2>
      <p class="muted">
        <?= $esEstudiante
            ? 'Cuando contactes a un instructor, la conversación aparecerá aquí.'
            : 'Aquí aparecerán las conversaciones que inicien tus estudiantes.' ?>
      </p>
    </div>
  <?php endif; ?>

  <div class="lista-niveles">
    <?php foreach ($conversaciones as $item): ?>
      <?php
      $url = BASE_URL . '/index.php?r=conversacion&curso='
          . (int) $item['id_curso'];

      if (!$esEstudiante) {
          $url .= '&alumno=' . (int) $item['id_estudiante'];
      }
      ?>

      <article class="panel mensajes-conversacion">
        <div class="mensajes-datos">
          <h2><?= htmlspecialchars($item['titulo']) ?></h2>

          <p>
            <span class="muted">
              <?= $esEstudiante ? 'Instructor:' : 'Estudiante:' ?>
            </span>
            <?= htmlspecialchars(
                $item['nombre'] . ' ' . $item['apellidos']
            ) ?>
          </p>
        </div>

        <a class="boton-secundario"
           href="<?= htmlspecialchars($url) ?>">
          Abrir conversación
        </a>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>