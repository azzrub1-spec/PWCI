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
        <?php if ($_SESSION['usuario']['rol'] === 'administrador'): ?>
          <a href="<?= BASE_URL ?>/index.php?r=categorias">Categorías</a>
          <a href="<?= BASE_URL ?>/index.php?r=usuarios-admin">Usuarios</a>
        <?php endif; ?>
        <?php if (in_array($_SESSION['usuario']['rol'], ['estudiante', 'instructor'], true)): ?>
          <a href="<?= BASE_URL ?>/index.php?r=mensajes">Mensajes</a>
        <?php endif; ?>
        <?php if ($_SESSION['usuario']['rol'] === 'estudiante'): ?>
          <a href="<?= BASE_URL ?>/index.php?r=mis-inscripciones">Mis cursos</a>
        <?php endif; ?>
        <?php if ($_SESSION['usuario']['rol'] === 'instructor'): ?>
          <a href="<?= BASE_URL ?>/index.php?r=mis-cursos">Mis cursos</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>
  </header>
  <main>

