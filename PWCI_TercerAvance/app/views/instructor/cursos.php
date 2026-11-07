<?php
$title = 'Mis cursos';
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <h1>Mis cursos</h1>
  </div>

  <p id="estado-cursos" class="aviso" role="status" aria-live="polite"></p>

  <form id="formulario-curso" class="formulario panel" enctype="multipart/form-data">
    <h2 id="titulo-formulario-curso">Nuevo curso</h2>

    <label class="campo" for="titulo-curso">
      Título
      <input id="titulo-curso" name="titulo" maxlength="150" required>
    </label>

    <label class="campo" for="descripcion-curso">
      Descripción
      <textarea id="descripcion-curso" name="descripcion" rows="4" required></textarea>
    </label>

    <div class="dos-columnas">
      <label class="campo" for="categoria-curso">
        Categoría
        <select id="categoria-curso" name="categoria_id" required>
          <option value="">Selecciona una categoría</option>
        </select>
      </label>
      <label class="campo" for="costo-curso">
        Costo del curso completo (MXN)
        <input id="costo-curso" name="costo" type="number" min="0"
               max="99999999.99" step="0.01" value="0" required>
        <small>Escribe 0 si el curso completo es gratuito.</small>
      </label>
    </div>

    <label class="campo" for="imagen-curso">
      Imagen del curso
      <input id="imagen-curso" name="imagen" type="file"
             accept="image/jpeg,image/png" required>
      <small id="ayuda-imagen-curso">JPG o PNG, hasta 2 MB. Obligatoria al crear un curso.</small>
    </label>
    <img id="vista-imagen-curso" class="imagen-curso-formulario" alt="Imagen del curso" hidden>

    <div class="acciones-formulario">
      <button id="cancelar-curso" class="boton-secundario" type="button" hidden>
        Cancelar edición
      </button>
      <button id="guardar-curso" class="boton" type="submit" disabled>Crear curso</button>
    </div>
  </form>

  <h2>Cursos registrados</h2>
  <div id="lista-mis-cursos" class="rejilla"></div>
</section>
<script>
  window.KONNEKT_CURSOS = <?= json_encode([
      'api' => dirname(BASE_URL) . '/api/index.php/cursos',
      'categoriasApi' => dirname(BASE_URL) . '/api/index.php/categorias',
      'publicUrl' => BASE_URL,
      'csrfToken' => $csrfToken,
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>/js/instructor-cursos.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
