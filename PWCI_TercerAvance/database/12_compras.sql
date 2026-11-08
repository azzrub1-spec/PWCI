USE konnekt;

-- Aplicar una vez a una base del avance 11. Una instalación nueva usa konnekt.sql.
ALTER TABLE pago
  ADD COLUMN id_nivel INT UNSIGNED NULL AFTER id_inscripcion,
  ADD CONSTRAINT fk_pago_nivel FOREIGN KEY (id_nivel) REFERENCES nivel(id_nivel)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT uq_pago_referencia UNIQUE (referencia_pasarela);
