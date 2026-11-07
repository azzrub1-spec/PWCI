<?php
$title = $nivel['titulo'];
$id = (int) $nivel['id_nivel'];
$material = BASE_URL . '/index.php?r=archivo-estudiante&amp;id=' . $id . '&amp;tipo=';
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <a href="<?= BASE_URL ?>/index.php?r=curso&amp;curso=<?= (int) $nivel['id_curso'] ?>"><?= htmlspecialchars($nivel['curso_titulo']) ?></a>
    <h1><?= htmlspecialchars($nivel['titulo']) ?></h1>
  </div>
  <article class="panel">
    <video class="video-nivel" controls preload="metadata" src="<?= $material ?>video">
      Tu navegador no puede reproducir este video.
    </video>
    <?php if (!empty($nivel['contenido_texto'])): ?>
      <p class="texto-nivel"><?= htmlspecialchars($nivel['contenido_texto']) ?></p>
    <?php endif; ?>
    <?php if (!empty($nivel['imagen'])): ?>
      <img class="imagen-contenido-nivel" src="<?= $material ?>imagen" alt="Imagen del nivel">
    <?php endif; ?>
    <?php if (!empty($nivel['archivo_pdf'])): ?>
      <p><a href="<?= $material ?>pdf" target="_blank" rel="noopener">Abrir material PDF</a></p>
    <?php endif; ?>
    <?php if (preg_match('~^https?://~i', $nivel['enlace'] ?? '')): ?>
      <p><a href="<?= htmlspecialchars($nivel['enlace']) ?>" target="_blank" rel="noopener noreferrer">Abrir enlace del nivel</a></p>
    <?php endif; ?>
    <?php if ((bool) $nivel['completado']): ?>
      <p class="aviso exito">Nivel completado.</p>
    <?php else: ?>
      <form action="<?= dirname(BASE_URL) ?>/api/index.php/progreso/<?= $id ?>"
            method="post" data-estudiante-accion data-csrf="<?= htmlspecialchars($csrf) ?>">
        <button type="submit" class="boton">Marcar como completado</button>
        <p role="status" data-estado></p>
      </form>
    <?php endif; ?>
  </article>
</section>
<script src="<?= BASE_URL ?>/js/estudiante.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
