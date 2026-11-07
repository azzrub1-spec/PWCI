<?php
$title = 'Conversación';
$peer = $esEstudiante ? 'instructor' : 'estudiante';
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <a href="<?= BASE_URL ?>/index.php?r=mensajes">Volver a Mensajes</a>
    <h1><?= htmlspecialchars($conversacion['titulo']) ?></h1>
    <p>Conversación privada con
      <?= htmlspecialchars($conversacion[$peer . '_nombre'] . ' ' . $conversacion[$peer . '_apellidos']) ?>.
    </p>
  </div>

  <?php if ($historial === []): ?>
    <p class="muted">Todavía no hay mensajes.</p>
  <?php endif; ?>

  <div class="lista-niveles">
    <?php foreach ($historial as $item): ?>
      <?php
      $imagen = (string) $item['imagen_perfil'];
      $nombre = $item['nombre'] . ' ' . $item['apellidos'];
      $tieneImagen = preg_match('~\Auploads/perfiles/[a-f0-9]{32}\.(jpg|png)\z~', $imagen) === 1
          && is_file(dirname(__DIR__, 3) . '/public/' . $imagen);
      ?>
      <article class="panel">
        <?php if ($tieneImagen): ?>
          <img src="<?= BASE_URL ?>/<?= htmlspecialchars($imagen) ?>" alt="" width="40" height="40">
        <?php endif; ?>
        <strong><?= htmlspecialchars($nombre) ?></strong>
        <time class="muted" datetime="<?= htmlspecialchars(str_replace(' ', 'T', $item['fecha_envio'])) ?>">
          <?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['fecha_envio']))) ?>
        </time>
        <p><?= nl2br(htmlspecialchars($item['texto'])) ?></p>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($aviso !== null): ?>
    <p class="aviso <?= $aviso['exito'] ? 'exito' : 'error' ?>" role="status">
      <?= htmlspecialchars($aviso['mensaje']) ?>
    </p>
  <?php endif; ?>

  <?php if ($puedeEnviar): ?>
    <form method="post" action="<?= BASE_URL ?>/index.php?r=mensaje-enviar" class="formulario panel">
      <input type="hidden" name="curso" value="<?= (int) $conversacion['id_curso'] ?>">
      <?php if (!$esEstudiante): ?>
        <input type="hidden" name="alumno" value="<?= (int) $conversacion['id_estudiante'] ?>">
      <?php endif; ?>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label class="campo">Mensaje
        <textarea name="texto" rows="4" maxlength="1000" required></textarea>
      </label>
      <button type="submit" class="boton">Enviar mensaje</button>
    </form>
  <?php else: ?>
    <p class="muted">Esta conversación ya no admite mensajes.</p>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
