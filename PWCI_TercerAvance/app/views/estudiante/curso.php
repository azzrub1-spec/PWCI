<?php
$title = $curso['titulo'];
$courseId = (int) $curso['id_curso'];
$freeLevels = array_filter($niveles, static fn (array $n): bool => (bool) $n['gratis'] && (float) $n['costo_nivel'] === 0.0);
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <span class="etiqueta"><?= htmlspecialchars($curso['categoria']) ?></span>
    <h1><?= htmlspecialchars($curso['titulo']) ?></h1>
    <p>Instructor: <?= htmlspecialchars($curso['instructor']) ?></p>
    <p><?= nl2br(htmlspecialchars($curso['descripcion'])) ?></p>
  </div>

  <?php if ($inscrito): ?>
    <p class="aviso exito">Niveles completados: <?= count(array_intersect(array_column($niveles, 'id_nivel'), $completados)) ?> de <?= count($niveles) ?>.</p>
  <?php elseif ($esEstudiante && $freeLevels !== []): ?>
    <form action="<?= dirname(BASE_URL) ?>/api/index.php/inscripciones/<?= $courseId ?>"
          method="post" data-estudiante-accion data-csrf="<?= htmlspecialchars($csrf) ?>">
      <button type="submit" class="boton">Inscribirme</button>
      <p role="status" data-estado></p>
    </form>
  <?php elseif (empty($_SESSION['usuario']) && $freeLevels !== []): ?>
    <a class="boton" href="<?= BASE_URL ?>/index.php?r=login">Ingresar para inscribirme</a>
  <?php endif; ?>

  <?php if ($esEstudiante): ?>
    <p>
      <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=conversacion&amp;curso=<?= $courseId ?>">
        Preguntar al instructor
      </a>
    </p>
  <?php endif; ?>

  <h2>Niveles</h2>
  <?php if ($niveles === []): ?>
    <p class="muted">El instructor todavía no ha agregado niveles.</p>
  <?php elseif ($freeLevels === [] && !$inscrito): ?>
    <p class="muted">Este curso no tiene niveles gratuitos disponibles.</p>
  <?php endif; ?>
  <div class="lista-niveles">
    <?php foreach ($niveles as $index => $nivel): ?>
      <?php $levelId = (int) $nivel['id_nivel']; $free = isset($freeLevels[$index]); ?>
      <article class="panel">
        <h3><?= (int) $nivel['orden'] ?>. <?= htmlspecialchars($nivel['titulo']) ?></h3>
        <?php if (!$free): ?>
          <p>Nivel de pago: $<?= number_format((float) $nivel['costo_nivel'], 2) ?> MXN.</p>
        <?php elseif (!$inscrito): ?>
          <p>Nivel gratuito</p>
        <?php else: ?>
          <p><?= in_array($levelId, $completados, true) ? 'Completado' : 'Pendiente' ?></p>
          <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=nivel-estudiante&amp;id=<?= $levelId ?>">Abrir nivel</a>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
  <h2 id="opiniones">Opiniones del curso</h2>
  <?php if ((int) $calificacion['cantidad'] > 0): ?>
    <p><strong><?= number_format((float) $calificacion['promedio'], 2) ?> de 5 estrellas</strong>
      (<?= (int) $calificacion['cantidad'] ?> calificaciones).</p>
  <?php else: ?>
    <p>Todavía no hay calificaciones.</p>
  <?php endif; ?>
  <?php if ($avisoResena !== null): ?>
    <p class="aviso <?= $avisoResena['exito'] ? 'exito' : 'error' ?>" role="status"><?= htmlspecialchars($avisoResena['mensaje']) ?></p>
  <?php endif; ?>

  <?php if ($puedeOpinar): ?>
    <form method="post" action="<?= BASE_URL ?>/index.php?r=resena-publicar" class="formulario panel">
      <input type="hidden" name="curso" value="<?= $courseId ?>">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label class="campo">Calificación
        <select name="puntuacion" required>
          <option value="">Elige una calificación</option>
          <?php for ($score = 5; $score >= 1; $score--): ?>
            <option value="<?= $score ?>"><?= $score ?> de 5</option>
          <?php endfor; ?>
        </select>
      </label>
      <label class="campo">Comentario (opcional)
        <textarea name="texto" maxlength="1000" rows="3"></textarea>
      </label>
      <p class="muted">Si ya calificaste el curso, tu nueva calificación reemplazará la anterior.</p>
      <button class="boton" type="submit">Guardar opinión</button>
    </form>
  <?php elseif ($esEstudiante): ?>
    <p class="muted">Termina todos los niveles para comentar y calificar el curso.</p>
  <?php endif; ?>

  <?php if ($comentarios === []): ?>
    <p>No hay comentarios todavía.</p>
  <?php endif; ?>
  <div class="lista-niveles">
    <?php foreach ($comentarios as $comentario): ?>
      <article class="panel">
        <h3><?= htmlspecialchars($comentario['nombre'] . ' ' . $comentario['apellidos']) ?></h3>
        <p class="muted"><?= htmlspecialchars($comentario['fecha_creacion']) ?></p>
        <?php if ((bool) $comentario['eliminado']): ?>
          <p>Este comentario fue eliminado.</p>
          <?php if ($esAdministrador): ?>
            <p class="muted">Eliminado el <?= htmlspecialchars($comentario['fecha_eliminacion'] ?? '') ?>.
              Causa: <?= htmlspecialchars($comentario['causa_eliminacion'] ?? '') ?></p>
          <?php endif; ?>
        <?php else: ?>
          <p><?= nl2br(htmlspecialchars($comentario['texto'])) ?></p>
          <?php if ($esAdministrador): ?>
            <form method="post" action="<?= BASE_URL ?>/index.php?r=comentario-moderar" class="formulario"
                  onsubmit="return confirm('¿Eliminar este comentario?');">
              <input type="hidden" name="curso" value="<?= $courseId ?>">
              <input type="hidden" name="comentario" value="<?= (int) $comentario['id_comentario'] ?>">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
              <label class="campo">Causa de eliminación
                <input name="causa" required maxlength="255">
              </label>
              <button class="boton-peligro" type="submit">Eliminar comentario</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<script src="<?= BASE_URL ?>/js/estudiante.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
