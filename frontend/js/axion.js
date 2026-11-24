/**
 * frontend/js/axion.js
 * Máscaras de conferência, validação matemática de CPF, busca de CEP (API gratuita ViaCEP/BrasilAPI)
 * e carregamento dinâmico de dados para formulários do Axion.
 */

const AxionUtils = {
    // Formata CPF: 000.000.000-00
    mascaraCPF(valor) {
        return valor
            .replace(/\D/g, '')
            .slice(0, 11)
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    },

    // Validação matemática oficial de CPF (Módulo 11)
    validarCPF(cpf) {
        const limpo = (cpf || '').replace(/\D/g, '');
        if (limpo.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(limpo)) return false;

        let soma = 0;
        for (let i = 0; i < 9; i++) {
            soma += parseInt(limpo.charAt(i), 10) * (10 - i);
        }
        let resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(limpo.charAt(9), 10)) return false;

        soma = 0;
        for (let i = 0; i < 10; i++) {
            soma += parseInt(limpo.charAt(i), 10) * (11 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        return resto === parseInt(limpo.charAt(10), 10);
    },

    // Formata CEP: 00000-000
    mascaraCEP(valor) {
        return valor
            .replace(/\D/g, '')
            .slice(0, 8)
            .replace(/(\d{5})(\d)/, '$1-$2');
    },

    // Formata Telefone: (00) 00000-0000 ou (00) 0000-0000
    mascaraTelefone(valor) {
        const d = valor.replace(/\D/g, '').slice(0, 11);
        if (d.length <= 10) {
            return d
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        }
        return d
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d)/, '$1-$2');
    },

    // Formata CNH (11 dígitos numéricos)
    mascaraCNH(valor) {
        return valor.replace(/\D/g, '').slice(0, 11);
    },

    // Formata Renavam (11 dígitos numéricos)
    mascaraRenavam(valor) {
        return valor.replace(/\D/g, '').slice(0, 11);
    },

    // Formata Placa (Padrão Mercosul ABC1D23 ou Antigo ABC-1234 -> normaliza para 7 caracteres sem hífen ou com hífen visual)
    mascaraPlaca(valor) {
        return valor
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '')
            .slice(0, 7);
    },

    validarPlaca(placa) {
        const limpa = (placa || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        return /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/.test(limpa);
    },

    // Formata Chassi (17 caracteres alfanuméricos, sem I, O, Q)
    mascaraChassi(valor) {
        return valor
            .toUpperCase()
            .replace(/[IOQ]/g, '')
            .replace(/[^A-Z0-9]/g, '')
            .slice(0, 17);
    },

    // Busca de CEP gratuita via ViaCEP com fallback para BrasilAPI
    async buscarCEP(cepInput) {
        const cepLimpo = (cepInput.value || '').replace(/\D/g, '');
        const feedbackEl = document.getElementById('cep-feedback');

        if (cepLimpo.length !== 8) {
            if (feedbackEl) {
                feedbackEl.textContent = 'Digite os 8 dígitos do CEP para buscar.';
                feedbackEl.className = 'form-text text-warning';
            }
            return;
        }

        const campoLogradouro = document.getElementById('logradouro');
        const campoBairro = document.getElementById('bairro');
        const campoCidade = document.getElementById('cidade');
        const campoEstado = document.getElementById('estado');
        const campoNumero = document.getElementById('numero');

        if (feedbackEl) {
            feedbackEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Consultando CEP na base gratuita...';
            feedbackEl.className = 'form-text text-primary fw-semibold';
        }

        try {
            let dados = null;
            const resp = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
            if (resp.ok) {
                const json = await resp.json();
                if (!json.erro) {
                    dados = {
                        logradouro: json.logradouro || '',
                        bairro: json.bairro || '',
                        cidade: json.localidade || '',
                        estado: json.uf || ''
                    };
                }
            }

            // Fallback gratuito para BrasilAPI caso ViaCEP falhe
            if (!dados) {
                const resp2 = await fetch(`https://brasilapi.com.br/api/cep/v1/${cepLimpo}`);
                if (resp2.ok) {
                    const json2 = await resp2.json();
                    dados = {
                        logradouro: json2.street || '',
                        bairro: json2.neighborhood || '',
                        cidade: json2.city || '',
                        estado: json2.state || ''
                    };
                }
            }

            if (dados) {
                if (campoLogradouro && dados.logradouro) campoLogradouro.value = dados.logradouro;
                if (campoBairro && dados.bairro) campoBairro.value = dados.bairro;
                if (campoCidade && dados.cidade) campoCidade.value = dados.cidade;
                if (campoEstado && dados.estado) campoEstado.value = dados.estado;

                cepInput.classList.remove('is-invalid');
                cepInput.classList.add('is-valid');
                if (feedbackEl) {
                    feedbackEl.textContent = `Endereço preenchido: ${dados.cidade}/${dados.estado}`;
                    feedbackEl.className = 'form-text text-success fw-semibold';
                }
                if (campoNumero && !campoNumero.value) {
                    campoNumero.focus();
                }
            } else {
                cepInput.classList.remove('is-valid');
                cepInput.classList.add('is-invalid');
                if (feedbackEl) {
                    feedbackEl.textContent = 'CEP não encontrado. Preencha o endereço manualmente.';
                    feedbackEl.className = 'form-text text-danger fw-semibold';
                }
            }
        } catch (err) {
            if (feedbackEl) {
                feedbackEl.textContent = 'Não foi possível consultar a API de CEP no momento. Preencha manualmente.';
                feedbackEl.className = 'form-text text-muted';
            }
        }
    },

    inicializarMascarasEValidacoes() {
        // 1. Campo CPF
        document.querySelectorAll('#cpf, [data-mask="cpf"]').forEach((input) => {
            let msgEl = input.parentElement.querySelector('.cpf-feedback');
            if (!msgEl) {
                msgEl = document.createElement('div');
                msgEl.className = 'cpf-feedback small mt-1';
                input.parentElement.appendChild(msgEl);
            }

            const atualizarStatusCPF = () => {
                input.value = AxionUtils.mascaraCPF(input.value);
                const digitos = input.value.replace(/\D/g, '');
                if (digitos.length === 0) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                } else if (digitos.length < 11) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `Digite os 11 dígitos do CPF (${digitos.length}/11)`;
                    msgEl.className = 'cpf-feedback small mt-1 text-warning-emphasis';
                    input.setCustomValidity('CPF incompleto.');
                } else if (AxionUtils.validarCPF(input.value)) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ CPF válido (dígitos verificadores conferidos)';
                    msgEl.className = 'cpf-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = '✗ CPF inválido (dígitos verificadores incorretos)';
                    msgEl.className = 'cpf-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('CPF inválido. Verifique os números digitados.');
                }
            };

            input.addEventListener('input', atualizarStatusCPF);
            input.addEventListener('blur', atualizarStatusCPF);
            if (input.value) atualizarStatusCPF();
        });

        // 2. Campo CEP + Busca automática ViaCEP
        document.querySelectorAll('#cep, [data-mask="cep"]').forEach((input) => {
            let ultimoCepConsultado = '';
            input.addEventListener('input', () => {
                input.value = AxionUtils.mascaraCEP(input.value);
                const limpo = input.value.replace(/\D/g, '');
                if (limpo.length === 8 && limpo !== ultimoCepConsultado) {
                    ultimoCepConsultado = limpo;
                    AxionUtils.buscarCEP(input);
                }
            });
            input.addEventListener('blur', () => {
                const limpo = input.value.replace(/\D/g, '');
                if (limpo.length === 8 && limpo !== ultimoCepConsultado) {
                    ultimoCepConsultado = limpo;
                    AxionUtils.buscarCEP(input);
                }
            });
        });

        const btnBuscarCep = document.getElementById('btn-buscar-cep');
        if (btnBuscarCep) {
            btnBuscarCep.addEventListener('click', () => {
                const cepInput = document.getElementById('cep');
                if (cepInput) AxionUtils.buscarCEP(cepInput);
            });
        }

        // 3. Campo Telefone
        document.querySelectorAll('#telefone, [data-mask="telefone"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = AxionUtils.mascaraTelefone(input.value);
            });
            if (input.value) input.value = AxionUtils.mascaraTelefone(input.value);
        });

        // 4. Campo CNH
        document.querySelectorAll('#cnh, [data-mask="cnh"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = AxionUtils.mascaraCNH(input.value);
            });
        });

        // 5. Campo Renavam
        document.querySelectorAll('#renavam, [data-mask="renavam"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = AxionUtils.mascaraRenavam(input.value);
            });
        });

        // 6. Campo Placa
        document.querySelectorAll('#placa, #placadoveiculo, [data-mask="placa"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = AxionUtils.mascaraPlaca(input.value);
                if (input.value.length === 7) {
                    if (AxionUtils.validarPlaca(input.value)) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                        input.setCustomValidity('');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                        input.setCustomValidity('Formato de placa inválido (Use ABC1D23 ou ABC1234).');
                    }
                } else {
                    input.classList.remove('is-valid', 'is-invalid');
                    input.setCustomValidity('');
                }
            });
        });

        // 7. Campo Chassi
        document.querySelectorAll('#chassi, [data-mask="chassi"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = AxionUtils.mascaraChassi(input.value);
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    AxionUtils.inicializarMascarasEValidacoes();
});

