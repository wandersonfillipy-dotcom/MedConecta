<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$tituloPagina = 'Busca';
$termo = trim((string) ($_GET['q'] ?? ''));
$paginas = paginas_site();
$resultados = [];

if (strlen($termo) >= 2) {
    $termoLower = mb_strtolower($termo, 'UTF-8');
    foreach ($paginas as $pagina) {
        $texto = mb_strtolower($pagina['titulo'] . ' ' . $pagina['palavras'], 'UTF-8');
        if (str_contains($texto, $termoLower)) {
            $resultados[] = $pagina;
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container container-narrow">
    <h1>Resultados da busca</h1>
    <?php if ($termo === ''): ?>
      <p class="subtitle">Digite um termo no campo de busca do cabeçalho.</p>
    <?php elseif (strlen($termo) < 2): ?>
      <p class="subtitle">Use pelo menos 2 caracteres para buscar.</p>
    <?php elseif (empty($resultados)): ?>
      <p class="subtitle">Nenhum resultado para <strong><?= e($termo) ?></strong>.</p>
    <?php else: ?>
      <p class="subtitle"><?= count($resultados) ?> resultado(s) para <strong><?= e($termo) ?></strong>:</p>
      <ul class="search-results">
        <?php foreach ($resultados as $r): ?>
          <li><a href="<?= e(url($r['url'])) ?>"><?= e($r['titulo']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
