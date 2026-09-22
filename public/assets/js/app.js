/* MedConecta — app.js */

const MC = window.MedConecta || { baseUrl: '/', csrfToken: '' };

function $(seletor, contexto = document) {
  return contexto.querySelector(seletor);
}

function $$(seletor, contexto = document) {
  return [...contexto.querySelectorAll(seletor)];
}

function escaparHtml(valor) {
  return String(valor ?? '').replace(/[&<>"']/g, caractere => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[caractere]);
}

function feedback(elemento, mensagem, tipo = 'erro') {
  if (!elemento) return;
  elemento.textContent = mensagem;
  elemento.className = 'feedback feedback-' + tipo;
}

async function apiPost(endpoint, dados) {
  dados.csrf = MC.csrfToken;

  const resposta = await fetch(MC.baseUrl + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(dados)
  });

  return resposta.json();
}

async function apiGet(endpoint) {
  const resposta = await fetch(MC.baseUrl + endpoint);
  return resposta.json();
}

function bindForm(seletorFormulario, endpoint, aoSalvar) {
  const formulario = $(seletorFormulario);
  if (!formulario) return;

  formulario.addEventListener('submit', async evento => {
    evento.preventDefault();

    const aviso = formulario.querySelector('[role="alert"]');
    const botao = formulario.querySelector('[type="submit"]');

    botao.disabled = true;
    feedback(aviso, 'Aguarde…', 'info');

    const dados = Object.fromEntries(new FormData(formulario));

    try {
      const resultado = await apiPost(endpoint, dados);

      if (resultado.ok) {
        aoSalvar(resultado, aviso, formulario);
      } else {
        const mensagem = resultado.erros
          ? resultado.erros.join(' ')
          : (resultado.erro || 'Erro desconhecido.');

        feedback(aviso, mensagem, 'erro');
      }
    } catch {
      feedback(
        aviso,
        'Falha de conexão. Verifique se o servidor está rodando.',
        'erro'
      );
    } finally {
      botao.disabled = false;
    }
  });
}

/* LOGIN */

bindForm('#form-login', 'api/login.php', resultado => {
  location.href = resultado.redirect || MC.baseUrl + 'dashboard.php';
});

/* CADASTRO */

bindForm('#form-cadastro', 'api/cadastro.php', resultado => {
  location.href = resultado.redirect || MC.baseUrl + 'login.php';
});

/* CONTATO */

bindForm('#form-contato', 'api/contato.php', (resultado, aviso, formulario) => {
  feedback(aviso, resultado.mensagem, 'sucesso');
  formulario.reset();
});

/* AGENDAMENTO */

const seletorMedico = $('#medico');
const campoEspecialidade = $('#especialidade');

const especialidadesPorMedico = {
  'Dra. Ana Silva': 'Clínica Geral',
  'Dr. Carlos Mendes': 'Cardiologia',
  'Dra. Marina Costa': 'Pediatria',
  'Dr. Paulo Ribeiro': 'Neurologia'
};

if (seletorMedico && campoEspecialidade) {
  seletorMedico.addEventListener('change', () => {
    campoEspecialidade.value =
      especialidadesPorMedico[seletorMedico.value] || '';
  });
}

bindForm('#form-agendamento', 'api/agendamento.php', resultado => {
  const confirmacao = $('#confirmacao-agendamento');
  const texto = $('#confirmacao-texto');

  if (confirmacao && texto && resultado.confirmacao) {
    const consulta = resultado.confirmacao;
    const data = new Date(consulta.data_hora).toLocaleString('pt-BR');

    texto.textContent =
      `${consulta.medico} · ${consulta.especialidade} · ${data}`;

    confirmacao.classList.remove('hidden');
  }

  carregarConsultas();
});

async function carregarConsultas() {
  const lista = $('#agenda-lista');
  if (!lista) return;

  try {
    const resultado = await apiGet('api/agendamento.php');

    if (!resultado.ok || !resultado.consultas?.length) {
      lista.innerHTML =
        '<p style="color:var(--text-muted);font-size:.9rem;padding:.5rem 0">' +
        'Nenhuma consulta agendada.</p>';
      return;
    }

    lista.innerHTML = resultado.consultas.map(consulta => `
      <div class="consulta-item">
        <div>
          <strong>${escaparHtml(consulta.medico)}</strong>
          · ${escaparHtml(consulta.especialidade)}
          <small>
            ${new Date(consulta.data_hora).toLocaleString('pt-BR')}
            · ${escaparHtml(consulta.local)}
          </small>
        </div>
        <span class="status-badge status-${escaparHtml(consulta.status)}">
          ${escaparHtml(consulta.status)}
        </span>
      </div>
    `).join('');
  } catch {
    /* A página continua utilizável se a agenda estiver indisponível. */
  }
}

/* DASHBOARD */

async function carregarDashboard() {
  const nome = $('#dash-nome');
  if (!nome) return;

  try {
    const resultado = await apiGet('api/paciente.php');

    if (resultado.ok) {
      nome.textContent = String(resultado.paciente.nome || '')
        .split(' ')[0];

      renderPerfil(resultado.paciente);
      renderConsultasDash(resultado.consultas);
    }
  } catch {
    /* Os documentos ainda podem ser carregados separadamente. */
  }

  await carregarDocumentos();
}

async function carregarDocumentos() {
  const lista = $('#lista-documentos');
  if (!lista) return;

  try {
    const resultado = await apiGet('api/documento.php');

    if (!resultado.ok) {
      lista.innerHTML =
        '<p style="color:var(--danger);font-size:.9rem;">' +
        'Erro ao carregar documentos.</p>';
      return;
    }

    renderDocumentos(resultado.documentos);
  } catch {
    lista.innerHTML =
      '<p style="color:var(--text-muted);font-size:.9rem;">' +
      'Não foi possível carregar documentos.</p>';
  }
}

function renderPerfil(paciente) {
  const perfil = $('#profile-data');
  if (!perfil) return;

  perfil.innerHTML = `
    <dt>Nome</dt><dd>${escaparHtml(paciente.nome)}</dd>
    <dt>E-mail</dt><dd>${escaparHtml(paciente.email)}</dd>
    <dt>CPF</dt><dd>${escaparHtml(paciente.cpf_formatado)}</dd>
    <dt>Telefone</dt><dd>${escaparHtml(paciente.telefone_formatado)}</dd>
    <dt>Nascimento</dt>
    <dd>
      ${paciente.data_nascimento
        ? new Date(paciente.data_nascimento + 'T12:00:00')
            .toLocaleDateString('pt-BR')
        : '—'}
    </dd>
    <dt>Cadastro</dt>
    <dd>
      ${paciente.created_at
        ? new Date(paciente.created_at).toLocaleDateString('pt-BR')
        : '—'}
    </dd>
  `;
}

function iconeTipo(tipo) {
  const icones = {
    Receita: '💊',
    Exame: '🔬',
    Atestado: '📋',
    Laudo: '📝',
    Outro: '📄'
  };

  return icones[tipo] || '📄';
}

function formatarDataDocumento(documento) {
  if (documento.data_documento) {
    const data = new Date(documento.data_documento.slice(0, 10) + 'T12:00:00');

    if (!Number.isNaN(data.getTime())) {
      return data.toLocaleDateString('pt-BR');
    }
  }

  if (documento.created_at) {
    const data = new Date(documento.created_at);

    if (!Number.isNaN(data.getTime())) {
      return data.toLocaleDateString('pt-BR');
    }
  }

  return 'Data não informada';
}

function renderDocumentos(documentos) {
  const lista = $('#lista-documentos');
  if (!lista) return;

  if (!documentos?.length) {
    lista.innerHTML = `
      <div class="doc-vazio">
        <span>📂</span>
        <p>Nenhum documento cadastrado ainda.</p>
        <small>
          Clique em "+ Adicionar documento" para enviar receitas,
          exames e laudos.
        </small>
      </div>
    `;
    return;
  }

  lista.innerHTML = documentos.map(documento => {
    const id = Number(documento.id);
    const temArquivo = Boolean(documento.arquivo_path) && id > 0;

    // O PHP verifica a sessão e se o arquivo pertence ao paciente.
    const urlArquivo = MC.baseUrl
      + 'api/arquivo.php?id='
      + encodeURIComponent(id);

    const pdf = temArquivo
      && String(documento.arquivo_path).toLowerCase().endsWith('.pdf');

    return `
      <div class="doc-item" role="listitem" data-id="${id}">
        <div class="doc-item-icone">
          ${iconeTipo(documento.tipo)}
        </div>

        <div class="doc-item-body">
          <div class="doc-item-topo">
            <strong class="doc-item-titulo">
              ${escaparHtml(documento.titulo)}
            </strong>
            <span class="doc-tipo-badge">
              ${escaparHtml(documento.tipo)}
            </span>
          </div>

          ${documento.especialidade
            ? `<small>🩺 ${escaparHtml(documento.especialidade)}</small>`
            : ''}

          ${documento.profissional
            ? `<small>👨‍⚕️ ${escaparHtml(documento.profissional)}</small>`
            : ''}

          ${documento.medicamentos
            ? `<small>💊 ${escaparHtml(documento.medicamentos)}</small>`
            : ''}

          <small class="doc-data">
            📅 ${formatarDataDocumento(documento)}
          </small>
        </div>

        <div class="doc-item-acoes">
          ${temArquivo ? `
            <button
              type="button"
              class="btn btn-sm btn-outline btn-ver-doc"
              data-id="${id}"
              data-tipo="${pdf ? 'pdf' : 'imagem'}"
            >
              👁️ Ver
            </button>

            <a
              class="btn btn-sm btn-ghost"
              href="${urlArquivo}"
              download
            >
              ⬇️ Baixar
            </a>
          ` : '<span class="doc-sem-arquivo">Sem arquivo</span>'}

          <button
            type="button"
            class="btn btn-sm btn-ghost btn-excluir-doc"
            data-id="${id}"
            style="color:var(--danger)"
            aria-label="Excluir documento"
          >
            🗑️
          </button>
        </div>
      </div>
    `;
  }).join('');

  $$('.btn-ver-doc', lista).forEach(botao => {
    botao.addEventListener('click', () => {
      const id = Number(botao.dataset.id);
      const documento = documentos.find(
        item => Number(item.id) === id
      );

      if (!documento) return;

      const urlArquivo = MC.baseUrl
        + 'api/arquivo.php?id='
        + encodeURIComponent(id);

      abrirVisualizador(
        urlArquivo,
        botao.dataset.tipo,
        documento.titulo
      );
    });
  });

  $$('.btn-excluir-doc', lista).forEach(botao => {
    botao.addEventListener('click', () => {
      excluirDocumento(botao.dataset.id);
    });
  });
}

function renderConsultasDash(consultas) {
  const lista = $('#lista-consultas');
  if (!lista) return;

  if (!consultas?.length) {
    lista.innerHTML =
      '<p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1rem;">' +
      'Nenhuma consulta registrada.</p>';
    return;
  }

  lista.innerHTML = consultas.map(consulta => `
    <div class="consulta-item">
      <div>
        <strong>${escaparHtml(consulta.medico)}</strong>
        · ${escaparHtml(consulta.especialidade)}

        <small>
          ${new Date(consulta.data_hora).toLocaleString('pt-BR')}
          · ${escaparHtml(consulta.local)}
        </small>
      </div>

      <span class="status-badge status-${escaparHtml(consulta.status)}">
        ${escaparHtml(consulta.status)}
      </span>
    </div>
  `).join('');
}

/* FILTRO DE DOCUMENTOS */

const filtroDocs = $('#filtro-docs');

if (filtroDocs) {
  filtroDocs.addEventListener('input', () => {
    const busca = filtroDocs.value.toLowerCase();

    $$('.doc-item').forEach(item => {
      item.style.display = item.textContent.toLowerCase().includes(busca)
        ? ''
        : 'none';
    });
  });
}

/* LOGOUT */

const botaoLogout = $('#btn-logout');

if (botaoLogout) {
  botaoLogout.addEventListener('click', async () => {
    try {
      await apiPost('api/logout.php', {});
    } catch {
      /* Redireciona mesmo se a resposta falhar. */
    }

    location.href = MC.baseUrl + 'index.php';
  });
}

/* MODAL DE UPLOAD */

function iniciarModal() {
  const modal = $('#modal-upload');
  const botaoAbrir = $('#btn-abrir-upload');
  const botaoFechar = $('#btn-fechar-modal');
  const botaoCancelar = $('#btn-cancelar-modal');
  const campoArquivo = $('#doc-arquivo');
  const areaUpload = $('#upload-area');
  const placeholder = $('#upload-placeholder');
  const preview = $('#upload-preview');
  const nomePreview = $('#upload-preview-nome');
  const iconePreview = $('#upload-preview-icon');
  const botaoRemover = $('#btn-remover-arquivo');
  const formulario = $('#form-upload');

  if (!modal || !botaoAbrir) return;

  function abrirModal() {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function fecharModal() {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
  }

  botaoAbrir.addEventListener('click', abrirModal);
  botaoFechar?.addEventListener('click', fecharModal);
  botaoCancelar?.addEventListener('click', fecharModal);

  modal.addEventListener('click', evento => {
    if (evento.target === modal) fecharModal();
  });

  document.addEventListener('keydown', evento => {
    if (evento.key === 'Escape') fecharModal();
  });

  function mostrarPreview(arquivo) {
    if (!arquivo || !nomePreview || !iconePreview || !placeholder || !preview) {
      return;
    }

    nomePreview.textContent = arquivo.name;
    iconePreview.textContent =
      arquivo.type === 'application/pdf' ? '📄' : '🖼️';

    placeholder.classList.add('hidden');
    preview.classList.remove('hidden');
  }

  function limparPreview() {
    if (!campoArquivo || !preview || !placeholder) return;

    campoArquivo.value = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
  }

  campoArquivo?.addEventListener('change', () => {
    if (campoArquivo.files[0]) {
      mostrarPreview(campoArquivo.files[0]);
    }
  });

  botaoRemover?.addEventListener('click', limparPreview);

  areaUpload?.addEventListener('dragover', evento => {
    evento.preventDefault();
    areaUpload.classList.add('drag-over');
  });

  areaUpload?.addEventListener('dragleave', () => {
    areaUpload.classList.remove('drag-over');
  });

  areaUpload?.addEventListener('drop', evento => {
    evento.preventDefault();
    areaUpload.classList.remove('drag-over');

    const arquivo = evento.dataTransfer.files[0];

    if (arquivo && campoArquivo) {
      campoArquivo.files = evento.dataTransfer.files;
      mostrarPreview(arquivo);
    }
  });

  formulario?.addEventListener('submit', async evento => {
    evento.preventDefault();

    const aviso = $('#upload-feedback');
    const botaoSalvar = $('#btn-salvar-doc');

    if (!botaoSalvar) return;

    const tipo = $('#doc-tipo')?.value || '';
    const titulo = $('#doc-titulo')?.value.trim() || '';
    const dataDocumento = $('#doc-data')?.value || '';
    const arquivo = campoArquivo?.files[0];

    if (!tipo || !titulo || !dataDocumento || !arquivo) {
      feedback(
        aviso,
        'Preencha tipo, título e data e selecione um arquivo.',
        'erro'
      );
      return;
    }

    if (arquivo.size > 5 * 1024 * 1024) {
      feedback(aviso, 'O arquivo deve ter até 5 MB.', 'erro');
      return;
    }

    if (!['application/pdf', 'image/jpeg', 'image/png'].includes(arquivo.type)) {
      feedback(aviso, 'Use um arquivo PDF, JPG ou PNG.', 'erro');
      return;
    }

    botaoSalvar.disabled = true;
    feedback(aviso, 'Salvando…', 'info');

    // FormData inclui automaticamente o campo data_documento do dashboard.
    const dados = new FormData(formulario);
    dados.set('csrf', MC.csrfToken);

    try {
      const resposta = await fetch(MC.baseUrl + 'api/documento.php', {
        method: 'POST',
        body: dados
      });

      const resultado = await resposta.json();

      if (resultado.ok) {
        feedback(aviso, '✅ ' + resultado.mensagem, 'sucesso');
        formulario.reset();
        limparPreview();

        setTimeout(async () => {
          fecharModal();
          await carregarDocumentos();
        }, 1200);
      } else {
        feedback(aviso, resultado.erro || 'Erro ao salvar.', 'erro');
      }
    } catch {
      feedback(aviso, 'Falha de conexão.', 'erro');
    } finally {
      botaoSalvar.disabled = false;
    }
  });
}

/* VISUALIZADOR DE DOCUMENTOS */

function abrirVisualizador(urlArquivo, tipo, titulo) {
  fecharVisualizador();

  const modal = document.createElement('div');

  modal.id = 'visualizador-modal';
  modal.className = 'modal-overlay';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');

  modal.innerHTML = `
    <div class="modal-box modal-box-visualizador">
      <div class="modal-header">
        <h3
          style="font-size:1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:75%"
        >
          ${escaparHtml(titulo)}
        </h3>

        <div style="display:flex;gap:.5rem;align-items:center;flex-shrink:0">
          <a
            href="${urlArquivo}"
            download
            class="btn btn-sm btn-outline"
          >
            ⬇️ Baixar
          </a>

          <button
            type="button"
            class="modal-fechar"
            id="btn-fechar-visualizador"
            aria-label="Fechar"
          >
            ✕
          </button>
        </div>
      </div>

      <div class="visualizador-corpo">
        ${tipo === 'pdf'
          ? `<iframe
               src="${urlArquivo}"
               class="visualizador-iframe"
               title="${escaparHtml(titulo)}"
             ></iframe>`
          : `<div class="visualizador-img-wrap">
               <img
                 src="${urlArquivo}"
                 alt="${escaparHtml(titulo)}"
                 class="visualizador-img"
               >
             </div>`
        }
      </div>
    </div>
  `;

  document.body.appendChild(modal);
  document.body.style.overflow = 'hidden';

  $('#btn-fechar-visualizador')
    ?.addEventListener('click', fecharVisualizador);

  modal.addEventListener('click', evento => {
    if (evento.target === modal) fecharVisualizador();
  });
}

function fecharVisualizador() {
  $('#visualizador-modal')?.remove();
  document.body.style.overflow = '';
}

document.addEventListener('keydown', evento => {
  if (evento.key === 'Escape') fecharVisualizador();
});

/* EXCLUIR DOCUMENTO */

async function excluirDocumento(id) {
  if (!confirm('Tem certeza que deseja excluir este documento?')) {
    return;
  }

  try {
    const dados = new FormData();

    dados.set('csrf', MC.csrfToken);
    dados.set('acao', 'excluir');
    dados.set('id', id);

    const resposta = await fetch(MC.baseUrl + 'api/documento.php', {
      method: 'POST',
      body: dados
    });

    const resultado = await resposta.json();

    if (resultado.ok) {
      await carregarDocumentos();
    } else {
      alert(resultado.erro || 'Erro ao excluir.');
    }
  } catch {
    alert('Falha de conexão.');
  }
}

/* INICIALIZAÇÃO */

document.addEventListener('DOMContentLoaded', () => {
  carregarDashboard();
  carregarConsultas();
  iniciarModal();
});