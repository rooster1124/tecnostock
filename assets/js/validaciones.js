/**
 * Validaciones en el navegador (cliente).
 * Avisan al usuario al instante, ANTES de enviar el formulario.
 * IMPORTANTE: PHP vuelve a validar todo en el servidor, porque esta parte
 * se puede desactivar desde el navegador.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ------------------------------------------------------------------
    // 1. Formularios con la clase "needs-validation" (estilo Bootstrap)
    //    Si hay campos inválidos, no se envía y se marcan en rojo.
    // ------------------------------------------------------------------
    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            // Quitar espacios al inicio y al final de los campos de texto
            form.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(function (campo) {
                if (!campo.readOnly) {
                    campo.value = campo.value.trim();
                }
            });

            if (!form.checkValidity()) {
                evento.preventDefault();
                evento.stopPropagation();
                // Lleva el foco al primer campo con error
                const primero = form.querySelector(':invalid');
                if (primero) {
                    primero.focus();
                }
            }
            form.classList.add('was-validated');
        });
    });

    // ------------------------------------------------------------------
    // 2. Confirmación antes de acciones importantes (desactivar, eliminar)
    // ------------------------------------------------------------------
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            if (!confirm(form.dataset.confirm)) {
                evento.preventDefault();
            }
        });
    });

    // ------------------------------------------------------------------
    // 3. Código de producto en mayúsculas mientras se escribe
    // ------------------------------------------------------------------
    document.querySelectorAll('input[data-mayusculas]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = campo.value.toUpperCase();
        });
    });

    // ------------------------------------------------------------------
    // 4. No permitir números negativos ni decimales en campos numéricos
    // ------------------------------------------------------------------
    document.querySelectorAll('input[type="number"]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            const valor = campo.value;
            if (valor !== '' && (!/^\d+$/.test(valor))) {
                campo.setCustomValidity('Ingrese un número entero sin signo negativo.');
            } else {
                campo.setCustomValidity('');
            }
        });
    });

    // ------------------------------------------------------------------
    // 5. Formulario de movimientos: motivos según el tipo, stock disponible
    //    y total de la venta en tiempo real
    // ------------------------------------------------------------------
    const formMov = document.getElementById('form-movimiento');
    if (formMov) {
        const motivos      = JSON.parse(formMov.dataset.motivos);
        const selMotivo    = document.getElementById('motivo');
        const selProducto  = document.getElementById('id_producto');
        const inpCantidad  = document.getElementById('cantidad');
        const info         = document.getElementById('info-producto');
        const resumen      = document.getElementById('resumen-venta');
        const errorCant    = document.getElementById('error-cantidad');
        const titulo       = document.getElementById('titulo-movimiento');
        const pesos        = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' });

        function tipoActual() {
            const marcado = formMov.querySelector('input[name="tipo"]:checked');
            return marcado ? marcado.value : '';
        }

        // Cambia la lista de motivos cuando se cambia entre Entrada y Salida
        function cargarMotivos() {
            const tipo = tipoActual();
            const anterior = selMotivo.value;
            selMotivo.innerHTML = '<option value="">Seleccione…</option>';
            (motivos[tipo] || []).forEach(function (m) {
                const op = document.createElement('option');
                op.value = m;
                op.textContent = m;
                if (m === anterior) {
                    op.selected = true;
                }
                selMotivo.appendChild(op);
            });
        }

        // Muestra el stock, valida la cantidad y calcula el total
        function actualizar() {
            const opcion   = selProducto.selectedOptions[0];
            const tipo     = tipoActual();
            const cantidad = parseInt(inpCantidad.value, 10);
            const esVenta  = tipo === 'SALIDA' && selMotivo.value === 'Venta';

            titulo.textContent = esVenta ? 'Registrar venta' : 'Registrar movimiento';
            inpCantidad.setCustomValidity('');
            inpCantidad.classList.remove('is-invalid');
            resumen.classList.add('d-none');
            info.textContent = '';
            info.className = 'form-text';

            if (!opcion || !opcion.value) {
                return;
            }

            const stock  = parseInt(opcion.dataset.stock, 10);
            const minimo = parseInt(opcion.dataset.minimo, 10);
            const precio = parseInt(opcion.dataset.precio, 10);

            info.textContent = 'Stock disponible: ' + stock + ' · Stock mínimo: ' + minimo
                             + ' · Precio: ' + pesos.format(precio);
            if (stock < minimo) {
                info.textContent += ' · ⚠ Stock crítico';
                info.className = 'form-text text-danger fw-semibold';
            }

            if (isNaN(cantidad) || cantidad <= 0) {
                return;
            }

            // RN04: una salida no puede dejar el stock bajo cero
            if (tipo === 'SALIDA' && cantidad > stock) {
                const msg = 'Stock insuficiente: solo hay ' + stock + ' unidad(es).';
                inpCantidad.setCustomValidity(msg);
                inpCantidad.classList.add('is-invalid');
                errorCant.textContent = msg;
                return;
            }
            errorCant.textContent = 'Ingrese una cantidad mayor que 0.';

            const nuevoStock = tipo === 'SALIDA' ? stock - cantidad : stock + cantidad;
            let texto = '';
            if (esVenta) {
                texto = 'Total de la venta: ' + pesos.format(cantidad * precio) + '. ';
            }
            texto += 'Stock después del movimiento: ' + nuevoStock + '.';
            if (nuevoStock < minimo) {
                texto += ' ⚠ Quedará bajo el stock mínimo (' + minimo + ').';
                resumen.className = 'alert alert-warning';
            } else {
                resumen.className = 'alert alert-success';
            }
            resumen.textContent = texto;
        }

        formMov.querySelectorAll('input[name="tipo"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                cargarMotivos();
                actualizar();
            });
        });
        selMotivo.addEventListener('change', actualizar);
        selProducto.addEventListener('change', actualizar);
        inpCantidad.addEventListener('input', actualizar);

        actualizar();
    }
});