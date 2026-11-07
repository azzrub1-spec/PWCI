<?php
$title = 'Niveles del curso';
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <h1>Niveles de <?= htmlspecialchars($curso['titulo']) ?></h1>
    <p id="cantidad-niveles"></p>
  </div>

  <p id="estado-niveles" class="aviso" role="status" aria-live="polite"></p>

  <form id="formulario-nivel" class="formulario panel" enctype="multipart/form-data">
    <h2 id="titulo-formulario-nivel">Nuevo nivel</h2>

    <label class="campo" for="titulo-nivel">
      Título
      <input id="titulo-nivel" name="titulo" maxlength="150" required>
    </label>

    <div class="dos-columnas">
      <label class="campo" for="orden-nivel">
        Orden dentro del curso
        <input id="orden-nivel" name="orden" type="number" min="1"
               max="4294967295" step="1" value="1" required>
      </label>
      <label class="campo" for="costo-nivel">
        Costo del nivel (MXN)
        <input id="costo-nivel" name="costo_nivel" type="number" min="0"
               max="99999999.99" step="0.01" value="0" required>
        <small>Escribe 0 si este nivel es gratuito.</small>
      </label>
    </div>

    <label class="campo" for="video-nivel">
      Video obligatorio
      <input id="video-nivel" name="video" type="file" accept="video/mp4,video/webm" required>
      <small id="ayuda-video-nivel">MP4 o WebM, hasta 50 MB.</small>
    </label>

    <label class="campo" for="texto-nivel">
      Contenido de texto (opcional)
      <textarea id="texto-nivel" name="contenido_texto" rows="5"></textarea>
    </label>

    <label class="campo" for="pdf-nivel">
      Archivo PDF (opcional)
      <input id="pdf-nivel" name="pdf" type="file" accept="application/pdf">
      <small>Hasta 5 MB. Al editar, deja el campo vacío para conservar el PDF actual.</small>
    </label>
    <label id="opcion-quitar-pdf" class="opcion-material" hidden>
      <input id="quitar-pdf" name="quitar_pdf" type="checkbox" value="1">
      Quitar el PDF actual
    </label>

    <label class="campo" for="imagen-nivel">
      Imagen del contenido (opcional)
      <input id="imagen-nivel" name="imagen" type="file" accept="image/jpeg,image/png">
      <small>JPG o PNG, hasta 2 MB. Al editar, deja el campo vacío para conservar la imagen actual.</small>
    </label>
    <label id="opcion-quitar-imagen" class="opcion-material" hidden>
      <input id="quitar-imagen" name="quitar_imagen" type="checkbox" value="1">
      Quitar la imagen actual
    </label>

    <label class="campo" for="enlace-nivel">
      Enlace externo (opcional)
      <input id="enlace-nivel" name="enlace" type="url" maxlength="2048" placeholder="https://">
    </label>

    <div class="acciones-formulario">
      <button id="cancelar-nivel" class="boton-secundario" type="button" hidden>
        Cancelar edición
      </button>
      <button id="guardar-nivel" class="boton" type="submit" disabled>Crear nivel</button>
    </div>
  </form>

  <h2>Niveles registrados</h2>
  <div id="lista-niveles" class="lista-niveles"></div>
</section>
<script>
  window.KONNEKT_NIVELES = <?= json_encode([
      'api' => dirname(BASE_URL) . '/api/index.php/niveles',
      'archivoUrl' => BASE_URL . '/index.php?r=archivo-nivel',
      'cursoId' => (int) $curso['id_curso'],
      'activo' => (bool) $curso['activo'],
      'csrfToken' => $csrfToken,
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= BASE_URL ?>/js/instructor-niveles.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
