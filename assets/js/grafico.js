document.addEventListener('DOMContentLoaded', function () {
    const lienzo = document.getElementById('grafico-ventas');
    if (!lienzo || typeof Chart === 'undefined') {
        return;
    }

    const datos = JSON.parse(lienzo.dataset.ventas);
    const pesos = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' });

    new Chart(lienzo, {
        type: 'bar',
        data: {
            labels: datos.map(function (d) { return d.etiqueta; }),
            datasets: [{
                label: 'Ventas',
                data: datos.map(function (d) { return d.total; }),
                backgroundColor: datos.map(function (d, i) {
                    return i === datos.length - 1 ? '#4f46e5' : 'rgba(79, 70, 229, 0.35)';
                }),
                borderRadius: 8,
                maxBarThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const d = datos[ctx.dataIndex];
                            return pesos.format(d.total) + ' · ' + d.unidades + ' unidad(es)';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (valor) { return pesos.format(valor); }
                    },
                    grid: { color: '#eef0f4' }
                }
            }
        }
    });
});