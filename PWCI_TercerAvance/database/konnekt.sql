-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-11-2026 a las 02:08:22
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `konnekt`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificacion`
--

CREATE TABLE `calificacion` (
  `id_calificacion` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_curso` int(10) UNSIGNED NOT NULL,
  `puntuacion` tinyint(3) UNSIGNED NOT NULL
) ;

--
-- Volcado de datos para la tabla `calificacion`
--

INSERT INTO `calificacion` (`id_calificacion`, `id_usuario`, `id_curso`, `puntuacion`) VALUES
(1, 3, 7, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `id_usuario_creador` int(10) UNSIGNED DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `nombre`, `descripcion`, `id_usuario_creador`, `fecha_creacion`) VALUES
(2, 'Programación', 'Cursos relacionados con desarrollo de software.', NULL, '2026-10-16 16:59:32'),
(3, 'Creatividad', 'Cursos relacionados con fotografía y creación de contenido.', NULL, '2026-10-16 16:59:32'),
(4, 'Fotografia', 'Cursos de fotografía', 4, '2026-11-06 19:25:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificado`
--

CREATE TABLE `certificado` (
  `id_certificado` int(10) UNSIGNED NOT NULL,
  `id_inscripcion` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(60) NOT NULL,
  `fecha_emision` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `certificado`
--

INSERT INTO `certificado` (`id_certificado`, `id_inscripcion`, `codigo`, `fecha_emision`) VALUES
(1, 4, 'KONNEKT-4', '2026-11-08 14:55:32'),
(2, 5, 'KONNEKT-5', '2026-11-12 09:22:49'),
(3, 6, 'KONNEKT-6', '2026-11-14 16:07:28'),
(4, 1, 'KONNEKT-1', '2026-11-06 22:06:13'),
(5, 2, 'KONNEKT-2', '2026-11-08 13:41:02'),
(7, 10, 'KONNEKT-10', '2026-11-14 19:06:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentario`
--

CREATE TABLE `comentario` (
  `id_comentario` int(10) UNSIGNED NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_curso` int(10) UNSIGNED NOT NULL,
  `texto` text NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_eliminacion` datetime DEFAULT NULL,
  `causa_eliminacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comentario`
--

INSERT INTO `comentario` (`id_comentario`, `id_usuario`, `id_curso`, `texto`, `fecha_creacion`, `eliminado`, `fecha_eliminacion`, `causa_eliminacion`) VALUES
(1, 3, 7, 'muy sencillo todo y ni siquiera habia contenido de lo que dijeron solo videos de baseball', '2026-11-07 11:44:03', 0, NULL, NULL),
(2, 3, 7, 'perdida de tiempo', '2026-11-07 11:44:56', 1, '2026-11-07 11:49:21', 'entonces para que te inscribes'),
(3, 3, 7, 'los videos fueron entretenidos aunque no se trataran de los temas', '2026-11-07 11:46:11', 1, '2026-11-07 11:49:00', 'no aceptamos calificaciones malas'),
(4, 3, 7, 'Opinión de prueba: pude acceder a los niveles y completar el curso.', '2026-11-14 19:01:36', 1, '2026-11-14 19:03:58', 'Porque es una prueba');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso`
--

CREATE TABLE `curso` (
  `id_curso` int(10) UNSIGNED NOT NULL,
  `id_instructor` int(10) UNSIGNED NOT NULL,
  `id_categoria` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `costo` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_publicacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `curso`
--

INSERT INTO `curso` (`id_curso`, `id_instructor`, `id_categoria`, `titulo`, `descripcion`, `imagen`, `costo`, `activo`, `fecha_publicacion`) VALUES
(1, 1, 2, 'API REST con PHP', 'Curso práctico', '', 0.00, 0, '2026-10-16 17:46:40'),
(5, 1, 2, 'API REST con PHP', 'Curso práctico', '', 0.00, 0, '2026-10-24 01:36:29'),
(6, 1, 2, 'PYTHON', 'Curso práctico', '', 10.00, 1, '2026-10-24 02:46:22'),
(7, 1, 2, 'HTML', 'Curso práctico', '', 15.00, 1, '2026-10-24 07:14:14'),
(8, 1, 2, 'CSS y JS', 'Uso del CSS y JS para crear pagina web', 'uploads/cursos/6ac78d74a9678d18379dada679938b7e.png', 75.00, 0, '2026-11-06 20:02:11'),
(9, 1, 2, 'MySQL', 'Creacion y uso de bases de datos', 'uploads/cursos/a51abae46145de9b3a54dabbc48950f9.png', 50.00, 1, '2026-11-08 13:36:46'),
(10, 1, 2, 'Curso de prueba Postman', 'Curso creado para probar la API', 'uploads/cursos/53f4d2ef8cb8de062a60ed68b20be8db.png', 15.00, 0, '2026-11-14 12:13:05'),
(11, 1, 2, 'Curso de prueba editado', 'Descripción actualizada desde Postman', 'uploads/cursos/b928115155421d58480a5076bbef20fd.png', 15.00, 1, '2026-11-14 15:07:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripcion`
--

CREATE TABLE `inscripcion` (
  `id_inscripcion` int(10) UNSIGNED NOT NULL,
  `id_estudiante` int(10) UNSIGNED NOT NULL,
  `id_curso` int(10) UNSIGNED NOT NULL,
  `fecha_inscripcion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_ultimo_acceso` datetime DEFAULT NULL,
  `fecha_terminacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripcion`
--

INSERT INTO `inscripcion` (`id_inscripcion`, `id_estudiante`, `id_curso`, `fecha_inscripcion`, `fecha_ultimo_acceso`, `fecha_terminacion`) VALUES
(1, 3, 7, '2026-11-06 22:05:33', '2026-11-07 11:42:34', '2026-11-06 22:06:13'),
(2, 3, 6, '2026-11-08 12:35:41', '2026-11-08 13:41:02', '2026-11-08 13:41:02'),
(3, 7, 6, '2026-11-08 13:45:59', NULL, NULL),
(4, 7, 9, '2026-11-08 14:52:25', '2026-11-08 14:55:32', '2026-11-08 14:55:32'),
(5, 9, 7, '2026-11-12 09:20:46', '2026-11-12 09:22:49', '2026-11-12 09:22:49'),
(6, 3, 11, '2026-11-14 15:57:54', '2026-11-14 17:25:55', '2026-11-14 16:07:28'),
(9, 3, 9, '2026-11-14 18:01:06', '2026-11-14 18:03:25', NULL),
(10, 9, 9, '2026-11-14 18:12:24', '2026-11-14 19:06:31', '2026-11-14 19:06:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensaje`
--

CREATE TABLE `mensaje` (
  `id_mensaje` int(10) UNSIGNED NOT NULL,
  `id_remitente` int(10) UNSIGNED NOT NULL,
  `id_destinatario` int(10) UNSIGNED NOT NULL,
  `id_curso` int(10) UNSIGNED NOT NULL,
  `texto` text NOT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mensaje`
--

INSERT INTO `mensaje` (`id_mensaje`, `id_remitente`, `id_destinatario`, `id_curso`, `texto`, `fecha_envio`) VALUES
(1, 3, 1, 7, 'buenas tardes, disculpe porque solo hay videos de baseball?', '2026-11-07 12:06:54'),
(2, 1, 3, 7, 'porque solo es una prueba', '2026-11-07 12:23:57'),
(3, 9, 1, 9, 'Hola, profesor. ¿Qué temas incluye el nivel Introducción de MySQL?', '2026-11-14 18:16:42'),
(4, 1, 9, 9, 'Hola, Juan. Incluye una introducción a las bases de datos y al uso de MySQL.', '2026-11-14 18:19:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nivel`
--

CREATE TABLE `nivel` (
  `id_nivel` int(10) UNSIGNED NOT NULL,
  `id_curso` int(10) UNSIGNED NOT NULL,
  `orden` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `video_url` varchar(255) NOT NULL,
  `contenido_texto` text DEFAULT NULL,
  `costo_nivel` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `gratis` tinyint(1) NOT NULL DEFAULT 0,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `enlace` varchar(2048) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nivel`
--

INSERT INTO `nivel` (`id_nivel`, `id_curso`, `orden`, `titulo`, `video_url`, `contenido_texto`, `costo_nivel`, `gratis`, `archivo_pdf`, `imagen`, `enlace`) VALUES
(1, 7, 1, 'Introducción con cambios', 'faefa2bdb49fa07f9753fb13bf0e9c44.mp4', 'El primer paso hacia la educación', 0.00, 1, '7e2ae927e658cbcd99f97bcec2e08d46.pdf', '5f1ac0da04bc51d28cf8f2a6c333334a.jpg', 'https://developer.mozilla.org/es/docs/Web/HTML'),
(4, 7, 2, 'CSS y JS', 'f46514d09b9f1ce27f6338f1ba49d818.mp4', '', 0.00, 1, NULL, NULL, NULL),
(5, 6, 1, 'Introduccion', 'a6d28e85300dd9811cd023993b6d882b.mp4', '', 5.00, 0, NULL, NULL, NULL),
(6, 9, 1, 'Introducción', '4491ad8bdf53467406f3fc1b9f882cd7.mp4', '', 25.00, 0, NULL, NULL, NULL),
(8, 11, 1, 'Nivel de prueba', '23bca145207a1f203d77448a56d74e9c.mp4', 'Contenido para probar la API', 0.00, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `id_pago` int(10) UNSIGNED NOT NULL,
  `id_inscripcion` int(10) UNSIGNED NOT NULL,
  `id_nivel` int(10) UNSIGNED DEFAULT NULL,
  `monto` decimal(10,2) UNSIGNED NOT NULL,
  `forma_pago` varchar(50) NOT NULL,
  `referencia_pasarela` varchar(150) DEFAULT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`id_pago`, `id_inscripcion`, `id_nivel`, `monto`, `forma_pago`, `referencia_pasarela`, `fecha_pago`) VALUES
(1, 2, NULL, 10.00, 'PayPal', 'paypal:3G264335GP9883122', '2026-11-08 12:35:41'),
(2, 3, 5, 5.00, 'PayPal', 'paypal:2NP728819J487733D', '2026-11-08 13:45:59'),
(3, 4, 6, 25.00, 'Tarjeta (Stripe)', 'stripe:cs_test_a1c5SljikHoTatymzcYOWYVdn8Gwlhkuovr5E4Age0wK87vxYJ9FVT1qz4', '2026-11-08 14:52:25'),
(4, 9, 6, 25.00, 'PayPal', 'paypal:9W283087H2086850F', '2026-11-14 18:01:06'),
(5, 10, 6, 25.00, 'Tarjeta (Stripe)', 'stripe:cs_test_a0KO3qik2BB8x66WWz35mAiVbbIs0tUiW1vJXOnJzecZlqsamZJZ8DWlU5', '2026-11-14 18:12:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progreso_nivel`
--

CREATE TABLE `progreso_nivel` (
  `id_progreso` int(10) UNSIGNED NOT NULL,
  `id_inscripcion` int(10) UNSIGNED NOT NULL,
  `id_nivel` int(10) UNSIGNED NOT NULL,
  `completado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_completado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `progreso_nivel`
--

INSERT INTO `progreso_nivel` (`id_progreso`, `id_inscripcion`, `id_nivel`, `completado`, `fecha_completado`) VALUES
(1, 1, 1, 1, '2026-11-06 22:06:13'),
(2, 1, 4, 1, '2026-11-07 11:42:34'),
(3, 2, 5, 1, '2026-11-08 13:41:02'),
(4, 4, 6, 1, '2026-11-08 14:55:32'),
(5, 5, 1, 1, '2026-11-12 09:21:29'),
(6, 5, 4, 1, '2026-11-12 09:22:49'),
(7, 6, 8, 1, '2026-11-14 16:07:28'),
(11, 10, 6, 1, '2026-11-14 19:06:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(150) NOT NULL,
  `genero` enum('Femenino','Masculino','Otro') NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `imagen_perfil` varchar(255) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('estudiante','instructor','administrador') NOT NULL,
  `intentos_fallidos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_ultimo_cambio` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `apellidos`, `genero`, `fecha_nacimiento`, `imagen_perfil`, `email`, `password_hash`, `rol`, `intentos_fallidos`, `activo`, `fecha_registro`, `fecha_ultimo_cambio`) VALUES
(1, 'Pedro Instructor', 'Huerta Diaz', 'Masculino', '1995-01-12', 'uploads/perfiles/292269561687770fc9f661866aa186b3.png', 'alex394@hotmail.com', '$2y$10$pkiZ2jG8dVrgqzXTZi24wO10x9DO67jyh4CNNOrc7mqymJ/jjZrfO', 'instructor', 0, 1, '2026-10-16 17:02:10', '2026-10-24 01:22:51'),
(2, 'Antonio', 'Mendez Treviño', 'Masculino', '2002-10-05', NULL, 'amen569@hotmail.com', '$2y$10$drIQLFRJ2wkbzE/ryNi6dO2tCtl4pqZ81JfACHjYCHaqowz58jeRS', 'estudiante', 0, 1, '2026-10-16 18:36:12', '2026-10-16 18:36:12'),
(3, 'Estudiante', 'Prueba', 'Femenino', '2000-01-12', 'uploads/perfiles/e05ddec2349430ce880672a548327e2e.png', 'vale698@gmail.com', '$2y$10$JWsTRNZwdDiLQoK2Vlp2QOaitERwsvJqb4/75koZYmtsSLWAvM9Z.', 'estudiante', 0, 1, '2026-10-17 12:16:12', '2026-11-14 17:54:00'),
(4, 'Administrador Prueba', 'Prueba', 'Masculino', '2000-01-01', '', 'admin@konnekt.com', '$2y$10$pkiZ2jG8dVrgqzXTZi24wO10x9DO67jyh4CNNOrc7mqymJ/jjZrfO', 'administrador', 0, 1, '2026-10-17 13:01:22', '2026-11-07 10:44:37'),
(5, 'Roberto', 'Rodriguez Peña', 'Masculino', '2003-05-12', NULL, 'rob356@hotmail.com', '$2y$10$lj3kAfujm7RfhUHxBTBI2.8edXRs.TNFWQYUIG4IcRBDdde0H80jG', 'estudiante', 0, 1, '2026-10-23 23:48:48', '2026-10-24 07:24:18'),
(6, 'Alejandro', 'Huerta', 'Otro', '2002-01-12', NULL, 'ale387@gmail.com', '$2y$10$QJCk66hybR559eJOOO78KuZ7aSPOcBAKJiJoeCLQsDehlRjXs3wAq', 'instructor', 0, 1, '2026-10-24 07:22:56', '2026-11-06 20:12:21'),
(7, 'Jorge', 'Perez Castro', 'Masculino', '2000-03-12', NULL, 'jorgesa174@gmail.com', '$2y$10$Qh9k0XIiMvxbXb01MFwEIemyUN8apmlBnS.dgsEwYvq6Yfo3BOJy6', 'estudiante', 0, 1, '2026-10-24 07:54:58', '2026-10-24 07:57:59'),
(8, 'Maria', 'Negrete Mendez', 'Femenino', '2002-01-12', NULL, 'maria74@hotmail.com', '$2y$10$HotRQjMXer.PWfq2whwXmuCQVeXcKXd9GWfgxa.cRU7/nzRyQpAge', 'instructor', 0, 1, '2026-11-06 20:14:13', '2026-11-06 20:14:13'),
(9, 'Juan', 'Rodriguez Huerta', 'Masculino', '1999-01-12', 'uploads/perfiles/bba17b6279295ac856c0db793b36fafa.png', 'juan97@gmail.com', '$2y$10$7wXh4MIW1twepPrjtwgbC.1EPrfNegCn1pyU7dljGHVSbLFEg9JaC', 'estudiante', 0, 1, '2026-11-12 09:18:06', '2026-11-14 18:24:34');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calificacion`
--
ALTER TABLE `calificacion`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `uq_calificacion` (`id_usuario`,`id_curso`),
  ADD KEY `fk_calificacion_curso` (`id_curso`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `fk_categoria_usuario` (`id_usuario_creador`);

--
-- Indices de la tabla `certificado`
--
ALTER TABLE `certificado`
  ADD PRIMARY KEY (`id_certificado`),
  ADD UNIQUE KEY `id_inscripcion` (`id_inscripcion`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `comentario`
--
ALTER TABLE `comentario`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `fk_comentario_usuario` (`id_usuario`),
  ADD KEY `fk_comentario_curso` (`id_curso`);

--
-- Indices de la tabla `curso`
--
ALTER TABLE `curso`
  ADD PRIMARY KEY (`id_curso`),
  ADD KEY `fk_curso_instructor` (`id_instructor`),
  ADD KEY `fk_curso_categoria` (`id_categoria`);

--
-- Indices de la tabla `inscripcion`
--
ALTER TABLE `inscripcion`
  ADD PRIMARY KEY (`id_inscripcion`),
  ADD UNIQUE KEY `uq_inscripcion` (`id_estudiante`,`id_curso`),
  ADD KEY `fk_inscripcion_curso` (`id_curso`);

--
-- Indices de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `fk_mensaje_remitente` (`id_remitente`),
  ADD KEY `fk_mensaje_destinatario` (`id_destinatario`),
  ADD KEY `fk_mensaje_curso` (`id_curso`);

--
-- Indices de la tabla `nivel`
--
ALTER TABLE `nivel`
  ADD PRIMARY KEY (`id_nivel`),
  ADD UNIQUE KEY `uq_nivel_orden` (`id_curso`,`orden`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD UNIQUE KEY `uq_pago_referencia` (`referencia_pasarela`),
  ADD KEY `fk_pago_inscripcion` (`id_inscripcion`),
  ADD KEY `fk_pago_nivel` (`id_nivel`);

--
-- Indices de la tabla `progreso_nivel`
--
ALTER TABLE `progreso_nivel`
  ADD PRIMARY KEY (`id_progreso`),
  ADD UNIQUE KEY `uq_progreso` (`id_inscripcion`,`id_nivel`),
  ADD KEY `fk_progreso_nivel` (`id_nivel`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `calificacion`
--
ALTER TABLE `calificacion`
  MODIFY `id_calificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `certificado`
--
ALTER TABLE `certificado`
  MODIFY `id_certificado` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `comentario`
--
ALTER TABLE `comentario`
  MODIFY `id_comentario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `curso`
--
ALTER TABLE `curso`
  MODIFY `id_curso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `inscripcion`
--
ALTER TABLE `inscripcion`
  MODIFY `id_inscripcion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `mensaje`
--
ALTER TABLE `mensaje`
  MODIFY `id_mensaje` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `nivel`
--
ALTER TABLE `nivel`
  MODIFY `id_nivel` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `id_pago` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `progreso_nivel`
--
ALTER TABLE `progreso_nivel`
  MODIFY `id_progreso` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calificacion`
--
ALTER TABLE `calificacion`
  ADD CONSTRAINT `fk_calificacion_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_calificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD CONSTRAINT `fk_categoria_usuario` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuario` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `certificado`
--
ALTER TABLE `certificado`
  ADD CONSTRAINT `fk_certificado_inscripcion` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripcion` (`id_inscripcion`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `comentario`
--
ALTER TABLE `comentario`
  ADD CONSTRAINT `fk_comentario_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comentario_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `curso`
--
ALTER TABLE `curso`
  ADD CONSTRAINT `fk_curso_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_curso_instructor` FOREIGN KEY (`id_instructor`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `inscripcion`
--
ALTER TABLE `inscripcion`
  ADD CONSTRAINT `fk_inscripcion_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inscripcion_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `mensaje`
--
ALTER TABLE `mensaje`
  ADD CONSTRAINT `fk_mensaje_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensaje_destinatario` FOREIGN KEY (`id_destinatario`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensaje_remitente` FOREIGN KEY (`id_remitente`) REFERENCES `usuario` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `nivel`
--
ALTER TABLE `nivel`
  ADD CONSTRAINT `fk_nivel_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_inscripcion` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripcion` (`id_inscripcion`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `nivel` (`id_nivel`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `progreso_nivel`
--
ALTER TABLE `progreso_nivel`
  ADD CONSTRAINT `fk_progreso_inscripcion` FOREIGN KEY (`id_inscripcion`) REFERENCES `inscripcion` (`id_inscripcion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progreso_nivel` FOREIGN KEY (`id_nivel`) REFERENCES `nivel` (`id_nivel`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
