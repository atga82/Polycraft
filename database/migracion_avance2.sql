-- Ejecutar SOLO una vez sobre una base portal_cursos que ya fue creada con el esquema anterior.
-- Si se está instalando desde cero, ejecutar portal_cursos.sql en su lugar y NO esta migración.
USE portal_cursos;

CREATE TABLE IF NOT EXISTS compras (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  estudiante_id      INT UNSIGNED NOT NULL,
  curso_id           INT UNSIGNED NOT NULL,
  monto              DECIMAL(10,2) NOT NULL,
  estado             ENUM('pendiente','aprobada','rechazada','cancelada','error') NOT NULL DEFAULT 'pendiente',
  referencia_externa VARCHAR(120) NOT NULL,
  mp_order_id        VARCHAR(100) NULL,
  mp_status          VARCHAR(40) NULL,
  mp_status_detail   VARCHAR(80) NULL,
  fecha_creacion     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_compras_referencia (referencia_externa),
  UNIQUE KEY uq_compras_mp_order (mp_order_id),
  KEY idx_compras_estudiante (estudiante_id),
  KEY idx_compras_curso (curso_id),
  CONSTRAINT chk_compras_monto CHECK (monto > 0),
  CONSTRAINT fk_compras_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios (id),
  CONSTRAINT fk_compras_curso FOREIGN KEY (curso_id) REFERENCES cursos (id)
) ENGINE=InnoDB;

ALTER TABLE pagos ADD COLUMN compra_id INT UNSIGNED NULL AFTER nivel_id;
ALTER TABLE pagos ADD UNIQUE KEY uq_pagos_compra (compra_id);
ALTER TABLE pagos ADD CONSTRAINT fk_pagos_compra FOREIGN KEY (compra_id) REFERENCES compras (id);

INSERT IGNORE INTO formas_pago (id, nombre) VALUES (5, 'Mercado Pago');
