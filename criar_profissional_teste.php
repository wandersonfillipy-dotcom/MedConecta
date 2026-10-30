<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = db();

$email = 'carlos.mendes@medconecta.local';
$senha = 'MedConecta@2026';

function gerarCpfTeste(): string
{
    do {
        $base = '';

        for ($i = 0; $i < 9; $i++) {
            $base .= (string) random_int(0, 9);
        }
    } while (preg_match('/^(\d)\1{8}$/', $base));

    $calcularDigito = static function (string $numeros, int $pesoInicial): int {
        $soma = 0;

        for ($i = 0; $i < strlen($numeros); $i++) {
            $soma += (int) $numeros[$i] * ($pesoInicial - $i);
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    };

    $base .= (string) $calcularDigito($base, 10);
    $base .= (string) $calcularDigito($base, 11);

    return $base;
}

try {
    $pdo->beginTransaction();

    $consultaUsuario = $pdo->prepare(
        'SELECT id FROM pacientes WHERE email = :email LIMIT 1'
    );
    $consultaUsuario->execute(['email' => $email]);

    $usuarioId = $consultaUsuario->fetchColumn();

    if ($usuarioId) {
        $usuarioId = (int) $usuarioId;

        $atualizarPerfil = $pdo->prepare(
            "UPDATE pacientes
             SET perfil = 'profissional'
             WHERE id = :id"
        );
        $atualizarPerfil->execute(['id' => $usuarioId]);
    } else {
        $cpf = gerarCpfTeste();

        $inserir = $pdo->prepare(
            'INSERT INTO pacientes (
                nome,
                cpf,
                data_nascimento,
                email,
                telefone,
                senha_hash,
                lgpd_aceite,
                perfil
            ) VALUES (
                :nome,
                :cpf,
                :data_nascimento,
                :email,
                :telefone,
                :senha_hash,
                1,
                :perfil
            )'
        );

        $inserir->execute([
            'nome' => 'Dr. Carlos Mendes',
            'cpf' => $cpf,
            'data_nascimento' => '1985-01-15',
            'email' => $email,
            'telefone' => '61999990000',
            'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
            'perfil' => 'profissional',
        ]);

        $usuarioId = (int) $pdo->lastInsertId();
    }

    // Confirma que a consulta de teste pertence ao paciente esperado
    // e corresponde ao atendimento do Dr. Carlos Mendes.
    $consulta = $pdo->prepare(
        "SELECT id
         FROM consultas
         WHERE id = 1
           AND paciente_id = 3
           AND medico = 'Dr. Carlos Mendes'
         LIMIT 1"
    );
    $consulta->execute();

    if (!$consulta->fetchColumn()) {
        throw new RuntimeException(
            'A consulta de teste esperada não foi encontrada. Nenhuma alteração foi confirmada.'
        );
    }

    // Vincula o profissional à consulta sem alterar o histórico.
    $vincularConsulta = $pdo->prepare(
        'UPDATE consultas
         SET profissional_id = :profissional_id
         WHERE id = 1 AND paciente_id = 3'
    );
    $vincularConsulta->execute([
        'profissional_id' => $usuarioId,
    ]);

    // Se a conversa já existir, preserva as mensagens e atualiza
    // somente o profissional responsável.
    $vincularConversa = $pdo->prepare(
        'UPDATE conversas
         SET profissional_id = :profissional_id
         WHERE consulta_id = 1 AND paciente_id = 3'
    );
    $vincularConversa->execute([
        'profissional_id' => $usuarioId,
    ]);

    $pdo->commit();

    echo "Profissional de teste configurado com sucesso.\n";
    echo "Nome: Dr. Carlos Mendes\n";
    echo "E-mail: {$email}\n";
    echo "Senha: {$senha}\n";
    echo "ID do profissional: {$usuarioId}\n";
    echo "Consulta de teste: 1\n";
    echo "Histórico de mensagens preservado.\n";
} catch (Throwable $erro) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(
        STDERR,
        "Não foi possível configurar o profissional: "
        . $erro->getMessage()
        . PHP_EOL
    );

    exit(1);
}