/** Escapa texto vindo do banco antes de montar HTML (evita que um nome com <script> rode na página). */
function axionEsc(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/** Foto de perfil redonda; sem foto, mostra as iniciais do nome. */
function axionAvatar(fotoUrl, nome, tamanho = 36) {
    const estilo = `width:${tamanho}px;height:${tamanho}px;font-size:${Math.round(tamanho * 0.4)}px`;
    if (fotoUrl) return `<img src="${axionEsc(fotoUrl)}" alt="" class="axion-avatar" style="${estilo}">`;
    const partes = String(nome || '?').trim().split(/\s+/);
    const iniciais = ((partes[0] || '')[0] || '?') + (partes.length > 1 ? partes[partes.length - 1][0] : '');
    return `<span class="axion-avatar axion-avatar-iniciais" style="${estilo}">${axionEsc(iniciais.toUpperCase())}</span>`;
}

/**
 * Sessão do usuário logado: carrega quem está logado, coloca o token de segurança (csrf) em todos
 * os formulários POST da página e manda para o login se a sessão tiver expirado.
 * Uso: const ctx = await AxionSessao.pronta;
 */
const AxionSessao = {
    pronta: (async () => {
        try {
            const resp = await fetch('backend/api.php?acao=contexto', { headers: { Accept: 'application/json' } });
            if (resp.status === 401) {
                window.location.href = 'index.html';
                return null;
            }
            const ctx = await resp.json();
            const injetar = () => document.querySelectorAll('form[method="post"], form[method="POST"]').forEach((f) => {
                let campo = f.querySelector('input[name="csrf"]');
                if (!campo) {
                    campo = document.createElement('input');
                    campo.type = 'hidden';
                    campo.name = 'csrf';
                    f.appendChild(campo);
                }
                campo.value = ctx.csrf;
            });
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', injetar);
            else injetar();
            return ctx;
        } catch (e) {
            console.error('Falha ao carregar a sessão', e);
            return null;
        }
    })()
};

/** Mostra a mensagem de erro que o servidor devolveu na URL (?erro=...). */
document.addEventListener('DOMContentLoaded', () => {
    const erro = new URLSearchParams(window.location.search).get('erro');
    if (!erro || document.body.dataset.semAlertaErro !== undefined) return;
    const textos = {
        usuario_nao_selecionado: 'Selecione um usuário na lista antes de salvar.',
        veiculo_nao_selecionado: 'Selecione um veículo na lista antes de continuar.',
        checklist_nao_selecionado: 'Selecione um checklist na lista antes de continuar.',
        sem_permissao: 'Seu nível de acesso não permite esta operação.',
        nao_pode_mudar_proprio_perfil: 'Você não pode alterar o seu próprio nível de acesso.',
        nao_pode_excluir_a_si_mesmo: 'Você não pode excluir a sua própria conta.',
        cpf_invalido: 'CPF inválido.',
        email_invalido: 'E-mail inválido.',
        cpf_ou_email_duplicado: 'Já existe outro usuário com este CPF ou e-mail.',
        placa_renavam_ou_chassi_duplicado: 'Já existe outro veículo com esta placa, Renavam ou chassi.',
        supervisor_invalido: 'Supervisor responsável inválido.',
        checklist_invalido: 'Escolha um modelo de checklist.',
        veiculo_invalido: 'Escolha um veículo da frota.',
        km_invalido: 'Informe a quilometragem atual.',
        motorista_invalido: 'Escolha o motorista.',
        vistoriador_invalido: 'Não há supervisor responsável definido. Peça a um gestor para vincular seu cadastro a um supervisor.',
        resposta_obrigatoria: 'Responda todas as perguntas obrigatórias.',
        descricao_obrigatoria: 'Descreva a não conformidade encontrada.',
        arquivo_nao_e_imagem: 'O anexo precisa ser uma imagem (JPG, PNG, WEBP ou GIF).',
        arquivo_muito_grande: 'A imagem pode ter no máximo 8 MB.',
        formulario_expirado: 'O formulário expirou. Recarregue a página e tente de novo.',
        km_menor_que_atual: 'O km informado é menor que o último registrado para este veículo. O hodômetro não volta.',
        cnh_vencida: 'A CNH do motorista está vencida. Atualize o cadastro antes de fazer a vistoria.',
        checklist_sem_perguntas: 'Este modelo de checklist não tem perguntas. Edite o modelo antes de usar.',
        veiculo_com_historico: 'Este veículo já tem vistorias registradas e não pode ser apagado. Mude o status dele em "Editar Veículo".',
        cnh_obrigatoria: 'Motorista precisa ter o número da CNH (11 dígitos).',
        categoria_cnh_obrigatoria: 'Informe a categoria da CNH do motorista.',
        validade_cnh_obrigatoria: 'Informe a validade da CNH do motorista.',
        nao_pode_desativar_a_si_mesmo: 'Você não pode desativar a sua própria conta.',
        vistoria_invalida: 'Esta vistoria não pode mais ser alterada.',
        checklist_de_outro: 'Este modelo foi criado por outro supervisor. Só quem criou (ou o administrador) pode alterar.',
        checklist_nao_aprovado: 'Este modelo ainda não foi aprovado pelo administrador.'
    };
    const main = document.querySelector('main') || document.body;
    const div = document.createElement('div');
    div.className = 'alert alert-danger shadow-sm';
    div.textContent = textos[erro] || ('Não foi possível concluir: ' + erro.replace(/_/g, ' '));
    main.prepend(div);
});

/**
 * Layout padrão das telas internas: mesmo cabeçalho e mesmo menu lateral do Painel.
 * A página só precisa ter <body data-secao="frota" data-titulo="Cadastrar veículo">.
 */
const AxionLayout = {
    secoes: {
        inicio: ['bi-speedometer2', 'Painel'],
        aprovacoes: ['bi-patch-check', 'Aprovações'],
        vistorias: ['bi-clipboard2-check', 'Vistorias'],
        frota: ['bi-truck-front', 'Frota'],
        usuarios: ['bi-people', 'Usuários'],
        checklists: ['bi-ui-checks-grid', 'Modelos de checklist'],
        admin: ['bi-gear', 'Administração'],
        perfil: ['bi-person-circle', 'Meu perfil'],
    },

    // Mesma ordem e mesmos nomes do menu do painel (oqfazer.php)
    menuDoPerfil(perfil) {
        if (perfil === 'admin') return ['inicio', 'aprovacoes', 'usuarios', 'checklists', 'frota', 'vistorias', 'admin'];
        if (perfil === 'supervisor') return ['inicio', 'vistorias', 'usuarios', 'frota', 'checklists'];
        return ['inicio', 'vistorias', 'frota'];
    },

    rotulo(secao, perfil) {
        if (secao === 'vistorias' && perfil === 'motorista') return 'Minhas vistorias';
        if (secao === 'vistorias' && perfil === 'supervisor') return 'Vistorias da equipe';
        if (secao === 'usuarios' && perfil === 'supervisor') return 'Minha equipe';
        if (secao === 'checklists' && perfil === 'supervisor') return 'Checklists';
        return this.secoes[secao][1];
    },

    montar(ctx) {
        const body = document.body;
        const secaoAtual = body.dataset.secao;
        const u = ctx.usuario;
        const podeVistoriar = ['supervisor', 'motorista'].includes(u.perfil);
        const ehAdmin = u.perfil === 'admin';
        if (ehAdmin) body.classList.add('tema-admin');
        const pend = Number(ctx.pendencias || 0);

        // Cabeçalho
        document.querySelectorAll('nav.axion-navbar').forEach(n => n.remove());
        const nav = document.createElement('nav');
        nav.className = 'axion-navbar text-white';
        nav.innerHTML = `
            <div class="container-fluid px-3 px-lg-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a href="oqfazer.php" class="axion-brand"><i class="bi bi-truck-front-fill fs-4"></i><span>AXION</span><span class="axion-brand-badge">${ehAdmin ? 'Administração' : 'Frota & Checklists'}</span></a>
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    ${pend > 0 ? (ehAdmin
                        ? `<a href="oqfazer.php?aba=aprovacoes" class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-bell-fill me-1"></i>${pend} para aprovar</a>`
                        : `<a href="oqfazer.php?aba=vistorias&filtro=minhas_pendencias" class="btn btn-sm btn-warning fw-semibold"><i class="bi bi-bell-fill me-1"></i>${pend} ${u.perfil === 'motorista' ? 'para assinar' : 'para aprovar'}</a>`) : ''}
                    <a href="meuPerfil.html" class="d-flex align-items-center gap-2 small border-start ps-3 border-light border-opacity-25 text-white text-decoration-none" title="Meu perfil">
                        ${axionAvatar(u.foto_url, u.nome, 34)}
                        <div class="lh-sm d-none d-md-block"><div class="fw-semibold">${axionEsc(u.nome)}</div><div class="text-white-50">${axionEsc(u.perfil.charAt(0).toUpperCase() + u.perfil.slice(1))} · Meu perfil</div></div>
                    </a>
                    <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right me-1"></i>Sair</a>
                </div>
            </div>`;

        // Menu lateral
        const menu = this.menuDoPerfil(u.perfil).map(s => `
            <a href="oqfazer.php?aba=${s}" class="menu-link ${s === secaoAtual ? 'ativo' : ''}">
                <i class="bi ${this.secoes[s][0]}"></i><span>${this.rotulo(s, u.perfil)}</span>
                ${((s === 'vistorias' && !ehAdmin) || (s === 'aprovacoes' && ehAdmin)) && pend > 0 ? `<span class="badge rounded-pill bg-warning text-dark">${pend}</span>` : ''}
            </a>`).join('');

        const layout = document.createElement('div');
        layout.className = 'painel-layout';
        layout.innerHTML = `
            <aside class="painel-menu"><div class="menu-box">
                ${podeVistoriar ? '<a href="cadcheck.html" class="btn btn-warning fw-semibold w-100 mb-2 menu-cta"><i class="bi bi-plus-circle me-1"></i>Nova vistoria</a>' : ''}
                <div class="menu-titulo">Menu</div>${menu}
            </div></aside>
            <div class="painel-conteudo"></div>`;
        const conteudo = layout.querySelector('.painel-conteudo');

        // Caminho: Painel › Seção › Tela atual
        if (secaoAtual && secaoAtual !== 'inicio') {
            conteudo.insertAdjacentHTML('beforeend', `<div class="trilha">
                <a href="oqfazer.php"><i class="bi bi-house-door"></i> Painel</a><i class="bi bi-chevron-right small"></i>
                <a href="${secaoAtual === 'perfil' ? 'meuPerfil.html' : 'oqfazer.php?aba=' + secaoAtual}">${this.rotulo(secaoAtual, u.perfil)}</a>
                ${body.dataset.titulo ? `<i class="bi bi-chevron-right small"></i><span class="atual">${axionEsc(body.dataset.titulo)}</span>` : ''}
            </div>`);
        }

        const principal = document.querySelector('body > main');
        if (principal) {
            principal.classList.remove('min-vh-100', 'align-items-center', 'justify-content-center', 'd-flex', 'container');
            conteudo.appendChild(principal);
        }
        body.prepend(layout);
        body.prepend(nav);
        body.classList.remove('bg-light');
    }
};

document.addEventListener('DOMContentLoaded', async () => {
    if (document.body.dataset.secao === undefined) return;
    const mostrar = () => document.body.classList.add('layout-pronto');
    setTimeout(mostrar, 1500);
    const ctx = await AxionSessao.pronta;
    if (ctx) AxionLayout.montar(ctx);
    mostrar();
});
