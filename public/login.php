<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (usuario_logado()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}
$tituloPagina = 'Login';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container container-narrow">
    <h1>Entrar no MedConecta</h1>
    <p class="subtitle">Acesse receitas, atestados e seu histórico médico digital.</p>

    <form id="form-login" class="form-card" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required autocomplete="username" aria-required="true">
      </div>

      <div class="form-group">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required autocomplete="current-password" aria-required="true">
      </div>

      <div id="form-feedback" class="feedback" role="alert" aria-live="polite"></div>

      <button type="submit" class="btn btn-primary btn-lg btn-block">Entrar</button>
      <p class="form-footer">Não tem conta? <a href="<?= e(url('cadastro.php')) ?>">Cadastre-se</a></p>
    </form>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
