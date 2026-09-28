/* MedConecta — app.js */

const MC = window.MedConecta || { baseUrl: '/', csrfToken: '' };

function $(sel, ctx = document) { return ctx.querySelector(sel); }
function $$(sel, ctx = document) { return [...ctx.querySelectorAll(sel)]; }

function feedback(el, msg, tipo = 'erro') {
  if (!el) return;
  el.textContent = msg;
  el.className = 'feedback feedback-' + tipo;
}

async function apiPost(endpoint, data) {
  data.csrf = MC.csrfToken;
  const res = await fetch(MC.baseUrl + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  return res.json();
}

async function apiGet(endpoint) {
  const res = await fetch(MC.baseUrl + endpoint);
  return res.json();
}

function bindForm(formId, endpoint, onSuccess) {
  const form = $(formId);
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fb  = form.querySelector('[role="alert"]');
    const btn = form.querySelector('[type="submit"]');
    btn.disabled = true;
    feedback(fb, 'Aguarde…', 'info');
    const data = Object.fromEntries(new FormData(form));
    try {
      const json = await apiPost(endpoint, data);
      if (json.ok) {
        onSuccess(json, fb, form);
      } else {
        const err = json.erros ? json.erros.join(' ') : (json.erro || 'Erro desconhecido.');
        feedback(fb, err, 'erro');
      }
    } catch {
      feedback(fb, 'Falha de conexão. Verifique se o servidor está rodando.', 'erro');
    } finally {
      btn.disabled = false;
    }
  });
}

/* ── LOGIN ── */
bindForm('#form-login', 'api/login.php', (json) => {
  location.href = json.redirect || MC.baseUrl + 'dashboard.php';
});

/* ── CADASTRO ── */
bindForm('#form-cadastro', 'api/cadastro.php', (json) => {
  location.href = json.redirect || MC.baseUrl + 'login.php';
});

/* ── CONTATO ── */
bindForm('#form-contato', 'api/contato.php', (json, fb, form) => {
  feedback(fb, json.mensagem, 'sucesso');
  form.reset();
});

/* ── AGENDAMENTO ── */
const selMedico = $('#medico');
const inpEspec  = $('#especialidade');
const mapEspec  = {
  'Dra. Ana Silva':    'Clínica Geral',
  'Dr. Carlos Mendes': 'Cardiologia',
  'Dra. Marina Costa': 'Pediatria',
  'Dr. Paulo Ribeiro': 'Neurologia',
};
if (selMedico && inpEspec) {
  selMedico.addEventListener('change', () => {
    inpEspec.value = mapEspec[selMedico.value] || '';
  });
}

bindForm('#form-agendamento', 'api/agendamento.php', (json) => {
  const conf = $('#confirmacao-agendamento');
  const txt  = $('#confirmacao-texto');
  if (conf && txt && json.confirmacao) {
    const c  = json.confirmacao;
    const dt = new Date(c.data_hora).toLocaleString('pt-BR');
    txt.textContent = `${c.medico} · ${c.especialidade} · ${dt}`;
    conf.classList.remove('hidden');
  }
  carregarConsultas();
});

async function carregarConsultas() {
  const lista = $('#agenda-lista');
  if (!lista) return;
  try {
    const json = await apiGet('api/agendamento.php');
    if (!json.ok || !json.consultas.length) {
      lista.innerHTML = '<p style="color:var(--text-muted);font-size:.9rem;padding:.5rem 0">Nenhuma consulta agendada.</p>';
      return;
    }
    lista.innerHTML = json.consultas.map(c => `
      <div class="consulta-item">
        <div>
          <strong>${c.medico}</strong> · ${c.especialidade}
          <small>${new Date(c.data_hora).toLocaleString('pt-BR')} · ${c.local}</small>
        </div>
        <span class="status-badge status-${c.status}">${c.status}</span>
      </div>`).join('');
  } catch { /* silencioso */ }
}

/* ── DASHBOARD ── */
async function carregarDashboard() {
  const dashNome = $('#dash-nome');
  if (!dashNome) return;
  try {
    const json = await apiGet('api/paciente.php');
    if (!json.ok) return;
    dashNome.textContent = json.paciente.nome.split(' ')[0];
    renderPerfil(json.paciente);
    renderConsultasDash(json.consultas);
  } catch { /* banco não configurado */ }

  // Carrega documentos separadamente via api/documento.php
  await carregarDocumentos();
}

async function carregarDocumentos() {
  const el = $('#lista-documentos');
  if (!el) return;
  try {
    const json = await apiGet('api/documento.php');
    if (!json.ok) {
      el.innerHTML = '<p style="color:var(--danger);font-size:.9rem;">Erro ao carregar documentos.</p>';
      return;
    }
    renderDocumentos(json.documentos);
  } catch {
    el.innerHTML = '<p style="color:var(--text-muted);font-size:.9rem;">Não foi possível carregar documentos.</p>';
  }
}

