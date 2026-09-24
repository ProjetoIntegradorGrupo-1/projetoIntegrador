function inicializarDashboard(totalInspecoes, totalPendentes, ocorrenciasAbertas, totalVeiculos) {
    // 1. Gráfico de Barras (Vistorias e Indicadores)
    const ctxBarras = document.getElementById('graficoBarras').getContext('2d');
    new Chart(ctxBarras, {
        type: 'bar',
        data: {
            labels: ['Total Vistorias', 'Pendentes', 'Ocorrências', 'Frota Ativa'],
            datasets: [{
                label: 'Métricas da Frota',
                data: [totalInspecoes, totalPendentes, ocorrenciasAbertas, totalVeiculos],
                backgroundColor: [
                    'rgba(13, 110, 253, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(25, 135, 84, 0.8)'
                ],
                borderRadius: 6,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { stepSize: 1 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Gráfico de Rosca (Distribuição de Ocorrências / Estado)
    const ctxRosca = document.getElementById('graficoRosca').getContext('2d');
    new Chart(ctxRosca, {
        type: 'doughnut',
        data: {
            labels: ['Vistorias Realizadas', 'Ocorrências Abertas'],
            datasets: [{
                data: [totalInspecoes, ocorrenciasAbertas],
                backgroundColor: [
                    '#0d6efd',
                    '#dc3545'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 12 } }
                }
            },
            cutout: '70%'
        }
    });
}