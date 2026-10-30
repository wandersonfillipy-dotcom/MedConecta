<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$usuario = usuario_logado();

if (!$usuario) {
    json_response([
        'ok' => false,
        'erro' => 'Não autenticado.'
    ], 401);
}

$usuarioId = (int) $usuario['id'];
$perfil = (string) ($usuario['perfil'] ?? '');
$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

if ($metodo === 'GET') {
    /*
     * O paciente consulta seu próprio prontuário.
     * Um profissional só consulta o prontuário de um paciente
     * que o autorizou.
     */
    if ($perfil === 'paciente') {
        $pacienteId = $usuarioId;
    } elseif ($perfil === 'profissional') {
        $pacienteId = filter_input(
            INPUT_GET,
            'paciente_id',
            FILTER_VALIDATE_INT
        );

        if (!$pacienteId || $pacienteId <= 0) {
            json_response([
                'ok' => false,
                'erro' => 'Informe um paciente válido.'
            ], 422);
        }

        try {
            $stmt = db()->prepare(
                'SELECT id
                 FROM autorizacoes_prontuario
                 WHERE paciente_id = :paciente_id
                   AND profissional_id = :profissional_id
                 LIMIT 1'
            );

            $stmt->execute([
                'paciente_id' => $pacienteId,
                'profissional_id' => $usuarioId
            ]);

            if (!$stmt->fetch()) {
                json_response([
                    'ok' => false,
                    'erro' => 'Acesso ao prontuário não autorizado.'
                ], 403);
            }
        } catch (Throwable $erro) {
            json_response([
                'ok' => false,
                'erro' => 'Erro ao verificar autorização.'
            ], 500);
        }
    } else {
        json_response([
            'ok' => false,
            'erro' => 'Acesso negado.'
        ], 403);
    }

    try {
        $stmt = db()->prepare(
            'SELECT id, historico_clinico, alergias,
                    medicamentos, atualizado_em
             FROM prontuarios
             WHERE paciente_id = :paciente_id
             LIMIT 1'
        );

        $stmt->execute(['paciente_id' => $pacienteId]);
        $prontuario = $stmt->fetch();

        $stmt = db()->prepare(
            'SELECT id, tipo, titulo, data_documento,
                    especialidade, profissional, medicamentos,
                    arquivo_path, created_at
             FROM documentos
             WHERE paciente_id = :paciente_id
             ORDER BY created_at DESC, id DESC'
        );

        $stmt->execute(['paciente_id' => $pacienteId]);
        $documentos = $stmt->fetchAll();

        $stmt = db()->prepare(
            'SELECT a.id, a.alterado_em, p.nome AS responsavel
             FROM prontuario_alteracoes AS a
             INNER JOIN prontuarios AS pr
                 ON pr.id = a.prontuario_id
             INNER JOIN pacientes AS p
                 ON p.id = a.responsavel_id
             WHERE pr.paciente_id = :paciente_id
             ORDER BY a.alterado_em DESC, a.id DESC'
        );

        $stmt->execute(['paciente_id' => $pacienteId]);
        $alteracoes = $stmt->fetchAll();

        json_response([
            'ok' => true,
            'prontuario' => $prontuario ?: [
                'historico_clinico' => '',
                'alergias' => '',
                'medicamentos' => '',
                'atualizado_em' => null
            ],
            'documentos' => $documentos,
            'alteracoes' => $alteracoes
        ]);
    } catch (Throwable $erro) {
        json_response([
            'ok' => false,
            'erro' => 'Erro ao consultar o prontuário.'
        ], 500);
    }
}

if ($metodo !== 'POST') {
    json_response([
        'ok' => false,
        'erro' => 'Método não permitido.'
    ], 405);
}

/*
 * Nesta etapa, apenas o paciente altera seu próprio prontuário.
 */
if ($perfil !== 'paciente') {
    json_response([
        'ok' => false,
        'erro' => 'Acesso negado.'
    ], 403);
}

if (!validar_csrf($_POST['csrf'] ?? null)) {
    json_response([
        'ok' => false,
        'erro' => 'Token inválido.'
    ], 403);
}

$historicoClinico = trim(
    (string) ($_POST['historico_clinico'] ?? '')
);

$alergias = trim(
    (string) ($_POST['alergias'] ?? '')
);

$medicamentos = trim(
    (string) ($_POST['medicamentos'] ?? '')
);

if (
    mb_strlen($historicoClinico) > 10000
    || mb_strlen($alergias) > 10000
    || mb_strlen($medicamentos) > 10000
) {
    json_response([
        'ok' => false,
        'erro' => 'Cada campo deve ter até 10.000 caracteres.'
    ], 422);
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    /*
     * Garante que o prontuário do paciente exista.
     */
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO prontuarios (paciente_id)
         VALUES (:paciente_id)'
    );

    $stmt->execute(['paciente_id' => $usuarioId]);

    /*
     * Bloqueia o registro enquanto lê os valores anteriores
     * e grava a nova versão.
     */
    $stmt = $pdo->prepare(
        'SELECT id, historico_clinico, alergias, medicamentos
         FROM prontuarios
         WHERE paciente_id = :paciente_id
         FOR UPDATE'
    );

    $stmt->execute(['paciente_id' => $usuarioId]);
    $anterior = $stmt->fetch();

    if (!$anterior) {
        throw new RuntimeException('Prontuário não encontrado.');
    }

    $stmt = $pdo->prepare(
        'UPDATE prontuarios
         SET historico_clinico = :historico_clinico,
             alergias = :alergias,
             medicamentos = :medicamentos
         WHERE id = :id'
    );

    $stmt->execute([
        'historico_clinico' => $historicoClinico,
        'alergias' => $alergias,
        'medicamentos' => $medicamentos,
        'id' => $anterior['id']
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO prontuario_alteracoes (
             prontuario_id,
             responsavel_id,
             historico_clinico_anterior,
             alergias_anteriores,
             medicamentos_anteriores,
             historico_clinico_novo,
             alergias_novas,
             medicamentos_novos
         ) VALUES (
             :prontuario_id,
             :responsavel_id,
             :historico_clinico_anterior,
             :alergias_anteriores,
             :medicamentos_anteriores,
             :historico_clinico_novo,
             :alergias_novas,
             :medicamentos_novos
         )'
    );

    $stmt->execute([
        'prontuario_id' => $anterior['id'],
        'responsavel_id' => $usuarioId,
        'historico_clinico_anterior' =>
            $anterior['historico_clinico'],
        'alergias_anteriores' =>
            $anterior['alergias'],
        'medicamentos_anteriores' =>
            $anterior['medicamentos'],
        'historico_clinico_novo' =>
            $historicoClinico,
        'alergias_novas' =>
            $alergias,
        'medicamentos_novos' =>
            $medicamentos
    ]);

    $pdo->commit();

    json_response([
        'ok' => true,
        'mensagem' => 'Prontuário atualizado com sucesso.'
    ]);
} catch (Throwable $erro) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'ok' => false,
        'erro' => 'Erro ao salvar o prontuário.'
    ], 500);
}