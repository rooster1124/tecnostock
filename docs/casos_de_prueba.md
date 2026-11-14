# Casos de prueba – TecnoStock

Pruebas funcionales realizadas sobre la base de datos de ejemplo (`database/tecnostock.sql` recién importada).

**Cuentas usadas:** Encargado (`admin@tecnostock.cl` / `admin123`) y Vendedor (`vendedor@tecnostock.cl` / `vendedor123`).

**Estado:** ✅ = resultado obtenido igual al esperado · ❌ = falla · ☐ = pendiente

## 1. Inicio de sesión y roles

| ID | Requisito | Caso | Pasos y datos | Resultado esperado | Estado |
|---|---|---|---|---|:---:|
| CP01 | RF01 | Login correcto | Ingresar `admin@tecnostock.cl` / `admin123` | Entra al Inicio y muestra "Hola, Encargado de Inventario" con la etiqueta Encargado | ☐ |
| CP02 | RF01 | Contraseña incorrecta | Ingresar `admin@tecnostock.cl` / `clave999` | No entra. Mensaje "Correo o contraseña incorrectos." | ☐ |
| CP03 | RNF07 | Acceso sin sesión | Cerrar sesión y abrir `localhost/tecnostock/productos/index.php` | Redirige al inicio de sesión con "Debe iniciar sesión para continuar." | ☐ |
| CP04 | RF12, RN09 | Menú del Vendedor | Ingresar como Vendedor | No aparece "Categorías" ni los botones de crear, editar o desactivar productos | ☐ |
| CP05 | RN09 | Vendedor entra por URL a página restringida | Como Vendedor, abrir `localhost/tecnostock/categorias/index.php` | Redirige al Inicio con "No tiene permisos para realizar esta acción." | ☐ |

## 2. Categorías

| ID | Requisito | Caso | Pasos y datos | Resultado esperado | Estado |
|---|---|---|---|---|:---:|
| CP06 | RF09 | Crear categoría | Categorías → Nueva categoría → Nombre: `Gaming` | Mensaje "Categoría "Gaming" creada correctamente." y aparece en la lista | ☐ |
| CP07 | RF09 | Nombre repetido | Crear otra categoría con nombre `Audio` | No se guarda. "Ya existe una categoría con ese nombre." | ☐ |
| CP08 | RN08 | Desactivar categoría con productos activos | Desactivar la categoría `Audio` | No se desactiva. Aviso de que tiene producto(s) activo(s) | ☐ |

## 3. Productos

| ID | Requisito | Caso | Pasos y datos | Resultado esperado | Estado |
|---|---|---|---|---|:---:|
| CP09 | RF02 | Crear producto | Nuevo producto: código `GAM-001`, nombre `Control inalámbrico`, categoría `Gaming`, precio `29990`, stock inicial `10`, mínimo `3` | Mensaje "…creado con stock inicial de 10 unidad(es)." y en Movimientos aparece una entrada "Stock inicial" de +10 | ☐ |
| CP10 | RN01 | Código repetido | Crear producto con código `AUD-001` | No se guarda. "Ya existe un producto con el código AUD-001." | ☐ |
| CP11 | RN02 | Valores negativos | Crear producto con precio `-500` | El formulario marca el campo en rojo y no deja guardar | ☐ |
| CP12 | RF04 | Búsqueda | Buscar `cable` | Muestra solo los productos cuyo nombre, código o categoría contiene "cable" | ☐ |
| CP13 | RF05 | Editar producto | Editar `Control inalámbrico` y cambiar precio a `27990` | Mensaje "…actualizado correctamente." y el precio nuevo en la lista | ☐ |
| CP14 | RF06, RN05 | Producto con movimientos | En un producto con historial (ej. `AUD-002`) | No aparece el botón Eliminar, solo Desactivar. Al desactivar: "…desactivado. Su historial de movimientos se conserva." | ☐ |

## 4. Ventas, carga de stock y alertas

| ID | Requisito | Caso | Pasos y datos | Resultado esperado | Estado |
|---|---|---|---|---|:---:|
| CP15 | RF11 | Venta normal | Registrar venta: `Cable USB-C 1m`, cantidad `2` | Antes de guardar muestra el total ($9.980). Al guardar: "Venta registrada: 2 x Cable USB-C 1m = $9.980." y el stock baja en 2 | ☐ |
| CP16 | RN04 | Venta mayor al stock | Registrar venta de `Teclado mecánico K70` (stock 6), cantidad `10` | No se registra. "Stock insuficiente: solo hay 6 unidad(es)." | ☐ |
| CP17 | RF07 | Cargar stock | Cargar stock: `Parlante portátil Mini`, cantidad `10` | "Entrada registrada: +10 unidad(es)…" y el stock sube en 10 | ☐ |
| CP18 | RF08 | Alerta de stock crítico | Registrar venta que deje un producto bajo su mínimo (ej. `Teclado mecánico K70`, cantidad `4`) | Mensaje amarillo "ATENCIÓN: quedó bajo su stock mínimo", aparece en la tabla de stock crítico del Inicio y sube el contador rojo del menú | ☐ |
| CP19 | RN07, RN09 | Vendedor intenta otro motivo | Como Vendedor, abrir Registrar venta | Solo puede elegir "Venta" o "Compra a proveedor"; no ve "Producto dañado" ni "Ajuste de inventario" | ☐ |
| CP20 | RF10 | Filtrar historial | Movimientos → Tipo: Salidas, Motivo: Venta | Solo aparecen ventas, y las tarjetas de totales se recalculan con el filtro | ☐ |

## 5. Ofertas y gráfico

| ID | Requisito | Caso | Pasos y datos | Resultado esperado | Estado |
|---|---|---|---|---|:---:|
| CP21 | RF13 | Oferta de ejemplo visible | Abrir Productos | `AUD-001` muestra $24.990 tachado, $19.990 y "Oferta · quedan 5" | ☐ |
| CP22 | RF13, RF14, RN11 | Venta que agota la oferta | Registrar venta: `Audífonos Bluetooth X200`, cantidad `7` | Total $149.930 (5 × $19.990 + 2 × $24.990). Mensaje "…5 unidad(es) con oferta a $19.990. La oferta terminó: el producto vuelve a su precio normal ($24.990)." En Ofertas queda "Finalizada 5 de 5" | ☐ |
| CP23 | RN10 | Precio de oferta mayor al normal | Nueva oferta: `Cable USB-C 1m` (normal $4.990), precio `5990` | No se guarda. "El precio de oferta debe ser menor al precio normal…" | ☐ |
| CP24 | RN10 | Cantidad de oferta mayor al stock | Nueva oferta: `Teclado mecánico K70`, cantidad `50` | No se guarda. "La cantidad no puede superar el stock actual…" | ☐ |
| CP25 | RN10 | Dos ofertas activas | Crear una oferta para un producto y luego otra para el mismo | La segunda no se guarda. "Este producto ya tiene una oferta activa. Cancélela antes de crear otra." | ☐ |
| CP26 | RF13 | Cancelar oferta | Ofertas → Cancelar en una oferta activa | "Oferta cancelada. El producto vuelve a su precio normal." y queda con estado Cancelada | ☐ |
| CP27 | RF15 | Gráfico de ventas | Abrir Inicio | El gráfico muestra los últimos 7 días con la barra de Hoy destacada; al pasar el mouse muestra el total en pesos y las unidades | ☐ |

## Resumen

| Total de casos | Aprobados | Fallidos |
|:---:|:---:|:---:|
| 27 | – | – |