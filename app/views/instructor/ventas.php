<?php
$title = $course === null ? 'Reporte de ventas' : 'Detalle de ventas';
$money = static fn ($amount): string => '$' . number_format((float) $amount, 2, '.', ',');
$date = static function (string $value): string {
    $months = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return substr($value, 8, 2) . ' ' . ($months[(int) substr($value, 5, 2)] ?? '') . ' ' . substr($value, 0, 4);
};
$reportUrl = static fn (array $extra = []): string => htmlspecialchars(
    BASE_URL . '/index.php?' . http_build_query(
        ['r' => 'ventas-instructor'] + $extra + array_filter($filters, static fn (string $value): bool => $value !== '')
    )
);
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <h1><?= $title ?></h1>
    <?php if ($course !== null): ?>
      <p><?= htmlspecialchars($course['titulo']) ?> · <?= htmlspecialchars($course['categoria']) ?></p>
    <?php endif; ?>
  </div>

  <?php if ($course === null): ?>
    <form method="get" action="<?= BASE_URL ?>/index.php" class="formulario panel">
      <input type="hidden" name="r" value="ventas-instructor">
      <div class="dos-columnas">
        <label class="campo">Cursos creados desde
          <input type="date" name="desde" value="<?= htmlspecialchars($filters['desde']) ?>">
        </label>
        <label class="campo">Cursos creados hasta
          <input type="date" name="hasta" value="<?= htmlspecialchars($filters['hasta']) ?>">
        </label>
        <label class="campo">Categoría
          <select name="categoria">
            <option value="">Todas</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id_categoria'] ?>" <?= (string) $category['id_categoria'] === $filters['categoria'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($category['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="campo">Estado del curso
          <select name="activos">
            <option value="">Todos</option>
            <option value="1" <?= $filters['activos'] === '1' ? 'selected' : '' ?>>Solo activos</option>
          </select>
        </label>
      </div>
      <div class="acciones">
        <button class="boton" type="submit">Consultar</button>
        <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=ventas-instructor">Limpiar filtros</a>
      </div>
    </form>
    <?php if ($error !== null): ?>
      <p class="aviso error" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($courses === []): ?>
      <p>No hay cursos que coincidan con los filtros.</p>
    <?php else: ?>
      <div class="tabla-contenedor">
        <table aria-label="Ventas por curso">
          <thead><tr><th>Curso</th><th>Fecha de creación</th><th>Inscritos</th><th>Niveles cursados en promedio</th><th>Ingresos (MXN)</th><th>Detalle</th></tr></thead>
          <tbody>
          <?php foreach ($courses as $row): ?>
            <?php $average = (int) $row['inscritos'] > 0 ? (int) $row['niveles_cursados'] / (int) $row['inscritos'] : 0; ?>
            <tr>
              <th scope="row"><?= htmlspecialchars($row['titulo']) ?><?= (int) $row['activo'] === 0 ? ' (Inactivo)' : '' ?></th>
              <td><?= $date($row['fecha_publicacion']) ?></td>
              <td><?= (int) $row['inscritos'] ?></td>
              <td><?= number_format($average, 2) ?> de <?= (int) $row['niveles'] ?></td>
              <td><?= $money($row['ingresos']) ?></td>
              <td><a href="<?= $reportUrl(['curso' => (int) $row['id_curso']]) ?>">Ver alumnos</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="panel">
        <h2>Ingresos por forma de pago</h2>
        <?php if ($payments === []): ?>
          <p>No hay pagos registrados para estos cursos.</p>
        <?php else: ?>
          <ul>
          <?php foreach ($payments as $payment): ?>
            <li><?= htmlspecialchars($payment['forma_pago']) ?>: <?= $money($payment['ingresos']) ?></li>
          <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <p><strong>Total general:</strong> <?= $money(array_sum(array_column($payments, 'ingresos'))) ?> MXN</p>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <?php if ($students === []): ?>
      <p>Este curso todavía no tiene alumnos inscritos.</p>
    <?php else: ?>
      <div class="tabla-contenedor">
        <table aria-label="Ventas por alumno">
          <thead><tr><th>Alumno</th><th>Fecha de inscripción</th><th>Avance</th><th>Pagado (MXN)</th><th>Forma de pago</th></tr></thead>
          <tbody>
          <?php foreach ($students as $row): ?>
            <tr>
              <th scope="row"><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']) ?></th>
              <td><?= $date($row['fecha_inscripcion']) ?></td>
              <td><?= (int) $row['cursados'] ?> de <?= (int) $course['niveles'] ?> niveles</td>
              <td><?= $money($row['pagado']) ?></td>
              <td><?= htmlspecialchars($row['formas'] === null ? 'Sin pago registrado' : str_replace(',', ', ', $row['formas'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <p><strong>Total del curso:</strong> <?= $money($course['ingresos']) ?> MXN</p>
    <a class="boton-secundario" href="<?= $reportUrl() ?>">Volver al resumen</a>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
