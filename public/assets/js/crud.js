const MedConectaCrud = (function () {
  'use strict';

  const cfg = () => window.MedConecta || { baseUrl: '/', csrfToken: '' };

  function api(path) {
    const base = cfg().baseUrl.replace(/\/?$/, '/');
    return base + path.replace(/^\//, '');
  }

  function showFb(id, msg, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.className = 'feedback show ' + (ok ? 'success' : 'error');
  }

  async function request(url, method, body) {
    const opts = {
      method,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    };
    if (body && method !== 'GET') {
      opts.body = JSON.stringify({ ...body, csrf: cfg().csrfToken });
    }
    const res = await fetch(url, opts);
    return res.json();
  }

  function fmtDate(d) {
    if (!d) return '';
    const p = String(d).split(/[T\s]/)[0].split('-');
    return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : d;
  }

  function fmtDateTime(d) {
    const dt = new Date(d);
    return Number.isNaN(dt.getTime()) ? d : dt.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
  }

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  function statusBadge(status) {
    const map = { disponivel: 'success', agendada: 'info', pendente: 'warn', cancelada: 'muted', cancelado: 'muted', realizada: 'success' };
    return `<span class="badge-status ${map[status] || ''}">${esc(status)}</span>`;
  }

  function initExames() {
    const form = document.getElementById('form-exame');
    const tbody = document.querySelector('#tabela-exames tbody');
    const cancelBtn = document.getElementById('exame-cancelar');
    const titulo = document.getElementById('form-exame-titulo');

    async function load() {
      const data = await request(api('api/exames.php'), 'GET');
      if (!data.ok) {
        tbody.innerHTML = `<tr><td colspan="5">${esc(data.erro)}</td></tr>`;
        return;
      }
      if (!data.exames.length) {
        tbody.innerHTML = '<tr><td colspan="5">Nenhum exame cadastrado.</td></tr>';
        return;
      }
      tbody.innerHTML = data.exames
        .map(
          (e) => `<tr>
          <td>${esc(e.nome)}</td><td>${fmtDate(e.data_exame)}</td><td>${esc(e.tipo)}</td>
          <td>${statusBadge(e.status)}</td>
          <td class="actions">
            <button type="button" class="btn btn-sm btn-ghost" data-edit-exame='${JSON.stringify(e)}'>Editar</button>
            <button type="button" class="btn btn-sm btn-danger" data-del-exame="${e.id}">Excluir</button>
          </td></tr>`
        )
        .join('');
      tbody.querySelectorAll('[data-edit-exame]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const e = JSON.parse(btn.getAttribute('data-edit-exame'));
          document.getElementById('exame-id').value = e.id;
          document.getElementById('exame-nome').value = e.nome;
          document.getElementById('exame-data').value = e.data_exame;
          document.getElementById('exame-tipo').value = e.tipo;
          document.getElementById('exame-status').value = e.status;
          titulo.textContent = 'Editar exame';
          cancelBtn.hidden = false;
        });
      });
      tbody.querySelectorAll('[data-del-exame]').forEach((btn) => {
        btn.addEventListener('click', async () => {
          if (!confirm('Excluir este exame?')) return;
          const r = await request(api('api/exames.php'), 'DELETE', { id: +btn.dataset.delExame });
          showFb('crud-feedback-exames', r.mensagem || r.erro, r.ok);
          if (r.ok) load();
        });
      });
    }

    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = document.getElementById('exame-id').value;
      const payload = {
        nome: document.getElementById('exame-nome').value,
        data_exame: document.getElementById('exame-data').value,
        tipo: document.getElementById('exame-tipo').value,
        status: document.getElementById('exame-status').value,
      };
      const r = id
        ? await request(api('api/exames.php'), 'PUT', { id: +id, ...payload })
        : await request(api('api/exames.php'), 'POST', payload);
      showFb('crud-feedback-exames', r.mensagem || r.erro, r.ok);
      if (r.ok) {
        form.reset();
        document.getElementById('exame-id').value = '';
        titulo.textContent = 'Novo exame';
        cancelBtn.hidden = true;
        load();
      }
    });

    cancelBtn?.addEventListener('click', () => {
      form.reset();
      document.getElementById('exame-id').value = '';
      titulo.textContent = 'Novo exame';
      cancelBtn.hidden = true;
    });

    load();
  }

  function initConsultas() {
    const form = document.getElementById('form-consulta');
    const tbody = document.querySelector('#tabela-consultas tbody');
    const cancelBtn = document.getElementById('consulta-cancelar');
    const titulo = document.getElementById('form-consulta-titulo');

    async function load() {
      const data = await request(api('api/agendamento.php'), 'GET');
      if (!data.ok) {
        tbody.innerHTML = `<tr><td colspan="6">${esc(data.erro)}</td></tr>`;
        return;
      }
      if (!data.consultas.length) {
        tbody.innerHTML = '<tr><td colspan="6">Nenhuma consulta.</td></tr>';
        return;
      }
      tbody.innerHTML = data.consultas
        .map(
          (c) => `<tr>
          <td>${esc(c.medico)}</td><td>${esc(c.especialidade)}</td>
          <td>${fmtDateTime(c.data_hora)}</td><td>${esc(c.local)}</td>
          <td>${statusBadge(c.status)}</td>
          <td class="actions">
            <button type="button" class="btn btn-sm btn-ghost" data-edit-consulta='${JSON.stringify(c).replace(/'/g, '&#39;')}'>Editar</button>
            <button type="button" class="btn btn-sm btn-danger" data-del-consulta="${c.id}">Excluir</button>
          </td></tr>`
        )
        .join('');
      tbody.querySelectorAll('[data-edit-consulta]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const c = JSON.parse(btn.getAttribute('data-edit-consulta'));
          document.getElementById('consulta-id').value = c.id;
          document.getElementById('consulta-medico').value = c.medico;
          document.getElementById('consulta-especialidade').value = c.especialidade;
          document.getElementById('consulta-local').value = c.local;
          document.getElementById('consulta-data').value = String(c.data_hora).replace(' ', 'T').slice(0, 16);
          document.getElementById('consulta-status').value = c.status;
          titulo.textContent = 'Editar consulta';
          cancelBtn.hidden = false;
        });
      });
      tbody.querySelectorAll('[data-del-consulta]').forEach((btn) => {
        btn.addEventListener('click', async () => {
          if (!confirm('Excluir esta consulta?')) return;
          const r = await request(api('api/agendamento.php'), 'DELETE', { id: +btn.dataset.delConsulta });
          showFb('crud-feedback-consultas', r.mensagem || r.erro, r.ok);
          if (r.ok) load();
        });
      });
    }

    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = document.getElementById('consulta-id').value;
      const payload = {
        medico: document.getElementById('consulta-medico').value,
        especialidade: document.getElementById('consulta-especialidade').value,
        local: document.getElementById('consulta-local').value,
        data_hora: document.getElementById('consulta-data').value,
        status: document.getElementById('consulta-status').value,
      };
      const r = id
        ? await request(api('api/agendamento.php'), 'PUT', { id: +id, ...payload })
        : await request(api('api/agendamento.php'), 'POST', payload);
      showFb('crud-feedback-consultas', r.mensagem || r.erro, r.ok);
      if (r.ok) {
        form.reset();
        document.getElementById('consulta-id').value = '';
        document.getElementById('consulta-local').value = 'MedConecta — Telemedicina';
        titulo.textContent = 'Nova consulta';
        cancelBtn.hidden = true;
        load();
      }
    });

    cancelBtn?.addEventListener('click', () => {
      form.reset();
      document.getElementById('consulta-id').value = '';
      titulo.textContent = 'Nova consulta';
      cancelBtn.hidden = true;
    });

    load();
  }

  function initEspecialidades() {
    const form = document.getElementById('form-especialidade');
    const tbody = document.querySelector('#tabela-especialidades tbody');
    const cancelBtn = document.getElementById('esp-cancelar');
    const titulo = document.getElementById('form-esp-titulo');

    async function load() {
      const data = await request(api('api/especialidades.php?todas=1'), 'GET');
      if (!data.ok) {
        tbody.innerHTML = `<tr><td colspan="5">${esc(data.erro)}</td></tr>`;
        return;
      }
      tbody.innerHTML = data.especialidades
        .map(
          (e) => `<tr>
          <td>${esc(e.nome)}</td><td>${esc(e.descricao)}</td><td>${esc(e.icone)}</td>
          <td>${e.ativo == 1 ? 'Sim' : 'Não'}</td>
          <td class="actions">
            <button type="button" class="btn btn-sm btn-ghost" data-edit-esp='${JSON.stringify(e).replace(/'/g, '&#39;')}'>Editar</button>
            <button type="button" class="btn btn-sm btn-danger" data-del-esp="${e.id}">Excluir</button>
          </td></tr>`
        )
        .join('');
      tbody.querySelectorAll('[data-edit-esp]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const e = JSON.parse(btn.getAttribute('data-edit-esp'));
          document.getElementById('esp-id').value = e.id;
          document.getElementById('esp-nome').value = e.nome;
          document.getElementById('esp-descricao').value = e.descricao;
          document.getElementById('esp-icone').value = e.icone;
          document.getElementById('esp-ativo').checked = e.ativo == 1;
          titulo.textContent = 'Editar especialidade';
          cancelBtn.hidden = false;
        });
      });
      tbody.querySelectorAll('[data-del-esp]').forEach((btn) => {
        btn.addEventListener('click', async () => {
          if (!confirm('Excluir especialidade?')) return;
          const r = await request(api('api/especialidades.php'), 'DELETE', { id: +btn.dataset.delEsp });
          showFb('crud-feedback-esp', r.mensagem || r.erro, r.ok);
          if (r.ok) load();
        });
      });
    }

    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = document.getElementById('esp-id').value;
      const payload = {
        nome: document.getElementById('esp-nome').value,
        descricao: document.getElementById('esp-descricao').value,
        icone: document.getElementById('esp-icone').value,
        ativo: document.getElementById('esp-ativo').checked ? 1 : 0,
      };
      const r = id
        ? await request(api('api/especialidades.php'), 'PUT', { id: +id, ...payload })
        : await request(api('api/especialidades.php'), 'POST', payload);
      showFb('crud-feedback-esp', r.mensagem || r.erro, r.ok);
      if (r.ok) {
        form.reset();
        document.getElementById('esp-id').value = '';
        document.getElementById('esp-ativo').checked = true;
        titulo.textContent = 'Nova especialidade';
        cancelBtn.hidden = true;
        load();
      }
    });

    cancelBtn?.addEventListener('click', () => {
      form.reset();
      document.getElementById('esp-id').value = '';
      titulo.textContent = 'Nova especialidade';
      cancelBtn.hidden = true;
    });

    load();
  }

  return { initExames, initConsultas, initEspecialidades };
})();
