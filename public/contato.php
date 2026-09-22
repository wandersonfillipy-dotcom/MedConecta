<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$tituloPagina = 'Contato';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container container-narrow">
    <h1>Fale conosco</h1>
    <p class="subtitle">SAC, suporte técnico e dúvidas sobre LGPD e uso da plataforma.</p>

    <div class="cards-grid contact-channels">
      <article class="card">
        <h3>WhatsApp</h3>
        <p>(11) 99999-0000 — horário comercial</p>
      </article>
      <article class="card">
        <h3>Chat na plataforma</h3>
        <p>Disponível após login na sua área</p>
      </article>
      <article class="card">
        <h3>E-mail SAC</h3>
        <p>suporte@medconecta.app</p>
      </article>
    </div>

    <form id="form-contato" class="form-card">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-group">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required>
      </div>
      <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="assunto">Assunto</label>
        <input type="text" id="assunto" name="assunto" required>
      </div>
      <div class="form-group">
        <label for="mensagem">Mensagem</label>
        <textarea id="mensagem" name="mensagem" rows="5" required minlength="10"></textarea>
      </div>
      <div id="form-feedback" class="feedback" role="alert" aria-live="polite"></div>
      <button type="submit" class="btn btn-primary btn-lg btn-block">Enviar mensagem</button>
    </form>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
