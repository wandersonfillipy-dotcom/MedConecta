<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$usuario = usuario_logado();
if (!$usuario) {
    json_response(['ok' => false, 'erro' => 'Faça login para agendar'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = db()->prepare(
            'SELECT * FROM consultas WHERE paciente_id = :id ORDER BY data_hora ASC'
        );
        $stmt->execute(['id' => $usuario['id']]);
        json_response(['ok' => true, 'consultas' => $stmt->fetchAll()]);
    } catch (Throwable) {
        json_response(['ok' => false, 'erro' => 'Erro ao listar consultas'], 500);
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!validar_csrf($input['csrf'] ?? null)) {
        json_response(['ok' => false, 'erro' => 'Token inválido'], 403);
    }

    $acao = $input['acao'] ?? 'criar';

    if ($acao === 'cancelar') {
        $id = (int) ($input['id'] ?? 0);
        $stmt = db()->prepare(
            'UPDATE consultas SET status = "cancelada" WHERE id = :id AND paciente_id = :pid'
        );
        $stmt->execute(['id' => $id, 'pid' => $usuario['id']]);
        json_response(['ok' => true, 'mensagem' => 'Consulta cancelada.']);
    }

    $medico = trim((string) ($input['medico'] ?? ''));
    $especialidade = trim((string) ($input['especialidade'] ?? ''));
    $local = trim((string) ($input['local'] ?? 'MedConecta — Telemedicina'));
    $dataHora = (string) ($input['data_hora'] ?? '');

    if ($medico === '' || $especialidade === '' || $dataHora === '') {
        json_response(['ok' => false, 'erro' => 'Preencha médico, especialidade e data/hora.'], 422);
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO consultas (paciente_id, medico, especialidade, local, data_hora, status)
             VALUES (:pid, :m, :e, :l, :dh, "agendada")'
        );
        $stmt->execute([
            'pid' => $usuario['id'],
            'm' => $medico,
            'e' => $especialidade,
            'l' => $local,
            'dh' => $dataHora,
        ]);
        json_response([
            'ok' => true,
            'mensagem' => 'Consulta agendada com sucesso!',
            'confirmacao' => [
                'medico' => $medico,
                'especialidade' => $especialidade,
                'data_hora' => $dataHora,
            ],
        ]);
    } catch (Throwable) {
        json_response(['ok' => false, 'erro' => 'Erro ao agendar'], 500);
    }
}

json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);
