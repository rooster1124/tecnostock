-- TecnoStock - Sistema de inventario
-- Script de creación de la base de datos (MySQL / MariaDB - XAMPP)
-- Autor: Nicolas Tapia

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS tecnostock;
CREATE DATABASE tecnostock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnostock;

-- Rol agregado: ENCARGADO administra todo, VENDEDOR vende y carga stock
CREATE TABLE usuario (
    id_usuario  INT          NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100) NOT NULL,
    correo      VARCHAR(120) NOT NULL,
    clave       VARCHAR(255) NOT NULL,
    rol         ENUM('ENCARGADO', 'VENDEDOR') NOT NULL DEFAULT 'VENDEDOR',
    estado      TINYINT(1)   NOT NULL DEFAULT 1,

    PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuario_correo  UNIQUE (correo),
    CONSTRAINT chk_usuario_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

CREATE TABLE categoria (
    id_categoria INT          NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(60)  NOT NULL,
    descripcion  VARCHAR(255) NULL,
    estado       TINYINT(1)   NOT NULL DEFAULT 1,

    PRIMARY KEY (id_categoria),
    CONSTRAINT uq_categoria_nombre  UNIQUE (nombre),
    CONSTRAINT chk_categoria_estado CHECK (estado IN (0, 1))
) ENGINE = InnoDB;

