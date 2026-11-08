<?php
$title = 'Compra';
require dirname(__DIR__) . '/layout/header.php';
?>

<section class="pagina pagina-formulario">
  <div class="titulo">
    <span class="etiqueta">PAGOS DE PRUEBA</span>
    <h1>Compra de prueba</h1>
  </div>

  <div class="panel">
    <?php if ($error !== null): ?>
      <p class="aviso error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success !== null): ?>
      <p class="aviso exito" role="status"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if ($item !== null): ?>
      <p><strong><?= htmlspecialchars($item['titulo']) ?></strong></p>
      <p>Total: $<?= number_format((float) $item['monto'], 2) ?> MXN. No se usa dinero real.</p>
      <?php if ($success === null && !in_array($_GET['r'] ?? '', ['paypal-capturar', 'stripe-confirmar'], true)): ?>
        <form method="post" action="<?= BASE_URL ?>/index.php?r=paypal-crear">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="curso" value="<?= (int) $item['curso'] ?>">
          <?php if ($item['nivel'] !== null): ?>
            <input type="hidden" name="nivel" value="<?= (int) $item['nivel'] ?>">
          <?php endif; ?>
          <button class="boton" type="submit">Continuar con PayPal Sandbox</button>
        </form>
        <h2>Tarjeta con Stripe</h2>
        <p>Usa una tarjeta de prueba en la página de Stripe.</p>
        <form method="post" action="<?= BASE_URL ?>/index.php?r=stripe-crear">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="curso" value="<?= (int) $item['curso'] ?>">
          <?php if ($item['nivel'] !== null): ?>
            <input type="hidden" name="nivel" value="<?= (int) $item['nivel'] ?>">
          <?php endif; ?>
          <button class="boton-secundario" type="submit">Continuar con Stripe en modo de prueba</button>
        </form>
      <?php endif; ?>
      <p><a href="<?= BASE_URL ?>/index.php?r=curso&amp;curso=<?= (int) $item['curso'] ?>">Volver al curso</a></p>
    <?php else: ?>
      <p><a href="<?= BASE_URL ?>/index.php?r=catalogo">Ir al catálogo</a></p>
    <?php endif; ?>
  </div>
</section>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
