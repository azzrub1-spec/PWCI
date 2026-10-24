<?php $title = 'Catálogo'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina">
  <div class="titulo">
    <span class="etiqueta">CATÁLOGO</span>
    <h1>Encuentra tu próximo curso</h1>
  </div>
  <div class="filtros">
    <input id="buscar" type="search" placeholder="Buscar por título">
    <button class="boton" id="filtrar" type="button">Buscar</button>
  </div>
  <p id="estado-catalogo" class="muted" role="status"></p>
  <div class="rejilla" id="cursos"></div>
</section>
<script>
  window.KONNEKT_API = '<?= dirname(BASE_URL) ?>/api/index.php/cursos';
</script>
<script src="<?= BASE_URL ?>/js/catalogo-api.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

