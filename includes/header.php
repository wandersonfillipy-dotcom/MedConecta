<?php

$cfg = app_config();
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$usuario = usuario_logado();

$paginaArea = 'dashboard.php';

if ($usuario) {
    $paginasPorPerfil = [
        'paciente' => 'dashboard.php',
        'profissional' => 'profissional.php',
        'cuidador' => 'cuidador.php',
        'administrador' => 'admin.php',
    ];

    $perfilAtual = $usuario['perfil'] ?? 'paciente';
    $paginaArea = $paginasPorPerfil[$perfilAtual] ?? 'dashboard.php';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
  >

  <meta
    name="description"
    content="MedConecta - Cuidado que conecta. Agende consultas e acesse seus documentos médicos."
  >

  <title>
    <?= e($tituloPagina ?? $cfg['nome']) ?> | <?= e($cfg['nome']) ?>
  </title>

  <link
    rel="stylesheet"
    href="<?= e(url('assets/css/style.css')) ?>?v=4"
  >

  <link
    rel="stylesheet"
    href="<?= e(url('assets/css/acessibilidade.css')) ?>?v=1"
  >

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
  >

  <link
    href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
  >

  <script>
    // Restaura as preferências antes de exibir a página.
    (() => {
      try {
        const raiz = document.documentElement;
        const tamanho = localStorage.getItem('mc_tamanho_fonte');

        raiz.dataset.fonte =
          ['normal', 'grande', 'maior'].includes(tamanho)
            ? tamanho
            : 'normal';

        raiz.classList.toggle(
          'alto-contraste',
          localStorage.getItem('mc_contraste') === '1'
        );

        raiz.classList.toggle(
          'modo-tea',
          localStorage.getItem('mc_modo_tea') === '1'
        );
      } catch (erro) {
        document.documentElement.dataset.fonte = 'normal';
      }
    })();
  </script>
</head>

<body>

<a class="skip-link" href="#conteudo-principal">
  Ir para o conteúdo
</a>

<header class="site-header" role="banner">
  <div class="container header-inner">

    <a
      class="logo"
      href="<?= e(url('index.php')) ?>"
      aria-label="MedConecta - página inicial"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 44 56"
        height="40"
        fill="none"
        aria-hidden="true"
      >
        <circle
          cx="22"
          cy="20"
          r="15"
          stroke="#2EC4B6"
          stroke-width="3.5"
        />

        <path
          d="M22 35 Q10 46 22 50 Q34 46 22 35Z"
          fill="#2EC4B6"
        />

        <line
          x1="22"
          y1="13"
          x2="22"
          y2="27"
          stroke="#2EC4B6"
          stroke-width="3.5"
          stroke-linecap="round"
        />

        <line
          x1="15"
          y1="20"
          x2="29"
          y2="20"
          stroke="#2EC4B6"
          stroke-width="3.5"
          stroke-linecap="round"
        />
      </svg>

      <span class="logo-text-svg">MedConecta</span>
    </a>

    <button
      type="button"
      class="menu-toggle"
      id="menu-toggle"
      aria-label="Abrir menu"
      aria-expanded="false"
      aria-controls="nav-principal"
    >
      ☰
    </button>

    <nav
      class="nav-principal"
      id="nav-principal"
      aria-label="Menu principal"
    >
      <ul>
        <li>
          <a
            href="<?= e(url('index.php')) ?>"
            <?= $paginaAtual === 'index.php'
              ? 'aria-current="page"'
              : '' ?>
          >
            <span class="tea-icon" aria-hidden="true">⌂</span>
            Início
          </a>
        </li>

        <li>
          <a
            href="<?= e(url('sobre.php')) ?>"
            <?= $paginaAtual === 'sobre.php'
              ? 'aria-current="page"'
              : '' ?>
          >
            <span class="tea-icon" aria-hidden="true">ℹ</span>
            Sobre
          </a>
        </li>

        <li>
          <a
            href="<?= e(url('locais.php')) ?>"
            <?= $paginaAtual === 'locais.php'
              ? 'aria-current="page"'
              : '' ?>
          >
            <span class="tea-icon" aria-hidden="true">⌖</span>
            Locais
          </a>
        </li>

        <?php if ($usuario): ?>
          <li>
            <a
              href="<?= e(url($paginaArea)) ?>"
              <?= $paginaAtual === $paginaArea
                ? 'aria-current="page"'
                : '' ?>
            >
              <span class="tea-icon" aria-hidden="true">▣</span>
              Minha área
            </a>
          </li>

          <?php if (usuario_tem_perfil('paciente')): ?>
            <li>
              <a
                href="<?= e(url('agendamento.php')) ?>"
                <?= $paginaAtual === 'agendamento.php'
                  ? 'aria-current="page"'
                  : '' ?>
              >
                <span class="tea-icon" aria-hidden="true">▦</span>
                Agendar
              </a>
            </li>
          <?php endif; ?>

          <?php if (usuario_tem_perfil('administrador')): ?>
            <li>
              <a
                href="<?= e(url('admin.php')) ?>"
                <?= $paginaAtual === 'admin.php'
                  ? 'aria-current="page"'
                  : '' ?>
              >
                <span class="tea-icon" aria-hidden="true">⚙</span>
                Administração
              </a>
            </li>
          <?php endif; ?>
        <?php endif; ?>

        <li>
          <a
            href="<?= e(url('contato.php')) ?>"
            <?= $paginaAtual === 'contato.php'
              ? 'aria-current="page"'
              : '' ?>
          >
            <span class="tea-icon" aria-hidden="true">✉</span>
            Contato
          </a>
        </li>
      </ul>
    </nav>

    <div class="header-actions">
      <form
        class="busca-header"
        action="<?= e(url('busca.php')) ?>"
        method="get"
        role="search"
        aria-label="Buscar no site"
      >
        <label class="sr-only" for="busca-q">Buscar</label>

        <input
          type="search"
          id="busca-q"
          name="q"
          placeholder="Buscar..."
          minlength="2"
          autocomplete="off"
        >

        <button
          type="submit"
          class="btn btn-icon btn-ghost"
          aria-label="Buscar"
        >
          🔍
        </button>
      </form>

      <details class="acessibilidade" id="acessibilidade">
        <summary>
          Acessibilidade
        </summary>

        <div class="acessibilidade-painel">
          <p><strong>Configurações de acessibilidade</strong></p>

          <button
            type="button"
            id="btn-contraste"
            class="btn btn-outline"
            aria-pressed="false"
          >
            Alto contraste
          </button>

          <fieldset class="controle-fonte">
            <legend>Tamanho da fonte</legend>

            <button
              type="button"
              data-fonte="normal"
              aria-pressed="true"
            >
              Normal
            </button>

            <button
              type="button"
              data-fonte="grande"
              aria-pressed="false"
            >
              Grande
            </button>

            <button
              type="button"
              data-fonte="maior"
              aria-pressed="false"
            >
              Maior
            </button>
          </fieldset>

          <button
            type="button"
            id="btn-modo-tea"
            class="btn btn-outline"
            aria-pressed="false"
            aria-describedby="descricao-modo-tea"
          >
            Modo TEA
          </button>

          <p id="descricao-modo-tea">
            Reduz movimentos e mostra símbolos junto aos nomes do menu.
          </p>

          <div class="temporizador-tea" id="temporizador-tea">
            <p>
              <strong>Tempo da atividade:</strong>
              <output id="tempo-restante" aria-label="Tempo restante">
                05:00
              </output>
            </p>

            <progress
              id="tempo-progresso"
              max="300"
              value="0"
              aria-label="Tempo decorrido"
            ></progress>

            <div class="temporizador-acoes">
              <button type="button" id="tempo-iniciar">
                Iniciar
              </button>

              <button type="button" id="tempo-pausar">
                Pausar
              </button>

              <button type="button" id="tempo-reiniciar">
                Reiniciar
              </button>
            </div>
          </div>
        </div>
      </details>

      <?php if ($usuario): ?>
        <span class="user-pill">
          <?= e(explode(' ', $usuario['nome'])[0]) ?>
        </span>

        <button
          type="button"
          id="btn-logout"
          class="btn btn-outline btn-sm"
        >
          Sair
        </button>
      <?php else: ?>
        <a
          href="<?= e(url('login.php')) ?>"
          class="btn btn-outline btn-sm"
        >
          Entrar
        </a>

        <a
          href="<?= e(url('cadastro.php')) ?>"
          class="btn btn-primary btn-sm"
        >
          Cadastrar
        </a>
      <?php endif; ?>
    </div>

  </div>
</header>

<main id="conteudo-principal" class="site-main">