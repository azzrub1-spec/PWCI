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
</section>
<script src="<?= BASE_URL ?>/js/estudiante.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
