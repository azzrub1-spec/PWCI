<?php $title = 'Ingresar'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina pagina-acceso">
  <div class="titulo">
    <span class="etiqueta">ACCESO</span>
    <h1>Iniciar sesión</h1>
  </div>
  <?php if (!empty($error)): ?>
    <p class="aviso error" role="alert"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p class="aviso exito" role="status"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>
  <form class="panel formulario" method="post" action="<?= BASE_URL ?>/index.php?r=login">
    <label class="campo">Correo electrónico
      <input name="email" type="email" autocomplete="email" required>
    </label>
    <label class="campo">Contraseña
      <input name="password" type="password" autocomplete="current-password" required>
    </label>
    <button class="boton" type="submit">Ingresar</button>
  </form>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

