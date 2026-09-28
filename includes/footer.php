<?php $cfg = app_config(); ?>
</main>

<footer class="site-footer" role="contentinfo">
  <div class="container footer-grid">
    <div>
      <strong style="color:#fff;font-size:1.1rem;"><?= e($cfg['nome']) ?></strong>
      <p style="margin-top:.4rem;opacity:.8;"><?= e($cfg['tagline']) ?></p>
      <p style="font-size:.8rem;opacity:.6;margin-top:.5rem;">Versão <?= e($cfg['versao']) ?> 
    </div>
    <nav aria-label="Links do rodapé">
      <ul class="footer-links">
        <li><a href="<?= e(url('sobre.php')) ?>">Sobre o projeto</a></li>
        <li><a href="<?= e(url('contato.php')) ?>">Fale conosco</a></li>
      </ul>
    </nav>
    <div>
      <p><strong style="color:#fff;">Conformidade</strong></p>
      <p style="font-size:.85rem;opacity:.8;margin-top:.35rem;">Dados protegidos conforme LGPD.<br>Faculdade SENAC · Projeto acadêmico</p>
    </div>
  </div>
  <div class="container footer-bottom-inner">
    <p>&copy; <?= date('Y') ?> <?= e($cfg['nome']) ?>. Todos os direitos reservados.</p>
  </div>
</footer>

<script>
  window.MedConecta = {
    baseUrl:    <?= json_encode(url('')) ?>,
    csrfToken:  <?= json_encode(csrf_token()) ?>
  };
</script>
<script src="<?= e(url('assets/js/validacao.js')) ?>?v=3" defer></script>
<script src="<?= e(url('assets/js/app.js')) ?>?v=3" defer></script>
</body>
</html>