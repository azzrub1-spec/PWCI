<?php
$title = 'Perfil';
require dirname(__DIR__) . '/layout/header.php';
$imagen = (string) ($usuario['imagen_perfil'] ?? '');
$tieneImagen = preg_match('~\Auploads/perfiles/[a-f0-9]{32}\.(jpg|png)\z~', $imagen) === 1
    && is_file(dirname(__DIR__, 3) . '/public/' . $imagen);
?>
<section class="pagina pagina-formulario">
  <div class="titulo">
    <span class="etiqueta"><?= strtoupper(htmlspecialchars($usuario['rol'])) ?></span>
    <h1>Mi perfil</h1>
    <p class="muted">Actualiza tus datos personales y tu imagen de perfil.</p>
  </div>
  <?php if (!empty($errors)): ?>
    <div class="aviso error" role="alert">
      <ul>
        <?php foreach ($errors as $item): ?>
          <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
  <?php if (!empty($success)): ?>
    <p class="aviso exito" role="status"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>
  <form
    class="panel formulario"
    method="post"
    action="<?= BASE_URL ?>/index.php?r=perfil"
    enctype="multipart/form-data"
  >
    <div class="perfil-foto">
      <?php if ($tieneImagen): ?>
        <div class="imagen-perfil">
          <img
            src="<?= htmlspecialchars(BASE_URL . '/' . $imagen) ?>"
            alt="Imagen de perfil de <?= htmlspecialchars($usuario['nombre']) ?>"
            width="96"
            height="96"
          >
        </div>
      <?php endif; ?>
      <label class="campo campo-imagen">
        Imagen de perfil
        <input
          name="imagen_perfil"
          type="file"
          accept="image/jpeg,image/png"
          aria-describedby="ayuda-imagen"
          <?= $tieneImagen ? '' : 'required' ?>
        >
        <small id="ayuda-imagen">
          JPG o PNG de hasta 2 MB.
          <?= $tieneImagen ? 'Si no eliges otra, conservarás la actual.' : 'Selecciona tu imagen para guardar el perfil.' ?>
        </small>
      </label>
    </div>
    <div class="dos-columnas">
      <label class="campo">Nombre
        <input name="nombre" maxlength="100" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
      </label>
      <label class="campo">Apellidos
        <input name="apellidos" maxlength="150" value="<?= htmlspecialchars($usuario['apellidos']) ?>" required>
      </label>
      <label class="campo">Género
        <select name="genero" required>
          <?php foreach (['Femenino', 'Masculino', 'Otro'] as $genero): ?>
            <option <?= $usuario['genero'] === $genero ? 'selected' : '' ?>>
              <?= $genero ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="campo">Fecha de nacimiento
        <input name="fecha_nacimiento" type="date" max="<?= date('Y-m-d') ?>"
               value="<?= htmlspecialchars($usuario['fecha_nacimiento']) ?>" required>
      </label>
    </div>
    <label class="campo">Correo electrónico
      <input type="email" value="<?= htmlspecialchars($usuario['email']) ?>" disabled>
    </label>
    <div class="acciones-formulario">
      <button class="boton" type="submit">Guardar cambios</button>
    </div>
  </form>
</section>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

