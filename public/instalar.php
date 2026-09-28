<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$tituloPagina = 'Guia de Instalação';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container container-narrow prose">
    <h1>Guia de instalação</h1>
    <p class="subtitle">Configure o ambiente local para desenvolver e testar o MedConecta.</p>

    <h2>Pré-requisitos</h2>
    <ul>
      <li><strong>XAMPP</strong> (PHP 8+ e MySQL) — <a href="https://www.apachefriends.org/" target="_blank" rel="noopener">apachefriends.org</a></li>
      <li>Ou PHP e MySQL instalados separadamente</li>
      <li>Navegador moderno (Chrome, Edge ou Firefox)</li>
    </ul>

    <h2>Passo a passo</h2>
    <ol class="steps-list">
      <li>Copie a pasta <code>MedConecta</code> para <code>C:\xampp\htdocs\MedConecta</code></li>
      <li>Inicie <strong>Apache</strong> e <strong>MySQL</strong> no painel do XAMPP</li>
      <li>Acesse <code>http://localhost/phpmyadmin</code> e importe <code>database/schema.sql</code></li>
      <li>Copie <code>.env.example</code> para <code>.env</code> e ajuste senha do MySQL se necessário</li>
      <li>Abra <code>http://localhost/MedConecta/public/</code> no navegador</li>
      <li>Cadastre um paciente em <strong>Cadastro</strong> e faça login</li>
    </ol>

    <h2>Alternativa: servidor embutido do PHP</h2>
    <pre><code>cd MedConecta
php -S localhost:8080 -t public</code></pre>
    <p>Acesse: <code>http://localhost:8080</code></p>

    <h2>Validação do ambiente</h2>
    <ul>
      <li>Página inicial carrega sem erro 500</li>
      <li>Cadastro retorna mensagem de sucesso</li>
      <li>Login redireciona para o dashboard</li>
      <li>Dashboard lista dados do paciente via API</li>
    </ul>

    <p>Documentação completa também em <code>README.md</code> na raiz do projeto.</p>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
