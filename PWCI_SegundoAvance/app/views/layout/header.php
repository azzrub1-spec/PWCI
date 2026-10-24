<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? APP_NAME) ?> | KONNEKT</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/estilos.css">
</head>
<body>
  <header class="encabezado">
    <a class="marca" href="<?= BASE_URL ?>/index.php?r=inicio">KONNEKT</a>
    <nav aria-label="Navegación principal">
      <a href="<?= BASE_URL ?>/index.php?r=inicio">Inicio</a>
      <a href="<?= BASE_URL ?>/index.php?r=catalogo">Catálogo</a>
      <?php if (empty($_SESSION['usuario'])): ?>
        <a href="<?= BASE_URL ?>/index.php?r=login">Ingresar</a>
        <a href="<?= BASE_URL ?>/index.php?r=registro">Registro</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/index.php?r=panel">Mi panel</a>
        <a href="<?= BASE_URL ?>/index.php?r=perfil">Perfil</a>
        <?php if ($_SESSION['usuario']['rol'] === 'estudiante'): ?>
          <a href="<?= BASE_URL ?>/index.php?r=pago">Pago PayPal</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </header>
  <main>

