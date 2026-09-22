<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$slug = $_GET['tipo'] ?? '';

if (empty($slug)) {
    header('Location: ' . url('index.php'));
    exit;
}

$detalhes = [
    'clinico-geral' => ['nome' => 'Clínico Geral',  'icon' => '🩺', 'cor' => '#2EC4B6', 'desc' => 'Atendimento integral, focado na prevenção, diagnóstico precoce e orientação contínua para a saúde da sua família.'],
    'pediatria'     => ['nome' => 'Pediatria',       'icon' => '👶', 'cor' => '#FF6B9D', 'desc' => 'Cuidado dedicado e humanizado para o desenvolvimento saudável de crianças e adolescentes.'],
    'cardiologia'   => ['nome' => 'Cardiologia',     'icon' => '❤️', 'cor' => '#FF4757', 'desc' => 'Prevenção, diagnóstico e tratamento completo de doenças do coração com alta tecnologia e precisão.'],
    'ginecologia'   => ['nome' => 'Ginecologia',     'icon' => '🌸', 'cor' => '#A855F7', 'desc' => 'Acompanhamento completo da saúde integral da mulher em todas as fases da vida.'],
    'psicologia'    => ['nome' => 'Psicologia',      'icon' => '🧠', 'cor' => '#3B82F6', 'desc' => 'Suporte emocional e clínico para o desenvolvimento da saúde mental e bem-estar.'],
    'ortopedia'     => ['nome' => 'Ortopedia',       'icon' => '🦴', 'cor' => '#F59E0B', 'desc' => 'Tratamento especializado para ossos, músculos e articulações com foco em reabilitação.'],
];

if (!array_key_exists($slug, $detalhes)) {
    header('Location: ' . url('index.php'));
    exit;
}

$info = $detalhes[$slug];

// Busca médicos — query corrigida para as colunas que existem na tabela
try {
    $stmt = db()->prepare(
        'SELECT nome, crm, descricao FROM medicos WHERE especialidade = :slug ORDER BY nome ASC'
    );
    $stmt->execute(['slug' => $slug]);
    $medicos = $stmt->fetchAll();
} catch (Throwable) {
    $medicos = [];
}

$tituloPagina = $info['nome'];
require dirname(__DIR__) . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="esp-breadcrumb">
  <div class="container">
    <a href="<?= e(url('index.php')) ?>">Início</a>
    <span aria-hidden="true">›</span>
    <a href="<?= e(url('index.php')) ?>#especialidades">Especialidades</a>
    <span aria-hidden="true">›</span>
    <span><?= e($info['nome']) ?></span>
  </div>
</div>

<!-- Hero da especialidade -->
<section class="esp-detalhe-hero" style="--esp-cor: <?= $info['cor'] ?>">
  <div class="container esp-detalhe-hero-inner">
    <div class="esp-detalhe-icon"><?= $info['icon'] ?></div>
    <div>
      <h1><?= e($info['nome']) ?></h1>
      <p><?= e($info['desc']) ?></p>
    </div>
  </div>
</section>

<!-- Banner acessibilidade -->
<section class="esp-acessibilidade">
  <div class="container">
    <div class="esp-acess-inner">
      <span class="esp-acess-icon">♿</span>
      <div>
        <strong>Compromisso com Inclusão e Melhor Idade</strong>
        <p>Todos os nossos especialistas são capacitados no atendimento humanizado a idosos, PCD e pacientes com mobilidade reduzida. Garantimos acessibilidade plena e comunicação acolhedora.</p>
      </div>
    </div>
  </div>
</section>

<!-- Médicos -->
<section class="section">
  <div class="container">
    <h2 class="section-title" style="text-align:left; margin-bottom:1.5rem">Nossos especialistas</h2>

    <?php if (empty($medicos)): ?>
      <div class="esp-vazio">
        <span>🔄</span>
        <p>Estamos atualizando nossa escala de profissionais para esta especialidade.</p>
        <p>Entre em contato via <a href="<?= e(url('contato.php')) ?>">Fale conosco</a>.</p>
      </div>
    <?php else: ?>
      <div class="medicos-grid">
        <?php foreach ($medicos as $m): ?>
          <div class="medico-card" style="--esp-cor: <?= $info['cor'] ?>">
            <div class="medico-avatar"><?= $info['icon'] ?></div>
            <div class="medico-info">
              <h3><?= e($m['nome']) ?></h3>
              <span class="medico-crm"><?= e($m['crm']) ?></span>
              <?php if (!empty($m['descricao'])): ?>
                <p class="medico-desc"><?= e($m['descricao']) ?></p>
              <?php endif; ?>
            </div>
             <a href="<?= e(url('agendamento.php')) ?>" class="btn btn-primary btn-lg" style="margin-top:1.25rem">
        📅 Agendar consulta
      </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>