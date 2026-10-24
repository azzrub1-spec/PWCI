<?php $title = 'Inicio'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina hero">
  <div>
    <span class="etiqueta">CURSOS EN LÍNEA</span>
    <h1>Convierte tu curiosidad en habilidades reales.</h1>
    <p>Explora cursos y aprende a tu ritmo con instructores de distintas áreas.</p>
    <div class="acciones">
      <a class="boton" href="<?= BASE_URL ?>/index.php?r=catalogo">Ver catálogo</a>
      <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=registro">Crear cuenta</a>
    </div>
  </div>
  <div class="hero-visual">
    <h2>Aprendizaje organizado por niveles</h2>
    <p>Contenido, seguimiento y comunicación en un mismo portal.</p>
  </div>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

