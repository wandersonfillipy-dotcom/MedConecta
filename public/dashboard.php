<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_perfil(['paciente']);

$tituloPagina = 'Minha área';

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <h1>Olá, <span id="dash-nome">...</span>!</h1>
    <p class="subtitle">
      Centralize seus documentos médicos e consultas em um só lugar.
    </p>

    <div class="dashboard-grid">

      <!-- Perfil -->
      <aside class="card profile-card" aria-labelledby="perfil-titulo">
        <h2 id="perfil-titulo">Seus dados</h2>
        <dl class="profile-list" id="profile-data">
          <dt>Carregando...</dt><dd></dd>
        </dl>

        <a
          class="btn btn-primary"
          href="<?= e(url('prontuario.php')) ?>"
          style="margin-top:1.25rem"
        >
          📋 Meu prontuário
        </a>
      </aside>

      <div class="dashboard-main">

        <!-- Documentos -->
        <section class="card" aria-labelledby="docs-titulo">
          <div class="card-header-row">
            <h2 id="docs-titulo">Documentos médicos</h2>

            <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
              <label class="sr-only" for="filtro-docs">Buscar documentos</label>
              <input
                type="search"
                id="filtro-docs"
                class="input-search"
                placeholder="Buscar..."
                aria-label="Filtrar documentos"
              >

              <button
                type="button"
                class="btn btn-primary btn-sm"
                id="btn-abrir-upload"
              >
                + Adicionar documento
              </button>
            </div>
          </div>

          <!-- Modal de upload -->
          <div
            id="modal-upload"
            class="modal-overlay hidden"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-titulo"
          >
            <div class="modal-box">
              <div class="modal-header">
                <h3 id="modal-titulo">Adicionar documento</h3>
                <button
                  type="button"
                  class="modal-fechar"
                  id="btn-fechar-modal"
                  aria-label="Fechar"
                >✕</button>
              </div>

              <form id="form-upload" enctype="multipart/form-data" novalidate>

                <div class="form-group">
                  <label for="doc-tipo">Tipo de documento</label>
                  <select id="doc-tipo" name="tipo" required>
                    <option value="">Selecione...</option>
                    <option value="Exame">Exame</option>
                    <option value="Receita">Receita médica</option>
                    <option value="Laudo">Laudo médico</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="doc-titulo">Título</label>
                  <input
                    type="text"
                    id="doc-titulo"
                    name="titulo"
                    maxlength="200"
                    required
                    placeholder="Ex: Exame de sangue"
                  >
                </div>

                <div class="form-group">
                  <label for="doc-data">Data do documento</label>
                  <input
                    type="date"
                    id="doc-data"
                    name="data_documento"
                    required
                  >
                </div>

                <div class="form-group">
                  <label for="doc-especialidade">Especialidade</label>
                  <input
                    type="text"
                    id="doc-especialidade"
                    name="especialidade"
                    placeholder="Ex: Cardiologia"
                  >
                </div>

                <div class="form-group">
                  <label for="doc-profissional">Profissional</label>
                  <input
                    type="text"
                    id="doc-profissional"
                    name="profissional"
                    placeholder="Ex: Dr. Carlos Mendes"
                  >
                </div>

                <div class="form-group">
                  <label for="doc-medicamentos">
                    Medicamentos (se houver)
                  </label>
                  <input
                    type="text"
                    id="doc-medicamentos"
                    name="medicamentos"
                    placeholder="Ex: Amoxicilina 500 mg"
                  >
                </div>

                <div class="form-group">
                  <label for="doc-arquivo">
                    Arquivo
                    <span style="color:var(--text-muted);font-weight:400">
                      (PDF, JPG ou PNG)
                    </span>
                  </label>

                  <div class="upload-area" id="upload-area">
                    <input
                      type="file"
                      id="doc-arquivo"
                      name="arquivo"
                      accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                      class="upload-input"
                      required
                    >

                    <div class="upload-placeholder" id="upload-placeholder">
                      <span class="upload-icon">📎</span>
                      <p>Clique ou arraste o arquivo aqui</p>
                      <small>PDF, JPG ou PNG — máximo 5 MB</small>
                    </div>

                    <div class="upload-preview hidden" id="upload-preview">
                      <span id="upload-preview-icon">📄</span>
                      <span id="upload-preview-nome"></span>
                      <button
                        type="button"
                        class="upload-remover"
                        id="btn-remover-arquivo"
                        aria-label="Remover arquivo"
                      >✕</button>
                    </div>
                  </div>
                </div>

                <div
                  id="upload-feedback"
                  class="feedback"
                  role="alert"
                  aria-live="polite"
                ></div>

                <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.25rem;">
                  <button
                    type="button"
                    class="btn btn-ghost"
                    id="btn-cancelar-modal"
                  >Cancelar</button>

                  <button
                    type="submit"
                    class="btn btn-primary"
                    id="btn-salvar-doc"
                  >Salvar documento</button>
                </div>

              </form>
            </div>
          </div>

          <div id="lista-documentos" class="doc-list" role="list"></div>
        </section>

        <!-- Consultas -->
        <section class="card" aria-labelledby="consultas-titulo">
          <h2 id="consultas-titulo">Consultas</h2>
          <div id="lista-consultas" class="consulta-list"></div>

          <a
            class="btn btn-primary"
            href="<?= e(url('agendamento.php')) ?>"
            style="margin-top:.5rem"
          >
            📅 Agendar nova consulta
          </a>
        </section>

      </div>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>