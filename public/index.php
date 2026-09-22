<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$tituloPagina = 'Início';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="hero-landing" id="inicio">
  <div class="container hero-landing-grid">

    <div class="hero-landing-text">
      <span class="eyebrow">CUIDADO QUE CONECTA</span>
      <h1>Sua saúde <span class="text-brand">conectada</span><br>a quem entende de gente.</h1>
      <p class="hero-desc">
        Agende consultas, realize teleconsultas, acesse seus exames e converse com profissionais
        de saúde de forma simples e segura.
      </p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?= e(url('agendamento.php')) ?>">📅 Agendar consulta</a>
        <a class="btn btn-outline btn-lg" href="<?= e(url('login.php')) ?>">📹 Fazer teleconsulta</a>
      </div>
    </div>

    <div class="hero-landing-visual">
      <img src="medicamed.png" alt="Profissional de saúde utilizando tablet" class="hero-image" />
      <span class="float-icon fi-calendar">📅</span>
      <span class="float-icon fi-chat">💬</span>
      <span class="float-icon fi-video">🎥</span>
      <span class="float-icon fi-doc">📄</span>
    </div>

  </div>
</section>

<section class="features-strip" id="recursos">
  <div class="container features-4">
    <article class="feature-item">
      <div class="feature-icon">🛡️</div>
      <div>
        <h3>Segurança e privacidade</h3>
        <p>Seus dados protegidos com tecnologia de ponta e em conformidade com a LGPD.</p>
      </div>
    </article>
    <article class="feature-item">
      <div class="feature-icon">📁</div>
      <div>
        <h3>Tudo em um só lugar</h3>
        <p>Exames, receitas, histórico médico e informações importantes sempre com você.</p>
      </div>
    </article>
    <article class="feature-item">
      <div class="feature-icon">👨‍⚕️</div>
      <div>
        <h3>Profissionais qualificados</h3>
        <p>Conecte-se com médicos e especialistas de confiança perto de você.</p>
      </div>
    </article>
    <article class="feature-item">
      <div class="feature-icon">❤️</div>
      <div>
        <h3>Cuidado que acompanha</h3>
        <p>Sua saúde merece atenção em cada etapa da vida.</p>
      </div>
    </article>
  </div>
</section>

<section class="section especialidades-section" id="especialidades">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">NOSSA REDE</span>
        <h2>Especialidades</h2>
      </div>
      <a href="<?= e(url('especialidades.php')) ?>" class="link-arrow">Ver todas →</a>
    </div>
    <div class="especialidades-grid">
      <?php
      $esp = [
        ['icon' => '🩺', 'nome' => 'Clínico Geral',  'desc' => 'Atendimento integral e preventivo para toda a família.',       'cor' => '#2EC4B6', 'slug' => 'clinico-geral'],
        ['icon' => '👶', 'nome' => 'Pediatria',       'desc' => 'Cuidado especializado para crianças e adolescentes.',          'cor' => '#FF6B9D', 'slug' => 'pediatria'],
        ['icon' => '❤️', 'nome' => 'Cardiologia',     'desc' => 'Diagnóstico e tratamento das doenças do coração.',            'cor' => '#FF4757', 'slug' => 'cardiologia'],
        ['icon' => '🌸', 'nome' => 'Ginecologia',     'desc' => 'Saúde da mulher em todas as fases da vida.',                  'cor' => '#A855F7', 'slug' => 'ginecologia'],
        ['icon' => '🧠', 'nome' => 'Psicologia',      'desc' => 'Saúde mental e emocional com acolhimento.',                  'cor' => '#3B82F6', 'slug' => 'psicologia'],
        ['icon' => '🦴', 'nome' => 'Ortopedia',       'desc' => 'Cuidado completo de ossos, músculos e articulações.',         'cor' => '#F59E0B', 'slug' => 'ortopedia'],
      ];
      foreach ($esp as $e): ?>
        <article class="esp-card" style="--esp-cor: <?= $e['cor'] ?>">
          <div class="esp-icon-wrap">
            <span class="esp-icon-bg" aria-hidden="true"><?= $e['icon'] ?></span>
          </div>
          <div class="esp-body">
            <h3><?= htmlspecialchars($e['nome']) ?></h3>
            <p><?= htmlspecialchars($e['desc']) ?></p>
          </div>
          <a href="especialidade.php?tipo=<?= urlencode($e['slug']) ?>" class="esp-link">
            Saiba mais <span aria-hidden="true">→</span>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt" id="como-funciona">
  <div class="container cta-center">
    <h2>Comece em 3 passos</h2>
    <ol class="steps-hero">
      <li><div><strong>Cadastre-se</strong> com seus dados e aceite a LGPD</div></li>
      <li><div><strong>Agende</strong> consultas ou gerencie exames no painel</div></li>
      <li><div><strong>Acesse</strong> documentos e histórico com segurança</div></li>
    </ol>
    <a class="btn btn-primary btn-lg" href="<?= e(url('cadastro.php')) ?>">Criar conta gratuita</a>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>