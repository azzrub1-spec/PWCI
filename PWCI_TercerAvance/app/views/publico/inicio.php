<?php
$title = 'Inicio';
require dirname(__DIR__) . '/layout/header.php';
?>

<section class="pagina inicio-presentacion">
  <div class="hero inicio-hero">
    <div class="inicio-texto">
      <span class="etiqueta">CURSOS EN LÍNEA</span>

      <h1>Convierte tu curiosidad en habilidades reales.</h1>

      <p class="inicio-descripcion">
        Explora cursos y aprende a tu ritmo con instructores
        de distintas áreas.
      </p>

      <div class="acciones">
        <a
          class="boton"
          href="<?= BASE_URL ?>/index.php?r=catalogo"
        >
          Ver catálogo
        </a>

        <a
          class="boton-secundario"
          href="<?= BASE_URL ?>/index.php?r=registro"
        >
          Crear cuenta
        </a>
      </div>
    </div>

    <div class="hero-visual inicio-destacado">
      <span class="inicio-distintivo">PASO A PASO</span>

      <h2>Aprendizaje organizado por niveles</h2>

      <p>
        Contenido, seguimiento y comunicación en un mismo portal.
      </p>

      <ul class="inicio-ventajas">
        <li>Consulta los materiales de cada nivel.</li>
        <li>Lleva el seguimiento de tu avance.</li>
        <li>Resuelve dudas con tu instructor.</li>
      </ul>
    </div>
  </div>

  <section class="inicio-pasos" aria-labelledby="como-empezar">
    <div class="inicio-seccion">
      <span class="etiqueta">TU PRÓXIMO PASO</span>
      <h2 id="como-empezar">Empieza a aprender en KONNEKT</h2>
      <p>Elige un curso y avanza nivel por nivel.</p>
    </div>

    <div class="rejilla">
      <article class="tarjeta inicio-paso">
        <span class="inicio-numero" aria-hidden="true">01</span>
        <h3>Explora el catálogo</h3>
        <p>
          Busca por título, instructor o categoría y consulta
          los cursos disponibles.
        </p>
      </article>

      <article class="tarjeta inicio-paso">
        <span class="inicio-numero" aria-hidden="true">02</span>
        <h3>Elige cómo comenzar</h3>
        <p>
          Crea tu cuenta e inscríbete en niveles gratuitos
          o compra el contenido que te interese.
        </p>
      </article>

      <article class="tarjeta inicio-paso">
        <span class="inicio-numero" aria-hidden="true">03</span>
        <h3>Completa tus niveles</h3>
        <p>
          Revisa los materiales y registra tu avance.
          Al completar todos los niveles, obtén tu certificado.
        </p>
      </article>
    </div>
  </section>
</section>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>