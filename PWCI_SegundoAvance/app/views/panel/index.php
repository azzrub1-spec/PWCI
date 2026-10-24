<?php $title = 'Mi panel'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina">
  <div class="titulo">
    <span class="etiqueta"><?= strtoupper(htmlspecialchars($usuario['rol'])) ?></span>
    <h1>Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?></h1>
    <p>La sesión está protegida por el middleware de acceso.</p>
  </div>
  <form method="post" action="<?= BASE_URL ?>/index.php?r=logout">
    <button class="boton-secundario" type="submit">Cerrar sesión</button>
  </form>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