-- RN01: código único · RN02: sin negativos · RN04: stock nunca bajo cero
CREATE TABLE producto (
    id_producto  INT          NOT NULL AUTO_INCREMENT,
    codigo       VARCHAR(20)  NOT NULL,
    nombre       VARCHAR(100) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    precio       INT          NOT NULL,
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
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE INDEX idx_producto_nombre ON producto (nombre);

-- Oferta por cantidad: precio especial para las próximas N unidades vendidas.
-- Al venderse todas pasa a FINALIZADA y el producto vuelve a su precio normal.
CREATE TABLE oferta (
    id_oferta        INT      NOT NULL AUTO_INCREMENT,
    id_producto      INT      NOT NULL,
    precio_oferta    INT      NOT NULL,
    cantidad_total   INT      NOT NULL,
    cantidad_vendida INT      NOT NULL DEFAULT 0,
    estado           ENUM('ACTIVA', 'FINALIZADA', 'CANCELADA') NOT NULL DEFAULT 'ACTIVA',
    fecha_inicio     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin        DATETIME NULL,
    id_usuario       INT      NOT NULL,

    PRIMARY KEY (id_oferta),
    CONSTRAINT chk_oferta_precio   CHECK (precio_oferta > 0),
    CONSTRAINT chk_oferta_cantidad CHECK (cantidad_total > 0),
    CONSTRAINT chk_oferta_vendida  CHECK (cantidad_vendida >= 0 AND cantidad_vendida <= cantidad_total),
    CONSTRAINT fk_oferta_producto
        FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_oferta_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE INDEX idx_oferta_producto_estado ON oferta (id_producto, estado);

-- RN03: fecha, tipo, cantidad, producto y usuario obligatorios
-- RN05: ON DELETE RESTRICT impide borrar un producto con movimientos
-- Agregados: motivo (venta, compra, merma...), precio_unitario (precio del momento)
-- e id_oferta (si la venta se hizo con precio de oferta)
CREATE TABLE movimiento (
    id_movimiento   INT          NOT NULL AUTO_INCREMENT,
    fecha_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo            ENUM('ENTRADA', 'SALIDA') NOT NULL,
    motivo          VARCHAR(30)  NOT NULL,
    cantidad        INT          NOT NULL,
    precio_unitario INT          NOT NULL DEFAULT 0,
    observacion     VARCHAR(255) NULL,
    id_producto     INT          NOT NULL,
    id_usuario      INT          NOT NULL,
    id_oferta       INT          NULL,

    PRIMARY KEY (id_movimiento),
    CONSTRAINT chk_movimiento_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_movimiento_precio   CHECK (precio_unitario >= 0),
    CONSTRAINT chk_movimiento_motivo CHECK (
        (tipo = 'ENTRADA' AND motivo IN ('Stock inicial', 'Compra a proveedor', 'Devolución de cliente', 'Ajuste de inventario'))
        OR
        (tipo = 'SALIDA'  AND motivo IN ('Venta', 'Producto dañado', 'Devolución a proveedor', 'Ajuste de inventario'))
    ),
    CONSTRAINT fk_movimiento_producto
        FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_movimiento_oferta
        FOREIGN KEY (id_oferta) REFERENCES oferta (id_oferta)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE INDEX idx_movimiento_fecha ON movimiento (fecha_hora);

-- Datos de prueba
-- Encargado: admin@tecnostock.cl / admin123 · Vendedor: vendedor@tecnostock.cl / vendedor123
INSERT INTO usuario (nombre, correo, clave, rol) VALUES
('Encargado de Inventario', 'admin@tecnostock.cl',
 '$2y$12$xtEs4.6wkR8JY1Z7Sb5Nv.tVCRkV0tUAAPR4gaeWDr8NUxIDW/C.2', 'ENCARGADO'),
('Vendedor de Tienda', 'vendedor@tecnostock.cl',
 '$2y$12$2C4ja9v6UP1vm5cjwta/vep3Ox1z0WbuRR6rdTWfz4QLHbEZAOUBa', 'VENDEDOR');

INSERT INTO categoria (nombre, descripcion) VALUES
('Audio',               'Audífonos, parlantes y micrófonos'),
('Cables y cargadores', 'Cables USB, HDMI y cargadores'),
('Periféricos',         'Teclados, mouse y webcams'),
('Almacenamiento',      'Pendrives, discos y tarjetas de memoria'),
('Accesorios móviles',  'Carcasas, soportes y protectores');

-- Los marcados con (*) quedan bajo su stock mínimo
INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) VALUES
('AUD-001', 'Audífonos Bluetooth X200',  'Audífonos inalámbricos con cancelación de ruido', 24990, 15, 5, 1),
('AUD-002', 'Parlante portátil Mini',    'Parlante Bluetooth resistente al agua',          19990,  3, 5, 1), -- (*)
('CAB-001', 'Cable USB-C 1m',            'Cable de carga rápida USB-C a USB-C',             4990, 40, 10, 2),
('CAB-002', 'Cargador 20W USB-C',        'Cargador de pared con carga rápida',              12990,  8, 10, 2), -- (*)
('PER-001', 'Mouse inalámbrico M100',    'Mouse óptico 1600 DPI',                            8990, 25, 8, 3),
('PER-002', 'Teclado mecánico K70',      'Teclado mecánico retroiluminado',                 39990,  6, 3, 3),
('ALM-001', 'Pendrive 64GB',             'Memoria USB 3.0 de 64GB',                          7990, 30, 10, 4),
('MOV-001', 'Soporte de celular auto',   'Soporte magnético para rejilla de ventilación',    5990,  2, 4, 5); -- (*)

-- Producto desactivado: tiene movimientos, por eso no se elimina (RN05)
INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria) VALUES
('CAB-003', 'Cable HDMI 1.4 2m', 'Modelo descontinuado', 3990, 10, 5, 0, 2);

-- Fechas relativas a hoy: el gráfico siempre muestra la última semana
INSERT INTO movimiento (fecha_hora, tipo, motivo, cantidad, precio_unitario, observacion, id_producto, id_usuario) VALUES
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:00:00'), 'ENTRADA', 'Stock inicial', 20, 24990, NULL, 1, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:05:00'), 'ENTRADA', 'Stock inicial', 10, 19990, NULL, 2, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:10:00'), 'ENTRADA', 'Stock inicial', 50,  4990, NULL, 3, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:15:00'), 'ENTRADA', 'Stock inicial', 15, 12990, NULL, 4, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:20:00'), 'ENTRADA', 'Stock inicial', 25,  8990, NULL, 5, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:25:00'), 'ENTRADA', 'Stock inicial',  6, 39990, NULL, 6, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:30:00'), 'ENTRADA', 'Stock inicial', 30,  7990, NULL, 7, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:35:00'), 'ENTRADA', 'Stock inicial',  5,  5990, NULL, 8, 1),
(TIMESTAMP(CURDATE() - INTERVAL 10 DAY, '09:40:00'), 'ENTRADA', 'Stock inicial', 10,  3990, NULL, 9, 1),
(TIMESTAMP(CURDATE() - INTERVAL 6 DAY, '11:00:00'), 'SALIDA', 'Venta',           2, 24990, 'Venta en tienda',             1, 1),
(TIMESTAMP(CURDATE() - INTERVAL 5 DAY, '10:15:00'), 'SALIDA', 'Venta',           4,  4990, 'Venta en tienda',             3, 1),
(TIMESTAMP(CURDATE() - INTERVAL 5 DAY, '17:40:00'), 'SALIDA', 'Venta',           5, 12990, 'Venta por mayor',             4, 1),
(TIMESTAMP(CURDATE() - INTERVAL 4 DAY, '12:30:00'), 'SALIDA', 'Venta',           3, 19990, 'Venta en tienda',             2, 1),
(TIMESTAMP(CURDATE() - INTERVAL 3 DAY, '13:05:00'), 'SALIDA', 'Venta',           3, 24990, 'Venta en tienda',             1, 1),
(TIMESTAMP(CURDATE() - INTERVAL 3 DAY, '15:20:00'), 'SALIDA', 'Producto dañado', 3,  5990, 'Llegaron con el imán suelto', 8, 1),
(TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '10:00:00'), 'SALIDA', 'Venta',           6,  4990, 'Venta por mayor',             3, 1),
(TIMESTAMP(CURDATE() - INTERVAL 2 DAY, '16:45:00'), 'SALIDA', 'Venta',           2, 12990, 'Venta en tienda',             4, 1),
(TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '18:10:00'), 'SALIDA', 'Venta',           4, 19990, 'Venta en tienda',             2, 1);

-- Oferta activa de ejemplo: Audífonos a $19.990 por las próximas 5 unidades
INSERT INTO oferta (id_producto, precio_oferta, cantidad_total, fecha_inicio, id_usuario) VALUES
(1, 19990, 5, TIMESTAMP(CURDATE() - INTERVAL 1 DAY, '09:00:00'), 1);