function renderPerfil(p) {
  const el = $('#profile-data');
  if (!el) return;
  el.innerHTML = `
    <dt>Nome</dt><dd>${p.nome}</dd>
    <dt>E-mail</dt><dd>${p.email}</dd>
    <dt>CPF</dt><dd>${p.cpf_formatado}</dd>
    <dt>Telefone</dt><dd>${p.telefone_formatado}</dd>
    <dt>Nascimento</dt><dd>${new Date(p.data_nascimento + 'T12:00:00').toLocaleDateString('pt-BR')}</dd>
    <dt>Cadastro</dt><dd>${new Date(p.created_at).toLocaleDateString('pt-BR')}</dd>
  `;
}

function iconeTipo(tipo) {
  const mapa = { 'Receita':'💊', 'Exame':'🔬', 'Atestado':'📋', 'Laudo':'📝', 'Outro':'📄' };
  return mapa[tipo] || '📄';
}

function renderDocumentos(docs) {
  const el = $('#lista-documentos');
  if (!el) return;

  if (!docs || !docs.length) {
    el.innerHTML = `
      <div class="doc-vazio">
        <span>📂</span>
        <p>Nenhum documento cadastrado ainda.</p>
        <small>Clique em "+ Adicionar documento" para enviar receitas, exames e atestados.</small>
      </div>`;
    return;
  }

  el.innerHTML = docs.map(d => {
    const icone      = iconeTipo(d.tipo);
    const data       = new Date(d.created_at).toLocaleDateString('pt-BR');
    const temArquivo = d.arquivo_path && d.arquivo_path !== '';
    const isPdf      = temArquivo && d.arquivo_path.toLowerCase().endsWith('.pdf');

    return `
      <div class="doc-item" role="listitem" data-id="${d.id}">
        <div class="doc-item-icone">${icone}</div>
        <div class="doc-item-body">
          <div class="doc-item-topo">
            <strong class="doc-item-titulo">${d.titulo}</strong>
            <span class="doc-tipo-badge">${d.tipo}</span>
          </div>
          ${d.especialidade ? `<small>🩺 ${d.especialidade}</small>` : ''}
          ${d.profissional  ? `<small>👨‍⚕️ ${d.profissional}</small>`  : ''}
          ${d.medicamentos  ? `<small>💊 ${d.medicamentos}</small>`   : ''}
          <small class="doc-data">📅 ${data}</small>
        </div>
        <div class="doc-item-acoes">
          ${temArquivo ? `
            <button type="button"
              class="btn btn-sm btn-outline btn-ver-doc"
              data-path="${MC.baseUrl}${d.arquivo_path}"
              data-tipo="${isPdf ? 'pdf' : 'imagem'}"
              data-titulo="${d.titulo}">
              👁️ Ver
            </button>
            <a class="btn btn-sm btn-ghost"
              href="${MC.baseUrl}${d.arquivo_path}"
              download>
              ⬇️ Baixar
            </a>
          ` : '<span class="doc-sem-arquivo">Sem arquivo</span>'}
          <button type="button"
            class="btn btn-sm btn-ghost btn-excluir-doc"
            data-id="${d.id}"
            style="color:var(--danger)">
            🗑️
          </button>
        </div>
      </div>`;
  }).join('');

  // Eventos dos botões gerados dinamicamente
  $$('.btn-ver-doc').forEach(btn => {
    btn.addEventListener('click', () => {
      abrirVisualizador(btn.dataset.path, btn.dataset.tipo, btn.dataset.titulo);
    });
  });

  $$('.btn-excluir-doc').forEach(btn => {
    btn.addEventListener('click', () => excluirDocumento(btn.dataset.id));
  });
}

function renderConsultasDash(cons) {
  const el = $('#lista-consultas');
  if (!el) return;
  if (!cons || !cons.length) {
    el.innerHTML = '<p style="color:var(--text-muted);font-size:.9rem;margin-bottom:1rem;">Nenhuma consulta registrada.</p>';
    return;
  }
  el.innerHTML = cons.map(c => `
    <div class="consulta-item">
      <div>
        <strong>${c.medico}</strong> · ${c.especialidade}
        <small>${new Date(c.data_hora).toLocaleString('pt-BR')} · ${c.local}</small>
      </div>
      <span class="status-badge status-${c.status}">${c.status}</span>
    </div>`).join('');
}

