<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_perfil(['paciente']);

$tituloPagina = 'Meu prontuário';

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <h1>Meu prontuário</h1>
    <p class="subtitle">
      Consulte seus dados clínicos, documentos e alterações registradas.
    </p>

    <div class="dashboard-main">

      <section class="card" aria-labelledby="dados-clinicos-titulo">
        <h2 id="dados-clinicos-titulo">Dados clínicos</h2>

        <form id="form-prontuario">
          <div class="form-group">
            <label for="historico-clinico">Histórico clínico</label>
            <textarea
              id="historico-clinico"
              name="historico_clinico"
              rows="5"
              maxlength="10000"
              placeholder="Descreva informações importantes do seu histórico clínico"
            ></textarea>
          </div>

          <div class="form-group">
            <label for="alergias">Alergias</label>
            <textarea
              id="alergias"
              name="alergias"
              rows="3"
              maxlength="10000"
              placeholder="Ex.: alergia a medicamentos ou alimentos"
            ></textarea>
          </div>

          <div class="form-group">
            <label for="medicamentos">Medicamentos em uso</label>
            <textarea
              id="medicamentos"
              name="medicamentos"
              rows="3"
              maxlength="10000"
              placeholder="Informe os medicamentos que utiliza"
            ></textarea>
          </div>

          <p id="prontuario-atualizado"></p>

          <div
            id="prontuario-feedback"
            class="feedback"
            role="alert"
            aria-live="polite"
          ></div>

          <button
            type="submit"
            id="btn-salvar-prontuario"
            class="btn btn-primary"
          >
            Salvar prontuário
          </button>
        </form>
      </section>

      <section class="card" aria-labelledby="documentos-prontuario-titulo">
        <h2 id="documentos-prontuario-titulo">Documentos médicos</h2>

        <div id="prontuario-documentos" class="doc-list">
          Carregando documentos...
        </div>

        <a
          class="btn btn-ghost"
          href="<?= e(url('dashboard.php')) ?>"
          style="margin-top:1rem"
        >
          Gerenciar documentos
        </a>
      </section>

      <section class="card" aria-labelledby="alteracoes-titulo">
        <h2 id="alteracoes-titulo">Histórico de alterações</h2>

        <div id="prontuario-alteracoes">
          Carregando histórico...
        </div>
      </section>

    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const apiUrl = <?= json_encode(url('api/prontuario.php')) ?>;
  const arquivoUrl = <?= json_encode(url('api/arquivo.php')) ?>;

  const formulario = document.querySelector('#form-prontuario');
  const historico = document.querySelector('#historico-clinico');
  const alergias = document.querySelector('#alergias');
  const medicamentos = document.querySelector('#medicamentos');
  const atualizado = document.querySelector('#prontuario-atualizado');
  const documentos = document.querySelector('#prontuario-documentos');
  const alteracoes = document.querySelector('#prontuario-alteracoes');
  const feedback = document.querySelector('#prontuario-feedback');
  const botaoSalvar = document.querySelector('#btn-salvar-prontuario');

  function mostrarFeedback(mensagem, erro = false) {
    feedback.textContent = mensagem;
    feedback.style.color = erro ? '#b42318' : '#067647';
  }

  function formatarData(valor) {
    if (!valor) return '';

    const data = new Date(valor.replace(' ', 'T'));

    if (Number.isNaN(data.getTime())) {
      return valor;
    }

    return data.toLocaleString('pt-BR');
  }

  function renderizarDocumentos(lista) {
    documentos.replaceChildren();

    if (!Array.isArray(lista) || lista.length === 0) {
      documentos.textContent = 'Nenhum documento enviado ainda.';
      return;
    }

    for (const documento of lista) {
      const item = document.createElement('div');
      item.className = 'doc-item';

      const nome = document.createElement('span');
      nome.textContent =
        `${documento.tipo || 'Documento'}: ${documento.titulo || 'Sem título'}`;

      item.appendChild(nome);

      if (documento.arquivo_path) {
        const link = document.createElement('a');
        link.className = 'btn btn-ghost btn-sm';
        link.href =
          `${arquivoUrl}?id=${encodeURIComponent(documento.id)}`;
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = 'Ver arquivo';

        item.appendChild(link);
      }

      documentos.appendChild(item);
    }
  }

  function renderizarAlteracoes(lista) {
    alteracoes.replaceChildren();

    if (!Array.isArray(lista) || lista.length === 0) {
      alteracoes.textContent = 'Nenhuma alteração registrada ainda.';
      return;
    }

    const listaHtml = document.createElement('ul');

    for (const alteracao of lista) {
      const item = document.createElement('li');
      item.textContent =
        `${formatarData(alteracao.alterado_em)} — ` +
        `${alteracao.responsavel || 'Responsável não informado'}`;

      listaHtml.appendChild(item);
    }

    alteracoes.appendChild(listaHtml);
  }

  async function carregarProntuario() {
    try {
      const resposta = await fetch(apiUrl, {
        credentials: 'same-origin'
      });

      const dados = await resposta.json();

      if (!resposta.ok || !dados.ok) {
        throw new Error(dados.erro || 'Não foi possível carregar o prontuário.');
      }

      historico.value = dados.prontuario?.historico_clinico || '';
      alergias.value = dados.prontuario?.alergias || '';
      medicamentos.value = dados.prontuario?.medicamentos || '';

      atualizado.textContent = dados.prontuario?.atualizado_em
        ? `Última atualização: ${formatarData(dados.prontuario.atualizado_em)}`
        : 'O prontuário ainda não foi preenchido.';

      renderizarDocumentos(dados.documentos);
      renderizarAlteracoes(dados.alteracoes);
    } catch (erro) {
      mostrarFeedback(erro.message, true);
      documentos.textContent = 'Não foi possível carregar os documentos.';
      alteracoes.textContent = 'Não foi possível carregar o histórico.';
    }
  }

  formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    botaoSalvar.disabled = true;
    mostrarFeedback('Salvando prontuário...');

    const dadosFormulario = new FormData(formulario);
    dadosFormulario.set('csrf', window.MedConecta.csrfToken);

    try {
      const resposta = await fetch(apiUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: dadosFormulario
      });

      const dados = await resposta.json();

      if (!resposta.ok || !dados.ok) {
        throw new Error(dados.erro || 'Não foi possível salvar o prontuário.');
      }

      await carregarProntuario();
      mostrarFeedback(dados.mensagem || 'Prontuário salvo com sucesso.');
    } catch (erro) {
      mostrarFeedback(erro.message, true);
    } finally {
      botaoSalvar.disabled = false;
    }
  });

  carregarProntuario();
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>