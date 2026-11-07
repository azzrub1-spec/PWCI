CREATE DATABASE IF NOT EXISTS konnekt
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE konnekt;

CREATE TABLE usuario (
  id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellidos VARCHAR(150) NOT NULL,
  genero ENUM('Femenino', 'Masculino', 'Otro') NOT NULL,
  fecha_nacimiento DATE NOT NULL,
  imagen_perfil VARCHAR(255) NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('estudiante', 'instructor', 'administrador') NOT NULL,
  intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_ultimo_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

CREATE TABLE categoria (
  id_categoria INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT NOT NULL,
  id_usuario_creador INT UNSIGNED NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_categoria_usuario
    FOREIGN KEY (id_usuario_creador) REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE = InnoDB;

CREATE TABLE curso (
  id_curso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_instructor INT UNSIGNED NOT NULL,
  id_categoria INT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  descripcion TEXT NOT NULL,
  imagen VARCHAR(255) NULL,
  costo DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_curso_instructor
    FOREIGN KEY (id_instructor) REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_curso_categoria
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE nivel (
  id_nivel INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_curso INT UNSIGNED NOT NULL,
  orden INT UNSIGNED NOT NULL,
  titulo VARCHAR(150) NOT NULL,
  video_url VARCHAR(255) NOT NULL,
  contenido_texto TEXT NULL,
  costo_nivel DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  gratis TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT uq_nivel_orden UNIQUE (id_curso, orden),
  CONSTRAINT fk_nivel_curso
    FOREIGN KEY (id_curso) REFERENCES curso(id_curso)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE inscripcion (
  id_inscripcion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_estudiante INT UNSIGNED NOT NULL,
  id_curso INT UNSIGNED NOT NULL,
  fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_ultimo_acceso DATETIME NULL,
  fecha_terminacion DATETIME NULL,
  CONSTRAINT uq_inscripcion UNIQUE (id_estudiante, id_curso),
  CONSTRAINT fk_inscripcion_estudiante
    FOREIGN KEY (id_estudiante) REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_inscripcion_curso
    FOREIGN KEY (id_curso) REFERENCES curso(id_curso)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE progreso_nivel (
  id_progreso INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_inscripcion INT UNSIGNED NOT NULL,
  id_nivel INT UNSIGNED NOT NULL,
  completado TINYINT(1) NOT NULL DEFAULT 0,
  fecha_completado DATETIME NULL,
  CONSTRAINT uq_progreso UNIQUE (id_inscripcion, id_nivel),
  CONSTRAINT fk_progreso_inscripcion
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion(id_inscripcion)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_progreso_nivel
    FOREIGN KEY (id_nivel) REFERENCES nivel(id_nivel)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE pago (
  id_pago INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_inscripcion INT UNSIGNED NOT NULL,
  monto DECIMAL(10,2) UNSIGNED NOT NULL,
  forma_pago VARCHAR(50) NOT NULL,
  referencia_pasarela VARCHAR(150) NULL,
  fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pago_inscripcion
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion(id_inscripcion)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE comentario (
  id_comentario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_curso INT UNSIGNED NOT NULL,
  texto TEXT NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  eliminado TINYINT(1) NOT NULL DEFAULT 0,
  fecha_eliminacion DATETIME NULL,
  causa_eliminacion VARCHAR(255) NULL,
  CONSTRAINT fk_comentario_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_comentario_curso
    FOREIGN KEY (id_curso) REFERENCES curso(id_curso)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE calificacion (
  id_calificacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  id_curso INT UNSIGNED NOT NULL,
  puntuacion TINYINT UNSIGNED NOT NULL,
  CONSTRAINT uq_calificacion UNIQUE (id_usuario, id_curso),
  CONSTRAINT chk_puntuacion CHECK (puntuacion BETWEEN 1 AND 5),
  CONSTRAINT fk_calificacion_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_calificacion_curso
    FOREIGN KEY (id_curso) REFERENCES curso(id_curso)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE mensaje (
  id_mensaje INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_remitente INT UNSIGNED NOT NULL,
  id_destinatario INT UNSIGNED NOT NULL,
  id_curso INT UNSIGNED NOT NULL,
  texto TEXT NOT NULL,
  fecha_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mensaje_remitente
    FOREIGN KEY (id_remitente) REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mensaje_destinatario
    FOREIGN KEY (id_destinatario) REFERENCES usuario(id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mensaje_curso
    FOREIGN KEY (id_curso) REFERENCES curso(id_curso)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE certificado (
  id_certificado INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_inscripcion INT UNSIGNED NOT NULL UNIQUE,
  codigo VARCHAR(60) NOT NULL UNIQUE,
  fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_certificado_inscripcion
    FOREIGN KEY (id_inscripcion) REFERENCES inscripcion(id_inscripcion)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

INSERT INTO categoria (nombre, descripcion) VALUES
  ('Diseño', 'Cursos relacionados con diseño y experiencia de usuario.'),
  ('Programación', 'Cursos relacionados con desarrollo de software.'),
  ('Creatividad', 'Cursos relacionados con fotografía y creación de contenido.');

