//   AXION PRO - ACESSIBILIDADE E ZOOM
let nivelZoomAtual = 100; // Valor percentual inicial

function mudarZoom(direcao) {
    if (direcao === 'mais' && nivelZoomAtual < 150) {
        nivelZoomAtual += 10; // Aumenta 10% por clique
    } else if (direcao === 'menos' && nivelZoomAtual > 90) {
        nivelZoomAtual -= 10; // Diminui 10% por clique
    } else if (direcao === 'reset') {
        nivelZoomAtual = 100; // Volta ao padrão
    }

    aplicarZoom();
}

function aplicarZoom() {
    document.documentElement.style.fontSize = nivelZoomAtual + '%';
    localStorage.setItem('axion_zoom', nivelZoomAtual);
}

function toggleAltoContraste() {
    document.body.classList.toggle('accessibility-high-contrast');
    const ativo = document.body.classList.contains('accessibility-high-contrast');
    localStorage.setItem('axion_contraste', ativo ? '1' : '0');
}

// Restaura as preferências guardadas ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Restaura Contraste
    if (localStorage.getItem('axion_contraste') === '1') {
        document.body.classList.add('accessibility-high-contrast');
    }
    
    // Restaura Zoom
    const zoomSalvo = localStorage.getItem('axion_zoom');
    if (zoomSalvo) {
        nivelZoomAtual = parseInt(zoomSalvo, 10);
        document.documentElement.style.fontSize = nivelZoomAtual + '%';
    }
});