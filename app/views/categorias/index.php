<?php
$title = 'Categorías';
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <h1>Administrar categorías</h1>
  </div>

  <p id="estado-categorias" class="aviso" role="status" aria-live="polite"></p>

  <form id="formulario-categoria" class="formulario panel">
    <h2 id="titulo-formulario">Nueva categoría</h2>
    <label class="campo" for="nombre-categoria">
      Nombre
      <input id="nombre-categoria" name="nombre" maxlength="100" required>
    </label>
    <label class="campo" for="descripcion-categoria">
      Descripción
      <textarea id="descripcion-categoria" name="descripcion" rows="3"></textarea>
    </label>
    <div class="acciones-formulario">
      <button id="cancelar-edicion" class="boton-secundario" type="button" hidden>
        Cancelar edición
      </button>
      <button id="guardar-categoria" class="boton" type="submit">Crear categoría</button>
    </div>
  </form>

  <h2>Categorías registradas</h2>
  <div class="tabla-contenedor">
    <table>
      <caption class="muted">Nombre, descripción y datos de creación</caption>
      <thead>
        <tr>
          <th scope="col">Nombre</th>
          <th scope="col">Descripción</th>
          <th scope="col">Creada por</th>
          <th scope="col">Fecha y hora</th>
          <th scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody id="lista-categorias"></tbody>
    </table>
  </div>
</section>
<script>
  window.KONNEKT_CATEGORIAS = <?= json_encode([
      'api' => dirname(BASE_URL) . '/api/index.php/categorias',
      'csrfToken' => $csrfToken,
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>/js/categorias.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
