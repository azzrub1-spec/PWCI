<?php $title = 'Registro'; require dirname(__DIR__) . '/layout/header.php'; ?>
<section class="pagina pagina-formulario">
  <div class="titulo">
    <span class="etiqueta">REGISTRO</span>
    <h1>Crear cuenta</h1>
    <p class="muted">Todos los campos son obligatorios.</p>
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
  <form id="registro" class="panel formulario" method="post"
        action="<?= BASE_URL ?>/index.php?r=registro" novalidate>
    <div class="dos-columnas">
      <label class="campo">Nombre
        <input name="nombre" required>
      </label>
      <label class="campo">Apellidos
        <input name="apellidos" required>
      </label>
      <label class="campo">Fecha de nacimiento
        <input name="fecha_nacimiento" type="date" required>
      </label>
      <label class="campo">Género
        <select name="genero" required>
          <option value="">Selecciona</option>
          <option>Femenino</option>
          <option>Masculino</option>
          <option>Otro</option>
        </select>
      </label>
      <label class="campo">Tipo de cuenta
        <select name="rol" required>
          <option value="">Selecciona</option>
          <option value="estudiante">Estudiante</option>
          <option value="instructor">Instructor</option>
        </select>
      </label>
    </div>
    <label class="campo">Correo electrónico
      <input name="email" type="email" autocomplete="email" required>
    </label>
    <div class="dos-columnas">
      <label class="campo">Contraseña
        <input name="password" type="password" autocomplete="new-password" required>
      </label>
      <label class="campo">Confirmar contraseña
        <input name="password_confirmation" type="password" autocomplete="new-password" required>
      </label>
    </div>
    <small>8 caracteres, una mayúscula, un número y un carácter especial.</small>
    <div id="errores"></div>
    <button class="boton" type="submit">Crear cuenta</button>
  </form>
</section>
<script src="<?= BASE_URL ?>/js/validaciones-registro.js"></script>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>

