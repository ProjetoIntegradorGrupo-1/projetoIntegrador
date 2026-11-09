/**
 * frontend/js/axion.js
 * Máscaras de conferência, validações em tempo real (CPF, E-mail, Datas, CNH, Placa, Renavam, Chassi, Ano do Veículo),
 * corretor ortográfico de Marcas/Modelos de veículos e busca gratuita de CEP (ViaCEP / BrasilAPI).
 */

const AxionUtils = {
    // Dicionário de grafia correta de Marcas de veículos comercializadas no Brasil
    marcasConhecidas: {
        'volkswagen': 'Volkswagen', 'vw': 'Volkswagen', 'volks': 'Volkswagen', 'volksvagen': 'Volkswagen',
        'volkswagem': 'Volkswagen', 'wolkswagen': 'Volkswagen', 'wolksvagen': 'Volkswagen', 'vokswagen': 'Volkswagen',
        'hyundai': 'Hyundai', 'hiunday': 'Hyundai', 'hyunday': 'Hyundai', 'hiundai': 'Hyundai',
        'hyndai': 'Hyundai', 'hindai': 'Hyundai', 'hunday': 'Hyundai',
        'chevrolet': 'Chevrolet', 'gm': 'Chevrolet', 'chevorlet': 'Chevrolet', 'chevrole': 'Chevrolet',
        'chevrolete': 'Chevrolet', 'xevrolet': 'Chevrolet',
        'fiat': 'Fiat', 'fiate': 'Fiat',
        'toyota': 'Toyota', 'toiota': 'Toyota', 'toyotta': 'Toyota',
        'renault': 'Renault', 'reno': 'Renault', 'renout': 'Renault', 'renaut': 'Renault', 'renalt': 'Renault',
        'ford': 'Ford', 'forde': 'Ford',
        'honda': 'Honda', 'ronda': 'Honda',
        'nissan': 'Nissan', 'nisan': 'Nissan', 'nyssan': 'Nissan',
        'jeep': 'Jeep', 'jipe': 'Jeep', 'jep': 'Jeep',
        'peugeot': 'Peugeot', 'pejo': 'Peugeot', 'pegeout': 'Peugeot', 'peugout': 'Peugeot', 'pejô': 'Peugeot',
        'citroen': 'Citroën', 'citroën': 'Citroën', 'citroem': 'Citroën', 'sitroen': 'Citroën',
        'mercedes': 'Mercedes-Benz', 'mercedes-benz': 'Mercedes-Benz', 'mercedez': 'Mercedes-Benz',
        'mercedez-benz': 'Mercedes-Benz', 'mercedes benz': 'Mercedes-Benz',
        'volvo': 'Volvo', 'vouvo': 'Volvo',
        'scania': 'Scania', 'escania': 'Scania', 'scânia': 'Scania',
        'iveco': 'Iveco',
        'mitsubishi': 'Mitsubishi', 'mitsubichi': 'Mitsubishi', 'mitsubish': 'Mitsubishi', 'mitisubishi': 'Mitsubishi',
        'kia': 'Kia', 'kya': 'Kia',
        'bmw': 'BMW', 'audi': 'Audi', 'byd': 'BYD', 'gwm': 'GWM', 'caoachery': 'Caoa Chery', 'chery': 'Caoa Chery'
    },

    // Dicionário de grafia correta de Modelos populares
    modelosConhecidos: {
        'gol': 'Gol', 'gou': 'Gol',
        'saveiro': 'Saveiro', 'savero': 'Saveiro',
        'voyage': 'Voyage', 'voiage': 'Voyage',
        'polo': 'Polo', 'virtus': 'Virtus', 't-cross': 'T-Cross', 'tcross': 'T-Cross', 'nivus': 'Nivus', 'amarok': 'Amarok',
        'onix': 'Onix', 'ônix': 'Onix', 'oniks': 'Onix', 'onics': 'Onix',
        'prisma': 'Prisma', 'tracker': 'Tracker', 'traker': 'Tracker', 's10': 'S10', 's-10': 'S10',
        'montana': 'Montana', 'spin': 'Spin', 'cruze': 'Cruze',
        'strada': 'Strada', 'estrada': 'Strada',
        'toro': 'Toro', 'fiorino': 'Fiorino', 'mobi': 'Mobi', 'argo': 'Argo', 'cronos': 'Cronos', 'pulse': 'Pulse', 'ducato': 'Ducato',
        'hb20': 'HB20', 'hb 20': 'HB20', 'hb-20': 'HB20', 'creta': 'Creta', 'hr': 'HR',
        'hilux': 'Hilux', 'hillux': 'Hilux', 'hilucs': 'Hilux', 'hylux': 'Hilux',
        'corolla': 'Corolla', 'corola': 'Corolla', 'yaris': 'Yaris', 'sw4': 'SW4',
        'kwid': 'Kwid', 'kuid': 'Kwid', 'sandero': 'Sandero', 'sandeiro': 'Sandero',
        'logan': 'Logan', 'duster': 'Duster', 'oroch': 'Oroch', 'master': 'Master',
        'ranger': 'Ranger', 'ka': 'Ka', 'ecosport': 'EcoSport', 'transit': 'Transit',
        'renegade': 'Renegade', 'renegade': 'Renegade', 'compass': 'Compass', 'compas': 'Compass', 'commander': 'Commander',
        'sprinter': 'Sprinter', 'esprinter': 'Sprinter', 'accelo': 'Accelo', 'acelo': 'Accelo',
        'fh 540': 'FH 540', 'fh540': 'FH 540', 'vm 270': 'VM 270', 'vm270': 'VM 270'
    },

    // Distância de Levenshtein para sugerir grafia próxima
    levenshtein(a, b) {
        const matrix = [];
        for (let i = 0; i <= b.length; i++) matrix[i] = [i];
        for (let j = 0; j <= a.length; j++) matrix[0][j] = j;
        for (let i = 1; i <= b.length; i++) {
            for (let j = 1; j <= a.length; j++) {
                if (b.charAt(i - 1) === a.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        return matrix[b.length][a.length];
    },

    verificarGrafiaMarca(valor) {
        const limpo = (valor || '').trim();
        if (!limpo) return { valido: false, mensagem: '' };
        const chave = limpo.toLowerCase();

        // Verifica correspondência direta ou erro conhecido no dicionário
        if (AxionUtils.marcasConhecidas[chave]) {
            const correta = AxionUtils.marcasConhecidas[chave];
            const foiCorrigido = correta.toLowerCase() !== limpo.toLowerCase();
            return {
                valido: true,
                corrigido: foiCorrigido,
                valorCorreto: correta,
                mensagem: foiCorrigido
                    ? `✓ Grafia ajustada automaticamente para "${correta}"`
                    : `✓ Marca conferida: ${correta}`
            };
        }

        // Busca por similaridade (Levenshtein <= 2) entre as marcas oficiais
        const oficiais = [...new Set(Object.values(AxionUtils.marcasConhecidas))];
        for (const oficial of oficiais) {
            if (AxionUtils.levenshtein(chave, oficial.toLowerCase()) <= 2) {
                return {
                    valido: false,
                    sugestao: oficial,
                    mensagem: `✗ Grafia incorreta ("${limpo}"). Você quis dizer "${oficial}"?`
                };
            }
        }

        return {
            valido: true,
            corrigido: false,
            valorCorreto: limpo.charAt(0).toUpperCase() + limpo.slice(1),
            mensagem: `✓ Marca informada: ${limpo}`
        };
    },

    verificarGrafiaModelo(valor) {
        const limpo = (valor || '').trim();
        if (!limpo) return { valido: false, mensagem: '' };
        const partes = limpo.split(/\s+/);
        const primeiraPalavra = partes[0].toLowerCase();
        const chaveCompleta = limpo.toLowerCase();

        if (AxionUtils.modelosConhecidos[chaveCompleta]) {
            const correto = AxionUtils.modelosConhecidos[chaveCompleta];
            const foiCorrigido = correto.toLowerCase() !== chaveCompleta;
            return {
                valido: true,
                corrigido: foiCorrigido,
                valorCorreto: correto,
                mensagem: foiCorrigido ? `✓ Grafia do modelo ajustada para "${correto}"` : `✓ Modelo conferido`
            };
        }

        if (AxionUtils.modelosConhecidos[primeiraPalavra]) {
            const baseCorreta = AxionUtils.modelosConhecidos[primeiraPalavra];
            const foiCorrigido = baseCorreta.toLowerCase() !== primeiraPalavra;
            partes[0] = baseCorreta;
            const resultado = partes.join(' ');
            return {
                valido: true,
                corrigido: foiCorrigido,
                valorCorreto: resultado,
                mensagem: foiCorrigido ? `✓ Grafia do modelo ajustada para "${resultado}"` : `✓ Modelo conferido`
            };
        }

        // Verifica similaridade da primeira palavra com modelos conhecidos
        const oficiais = [...new Set(Object.values(AxionUtils.modelosConhecidos))];
        for (const oficial of oficiais) {
            if (primeiraPalavra.length >= 4 && AxionUtils.levenshtein(primeiraPalavra, oficial.toLowerCase()) === 1) {
                return {
                    valido: false,
                    sugestao: oficial,
                    mensagem: `✗ Possível erro de grafia em "${partes[0]}". Seria "${oficial}"?`
                };
            }
        }

        return { valido: true, corrigido: false, valorCorreto: limpo, mensagem: '✓ Grafia do modelo conferida' };
    },

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

    // Validação de E-mail com @ e domínio
    validarEmail(email) {
        const limpo = (email || '').trim();
        if (!limpo.includes('@')) return false;
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(limpo);
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

    // Formata Placa (Padrão Mercosul ABC1D23 ou Antigo ABC1234)
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

    // Helper para criar/atualizar mensagem de feedback abaixo de um input
    obterElementoFeedback(input, classeEspecifica) {
        let el = input.parentElement.querySelector('.' + classeEspecifica);
        if (!el) {
            el = document.createElement('div');
            el.className = classeEspecifica + ' small mt-1';
            input.parentElement.appendChild(el);
        }
        return el;
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
                    feedbackEl.textContent = `✓ Endereço preenchido: ${dados.cidade}/${dados.estado}`;
                    feedbackEl.className = 'form-text text-success fw-semibold';
                }
                if (campoNumero && !campoNumero.value) {
                    campoNumero.focus();
                }
            } else {
                cepInput.classList.remove('is-valid');
                cepInput.classList.add('is-invalid');
                if (feedbackEl) {
                    feedbackEl.textContent = '✗ CEP não encontrado. Preencha o endereço manualmente.';
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
        const hoje = new Date();
        const anoAtual = hoje.getFullYear();
        const mesAtual = String(hoje.getMonth() + 1).padStart(2, '0');
        const diaAtual = String(hoje.getDate()).padStart(2, '0');
        const dataHojeISO = `${anoAtual}-${mesAtual}-${diaAtual}`;
        const dataMinNascimentoISO = `${anoAtual - 100}-${mesAtual}-${diaAtual}`;
        const dataMaxValidadeCnhISO = `${anoAtual + 20}-${mesAtual}-${diaAtual}`;

        // 1. Campo CPF
        document.querySelectorAll('#cpf, [data-mask="cpf"]').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'cpf-feedback');
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
                    msgEl.textContent = `✗ CPF incompleto (${digitos.length}/11 dígitos)`;
                    msgEl.className = 'cpf-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('CPF incompleto.');
                } else if (AxionUtils.validarCPF(input.value)) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ CPF válido (dígitos conferidos)';
                    msgEl.className = 'cpf-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = '✗ CPF inválido (dígitos verificadores incorretos)';
                    msgEl.className = 'cpf-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('CPF inválido.');
                }
            };
            input.addEventListener('input', atualizarStatusCPF);
            input.addEventListener('blur', atualizarStatusCPF);
            if (input.value) atualizarStatusCPF();
        });

        // 2. Campo E-mail (Validação obrigatória com @ e domínio)
        document.querySelectorAll('#email, input[type="email"]').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'email-feedback');
            const validarCampoEmail = () => {
                const val = input.value.trim();
                if (!val) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                    return;
                }
                if (!val.includes('@')) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = '✗ E-mail inválido: é obrigatório conter o símbolo "@"';
                    msgEl.className = 'email-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('O e-mail deve conter @.');
                } else if (!AxionUtils.validarEmail(val)) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = '✗ Informe um domínio válido após o "@" (ex: nome@empresa.com)';
                    msgEl.className = 'email-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('Informe um e-mail completo válido.');
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ E-mail válido';
                    msgEl.className = 'email-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', validarCampoEmail);
            input.addEventListener('blur', validarCampoEmail);
            if (input.value) validarCampoEmail();
        });

        // 3. Campo Data de Nascimento (Apenas nascidos nos últimos 100 anos)
        document.querySelectorAll('#dtNascimento').forEach((input) => {
            input.setAttribute('min', dataMinNascimentoISO);
            input.setAttribute('max', dataHojeISO);
            const msgEl = AxionUtils.obterElementoFeedback(input, 'dtnasc-feedback');

            const validarDtNasc = () => {
                const val = input.value;
                if (!val) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                    return;
                }
                if (val < dataMinNascimentoISO) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `✗ Data inválida: só é permitido cadastrar nascidos nos últimos 100 anos (a partir de ${anoAtual - 100}).`;
                    msgEl.className = 'dtnasc-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('Data de nascimento superior ao limite de 100 anos.');
                } else if (val > dataHojeISO) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = '✗ Data de nascimento não pode ser no futuro.';
                    msgEl.className = 'dtnasc-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('Data de nascimento futura inválida.');
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ Data de nascimento válida';
                    msgEl.className = 'dtnasc-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', validarDtNasc);
            input.addEventListener('change', validarDtNasc);
            if (input.value) validarDtNasc();
        });

        // 4. Campo CNH (Fica vermelho se tiver menos de 11 dígitos)
        document.querySelectorAll('#cnh, [data-mask="cnh"]').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'cnh-feedback');
            const validarCampoCNH = () => {
                input.value = AxionUtils.mascaraCNH(input.value);
                const len = input.value.length;
                if (len === 0) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                } else if (len < 11) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `✗ CNH inválida: deve ter exatamente 11 dígitos (${len}/11)`;
                    msgEl.className = 'cnh-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('A CNH deve conter 11 dígitos.');
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ N° de CNH com 11 dígitos';
                    msgEl.className = 'cnh-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', validarCampoCNH);
            input.addEventListener('blur', validarCampoCNH);
            if (input.value) validarCampoCNH();
        });

        // 5. Campo Validade da CNH (No máximo 20 anos à frente da data atual)
        document.querySelectorAll('#validade-cnh').forEach((input) => {
            input.setAttribute('max', dataMaxValidadeCnhISO);
            const msgEl = AxionUtils.obterElementoFeedback(input, 'valcnh-feedback');

            const validarValidadeCNH = () => {
                const val = input.value;
                if (!val) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                    return;
                }
                if (val > dataMaxValidadeCnhISO) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `✗ Validade inválida: máximo de 20 anos à frente (até ${anoAtual + 20}).`;
                    msgEl.className = 'valcnh-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity(`A validade da CNH não pode passar de ${anoAtual + 20}.`);
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ Data de validade dentro do limite de 20 anos';
                    msgEl.className = 'valcnh-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', validarValidadeCNH);
            input.addEventListener('change', validarValidadeCNH);
            if (input.value) validarValidadeCNH();
        });

        // 6. Campo CEP + Busca automática ViaCEP
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

        // 7. Campo Telefone
        document.querySelectorAll('#telefone, [data-mask="telefone"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = AxionUtils.mascaraTelefone(input.value);
            });
            if (input.value) input.value = AxionUtils.mascaraTelefone(input.value);
        });

        // 8. Campos Marca e Modelo (Conferência ortográfica em português)
        document.querySelectorAll('#marca').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'marca-feedback');
            const conferirMarca = (aplicarCorrecao = false) => {
                const res = AxionUtils.verificarGrafiaMarca(input.value);
                if (!input.value.trim()) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                    return;
                }
                if (!res.valido && res.sugestao) {
                    if (aplicarCorrecao) {
                        input.value = res.sugestao;
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                        msgEl.textContent = `✓ Grafia corrigida para "${res.sugestao}"`;
                        msgEl.className = 'marca-feedback small mt-1 text-success fw-semibold';
                        input.setCustomValidity('');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                        msgEl.textContent = res.mensagem;
                        msgEl.className = 'marca-feedback small mt-1 text-danger fw-semibold';
                        input.setCustomValidity(res.mensagem);
                    }
                } else {
                    if (aplicarCorrecao && res.valorCorreto) {
                        input.value = res.valorCorreto;
                    }
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = res.mensagem;
                    msgEl.className = 'marca-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', () => conferirMarca(false));
            input.addEventListener('blur', () => conferirMarca(true));
            if (input.value) conferirMarca(true);
        });

        document.querySelectorAll('#modelo').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'modelo-feedback');
            const conferirModelo = (aplicarCorrecao = false) => {
                const res = AxionUtils.verificarGrafiaModelo(input.value);
                if (!input.value.trim()) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                    return;
                }
                if (!res.valido && res.sugestao) {
                    if (aplicarCorrecao) {
                        const partes = input.value.trim().split(/\s+/);
                        partes[0] = res.sugestao;
                        input.value = partes.join(' ');
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                        msgEl.textContent = `✓ Grafia corrigida para "${input.value}"`;
                        msgEl.className = 'modelo-feedback small mt-1 text-success fw-semibold';
                        input.setCustomValidity('');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                        msgEl.textContent = res.mensagem;
                        msgEl.className = 'modelo-feedback small mt-1 text-danger fw-semibold';
                        input.setCustomValidity(res.mensagem);
                    }
                } else {
                    if (aplicarCorrecao && res.valorCorreto) {
                        input.value = res.valorCorreto;
                    }
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = res.mensagem;
                    msgEl.className = 'modelo-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', () => conferirModelo(false));
            input.addEventListener('blur', () => conferirModelo(true));
            if (input.value) conferirModelo(true);
        });

        // 9. Campo Ano do Veículo (Sinalizar em vermelho se tiver mais de 10 anos)
        document.querySelectorAll('#ano').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'ano-feedback');
            const limiteDezAnos = anoAtual - 10;

            const validarAnoVeiculo = () => {
                const ano = parseInt(input.value, 10);
                if (!input.value || isNaN(ano)) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    return;
                }
                if (ano < limiteDezAnos) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `⚠ Atenção: Veículo com mais de 10 anos de fabricação (${anoAtual - ano} anos — anterior a ${limiteDezAnos})!`;
                    msgEl.className = 'ano-feedback small mt-1 text-danger fw-bold';
                } else if (ano > anoAtual + 1) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `✗ Ano inválido (superior a ${anoAtual + 1}).`;
                    msgEl.className = 'ano-feedback small mt-1 text-danger fw-semibold';
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = `✓ Idade da frota dentro do padrão (${anoAtual - ano} anos)`;
                    msgEl.className = 'ano-feedback small mt-1 text-success fw-semibold';
                }
            };
            input.addEventListener('input', validarAnoVeiculo);
            input.addEventListener('blur', validarAnoVeiculo);
            if (input.value) validarAnoVeiculo();
        });

        // 10. Campo Renavam (Sinalizar em vermelho se tiver menos de 11 dígitos)
        document.querySelectorAll('#renavam, [data-mask="renavam"]').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'renavam-feedback');
            const validarCampoRenavam = () => {
                input.value = AxionUtils.mascaraRenavam(input.value);
                const len = input.value.length;
                if (len === 0) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                } else if (len < 11) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `✗ Renavam inválido: deve ter exatamente 11 dígitos (${len}/11)`;
                    msgEl.className = 'renavam-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('O Renavam deve conter 11 dígitos.');
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ Renavam válido (11 dígitos)';
                    msgEl.className = 'renavam-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', validarCampoRenavam);
            input.addEventListener('blur', validarCampoRenavam);
            if (input.value) validarCampoRenavam();
        });

        // 11. Campo Placa
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
                } else if (input.value.length > 0) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    input.setCustomValidity('A placa deve conter 7 caracteres.');
                } else {
                    input.classList.remove('is-valid', 'is-invalid');
                    input.setCustomValidity('');
                }
            });
        });

        // 12. Campo Chassi (Sinalizar em vermelho se tiver menos de 17 caracteres)
        document.querySelectorAll('#chassi, [data-mask="chassi"]').forEach((input) => {
            const msgEl = AxionUtils.obterElementoFeedback(input, 'chassi-feedback');
            const validarCampoChassi = () => {
                input.value = AxionUtils.mascaraChassi(input.value);
                const len = input.value.length;
                if (len === 0) {
                    input.classList.remove('is-valid', 'is-invalid');
                    msgEl.textContent = '';
                    input.setCustomValidity('');
                } else if (len < 17) {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                    msgEl.textContent = `✗ Chassi incompleto: deve ter exatamente 17 caracteres (${len}/17)`;
                    msgEl.className = 'chassi-feedback small mt-1 text-danger fw-semibold';
                    input.setCustomValidity('O Chassi deve conter 17 caracteres.');
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                    msgEl.textContent = '✓ Chassi válido (17 caracteres ISO)';
                    msgEl.className = 'chassi-feedback small mt-1 text-success fw-semibold';
                    input.setCustomValidity('');
                }
            };
            input.addEventListener('input', validarCampoChassi);
            input.addEventListener('blur', validarCampoChassi);
            if (input.value) validarCampoChassi();
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    AxionUtils.inicializarMascarasEValidacoes();
});
