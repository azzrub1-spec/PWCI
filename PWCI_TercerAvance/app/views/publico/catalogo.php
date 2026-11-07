<?php $title = 'Catálogo'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina">
  <div class="titulo">
    <span class="etiqueta">CATÁLOGO</span>
    <h1>Encuentra tu próximo curso</h1>
  </div>
  <form class="filtros filtros-catalogo" id="filtros-catalogo" method="get"
        action="<?= BASE_URL ?>/index.php">
    <input type="hidden" name="r" value="catalogo">
    <label class="campo">Título
      <input name="titulo" type="search" placeholder="Buscar por título">
    </label>
    <label class="campo">Instructor
      <input name="instructor" type="search" placeholder="Nombre o apellido">
    </label>
    <label class="campo">Categoría
      <select id="categoria" name="categoria">
        <option value="">Todas las categorías</option>
      </select>
    </label>
    <label class="campo">Publicado desde
      <input name="desde" type="date" min="1000-01-01" max="9999-12-31">
    </label>
    <label class="campo">Publicado hasta
      <input name="hasta" type="date" min="1000-01-01" max="9999-12-31">
    </label>
    <div class="acciones">
      <button class="boton" type="submit">Buscar</button>
      <button class="boton-secundario" type="reset">Limpiar</button>
    </div>
  </form>
  <p id="estado-catalogo" class="muted" role="status"></p>
  <div class="rejilla" id="cursos"></div>
</section>
<script>
  window.KONNEKT_API = '<?= dirname(BASE_URL) ?>/api/index.php/cursos';
  window.KONNEKT_CATEGORIAS_API = '<?= dirname(BASE_URL) ?>/api/index.php/categorias';
  window.KONNEKT_PUBLIC_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>/js/catalogo-api.js?v=11.1"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