/* Filtro de documentos */
const filtroDocs = $('#filtro-docs');
if (filtroDocs) {
  filtroDocs.addEventListener('input', () => {
    const q = filtroDocs.value.toLowerCase();
    $$('.doc-item').forEach(item => {
      item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

/* ── LOGOUT ── */
const btnLogout = $('#btn-logout');
if (btnLogout) {
  btnLogout.addEventListener('click', async () => {
    try { await apiPost('api/logout.php', {}); } catch {}
    location.href = MC.baseUrl + 'index.php';
  });
}

/* ══════════════════════════════════════
   MODAL DE UPLOAD
══════════════════════════════════════ */
function iniciarModal() {
  const modalUpload   = $('#modal-upload');
  const btnAbrirModal = $('#btn-abrir-upload');
  const btnFechar     = $('#btn-fechar-modal');
  const btnCancelar   = $('#btn-cancelar-modal');
  const inputArquivo  = $('#doc-arquivo');
  const uploadArea    = $('#upload-area');
  const placeholder   = $('#upload-placeholder');
  const preview       = $('#upload-preview');
  const previewNome   = $('#upload-preview-nome');
  const previewIcon   = $('#upload-preview-icon');
  const btnRemover    = $('#btn-remover-arquivo');
  const formUpload    = $('#form-upload');

  if (!modalUpload || !btnAbrirModal) return;

  function abrirModal() {
    modalUpload.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function fecharModal() {
    modalUpload.classList.add('hidden');
    document.body.style.overflow = '';
  }

  btnAbrirModal.addEventListener('click', abrirModal);
  btnFechar?.addEventListener('click', fecharModal);
  btnCancelar?.addEventListener('click', fecharModal);
  modalUpload.addEventListener('click', (e) => {
    if (e.target === modalUpload) fecharModal();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') fecharModal();
  });

  function mostrarPreview(file) {
    if (!file || !previewNome || !previewIcon || !placeholder || !preview) return;
    previewNome.textContent = file.name;
    previewIcon.textContent = file.type === 'application/pdf' ? '📄' : '🖼️';
    placeholder.classList.add('hidden');
    preview.classList.remove('hidden');
  }

  function limparPreview() {
    if (!inputArquivo || !preview || !placeholder) return;
    inputArquivo.value = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
  }

  inputArquivo?.addEventListener('change', () => {
    if (inputArquivo.files[0]) mostrarPreview(inputArquivo.files[0]);
  });

  btnRemover?.addEventListener('click', limparPreview);

  uploadArea?.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
  });
  uploadArea?.addEventListener('dragleave', () => {
    uploadArea.classList.remove('drag-over');
  });
  uploadArea?.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) { inputArquivo.files = e.dataTransfer.files; mostrarPreview(file); }
  });

  formUpload?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fb  = $('#upload-feedback');
    const btn = $('#btn-salvar-doc');
    btn.disabled = true;
    feedback(fb, 'Salvando…', 'info');

    const formData = new FormData(formUpload);
    formData.set('csrf', MC.csrfToken);

    try {
      const res  = await fetch(MC.baseUrl + 'api/documento.php', {
        method: 'POST',
        body: formData,
      });
      const json = await res.json();
      if (json.ok) {
        feedback(fb, '✅ ' + json.mensagem, 'sucesso');
        formUpload.reset();
        limparPreview();
        setTimeout(async () => {
          fecharModal();
          await carregarDocumentos();
        }, 1200);
      } else {
        feedback(fb, json.erro || 'Erro ao salvar.', 'erro');
      }
    } catch {
      feedback(fb, 'Falha de conexão.', 'erro');
    } finally {
      btn.disabled = false;
    }
  });
}

/* ══════════════════════════════════════
   VISUALIZADOR DE DOCUMENTOS
══════════════════════════════════════ */
function abrirVisualizador(path, tipo, titulo) {
  $('#visualizador-modal')?.remove();

  const modal = document.createElement('div');
  modal.id = 'visualizador-modal';
  modal.className = 'modal-overlay';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');

  modal.innerHTML = `
    <div class="modal-box modal-box-visualizador">
      <div class="modal-header">
        <h3 style="font-size:1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:75%">
          ${titulo}
        </h3>
        <div style="display:flex;gap:.5rem;align-items:center;flex-shrink:0">
          <a href="${path}" download class="btn btn-sm btn-outline">⬇️ Baixar</a>
          <button type="button" class="modal-fechar" id="btn-fechar-visualizador" aria-label="Fechar">✕</button>
        </div>
      </div>
      <div class="visualizador-corpo">
        ${tipo === 'pdf'
          ? `<iframe src="${path}" class="visualizador-iframe" title="${titulo}"></iframe>`
          : `<div class="visualizador-img-wrap">
               <img src="${path}" alt="${titulo}" class="visualizador-img">
             </div>`
        }
      </div>
    </div>`;

  document.body.appendChild(modal);
  document.body.style.overflow = 'hidden';

  $('#btn-fechar-visualizador')?.addEventListener('click', fecharVisualizador);
  modal.addEventListener('click', (e) => {
    if (e.target === modal) fecharVisualizador();
  });
}

function fecharVisualizador() {
  $('#visualizador-modal')?.remove();
  document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') fecharVisualizador();
});

/* ══════════════════════════════════════
   EXCLUIR DOCUMENTO
══════════════════════════════════════ */
async function excluirDocumento(id) {
  if (!confirm('Tem certeza que deseja excluir este documento?')) return;
  try {
    const formData = new FormData();
    formData.set('csrf', MC.csrfToken);
    formData.set('acao', 'excluir');
    formData.set('id', id);

    const res  = await fetch(MC.baseUrl + 'api/documento.php', {
      method: 'POST',
      body: formData,
    });
    const json = await res.json();

    if (json.ok) {
      await carregarDocumentos();
    } else {
      alert(json.erro || 'Erro ao excluir.');
    }
  } catch {
    alert('Falha de conexão.');
  }
}

/* ══════════════════════════════════════
   INICIALIZAÇÃO — espera o DOM carregar
══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  carregarDashboard();
  carregarConsultas();
  iniciarModal();
});