<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

exigir_perfil(['cuidador']);

$tituloPagina = 'Área do cuidador';

require dirname(__DIR__) . '/includes/header.php';

$usuario = usuario_logado();
?>

<section class="section">

    <div class="container">

        <h1>
            Área do cuidador
        </h1>

        <p class="subtitle">
            Bem-vindo,
            <?= e($usuario['nome']) ?>.
        </p>

        <div class="dashboard-grid">

            <section class="card">

                <h2>Pacientes vinculados</h2>

                <p>
                    O cuidador visualizará somente os pacientes
                    que autorizaram seu vínculo.
                </p>

                <p>
                    Nenhum paciente vinculado até o momento.
                </p>

            </section>

            <section class="card">

                <h2>Acompanhamento</h2>

                <p>
                    Acompanhe informações e atividades permitidas
                    pelo paciente responsável.
                </p>

            </section>

        </div>

    </div>

</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>