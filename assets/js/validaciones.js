document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            form.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(function (campo) {
                if (!campo.readOnly) {
                    campo.value = campo.value.trim();
                }
            });

            if (!form.checkValidity()) {
                evento.preventDefault();
                evento.stopPropagation();
                const primero = form.querySelector(':invalid');
                if (primero) {
                    primero.focus();
                }
            }
            form.classList.add('was-validated');
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            if (!confirm(form.dataset.confirm)) {
                evento.preventDefault();
            }
        });
    });

    document.querySelectorAll('input[data-mayusculas]').forEach(function (campo) {
        campo.addEventListener('input', function () {
            campo.value = campo.value.toUpperCase();
        });
    });

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

    const verClave = document.getElementById('ver-clave');
    if (verClave) {
        verClave.addEventListener('click', function () {
            const clave = document.getElementById('clave');
            const oculta = clave.type === 'password';
            clave.type = oculta ? 'text' : 'password';
            verClave.innerHTML = oculta ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    }

    document.querySelectorAll('.demo-cuentas [data-correo]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            document.getElementById('correo').value = boton.dataset.correo;
            document.getElementById('clave').value = boton.dataset.clave;
        });
    });

    const formOferta = document.getElementById('form-oferta');
    if (formOferta) {
        const selProd   = document.getElementById('id_producto');
        const inpPrecio = document.getElementById('precio_oferta');
        const inpCant   = document.getElementById('cantidad_total');
        const resumenOf = document.getElementById('resumen-oferta');
        const errPrecio = document.getElementById('error-precio-oferta');
        const errCant   = document.getElementById('error-cantidad-oferta');
        const clp       = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' });

        function revisarOferta() {
            const opcion = selProd.selectedOptions[0];
            const precioOferta = parseInt(inpPrecio.value, 10);
            const cantidad = parseInt(inpCant.value, 10);
            inpPrecio.setCustomValidity('');
            inpCant.setCustomValidity('');
            inpPrecio.classList.remove('is-invalid');
            inpCant.classList.remove('is-invalid');
            resumenOf.classList.add('d-none');

            if (!opcion || !opcion.value) {
                return;
            }
            const precio = parseInt(opcion.dataset.precio, 10);
            const stock = parseInt(opcion.dataset.stock, 10);
            inpCant.max = stock;

            if (!isNaN(precioOferta) && precioOferta >= precio) {
                const msg = 'Debe ser menor al precio normal (' + clp.format(precio) + ').';
                inpPrecio.setCustomValidity(msg);
                inpPrecio.classList.add('is-invalid');
                errPrecio.textContent = msg;
                return;
            }
            if (!isNaN(cantidad) && cantidad > stock) {
                const msg = 'No puede superar el stock actual (' + stock + ' unidades).';
                inpCant.setCustomValidity(msg);
                inpCant.classList.add('is-invalid');
                errCant.textContent = msg;
                return;
            }
            if (!isNaN(precioOferta) && precioOferta > 0 && !isNaN(cantidad) && cantidad > 0) {
                const descuento = Math.round(100 - precioOferta * 100 / precio);
                resumenOf.textContent = clp.format(precio) + ' → ' + clp.format(precioOferta)
                    + ' (' + descuento + '% de descuento) para las próximas ' + cantidad
                    + ' unidad(es). Ahorro por unidad: ' + clp.format(precio - precioOferta) + '.';
                resumenOf.classList.remove('d-none');
            }
        }

        selProd.addEventListener('change', revisarOferta);
        inpPrecio.addEventListener('input', revisarOferta);
        inpCant.addEventListener('input', revisarOferta);
        revisarOferta();
    }

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
            if (parseInt(opcion.dataset.ofertaPrecio, 10) > 0) {
                info.textContent += ' · Oferta: ' + pesos.format(parseInt(opcion.dataset.ofertaPrecio, 10))
                                  + ' (quedan ' + opcion.dataset.ofertaRestante + ')';
            }
            if (stock < minimo) {
                info.textContent += ' · ⚠ Stock crítico';
                info.className = 'form-text text-danger fw-semibold';
            }

            if (isNaN(cantidad) || cantidad <= 0) {
                return;
            }

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
                const precioOferta = parseInt(opcion.dataset.ofertaPrecio, 10) || 0;
                const restante = parseInt(opcion.dataset.ofertaRestante, 10) || 0;
                const enOferta = precioOferta > 0 ? Math.min(cantidad, restante) : 0;
                const total = enOferta * precioOferta + (cantidad - enOferta) * precio;
                texto = 'Total de la venta: ' + pesos.format(total) + '. ';
                if (enOferta > 0) {
                    texto += enOferta + ' con oferta a ' + pesos.format(precioOferta);
                    if (cantidad > enOferta) {
                        texto += ' y ' + (cantidad - enOferta) + ' a precio normal';
                    }
                    texto += '. ';
                }
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