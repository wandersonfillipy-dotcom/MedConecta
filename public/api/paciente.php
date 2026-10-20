<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_response([
        'ok' => false,
        'erro' => 'Método não permitido.'
    ], 405);
}

$usuario = usuario_logado();

if (!$usuario) {
    json_response([
        'ok' => false,
        'erro' => 'Não autenticado.'
    ], 401);
}

if (($usuario['perfil'] ?? '') !== 'paciente') {
    json_response([
        'ok' => false,
        'erro' => 'Acesso permitido somente ao paciente.'
    ], 403);
}

try {
    $pdo = db();

    // Dados do paciente autenticado.
    $stmt = $pdo->prepare(
        'SELECT
            id,
            nome,
            cpf,
            data_nascimento,
            email,
            telefone,
            created_at
         FROM pacientes
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $usuario['id']
    ]);

    $paciente = $stmt->fetch();

    if (!$paciente) {
        json_response([
            'ok' => false,
            'erro' => 'Paciente não encontrado.'
        ], 404);
    }

    $paciente['cpf_formatado'] = formatar_cpf($paciente['cpf']);
    $paciente['telefone_formatado'] = formatar_telefone(
        $paciente['telefone']
    );

    // Documentos pertencentes ao paciente autenticado.
    $docs = $pdo->prepare(
        'SELECT
            id,
            tipo,
            titulo,
            especialidade,
            profissional,
            medicamentos,
            arquivo_path,
            created_at
         FROM documentos
         WHERE paciente_id = :id
         ORDER BY created_at DESC'
    );

    $docs->execute([
        'id' => $usuario['id']
    ]);

    $documentos = $docs->fetchAll();

    // Consultas e eventual conversa de chat vinculada.
    $cons = $pdo->prepare(
        'SELECT
            q.id,
            q.medico,
            q.especialidade,
            q.local,
            q.data_hora,
            q.status,
            q.profissional_id,
            c.id AS conversa_id,
            c.status AS chat_status
         FROM consultas q
         LEFT JOIN conversas c
            ON c.consulta_id = q.id
         WHERE q.paciente_id = :id
         ORDER BY q.data_hora DESC'
    );

    $cons->execute([
        'id' => $usuario['id']
    ]);

    $consultas = $cons->fetchAll();

    json_response([
        'ok' => true,
        'paciente' => $paciente,
        'documentos' => $documentos,
        'consultas' => $consultas
    ]);
} catch (Throwable $erro) {
    error_log('MedConecta paciente API: ' . $erro->getMessage());

    json_response([
        'ok' => false,
        'erro' => 'Erro ao carregar os dados do paciente.'
    ], 500);
}
