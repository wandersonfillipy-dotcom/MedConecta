<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$tituloPagina = 'Sobre';
require dirname(__DIR__) . '/includes/header.php';
$cfg = app_config();
?>

<!-- Hero da página Sobre com logo de fundo -->
<section class="sobre-hero">
  <div class="sobre-hero-bg-logo" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 56" fill="none">
      <circle cx="22" cy="20" r="15" stroke="white" stroke-width="3.5"/>
      <path d="M22 35 Q10 46 22 50 Q34 46 22 35Z" fill="white"/>
      <line x1="22" y1="13" x2="22" y2="27" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
      <line x1="15" y1="20" x2="29" y2="20" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
    </svg>
  </div>
  
  <div class="container sobre-hero-inner">
    <div class="sobre-logo-inline" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 56" height="52" fill="none">
        <circle cx="22" cy="20" r="15" stroke="white" stroke-width="3.5"/>
        <path d="M22 35 Q10 46 22 50 Q34 46 22 35Z" fill="white"/>
        <line x1="22" y1="13" x2="22" y2="27" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
        <line x1="15" y1="20" x2="29" y2="20" stroke="white" stroke-width="3.5" stroke-linecap="round"/>
      </svg>
      <span>MedConecta</span>
    </div>
    <h1>Sobre o MedConecta</h1>
    <p>Tecnologia, acessibilidade e cuidado humano unidos por um propósito.</p>
  </div>
</section>

<section class="section">
  <div class="container container-narrow prose">

    <p class="lead">
      Nossa plataforma foi desenhada para ser o elo de confiança entre profissionais de saúde
      e pacientes. Com foco em acessibilidade — especialmente para pessoas com TEA e PcD —
      desenvolvemos ferramentas que facilitam o cuidado e garantem a segurança das informações.
    </p>

    <h2>Proposta de valor</h2>
    <p>
      O MedConecta eleva o padrão de gestão de clínicas ao combinar tecnologia de alta performance
      com acessibilidade inclusiva. Eliminamos a burocracia operacional para que profissionais de
      saúde foquem no que é essencial, garantindo que o cuidado médico seja eficiente para a
      clínica e verdadeiramente acessível para todos os pacientes.
    </p>

    <div class="sobre-cards">
      <div class="sobre-card">
        <span class="sobre-card-icon">🎯</span>
        <h3>Missão</h3>
        <p>Conectar pacientes e profissionais de saúde com tecnologia acessível e segura.</p>
      </div>
      <div class="sobre-card">
        <span class="sobre-card-icon">👁️</span>
        <h3>Visão</h3>
        <p>Ser a plataforma de saúde digital mais inclusiva do Brasil.</p>
      </div>
      <div class="sobre-card">
        <span class="sobre-card-icon">💎</span>
        <h3>Valores</h3>
        <p>Acessibilidade, transparência, segurança e cuidado humano.</p>
      </div>
    </div>

    <h2>Para quem é</h2>
    <ul>
      <li>Pacientes que buscam autonomia na saúde</li>
      <li>Idosos e pessoas com TEA ou deficiências visuais/auditivas</li>
      <li>Médicos e enfermeiros que precisam de agilidade no atendimento</li>
      <li>Clínicas, laboratórios e planos de saúde (modelo B2B futuro)</li>
    </ul>

    <h2>Relacionamento e canais</h2>
    <p>
      Suporte via WhatsApp, chat na plataforma e SAC dedicado. Comunicação transparente
      e em conformidade com a LGPD. Acesso via navegador desktop e mobile.
    </p>

    <div class="sobre-equipe">
      <h2>Projeto acadêmico</h2>
      <p>Desenvolvido na <strong>Faculdade SENAC</strong> <?= date('Y') ?></p>
      <div class="sobre-badges">
    
      </div>
    </div>

  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>