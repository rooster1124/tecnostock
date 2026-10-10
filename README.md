# TecnoStock – Sistema de Inventario Web

Proyecto del ramo **Sistemas de Información** – Caso 1.
Autor: Nicolas Tapia

---

## 1. Problema

TecnoStock es un emprendimiento que vende accesorios tecnológicos. Hoy registra sus productos en una planilla, lo que provoca:

| Problema actual | Cómo lo resuelve el sistema |
|---|---|
| Códigos de producto duplicados | El código es único en la base de datos (`UNIQUE`) y se valida antes de guardar |
| Errores en las cantidades disponibles | El stock solo cambia mediante movimientos registrados (entradas y salidas) |
| Poca claridad del stock real | Listado con búsqueda y alerta visual de productos con bajo stock |

## 2. Objetivo

Construir una aplicación web, conectada a una base de datos relacional, que permita registrar, consultar, actualizar y desactivar productos, y controlar sus entradas y salidas de inventario, manteniendo la integridad de la información.

## 3. Actor

| Actor | Responsabilidad |
|---|---|
| **Encargado de inventario** | Inicia sesión y administra productos, categorías y movimientos de existencias |

## 4. Alcance

**Incluye:**
- Inicio y cierre de sesión.
- Gestión de categorías (crear, listar, editar, desactivar).
- Gestión de productos (crear, listar, buscar, editar, desactivar).
- Registro de entradas y salidas de stock, con historial.
- Alerta de productos con stock bajo el mínimo.

**No incluye:**
- Ventas, facturación ni boletas.
- Gestión de proveedores o clientes.
- Registro de usuarios desde la interfaz (el usuario se crea en el script SQL).
- Múltiples roles o permisos.

## 5. Requerimientos funcionales

| ID | Requerimiento |
|---|---|
| RF01 | El sistema debe permitir iniciar sesión con un usuario previamente registrado. |
| RF02 | El sistema debe permitir registrar productos con código, nombre, categoría, descripción, precio, stock actual y stock mínimo. |
| RF03 | El sistema debe mostrar el listado de productos. |
| RF04 | El sistema debe permitir buscar productos por nombre, código o categoría. |
| RF05 | El sistema debe permitir modificar el precio, la descripción, la categoría y el stock mínimo de un producto. |
| RF06 | El sistema debe permitir desactivar productos que ya no se comercializan. |
| RF07 | El sistema debe permitir registrar entradas y salidas de existencias. |
| RF08 | El sistema debe mostrar una alerta cuando el stock actual sea menor que el stock mínimo. |
| RF09 | El sistema debe permitir gestionar categorías de productos. |
| RF10 | El sistema debe mostrar el historial de movimientos. |

## 6. Requerimientos no funcionales

| ID | Requerimiento |
|---|---|
| RNF01 | La interfaz debe ser comprensible para un usuario no técnico, con mensajes claros. |
| RNF02 | El sistema debe funcionar en un servidor local (XAMPP). |
| RNF03 | Todas las consultas a la base de datos deben usar consultas preparadas (PDO). |
| RNF04 | Las contraseñas deben almacenarse cifradas (`password_hash`). |
| RNF05 | Las validaciones deben hacerse en el cliente (JavaScript) y repetirse en el servidor (PHP). |
| RNF06 | La interfaz debe ser responsiva (Bootstrap). |
| RNF07 | Las páginas internas solo deben ser accesibles con sesión iniciada. |

## 7. Reglas de negocio

| ID | Regla | Dónde se controla |
|---|---|---|
| RN01 | El código de producto no puede repetirse. | BD (`UNIQUE`) + PHP |
| RN02 | Precio, stock actual, stock mínimo y cantidad no pueden ser negativos. | BD (`CHECK`) + PHP + JS |
| RN03 | Todo movimiento registra fecha, tipo, cantidad, producto y usuario responsable. | BD (`NOT NULL`, FK) + PHP (sesión) |
| RN04 | Una salida no puede dejar el stock bajo cero. | PHP + BD (`CHECK`) |
| RN05 | Un producto con movimientos se desactiva; no se elimina físicamente. | BD (FK `RESTRICT`) + PHP |
| RN06 | El stock se actualiza automáticamente después de cada movimiento válido. | PHP (transacción) |

## 8. Decisiones de diseño

- **El stock solo cambia mediante movimientos.** Al crear un producto se ingresa su stock inicial, que queda registrado como una entrada. Después, el stock no se edita a mano: se registran entradas o salidas. Así cada cambio queda trazable y se evitan los errores de la planilla.
- **Borrado lógico.** Productos y categorías tienen un campo `estado` (activo/inactivo) en vez de eliminarse, para conservar el historial.
- **Transacciones.** Registrar un movimiento y actualizar el stock ocurren dentro de una misma transacción: si una parte falla, no se guarda ninguna.

## 9. Tecnologías

| Propósito | Herramienta |
|---|---|
| Editor | Visual Studio Code |
| Servidor local | XAMPP (Apache + MariaDB) |
| Base de datos | MySQL / MariaDB + phpMyAdmin |
| Interfaz | HTML5 + Bootstrap 5 |
| Servidor | PHP 8 + PDO |
| Validaciones | JavaScript |
| Versionamiento | Git + GitHub |

## 10. Instalación

1. Instalar XAMPP e iniciar Apache y MySQL.
2. Clonar o copiar este repositorio en `C:\xampp\htdocs\tecnostock`.
3. Abrir `http://localhost/phpmyadmin` e importar `database/tecnostock.sql`.
4. Abrir `http://localhost/tecnostock`.
5. Ingresar con el usuario de prueba indicado en el script SQL.

## 11. Estructura del proyecto

```
tecnostock/
├── assets/        CSS y JavaScript (validaciones)
├── categorias/    CRUD de categorías
├── config/        Conexión a la base de datos
├── database/      Script SQL
├── docs/          Diagrama ER, casos de prueba y evidencias
├── includes/      Encabezado, pie y control de sesión
├── movimientos/   Entradas, salidas e historial
├── productos/     CRUD de productos
└── index.php      Inicio de sesión
```
