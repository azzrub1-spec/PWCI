<?php
$title = 'Reporte de usuarios';
$esInstructor = $rol === 'instructor';
$meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
require dirname(__DIR__) . '/layout/header.php';
?>
<section class="pagina">
  <div class="titulo">
    <h1>Reporte de usuarios</h1>
    <p>Consulta las cuentas activas por tipo de usuario.</p>
  </div>
  <form method="get" action="<?= BASE_URL ?>/index.php" class="filtros">
    <input type="hidden" name="r" value="reporte-usuarios">
    <label class="campo">Tipo de usuario
      <select name="rol">
        <option value="instructor" <?= $esInstructor ? 'selected' : '' ?>>Instructores</option>
        <option value="estudiante" <?= !$esInstructor ? 'selected' : '' ?>>Estudiantes</option>
      </select>
    </label>
    <button class="boton" type="submit">Consultar</button>
  </form>
  <?php if ($error !== null): ?>
    <p class="aviso error" role="alert"><?= htmlspecialchars($error) ?></p>
  <?php elseif ($filas === []): ?>
    <p>No hay usuarios activos de este tipo.</p>
  <?php else: ?>
    <p>Usuarios activos: <?= count($filas) ?></p>
    <div class="tabla-contenedor">
      <table aria-label="Reporte de usuarios activos">
        <thead>
          <tr>
            <th scope="col">Usuario</th><th scope="col">Nombre</th><th scope="col">Fecha de ingreso</th>
            <th scope="col"><?= $esInstructor ? 'Cursos ofrecidos' : 'Cursos inscritos' ?></th>
            <th scope="col"><?= $esInstructor ? 'Total de ganancias' : 'Cursos terminados (%)' ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filas as $fila): ?>
            <?php
            $fecha = $fila['fecha_registro'];
            $mes = (int) substr($fecha, 5, 2);
            $fechaIngreso = isset($meses[$mes])
                ? substr($fecha, 8, 2) . ' ' . $meses[$mes] . ' ' . substr($fecha, 0, 4)
                : '—';
            $cursos = (int) $fila['cursos'];
            ?>
            <tr>
              <td><?= htmlspecialchars($fila['email']) ?></td>
              <th scope="row"><?= htmlspecialchars($fila['nombre'] . ' ' . $fila['apellidos']) ?></th>
              <td><?= $fechaIngreso ?></td>
              <td><?= $cursos ?></td>
              <td>
                <?php if ($esInstructor): ?>
                  $<?= number_format((float) $fila['ganancias'], 2, '.', ',') ?>
                <?php else: ?>
                  <?= number_format($cursos > 0 ? (int) $fila['terminados'] * 100 / $cursos : 0, 2, '.', ',') ?>%
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <div class="acciones">
    <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=usuarios-admin">Volver a usuarios</a>
  </div>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
