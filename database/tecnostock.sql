-- =====================================================================
-- TecnoStock - Sistema de Inventario
-- Script de creación de la base de datos
-- Motor: MySQL / MariaDB (XAMPP)
-- Autor: Nicolas Tapia
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Crear la base de datos
-- ---------------------------------------------------------------------
DROP DATABASE IF EXISTS tecnostock;

CREATE DATABASE tecnostock
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tecnostock;

-- ---------------------------------------------------------------------
-- 2. Tabla USUARIO
--    Encargado de inventario que inicia sesión y registra movimientos.
-- ---------------------------------------------------------------------
CREATE TABLE usuario (
    id_usuario  INT          NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100) NOT NULL,
    correo      VARCHAR(120) NOT NULL,
    clave       VARCHAR(255) NOT NULL,          -- hash generado con password_hash()
    estado      TINYINT(1)   NOT NULL DEFAULT 1, -- 1 = activo, 0 = inactivo

    PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuario_correo  UNIQUE (correo),
    CONSTRAINT chk_usuario_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 3. Tabla CATEGORIA
-- ---------------------------------------------------------------------
CREATE TABLE categoria (
    id_categoria INT          NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(60)  NOT NULL,
    descripcion  VARCHAR(255) NULL,
    estado       TINYINT(1)   NOT NULL DEFAULT 1,

    PRIMARY KEY (id_categoria),
    CONSTRAINT uq_categoria_nombre  UNIQUE (nombre),
    CONSTRAINT chk_categoria_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 4. Tabla PRODUCTO
--    RN01: código único            -> uq_producto_codigo
--    RN02: valores no negativos    -> chk_producto_precio / stock / minimo
--    RN04: stock nunca bajo cero   -> chk_producto_stock (última barrera)
-- ---------------------------------------------------------------------
CREATE TABLE producto (
    id_producto  INT          NOT NULL AUTO_INCREMENT,
    codigo       VARCHAR(20)  NOT NULL,
    nombre       VARCHAR(100) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    precio       INT          NOT NULL,           -- pesos chilenos (sin decimales)
    stock_actual INT          NOT NULL DEFAULT 0,
    stock_minimo INT          NOT NULL DEFAULT 0,
    estado       TINYINT(1)   NOT NULL DEFAULT 1,
    id_categoria INT          NOT NULL,

    PRIMARY KEY (id_producto),
    CONSTRAINT uq_producto_codigo  UNIQUE (codigo),
    CONSTRAINT chk_producto_precio CHECK (precio >= 0),
    CONSTRAINT chk_producto_stock  CHECK (stock_actual >= 0),
    CONSTRAINT chk_producto_minimo CHECK (stock_minimo >= 0),
    CONSTRAINT chk_producto_estado CHECK (estado IN (0, 1)),
    CONSTRAINT fk_producto_categoria
        FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB;

-- Índice para acelerar la búsqueda por nombre (RF04)
CREATE INDEX idx_producto_nombre ON producto (nombre);

-- ---------------------------------------------------------------------
-- 5. Tabla MOVIMIENTO
--    RN03: fecha, tipo, cantidad, producto y usuario obligatorios
--    RN05: ON DELETE RESTRICT impide borrar un producto con movimientos
-- ---------------------------------------------------------------------
CREATE TABLE movimiento (
    id_movimiento INT          NOT NULL AUTO_INCREMENT,
    fecha_hora    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo          ENUM('ENTRADA', 'SALIDA') NOT NULL,
    cantidad      INT          NOT NULL,
    observacion   VARCHAR(255) NULL,
    id_producto   INT          NOT NULL,
    id_usuario    INT          NOT NULL,

    PRIMARY KEY (id_movimiento),
    CONSTRAINT chk_movimiento_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_movimiento_producto
        FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB;

-- =====================================================================
-- DATOS DE PRUEBA
-- =====================================================================

-- Usuario de prueba
--   Correo: admin@tecnostock.cl
--   Clave:  admin123
INSERT INTO usuario (nombre, correo, clave) VALUES
('Encargado de Inventario', 'admin@tecnostock.cl',
 '$2y$12$xtEs4.6wkR8JY1Z7Sb5Nv.tVCRkV0tUAAPR4gaeWDr8NUxIDW/C.2');

-- Categorías
INSERT INTO categoria (nombre, descripcion) VALUES
('Audio',               'Audífonos, parlantes y micrófonos'),
('Cables y cargadores', 'Cables USB, HDMI y cargadores'),
('Periféricos',         'Teclados, mouse y webcams'),
('Almacenamiento',      'Pendrives, discos y tarjetas de memoria'),
('Accesorios móviles',  'Carcasas, soportes y protectores');

-- Productos
-- El stock_actual de cada producto coincide con la suma de sus movimientos.
-- Los productos marcados con (*) quedan bajo su stock mínimo (alerta RF08).
INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES
('AUD-001', 'Audífonos Bluetooth X200',  'Audífonos inalámbricos con cancelación de ruido', 24990, 15, 5, 1),
('AUD-002', 'Parlante portátil Mini',    'Parlante Bluetooth resistente al agua',          19990,  3, 5, 1), -- (*)
('CAB-001', 'Cable USB-C 1m',            'Cable de carga rápida USB-C a USB-C',             4990, 40, 10, 2),
('CAB-002', 'Cargador 20W USB-C',        'Cargador de pared con carga rápida',              12990,  8, 10, 2), -- (*)
('PER-001', 'Mouse inalámbrico M100',    'Mouse óptico 1600 DPI',                            8990, 25, 8, 3),
('PER-002', 'Teclado mecánico K70',      'Teclado mecánico retroiluminado',                 39990,  6, 3, 3),
('ALM-001', 'Pendrive 64GB',             'Memoria USB 3.0 de 64GB',                          7990, 30, 10, 4),
('MOV-001', 'Soporte de celular auto',   'Soporte magnético para rejilla de ventilación',    5990,  2, 4, 5); -- (*)

-- Movimientos
-- Stock inicial de cada producto registrado como ENTRADA (decisión de diseño)
INSERT INTO movimiento (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario) VALUES
('2026-10-01 09:00:00', 'ENTRADA', 20, 'Stock inicial', 1, 1),
('2026-10-01 09:05:00', 'ENTRADA', 10, 'Stock inicial', 2, 1),
('2026-10-01 09:10:00', 'ENTRADA', 50, 'Stock inicial', 3, 1),
('2026-10-01 09:15:00', 'ENTRADA', 15, 'Stock inicial', 4, 1),
('2026-10-01 09:20:00', 'ENTRADA', 25, 'Stock inicial', 5, 1),
('2026-10-01 09:25:00', 'ENTRADA',  6, 'Stock inicial', 6, 1),
('2026-10-01 09:30:00', 'ENTRADA', 30, 'Stock inicial', 7, 1),
('2026-10-01 09:35:00', 'ENTRADA',  5, 'Stock inicial', 8, 1),
-- Salidas posteriores
('2026-10-03 11:00:00', 'SALIDA',   5, 'Venta en tienda',     1, 1),
('2026-10-03 12:30:00', 'SALIDA',   7, 'Venta en tienda',     2, 1),
('2026-10-04 10:00:00', 'SALIDA',  10, 'Venta por mayor',     3, 1),
('2026-10-05 16:45:00', 'SALIDA',   7, 'Venta en tienda',     4, 1),
('2026-10-06 15:20:00', 'SALIDA',   3, 'Producto defectuoso', 8, 1);