<?php
$title = 'Mensajes';
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <h1>Mensajes privados</h1>
    <?php if ($esEstudiante): ?>
      <p>Para hacer una pregunta, abre un curso del catálogo y elige «Preguntar al instructor».</p>
    <?php else: ?>
      <p>Consulta y responde las preguntas de tus estudiantes.</p>
    <?php endif; ?>
  </div>

  <?php if ($conversaciones === []): ?>
    <p class="muted">Todavía no tienes conversaciones.</p>
  <?php endif; ?>

  <div class="lista-niveles">
    <?php foreach ($conversaciones as $item): ?>
      <?php
      $url = BASE_URL . '/index.php?r=conversacion&curso=' . (int) $item['id_curso'];
      if (!$esEstudiante) {
          $url .= '&alumno=' . (int) $item['id_estudiante'];
      }
      ?>
      <article class="panel">
        <h2><?= htmlspecialchars($item['titulo']) ?></h2>
        <p><?= htmlspecialchars($item['nombre'] . ' ' . $item['apellidos']) ?></p>
        <a class="boton-secundario" href="<?= htmlspecialchars($url) ?>">Abrir conversación</a>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
