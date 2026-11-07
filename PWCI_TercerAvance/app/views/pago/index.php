<?php
$title = 'Pago con PayPal';
require dirname(__DIR__) . '/layout/header.php';
?>

<section class="pagina pagina-formulario">
  <div class="titulo">
    <span class="etiqueta">PAYPAL SANDBOX</span>
    <h1>Pago de prueba</h1>
  </div>
  <div class="panel">
  <p>
    Esta prueba crea y captura una orden de $10.00 MXN mediante PayPal.
    No utiliza dinero real.
  </p>

  <?php if (!empty($error)): ?>
    <p class="aviso error" role="alert"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p class="aviso exito" role="status"><?= htmlspecialchars($success) ?></p>
    <p><strong>Orden:</strong> <?= htmlspecialchars($orderId ?? '') ?></p>
  <?php else: ?>
    <form method="post" action="<?= BASE_URL ?>/index.php?r=paypal-crear">
      <button class="boton" type="submit">Continuar con PayPal Sandbox</button>
    </form>
  <?php endif; ?>
  </div>
</section>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
