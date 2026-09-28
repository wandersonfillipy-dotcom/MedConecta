<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_perfil(['profissional']);

$tituloPagina = 'Área do profissional';

require dirname(__DIR__) . '/includes/header.php';

$usuario = usuario_logado();
?>

<section class="section">

    <div class="container">

        <h1>
            Área do profissional de saúde
        </h1>

        <p class="subtitle">
            Bem-vindo,
            <?= e($usuario['nome']) ?>.
        </p>

        <div class="dashboard-grid">

            <section class="card">

                <h2>Pacientes autorizados</h2>

                <p>
                    O profissional poderá acessar somente os
                    prontuários dos pacientes que concederam
                    autorização.
                </p>

                <p>
                    Nenhum paciente autorizou o acesso até o momento.
                </p>

            </section>

            <section class="card">

                <h2>Atendimentos</h2>

                <p>
                    Consulte os atendimentos e sessões agendadas.
                </p>

            </section>

        </div>

    </div>

</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>