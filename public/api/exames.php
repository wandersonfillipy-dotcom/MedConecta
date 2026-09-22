<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$usuario = usuario_logado();
if (!$usuario) {
    json_response(['ok' => false, 'erro' => 'Faça login'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$pid = (int) $usuario['id'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $pdo = db();

    if ($method === 'GET') {
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM exames WHERE id = :id AND paciente_id = :pid');
            $stmt->execute(['id' => $id, 'pid' => $pid]);
            $row = $stmt->fetch();
            if (!$row) {
                json_response(['ok' => false, 'erro' => 'Exame não encontrado'], 404);
            }
            json_response(['ok' => true, 'exame' => $row]);
        }
        $stmt = $pdo->prepare('SELECT * FROM exames WHERE paciente_id = :pid ORDER BY data_exame DESC');
        $stmt->execute(['pid' => $pid]);
        json_response(['ok' => true, 'exames' => $stmt->fetchAll()]);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (in_array($method, ['POST', 'PUT', 'DELETE'], true) && !validar_csrf($input['csrf'] ?? $_GET['csrf'] ?? null)) {
        json_response(['ok' => false, 'erro' => 'Token inválido'], 403);
    }

    if ($method === 'POST') {
        $nome = trim((string) ($input['nome'] ?? ''));
        $dataExame = (string) ($input['data_exame'] ?? '');
        $tipo = trim((string) ($input['tipo'] ?? ''));
        $status = (string) ($input['status'] ?? 'pendente');
        if ($nome === '' || $tipo === '' || $dataExame === '') {
            json_response(['ok' => false, 'erro' => 'Preencha nome, tipo e data.'], 422);
        }
        if (!in_array($status, ['pendente', 'disponivel', 'cancelado'], true)) {
            $status = 'pendente';
        }
        $stmt = $pdo->prepare(
            'INSERT INTO exames (paciente_id, nome, data_exame, tipo, status) VALUES (:pid, :n, :d, :t, :s)'
        );
        $stmt->execute(['pid' => $pid, 'n' => $nome, 'd' => $dataExame, 't' => $tipo, 's' => $status]);
        json_response(['ok' => true, 'mensagem' => 'Exame cadastrado.', 'id' => (int) $pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $id = (int) ($input['id'] ?? 0);
        $nome = trim((string) ($input['nome'] ?? ''));
        $dataExame = (string) ($input['data_exame'] ?? '');
        $tipo = trim((string) ($input['tipo'] ?? ''));
        $status = (string) ($input['status'] ?? 'pendente');
        if ($id < 1) {
            json_response(['ok' => false, 'erro' => 'ID inválido'], 422);
        }
        $stmt = $pdo->prepare(
            'UPDATE exames SET nome=:n, data_exame=:d, tipo=:t, status=:s
             WHERE id=:id AND paciente_id=:pid'
        );
        $stmt->execute(['n' => $nome, 'd' => $dataExame, 't' => $tipo, 's' => $status, 'id' => $id, 'pid' => $pid]);
        json_response(['ok' => true, 'mensagem' => 'Exame atualizado.']);
    }

    if ($method === 'DELETE') {
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM exames WHERE id = :id AND paciente_id = :pid');
        $stmt->execute(['id' => $id, 'pid' => $pid]);
        json_response(['ok' => true, 'mensagem' => 'Exame excluído.']);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'erro' => 'Erro no servidor: ' . $e->getMessage()], 500);
}

json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);
