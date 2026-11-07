<?php $title = 'Mis cursos'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina">
  <div class="titulo">
    <h1>Mis cursos</h1>
    <p>Cursos inscritos: <?= count($cursos) ?></p>
  </div>
  <?php if ($cursos === []): ?>
    <p>Todavía no tienes cursos inscritos. Visita el <a href="<?= BASE_URL ?>/index.php?r=catalogo">catálogo</a>.</p>
  <?php endif; ?>
  <div class="lista-niveles">
    <?php foreach ($cursos as $curso): ?>
      <?php $total = (int) $curso['total']; $completed = (int) $curso['completados']; ?>
      <article class="panel">
        <span class="etiqueta"><?= htmlspecialchars($curso['categoria']) ?></span>
        <h2><?= htmlspecialchars($curso['titulo']) ?></h2>
        <p><?= $completed ?> de <?= $total ?> niveles completados</p>
        <progress value="<?= $completed ?>" max="<?= max(1, $total) ?>" aria-label="Progreso del curso"></progress>
        <?php if ($total > 0 && $completed === $total): ?>
          <p>Curso completado.</p>
        <?php endif; ?>
        <?php if ((bool) $curso['activo']): ?>
          <div class="acciones">
            <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=curso&amp;curso=<?= (int) $curso['id_curso'] ?>">Ver niveles</a>
          </div>
        <?php else: ?>
          <p class="muted">Curso dado de baja.</p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
