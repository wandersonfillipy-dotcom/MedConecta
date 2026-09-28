<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_perfil(['administrador']);

$tituloPagina = 'Administração';

$usuarios = [];
$convenios = [];
$erro = '';

try {
    $pdo = db();

    $stmtUsuarios = $pdo->query(
        'SELECT
            id,
            nome,
            email,
            telefone,
            perfil,
            created_at
         FROM pacientes
         ORDER BY nome ASC'
    );

    $usuarios = $stmtUsuarios->fetchAll();

    $stmtConvenios = $pdo->query(
        'SELECT
            id,
            nome,
            descricao,
            ativo,
            created_at
         FROM convenios
         ORDER BY nome ASC'
    );

    $convenios = $stmtConvenios->fetchAll();
} catch (Throwable $e) {
    $erro = 'Não foi possível carregar os dados administrativos.';
}

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">

  <div class="container">

    <h1>Painel administrativo</h1>

    <p class="subtitle">
      Gerenciamento de usuários, perfis de acesso e convênios.
    </p>

    <div
      id="admin-feedback"
      class="feedback"
      role="alert"
      aria-live="polite"
    ></div>

    <?php if ($erro !== ''): ?>

      <div class="feedback show error" role="alert">
        <?= e($erro) ?>
      </div>

    <?php endif; ?>

    <div class="dashboard-grid">

      <article class="card">

        <h2>Usuários cadastrados</h2>

        <p>
          <strong><?= count($usuarios) ?></strong>
        </p>

      </article>

      <article class="card">

        <h2>Convênios cadastrados</h2>

        <p>
          <strong><?= count($convenios) ?></strong>
        </p>

      </article>

    </div>

    <section class="card" style="margin-top: 1rem;">

      <div class="card-header-row">
        <h2>Usuários</h2>
      </div>

      <div class="table-wrap">

        <table class="data-table">

          <thead>

            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>E-mail</th>
              <th>Telefone</th>
              <th>Perfil</th>
              <th>Data do cadastro</th>
            </tr>

          </thead>

          <tbody>

            <?php if (count($usuarios) === 0): ?>

              <tr>
                <td colspan="6">
                  Nenhum usuário cadastrado.
                </td>
              </tr>

            <?php else: ?>

              <?php foreach ($usuarios as $usuario): ?>

                <tr>

                  <td>
                    <?= (int) $usuario['id'] ?>
                  </td>

                  <td>
                    <?= e($usuario['nome']) ?>
                  </td>

                  <td>
                    <?= e($usuario['email']) ?>
                  </td>

                  <td>
                    <?= e($usuario['telefone']) ?>
                  </td>

                  <td>

                    <form class="form-perfil">

                      <input
                        type="hidden"
                        name="csrf"
                        value="<?= e(csrf_token()) ?>"
                      >

                      <input
                        type="hidden"
                        name="usuario_id"
                        value="<?= (int) $usuario['id'] ?>"
                      >

                      <select
                        name="perfil"
                        aria-label="Perfil de <?= e($usuario['nome']) ?>"
                      >

                        <option
                          value="paciente"
                          <?= $usuario['perfil'] === 'paciente'
                            ? 'selected'
                            : '' ?>
                        >
                          Paciente
                        </option>

                        <option
                          value="profissional"
                          <?= $usuario['perfil'] === 'profissional'
                            ? 'selected'
                            : '' ?>
                        >
                          Profissional
                        </option>

                        <option
                          value="cuidador"
                          <?= $usuario['perfil'] === 'cuidador'
                            ? 'selected'
                            : '' ?>
                        >
                          Cuidador
                        </option>

                        <option
                          value="administrador"
                          <?= $usuario['perfil'] === 'administrador'
                            ? 'selected'
                            : '' ?>
                        >
                          Administrador
                        </option>

                      </select>

                      <button
                        type="submit"
                        class="btn btn-primary btn-sm"
                      >
                        Salvar
                      </button>

                    </form>

                  </td>

                  <td>
                    <?= e($usuario['created_at']) ?>
                  </td>

                </tr>

              <?php endforeach; ?>

            <?php endif; ?>

          </tbody>

        </table>

      </div>

    </section>

    <section class="card" style="margin-top: 1rem;">

      <div class="card-header-row">
        <h2>Convênios</h2>
      </div>

      <div class="table-wrap">

        <table class="data-table">

          <thead>

            <tr>
              <th>ID</th>
              <th>Nome</th>
              <th>Descrição</th>
              <th>Situação</th>
            </tr>

          </thead>

          <tbody>

            <?php if (count($convenios) === 0): ?>

              <tr>
                <td colspan="4">
                  Nenhum convênio cadastrado.
                </td>
              </tr>

            <?php else: ?>

              <?php foreach ($convenios as $convenio): ?>

                <tr>

                  <td>
                    <?= (int) $convenio['id'] ?>
                  </td>

                  <td>
                    <?= e($convenio['nome']) ?>
                  </td>

                  <td>
                    <?= e($convenio['descricao']) ?>
                  </td>

                  <td>
                    <?= (int) $convenio['ativo'] === 1
                      ? 'Ativo'
                      : 'Inativo' ?>
                  </td>

                </tr>

              <?php endforeach; ?>

            <?php endif; ?>

          </tbody>

        </table>

      </div>

    </section>

  </div>

</section>

<script>
document.querySelectorAll('.form-perfil').forEach(function (form) {

  form.addEventListener('submit', async function (event) {

    event.preventDefault();

    const feedback =
      document.getElementById('admin-feedback');

    const botao =
      form.querySelector('button');

    const dados =
      new FormData(form);

    botao.disabled = true;
    botao.textContent = 'Salvando...';

    try {
      const resposta = await fetch(
        '<?= e(url('api/admin_perfil.php')) ?>',
        {
          method: 'POST',
          body: dados
        }
      );

      const resultado = await resposta.json();

      if (resultado.ok) {
        feedback.textContent =
          resultado.mensagem;

        feedback.className =
          'feedback show success';
      } else {
        feedback.textContent =
          resultado.erro ||
          'Não foi possível alterar o perfil.';

        feedback.className =
          'feedback show error';
      }
    } catch (erro) {
      feedback.textContent =
        'Erro de comunicação com o servidor.';

      feedback.className =
        'feedback show error';
    } finally {
      botao.disabled = false;
      botao.textContent = 'Salvar';
    }
  });
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>