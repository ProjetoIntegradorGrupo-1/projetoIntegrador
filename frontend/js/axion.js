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
