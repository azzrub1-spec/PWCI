<?php
$title = 'Certificado';
require dirname(__DIR__) . '/layout/header.php';
?>
<style>
  .diploma {
    max-width: 860px;
    margin: 1rem auto;
    padding: 3rem 1.5rem;
    text-align: center;
    border: 4px double #14575e;
  }

  .diploma h2 {
    overflow-wrap: anywhere;
  }
</style>

<section class="pagina">
  <div class="acciones">
    <a href="<?= BASE_URL ?>/index.php?r=mis-inscripciones">Volver a Mis cursos</a>
  </div>

  <article class="panel diploma">
    <p class="etiqueta">KONNEKT</p>
    <h1>Certificado de finalización</h1>
    <p>Se otorga a</p>
    <h2><?= htmlspecialchars($certificado['alumno']) ?></h2>
    <p>Por haber completado satisfactoriamente el curso</p>
    <h2><?= htmlspecialchars($certificado['titulo']) ?></h2>
    <p><strong>Fecha de terminación:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($certificado['fecha_emision']))) ?></p>
    <p><strong>Certifica:</strong> <?= htmlspecialchars($certificado['instructor']) ?></p>
    <p class="muted">Folio: <?= htmlspecialchars($certificado['codigo']) ?></p>
  </article>
</section>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>