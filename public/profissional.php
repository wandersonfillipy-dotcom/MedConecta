<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_perfil(['profissional']);

$usuario = usuario_logado();

if (!$usuario) {
    header('Location: ' . url('login.php'));
    exit;
}

$tituloPagina = 'Área do profissional';

$consultas = [];
$erroConsulta = false;

try {
    $pdo = db();
    $profissionalId = (int) $usuario['id'];

    $sql = "
        SELECT
            q.id,
            q.especialidade,
            q.local,
            q.data_hora,
            q.status,
            q.medico,
            p.nome AS paciente_nome,
            c.id AS conversa_id,
            c.status AS conversa_status
        FROM consultas q
        INNER JOIN pacientes p
            ON p.id = q.paciente_id
        LEFT JOIN conversas c
            ON c.consulta_id = q.id
            AND c.profissional_id = q.profissional_id
        WHERE q.profissional_id = :profissional_id
        ORDER BY q.data_hora DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'profissional_id' => $profissionalId,
    ]);

    $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log(
        'Erro ao carregar consultas do profissional: '
        . $e->getMessage()
    );

    $erroConsulta = true;
}

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
    <div class="container">

        <h1>Área do profissional de saúde</h1>

        <p class="subtitle">
            Bem-vindo, <?= e($usuario['nome']) ?>.
            Acompanhe suas consultas e converse com seus pacientes.
        </p>

        <section class="card">
            <h2>Consultas e atendimentos</h2>

            <?php if ($erroConsulta): ?>

                <p>
                    Não foi possível carregar as consultas.
                    Tente atualizar a página.
                </p>

            <?php elseif (empty($consultas)): ?>

                <p>
                    Nenhuma consulta está vinculada ao seu perfil
                    no momento.
                </p>

            <?php else: ?>

                <p>
                    Total de atendimentos:
                    <strong><?= count($consultas) ?></strong>
                </p>

                <?php foreach ($consultas as $consulta): ?>

                    <article
                        class="card"
                        style="margin-top: 1rem;"
                    >
                        <h3>
                            <?= e($consulta['paciente_nome']) ?>
                        </h3>

                        <p>
                            <strong>Número da consulta:</strong>
                            #<?= (int) $consulta['id'] ?>
                        </p>

                        <p>
                            <strong>Especialidade:</strong>
                            <?= e($consulta['especialidade']) ?>
                        </p>

                        <p>
                            <strong>Data e horário:</strong>
                            <?= date(
                                'd/m/Y \à\s H:i',
                                strtotime($consulta['data_hora'])
                            ) ?>
                        </p>

                        <p>
                            <strong>Local:</strong>
                            <?= e($consulta['local']) ?>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <?= e(ucfirst($consulta['status'])) ?>
                        </p>

                        <?php if ($consulta['status'] === 'cancelada'): ?>

                            <p>
                                Esta consulta foi cancelada.
                                O acesso ao chat não está disponível
                                por este painel.
                            </p>

                        <?php elseif (!empty($consulta['conversa_id'])): ?>

                            <p>
                                <strong>Chat:</strong>
                                <?= $consulta['conversa_status'] === 'ativa'
                                    ? 'Conversa ativa'
                                    : 'Conversa encerrada' ?>
                            </p>

                            <a
                                class="btn btn-primary"
                                href="<?= e(url(
                                    'chat.php?consulta_id='
                                    . (int) $consulta['id']
                                )) ?>"
                            >
                                Abrir chat
                            </a>

                        <?php else: ?>

                            <p>
                                Ainda não há conversa registrada
                                para esta consulta.
                            </p>

                            <a
                                class="btn btn-primary"
                                href="<?= e(url(
                                    'chat.php?consulta_id='
                                    . (int) $consulta['id']
                                )) ?>"
                            >
                                Abrir chat
                            </a>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>
        </section>

    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
