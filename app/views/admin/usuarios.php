<?php $title = 'Usuarios'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina">
  <div class="titulo">
    <h1>Usuarios</h1>
    <p>Administra el acceso a las cuentas del portal.</p>
    <div class="acciones">
      <a class="boton-secundario" href="<?= BASE_URL ?>/index.php?r=reporte-usuarios">Reporte de usuarios</a>
    </div>
  </div>
  <?php if ($aviso !== null): ?>
    <p class="aviso <?= $aviso['success'] ? 'exito' : 'error' ?>" role="status">
      <?= htmlspecialchars($aviso['message']) ?>
    </p>
  <?php endif; ?>
  <div class="tabla-contenedor">
    <table aria-label="Cuentas del portal">
      <thead>
        <tr>
          <th scope="col">Nombre</th><th scope="col">Correo</th><th scope="col">Rol</th>
          <th scope="col">Estado</th><th scope="col">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $usuario): ?>
          <?php $activo = (bool) $usuario['activo']; $id = (int) $usuario['id_usuario']; ?>
          <tr>
            <th scope="row"><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']) ?></th>
            <td><?= htmlspecialchars($usuario['email']) ?></td>
            <td><?= htmlspecialchars(ucfirst($usuario['rol'])) ?></td>
            <td><?= $activo ? 'Activa' : 'Bloqueada' ?></td>
            <td>
              <?php if ($id === (int) $_SESSION['usuario']['id']): ?>
                Tu cuenta
              <?php else: ?>
                <form method="post" action="<?= BASE_URL ?>/index.php?r=estado-usuario">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="usuario" value="<?= $id ?>">
                  <input type="hidden" name="activo" value="<?= $activo ? '0' : '1' ?>">
                  <button type="submit" class="<?= $activo ? 'boton-peligro' : 'boton-secundario' ?>">
                    <?= $activo ? 'Bloquear' : 'Desbloquear' ?>
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
