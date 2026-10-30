<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_perfil(['paciente', 'profissional', 'administrador']);

$usuario = usuario_logado();

if (!$usuario) {
    header('Location: ' . url('login.php'));
    exit;
}

$tituloPagina = 'Chat da consulta';

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container chat-page">

    <a
      class="btn btn-ghost"
      href="<?= e(url(
          ($usuario['perfil'] ?? '') === 'profissional'
              ? 'profissional.php'
              : 'dashboard.php'
      )) ?>"
      style="margin-bottom:1rem"
    >
      ← Voltar
    </a>

    <h1>Chat da consulta</h1>

    <p class="subtitle">
      Converse com o profissional responsável pelo seu atendimento.
    </p>

    <div
      id="chat-feedback"
      class="feedback"
      role="alert"
      aria-live="polite"
    ></div>

    <section class="card chat-card" aria-label="Conversa da consulta">

      <header class="chat-topo">
        <div class="chat-profissional">
          <div class="chat-avatar" aria-hidden="true">💬</div>

          <div>
            <h2 id="chat-titulo">Carregando conversa...</h2>
            <p id="chat-subtitulo">Aguarde enquanto buscamos os dados.</p>
          </div>
        </div>

        <span id="chat-status" class="chat-status">
          Carregando
        </span>
      </header>

      <aside class="chat-aviso" role="note">
        <span aria-hidden="true">ℹ️</span>
        <div>
          <strong>Importante</strong>
          <p>
            Este chat não substitui uma consulta médica e não deve ser
            utilizado para emergências. Em uma emergência, procure um
            serviço de urgência ou ligue para o SAMU (192).
          </p>
        </div>
      </aside>

      <div
        id="chat-mensagens"
        class="chat-mensagens"
        role="log"
        aria-live="polite"
        aria-relevant="additions text"
        aria-label="Histórico de mensagens"
      >
        <div class="chat-carregando">Carregando mensagens...</div>
      </div>

      <form id="chat-formulario" class="chat-formulario" hidden>
        <div class="chat-formulario-linha">
          <div style="flex:1;min-width:0">
            <label class="sr-only" for="chat-campo">
              Digite uma mensagem ou escreva uma legenda para o anexo
            </label>

            <textarea
              id="chat-campo"
              class="chat-campo-mensagem"
              name="mensagem"
              rows="2"
              maxlength="2000"
              placeholder="Escreva sua mensagem ou legenda..."
            ></textarea>

            <div class="chat-contador">
              <span id="chat-contagem">0</span>/2000 caracteres
            </div>
          </div>
        </div>

        <div class="chat-anexo-controles">
          <div class="chat-anexo-selecao">
            <input
              type="file"
              id="chat-anexo"
              name="anexo"
              accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
              hidden
            >

            <label
              for="chat-anexo"
              id="chat-anexo-botao"
              class="btn btn-ghost"
              role="button"
              tabindex="0"
            >
              <span aria-hidden="true">📎</span>
              Anexar arquivo
            </label>

            <span
              id="chat-anexo-nome"
              class="chat-anexo-nome"
              aria-live="polite"
            >
              Nenhum arquivo selecionado
            </span>
          </div>

          <button
            id="chat-enviar"
            type="submit"
            class="btn btn-primary"
          >
            Enviar
          </button>
        </div>

        <p class="chat-anexo-ajuda">
          Formatos permitidos: PDF, JPG, PNG e WEBP. Tamanho máximo: 10 MB.
        </p>
      </form>

      <div id="chat-encerrado" class="chat-encerrado-aviso" hidden>
        Esta conversa está encerrada e não aceita novas mensagens.
      </div>

    </section>
  </div>
</section>

<style>
  .chat-anexo-controles {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 0.75rem;
  }

  .chat-anexo-selecao {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    min-width: 0;
  }

  .chat-anexo-nome {
    font-size: 0.875rem;
    overflow-wrap: anywhere;
    color: var(--text-secondary, #555);
  }

  .chat-anexo-ajuda {
    font-size: 0.8rem;
    margin: 0.5rem 0 0;
    opacity: 0.8;
  }

  .chat-anexo-mensagem {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.65rem;
    padding: 0.7rem;
    border: 1px solid var(--border-color, #ddd);
    border-radius: 0.65rem;
    overflow-wrap: anywhere;
  }

  .chat-anexo-mensagem a {
    overflow-wrap: anywhere;
    word-break: break-word;
  }

  @media (max-width: 520px) {
    .chat-anexo-controles {
      align-items: stretch;
    }

    .chat-anexo-controles > button {
      width: 100%;
    }
  }
</style>

<script>
  window.MedConectaChat = {
    consultaId: <?= json_encode(
        filter_input(INPUT_GET, 'consulta_id', FILTER_VALIDATE_INT) ?: 0
    ) ?>,
    apiUrl: <?= json_encode(url('api/chat.php')) ?>,
    csrfToken: <?= json_encode(csrf_token()) ?>,
    usuarioId: <?= json_encode((int) $usuario['id']) ?>,
    perfil: <?= json_encode((string) $usuario['perfil']) ?>
  };
</script>

<script src="<?= e(url('assets/js/chat.js')) ?>" defer></script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
