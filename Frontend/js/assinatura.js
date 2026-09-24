function configurarCanvas(idCanvas) {
    const canvas = document.getElementById(idCanvas);
    const ctx = canvas.getContext('2d');
    let desenhando = false;

    // Ajusta a resolução do canvas conforme a largura real da tela
    function redimensionarCanvas() {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = 160;
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000000';
    }
    redimensionarCanvas();

    function obterPosicao(e) {
        const rect = canvas.getBoundingClientRect();
        const clienteX = e.touches ? e.touches[0].clientX : e.clientX;
        const clienteY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: clienteX - rect.left,
            y: clienteY - rect.top
        };
    }

    function iniciarDesenho(e) {
        desenhando = true;
        const pos = obterPosicao(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    }

    function desenhar(e) {
        if (!desenhando) return;
        const pos = obterPosicao(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    }

    function pararDesenho() {
        desenhando = false;
    }

    // Eventos de Mouse
    canvas.addEventListener('mousedown', iniciarDesenho);
    canvas.addEventListener('mousemove', desenhar);
    canvas.addEventListener('mouseup', pararDesenho);
    canvas.addEventListener('mouseleave', pararDesenho);

    // Eventos de Toque (Celular/Tablet)
    canvas.addEventListener('touchstart', iniciarDesenho);
    canvas.addEventListener('touchmove', desenhar);
    canvas.addEventListener('touchend', pararDesenho);
}

function limparCanvas(idCanvas) {
    const canvas = document.getElementById(idCanvas);
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function canvasEstaVazio(idCanvas) {
    const canvas = document.getElementById(idCanvas);
    const ctx = canvas.getContext('2d');
    const pixelData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    return !pixelData.some(channel => channel !== 0);
}

function finalizarChecklist() {
    const alertaErro = document.getElementById('alertaErro');
    const alertaSucesso = document.getElementById('alertaSucesso');

    // Oculta alertas prévios
    alertaErro.classList.add('d-none');
    alertaSucesso.classList.add('d-none');

    // Verifica se os dois quadros foram assinados
    if (canvasEstaVazio('canvasMotorista') || canvasEstaVazio('canvasVistoriador')) {
        alertaErro.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // Converte os desenhos dos quadros para texto base64
    document.getElementById('inputAssinaturaMotorista').value = document.getElementById('canvasMotorista').toDataURL();
    document.getElementById('inputAssinaturaVistoriador').value = document.getElementById('canvasVistoriador').toDataURL();

    // Exibe mensagem de sucesso e redireciona após 2 segundos
    alertaSucesso.classList.remove('d-none');
    window.scrollTo({ top: 0, behavior: 'smooth' });

    setTimeout(function () {
        // Envia o formulário para o backend
        document.getElementById('formAssinaturas').submit();
    }, 2000);
}

// Inicializa a captura de ambos os quadros de assinatura ao carregar a tela
document.addEventListener('DOMContentLoaded', function () {
    configurarCanvas('canvasMotorista');
    configurarCanvas('canvasVistoriador');
});