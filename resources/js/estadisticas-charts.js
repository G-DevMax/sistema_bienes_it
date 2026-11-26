const FALLBACK_PALETTE = [
    '#60a5fa',
    '#1CD6CE',
    '#a855f7',
    '#FEDB39',
    '#4ade80',
    '#f472b6',
    '#fb923c',
    '#818cf8',
];

function initFiltroMes() {
    const inputFiltroMes = document.getElementById('input-filtro-mes');
    const btnFiltroMes = document.getElementById('btn-filtro-mes');

    if (!inputFiltroMes) {
        return true;
    }

    const ahora = new Date();
    const anioMinimo = Number(inputFiltroMes.dataset.anioMinimo || 2026);
    const anioMaximo = Math.max(ahora.getFullYear(), Number(inputFiltroMes.dataset.anioMaximo || ahora.getFullYear()));
    const mesServidor = inputFiltroMes.dataset.mesServidor || '';
    const baseUrl = inputFiltroMes.dataset.baseUrl || window.location.pathname;

    inputFiltroMes.max = `${anioMaximo}-12`;

    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('mes') && ahora.getFullYear() >= anioMinimo) {
        const mesDispositivo = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}`;
        if (mesServidor && mesDispositivo !== mesServidor) {
            urlParams.set('mes', mesDispositivo);
            window.location.replace(`${window.location.pathname}?${urlParams.toString()}`);
            return false;
        }
    }

    const abrirPicker = () => {
        if (inputFiltroMes.showPicker) {
            try {
                inputFiltroMes.showPicker();
            } catch (_) { }
        }
    };

    if (btnFiltroMes) {
        btnFiltroMes.addEventListener('click', abrirPicker);
    }

    inputFiltroMes.addEventListener('click', abrirPicker);

    inputFiltroMes.addEventListener('change', () => {
        const val = inputFiltroMes.value;
        if (!val) {
            return;
        }
        if (inputFiltroMes.min && val < inputFiltroMes.min) {
            return;
        }
        if (inputFiltroMes.max && val > inputFiltroMes.max) {
            return;
        }
        const sep = baseUrl.includes('?') ? '&' : '?';
        window.location.href = `${baseUrl}${sep}mes=${encodeURIComponent(val)}`;
    });

    return true;
}

function buildChartConfig(chartType, chartLabel, labels, valores, colores, totalMes) {
    const esCircular = chartType === 'pie' || chartType === 'doughnut';
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        devicePixelRatio: Math.min(window.devicePixelRatio || 1, 2),
        animation: { duration: 500 },
    };

    if (esCircular) {
        return {
            type: chartType,
            data: {
                labels,
                datasets: [{
                    label: chartLabel,
                    data: valores,
                    backgroundColor: colores.map((hex) => `${hex}cc`),
                    borderColor: 'rgba(15, 23, 42, 0.8)',
                    borderWidth: 2,
                }],
            },
            options: {
                ...baseOptions,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(255, 255, 255, 0.85)',
                            usePointStyle: true,
                            padding: 16,
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const valor = Number(ctx.parsed ?? 0);
                                const pct = totalMes > 0 ? ((valor / totalMes) * 100).toFixed(1) : '0.0';
                                return `${ctx.label}: ${valor} (${pct}%)`;
                            },
                        },
                    },
                },
            },
        };
    }

    return {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: chartLabel,
                data: valores,
                backgroundColor: colores.map((hex) => `${hex}73`),
                borderColor: colores,
                borderWidth: 1.5,
                borderRadius: 6,
            }],
        },
        options: {
            ...baseOptions,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false,
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: 'rgba(255, 255, 255, 0.75)',
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.08)',
                    },
                },
                y: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.9)',
                    },
                    grid: {
                        display: false,
                    },
                },
            },
        },
    };
}

export function initEstadisticasPage() {
    if (!initFiltroMes()) {
        return;
    }

    const canvas = document.getElementById('grafico-estadisticas');
    const contenedorGrafico = document.getElementById('contenedor-grafico');
    const mensajeSinDatos = document.getElementById('mensaje-sin-datos');

    if (!canvas || typeof window.Chart === 'undefined' || !canvas.dataset.chartData) {
        return;
    }

    const chartType = canvas.dataset.chartType || 'bar';
    const chartLabel = canvas.dataset.chartLabel || 'Total';

    try {
        const datos = JSON.parse(canvas.dataset.chartData);
        const labels = Array.isArray(datos.labels) ? datos.labels : [];
        const valores = Array.isArray(datos.valores) ? datos.valores : [];
        const totalMes = Number(datos.total_mes ?? 0);

        if (!labels.length || totalMes === 0) {
            contenedorGrafico?.classList.add('hidden');
            mensajeSinDatos?.classList.remove('hidden');
            return;
        }

        contenedorGrafico?.classList.remove('hidden');
        mensajeSinDatos?.classList.add('hidden');

        const colores = Array.isArray(datos.colores) && datos.colores.length === labels.length
            ? datos.colores
            : labels.map((_, idx) => FALLBACK_PALETTE[idx % FALLBACK_PALETTE.length]);

        const config = buildChartConfig(chartType, chartLabel, labels, valores, colores, totalMes);
        new window.Chart(canvas, config);
    } catch (_) { }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEstadisticasPage);
} else {
    initEstadisticasPage();
}
