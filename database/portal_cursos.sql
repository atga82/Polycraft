CREATE DATABASE IF NOT EXISTS portal_cursos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE portal_cursos;

-- ------------------------------------------------------------
-- ROLES (estudiante, instructor, administrador)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
  id     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(20)      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- USUARIOS
-- La llave primaria es un id autoincremental (NO el email), así el
-- usuario puede cambiar su email/perfil sin romper ninguna relación.
-- fecha_modificacion se actualiza desde el modelo SOLO cuando el
-- usuario cambia su información personal (no en cada intento de login).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  rol_id             TINYINT UNSIGNED NOT NULL,
  nombre_completo    VARCHAR(120)     NOT NULL,
  genero             ENUM('masculino','femenino','otro') NOT NULL,
  fecha_nacimiento   DATE             NOT NULL,
  avatar             VARCHAR(255)     NOT NULL DEFAULT 'default.png',
  email              VARCHAR(150)     NOT NULL,
  password_hash      VARCHAR(255)     NOT NULL,
  activo             TINYINT(1)       NOT NULL DEFAULT 1,
  intentos_fallidos  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fecha_registro     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_modificacion DATETIME         NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_rol (rol_id),
  CONSTRAINT fk_usuarios_rol
    FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CATEGORIAS (solo el administrador las crea, edita o elimina)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre         VARCHAR(80)  NOT NULL,
  descripcion    TEXT         NULL,
  creado_por     INT UNSIGNED NOT NULL,
  fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nombre (nombre),
  KEY idx_categorias_creador (creado_por),
  CONSTRAINT fk_categorias_usuario
    FOREIGN KEY (creado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CURSOS
-- precio = costo del curso completo (0.00 = gratis).
-- activo = 0 es la baja lógica (no sale en búsquedas ni portada,
-- pero se conserva para reportes de ventas y Kardex).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cursos (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  instructor_id  INT UNSIGNED  NOT NULL,
  titulo         VARCHAR(150)  NOT NULL,
  descripcion    TEXT          NOT NULL,
  imagen         VARCHAR(255)  NOT NULL,
  precio         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  activo         TINYINT(1)    NOT NULL DEFAULT 1,
  fecha_creacion DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cursos_instructor (instructor_id),
  KEY idx_cursos_activo_fecha (activo, fecha_creacion),
  CONSTRAINT chk_cursos_precio CHECK (precio >= 0),
  CONSTRAINT fk_cursos_instructor
    FOREIGN KEY (instructor_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CURSO_CATEGORIA (un curso pertenece a una o más categorías)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS curso_categoria (
  curso_id     INT UNSIGNED NOT NULL,
  categoria_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (curso_id, categoria_id),
  KEY idx_cc_categoria (categoria_id),
  CONSTRAINT fk_cc_curso
    FOREIGN KEY (curso_id) REFERENCES cursos (id) ON DELETE CASCADE,
  CONSTRAINT fk_cc_categoria
    FOREIGN KEY (categoria_id) REFERENCES categorias (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NIVELES (cada nivel lleva un video obligatorio)
-- precio = costo del nivel suelto (0.00 = nivel gratis)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS niveles (
  id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  curso_id    INT UNSIGNED     NOT NULL,
  numero      TINYINT UNSIGNED NOT NULL,
  titulo      VARCHAR(150)     NOT NULL,
  descripcion TEXT             NULL,
  contenido   TEXT             NULL,
  video_url   VARCHAR(255)     NOT NULL,
  precio      DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  UNIQUE KEY uq_niveles_curso_numero (curso_id, numero),
  CONSTRAINT chk_niveles_precio CHECK (precio >= 0),
  CONSTRAINT fk_niveles_curso
    FOREIGN KEY (curso_id) REFERENCES cursos (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- RECURSOS_NIVEL (PDF, enlaces externos e imágenes de un nivel)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recursos_nivel (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nivel_id INT UNSIGNED NOT NULL,
  tipo     ENUM('pdf','enlace','imagen') NOT NULL,
  titulo   VARCHAR(150) NOT NULL,
  url      VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_recursos_nivel (nivel_id),
  CONSTRAINT fk_recursos_nivel
    FOREIGN KEY (nivel_id) REFERENCES niveles (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- FORMAS_PAGO
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS formas_pago (
  id     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(40)      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_formas_pago_nombre (nombre)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- COMPRAS (estado local + referencia a la order del proveedor)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- INSCRIPCIONES (Kardex: un alumno inscrito en un curso)
-- ultimo_acceso      = última vez que entró a algún nivel
-- fecha_terminacion  = NULL mientras el curso esté incompleto
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inscripciones (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  estudiante_id     INT UNSIGNED NOT NULL,
  curso_id          INT UNSIGNED NOT NULL,
  fecha_inscripcion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso     DATETIME     NULL,
  fecha_terminacion DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_inscripcion (estudiante_id, curso_id),
  KEY idx_inscripciones_curso (curso_id),
  CONSTRAINT fk_inscripciones_estudiante
    FOREIGN KEY (estudiante_id) REFERENCES usuarios (id),
  CONSTRAINT fk_inscripciones_curso
    FOREIGN KEY (curso_id) REFERENCES cursos (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PROGRESO_NIVELES (una fila = el alumno ya completó ese nivel)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progreso_niveles (
  inscripcion_id   INT UNSIGNED NOT NULL,
  nivel_id         INT UNSIGNED NOT NULL,
  fecha_completado DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (inscripcion_id, nivel_id),
  KEY idx_progreso_nivel (nivel_id),
  CONSTRAINT fk_progreso_inscripcion
    FOREIGN KEY (inscripcion_id) REFERENCES inscripciones (id) ON DELETE CASCADE,
  CONSTRAINT fk_progreso_nivel
    FOREIGN KEY (nivel_id) REFERENCES niveles (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PAGOS
-- nivel_id NULL  -> pago del curso completo
-- nivel_id con valor -> pago de ese nivel suelto
-- Los cursos/niveles gratis no generan fila de pago.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagos (
  id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  inscripcion_id INT UNSIGNED     NOT NULL,
  nivel_id       INT UNSIGNED     NULL,
  compra_id      INT UNSIGNED     NULL,
  forma_pago_id  TINYINT UNSIGNED NOT NULL,
  monto          DECIMAL(10,2)    NOT NULL,
  fecha_pago     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pagos_inscripcion (inscripcion_id),
  KEY idx_pagos_nivel (nivel_id),
  UNIQUE KEY uq_pagos_compra (compra_id),
  KEY idx_pagos_forma (forma_pago_id),
  CONSTRAINT chk_pagos_monto CHECK (monto >= 0),
  CONSTRAINT fk_pagos_inscripcion
    FOREIGN KEY (inscripcion_id) REFERENCES inscripciones (id),
  CONSTRAINT fk_pagos_nivel
    FOREIGN KEY (nivel_id) REFERENCES niveles (id),
  CONSTRAINT fk_pagos_compra
    FOREIGN KEY (compra_id) REFERENCES compras (id),
  CONSTRAINT fk_pagos_forma
    FOREIGN KEY (forma_pago_id) REFERENCES formas_pago (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- COMENTARIOS (calificación 1 a 5 estrellas + comentario)
-- Borrado lógico por el administrador: se guarda fecha y causa y la
-- vista muestra la leyenda "mensaje eliminado".
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comentarios (
  id                INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  curso_id          INT UNSIGNED     NOT NULL,
  usuario_id        INT UNSIGNED     NOT NULL,
  calificacion      TINYINT UNSIGNED NOT NULL,
  comentario        TEXT             NOT NULL,
  fecha_creacion    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  eliminado         TINYINT(1)       NOT NULL DEFAULT 0,
  fecha_eliminacion DATETIME         NULL,
  causa_eliminacion VARCHAR(255)     NULL,
  eliminado_por     INT UNSIGNED     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_comentario_usuario_curso (curso_id, usuario_id),
  KEY idx_comentarios_usuario (usuario_id),
  KEY idx_comentarios_eliminador (eliminado_por),
  CONSTRAINT chk_comentarios_calif CHECK (calificacion BETWEEN 1 AND 5),
  CONSTRAINT fk_comentarios_curso
    FOREIGN KEY (curso_id) REFERENCES cursos (id),
  CONSTRAINT fk_comentarios_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
  CONSTRAINT fk_comentarios_eliminador
    FOREIGN KEY (eliminado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MENSAJES PRIVADOS (normalmente alumno <-> instructor)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mensajes (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  remitente_id    INT UNSIGNED NOT NULL,
  destinatario_id INT UNSIGNED NOT NULL,
  curso_id        INT UNSIGNED NULL,
  contenido       TEXT         NOT NULL,
  fecha_envio     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  leido           TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_mensajes_remitente (remitente_id),
  KEY idx_mensajes_destinatario (destinatario_id),
  KEY idx_mensajes_curso (curso_id),
  CONSTRAINT fk_mensajes_remitente
    FOREIGN KEY (remitente_id) REFERENCES usuarios (id),
  CONSTRAINT fk_mensajes_destinatario
    FOREIGN KEY (destinatario_id) REFERENCES usuarios (id),
  CONSTRAINT fk_mensajes_curso
    FOREIGN KEY (curso_id) REFERENCES cursos (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CERTIFICADOS (se genera al terminar todos los niveles del curso)
-- Nombre del alumno, del curso, del instructor que certifica y la
-- fecha de terminación se obtienen con JOIN desde inscripcion_id.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS certificados (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  inscripcion_id INT UNSIGNED NOT NULL,
  codigo         CHAR(36)     NOT NULL,
  fecha_emision  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_certificado_inscripcion (inscripcion_id),
  UNIQUE KEY uq_certificado_codigo (codigo),
  CONSTRAINT fk_certificados_inscripcion
    FOREIGN KEY (inscripcion_id) REFERENCES inscripciones (id)
) ENGINE=InnoDB;

-- ============================================================
--  DATOS INICIALES (catálogos)
-- ============================================================
INSERT IGNORE INTO roles (id, nombre) VALUES
  (1, 'estudiante'),
  (2, 'instructor'),
  (3, 'administrador');

INSERT IGNORE INTO formas_pago (id, nombre) VALUES
  (1, 'Tarjeta de crédito'),
  (2, 'Tarjeta de débito'),
  (3, 'PayPal'),
  (4, 'Transferencia'),
  (5, 'Mercado Pago');