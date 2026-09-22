<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);
}

$usuario = usuario_logado();
if (!$usuario) {
    json_response(['ok' => false, 'erro' => 'Não autenticado'], 401);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT id, nome, cpf, data_nascimento, email, telefone, created_at
         FROM pacientes WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $usuario['id']]);
    $paciente = $stmt->fetch();
    if (!$paciente) {
        json_response(['ok' => false, 'erro' => 'Paciente não encontrado'], 404);
    }

    $paciente['cpf_formatado'] = formatar_cpf($paciente['cpf']);
    $paciente['telefone_formatado'] = formatar_telefone($paciente['telefone']);

    $docs = $pdo->prepare(
        'SELECT id, tipo, titulo, especialidade, profissional, medicamentos, arquivo_path, created_at
         FROM documentos WHERE paciente_id = :id ORDER BY created_at DESC'
    );
    $docs->execute(['id' => $usuario['id']]);
    $documentos = $docs->fetchAll();

    $cons = $pdo->prepare(
        'SELECT id, medico, especialidade, local, data_hora, status
         FROM consultas WHERE paciente_id = :id ORDER BY data_hora DESC'
    );
    $cons->execute(['id' => $usuario['id']]);
    $consultas = $cons->fetchAll();

    json_response([
        'ok' => true,
        'paciente' => $paciente,
        'documentos' => $documentos,
        'consultas' => $consultas,
    ]);
} catch (Throwable) {
    json_response(['ok' => false, 'erro' => 'Erro ao carregar dados'], 500);
}
