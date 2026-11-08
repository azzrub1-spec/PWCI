<?php
$title = 'Kardex';
$meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fecha = static function (?string $valor) use ($meses): string {
    if ($valor === null || $valor === '') {
        return '—';
    }
    $mes = (int) substr($valor, 5, 2);
    return isset($meses[$mes]) ? substr($valor, 8, 2) . ' ' . $meses[$mes] . ' ' . substr($valor, 0, 4) : '—';
};
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <h1>Kardex</h1>
    <p>Cursos inscritos: <?= $totalInscritos ?></p>
  </div>
  <form method="get" action="<?= BASE_URL ?>/index.php" class="panel formulario">
    <input type="hidden" name="r" value="mis-inscripciones">
    <div class="dos-columnas">
      <label class="campo">Inscripción desde
        <input type="date" name="desde" value="<?= htmlspecialchars($filtros['desde']) ?>">
      </label>
      <label class="campo">Inscripción hasta
        <input type="date" name="hasta" value="<?= htmlspecialchars($filtros['hasta']) ?>">
      </label>
      <label class="campo">Categoría
        <select name="categoria">
          <option value="">Todas</option>
          <?php foreach ($categorias as $id => $nombre): ?>
            <option value="<?= (int) $id ?>" <?= (string) $id === $filtros['categoria'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($nombre) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="dos-columnas">
      <label><input type="checkbox" name="terminados" value="1" <?= $filtros['terminados'] === '1' ? 'checked' : '' ?>> Solo cursos terminados</label>
      <label><input type="checkbox" name="activos" value="1" <?= $filtros['activos'] === '1' ? 'checked' : '' ?>> Solo cursos activos</label>
    </div>
    <div class="acciones-formulario">
      <button type="submit" class="boton">Filtrar</button>
      <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=mis-inscripciones">Limpiar filtros</a>
    </div>
  </form>

  <?php if ($errores !== []): ?>
    <div class="aviso error" role="alert">
      <ul>
        <?php foreach ($errores as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php elseif ($totalInscritos === 0): ?>
    <p>Todavía no tienes cursos inscritos. Visita el <a href="<?= BASE_URL ?>/index.php?r=catalogo">catálogo</a>.</p>
  <?php elseif ($cursos === []): ?>
    <p>No hay cursos que coincidan con los filtros.</p>
  <?php else: ?>
    <p>Mostrando <?= count($cursos) ?> de <?= $totalInscritos ?> cursos.</p>
    <div class="tabla-contenedor">
      <table aria-label="Kardex de cursos inscritos">
        <thead>
          <tr>
            <th scope="col">Curso</th><th scope="col">Categoría</th><th scope="col">Inscripción</th>
            <th scope="col">Último acceso</th><th scope="col">Terminación</th><th scope="col">Progreso</th>
            <th scope="col">Estado</th><th scope="col">Niveles</th><th scope="col">Certificado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cursos as $curso): ?>
            <?php
            $total = (int) $curso['total'];
            $completados = (int) $curso['completados'];
            $avance = $total > 0 ? (int) round($completados * 100 / $total) : 0;
            ?>
            <tr>
              <th scope="row"><?= htmlspecialchars($curso['titulo']) ?></th>
              <td><?= htmlspecialchars($curso['categoria']) ?></td>
              <td><?= $fecha($curso['fecha_inscripcion']) ?></td>
              <td><?= $fecha($curso['fecha_ultimo_acceso']) ?></td>
              <td><?= $fecha($curso['terminado'] ? $curso['fecha_terminacion'] : null) ?></td>
              <td><?= $completados ?> de <?= $total ?> (<?= $avance ?>%)</td>
              <td>
                <?= $curso['terminado'] ? 'Terminado' : 'Incompleto' ?><br>
                <span class="muted"><?= (bool) $curso['activo'] ? 'Activo' : 'Dado de baja' ?></span>
              </td>
              <td>
                <?php if ((bool) $curso['activo']): ?>
                  <a href="<?= BASE_URL ?>/index.php?r=curso&amp;curso=<?= (int) $curso['id_curso'] ?>">Ver niveles</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td>
                <?php if ((bool) $curso['tiene_certificado']): ?>
                  <a href="<?= BASE_URL ?>/index.php?r=certificado&amp;curso=<?= (int) $curso['id_curso'] ?>">Ver certificado</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
