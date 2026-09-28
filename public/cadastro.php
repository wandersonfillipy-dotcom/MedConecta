<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (usuario_logado()) {
    header('Location: ' . url('dashboard.php'));
    exit;
}
$tituloPagina = 'Cadastro de Paciente';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container container-narrow">
    <h1>Cadastro de paciente</h1>
    <p class="subtitle">Preencha seus dados para acessar o MedConecta com segurança.</p>

    <form id="form-cadastro" class="form-card" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <div class="form-group">
        <label for="nome">Nome completo <span aria-hidden="true">*</span></label>
        <input type="text" id="nome" name="nome" required minlength="3" autocomplete="name" aria-required="true">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="cpf">CPF <span aria-hidden="true">*</span></label>
          <input type="text" id="cpf" name="cpf" required maxlength="14" inputmode="numeric" data-mask="cpf" aria-required="true" aria-describedby="cpf-hint">
          <small id="cpf-hint" class="hint">Somente números — validação automática</small>
        </div>
        <div class="form-group">
          <label for="data_nascimento">Data de nascimento <span aria-hidden="true">*</span></label>
          <input type="date" id="data_nascimento" name="data_nascimento" required aria-required="true">
        </div>
      </div>

      <div class="form-group">
        <label for="email">E-mail <span aria-hidden="true">*</span></label>
        <input type="email" id="email" name="email" required autocomplete="email" aria-required="true">
      </div>

      <div class="form-group">
        <label for="telefone">Telefone <span aria-hidden="true">*</span></label>
        <input type="tel" id="telefone" name="telefone" required data-mask="telefone" autocomplete="tel" aria-required="true">
      </div>

      <div class="form-group">
        <label for="senha">Senha <span aria-hidden="true">*</span></label>
        <input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password" aria-required="true" aria-describedby="senha-hint">
        <small id="senha-hint" class="hint">Mínimo de 8 caracteres</small>
      </div>

      <div class="form-group checkbox-group">
        <input type="checkbox" id="lgpd" name="lgpd" value="1" required aria-required="true">
        <label for="lgpd">
          Li e aceito o tratamento dos meus dados conforme a <strong>LGPD</strong> para fins de saúde e uso da plataforma MedConecta.
        </label>
      </div>

      <div id="form-feedback" class="feedback" role="alert" aria-live="polite"></div>

      <button type="submit" class="btn btn-primary btn-lg btn-block">Cadastrar</button>
      <p class="form-footer">Já possui conta? <a href="<?= e(url('login.php')) ?>">Fazer login</a></p>
    </form>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
