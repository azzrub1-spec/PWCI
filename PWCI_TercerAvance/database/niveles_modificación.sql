-- Ejecutar una sola vez sobre la base existente del segundo/tercer avance.
-- Agrega rutas de materiales; no elimina tablas ni registros.
USE konnekt;

ALTER TABLE nivel
  ADD COLUMN archivo_pdf VARCHAR(255) NULL,
  ADD COLUMN imagen VARCHAR(255) NULL,
  ADD COLUMN enlace VARCHAR(2048) NULL;
