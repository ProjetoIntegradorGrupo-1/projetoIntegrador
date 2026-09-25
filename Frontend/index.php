
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="css/axion-pro.css">

<!-- CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/axion-pro.css">
    <style>
        /* Bakgrong piesa lek na di picha[cite: 1] */
        body {
            background-image: url('https://www.kingcar.com.br/uploads/fotosdaloja/img_0316-1jpgknrq3.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
        }

        /* Glasfoshon (Glassmorphism) Efek */
        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            color: #ffffff;
        }

        .glass-card .form-control {
            background: rgba(255, 255, 255, 0.7);
            border: none;
        }

        .glass-card .form-control:focus {
            background: rgba(255, 255, 255, 0.9);
        }

        .glass-card a {
            color: #ffffff !important;
            text-decoration: underline !important;
        }
    </style>
</head>
<body>

    <div class="container d-flex justify-content-center align-items-center vh-100">

        <div class="glass-card p-4" style="max-width: 420px; width: 100%;">

            <!-- LOGOTIPO / IDENTIDADE VISUAL -->
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm mb-2" style="width: 55px; height: 55px; font-size: 1.4rem;">
                    
            </div>

            <hr class="border-light mb-4">

            <form action="../backend/auth/processar_login.php" method="POST">

                <div class="mb-3">
                    <label for="user" class="form-label">
                        Usuário
                    </label>

                    <input
                        type="text"
                        name="txtuser"
                        id="user"
                        class="form-control"
                        placeholder="Digite seu e-mail ou CPF"
                        required
                        autofocus
                    >
                </div>

                <div class="mb-3">
                    <label for="senha" class="form-label">
                        Senha
                    </label>

                    <input
                        type="password"
                        name="txtsenha"
                        id="senha"
                        class="form-control"
                        placeholder="Digite sua senha"
                        required
                    >
                </div>

                <div class="mb-3 text-end">
                    <a
                        href="esqueciSenha.html"
                        class="text-decoration-none small"
                    >
                        Esqueci minha senha
                    </a>
                </div>

                <div class="d-grid gap-2">

                    <input
                        type="submit"
                        value="Entrar"
                        class="btn btn-primary"
                    >

                </div>

            </form>

        </div>

    </div>

   <!-- BARRA DE ACESSIBILIDADE FLUTUANTE -->
    <div class="position-fixed bottom-0 start-0 p-3" style="z-index: 1050;">
        <div class="bg-white p-2 rounded-pill shadow-sm border d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" onclick="toggleAltoContraste()" title="Ativar Alto Contraste">
                🌓 Contraste
            </button>
            <div class="vr"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle fw-bold" onclick="mudarZoom('menos')" title="Diminuir Letra">
                A-
            </button>
            <button type="button" class="btn btn-sm btn-light rounded-pill px-2 text-muted small" onclick="mudarZoom('reset')" title="Restaurar Padrão">
                Zoom
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle fw-bold" onclick="mudarZoom('mais')" title="Aumentar Letra">
                A+
            </button>
        </div>
    </div>

    <!-- Scripts essenciais no final -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/axion-accessibility.js"></script>

</body>

</html>