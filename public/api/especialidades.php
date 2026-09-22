<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $pdo = db();

    if ($method === 'GET') {
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM especialidades WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            if (!$row) {
                json_response(['ok' => false, 'erro' => 'Não encontrado'], 404);
            }
            json_response(['ok' => true, 'especialidade' => $row]);
        }
        $onlyActive = !isset($_GET['todas']);
        $sql = $onlyActive
            ? 'SELECT * FROM especialidades WHERE ativo = 1 ORDER BY nome'
            : 'SELECT * FROM especialidades ORDER BY nome';
        json_response(['ok' => true, 'especialidades' => $pdo->query($sql)->fetchAll()]);
    }

    if (!usuario_logado()) {
        json_response(['ok' => false, 'erro' => 'Faça login para gerenciar'], 401);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (!validar_csrf($input['csrf'] ?? $_GET['csrf'] ?? null)) {
        json_response(['ok' => false, 'erro' => 'Token inválido'], 403);
    }

    if ($method === 'POST') {
        $nome = trim((string) ($input['nome'] ?? ''));
        $descricao = trim((string) ($input['descricao'] ?? ''));
        $icone = trim((string) ($input['icone'] ?? 'stethoscope'));
        if ($nome === '' || $descricao === '') {
            json_response(['ok' => false, 'erro' => 'Nome e descrição obrigatórios.'], 422);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO especialidades (nome, descricao, icone) VALUES (:n, :d, :i)'
        );
        $stmt->execute(['n' => $nome, 'd' => $descricao, 'i' => $icone]);
        json_response(['ok' => true, 'mensagem' => 'Especialidade criada.', 'id' => (int) $pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $id = (int) ($input['id'] ?? 0);
        $nome = trim((string) ($input['nome'] ?? ''));
        $descricao = trim((string) ($input['descricao'] ?? ''));
        $icone = trim((string) ($input['icone'] ?? 'stethoscope'));
        $ativo = !empty($input['ativo']) ? 1 : 0;
        $stmt = $pdo->prepare(
            'UPDATE especialidades SET nome=:n, descricao=:d, icone=:i, ativo=:a WHERE id=:id'
        );
        $stmt->execute(['n' => $nome, 'd' => $descricao, 'i' => $icone, 'a' => $ativo, 'id' => $id]);
        json_response(['ok' => true, 'mensagem' => 'Especialidade atualizada.']);
    }

    if ($method === 'DELETE') {
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $pdo->prepare('DELETE FROM especialidades WHERE id = :id')->execute(['id' => $id]);
        json_response(['ok' => true, 'mensagem' => 'Especialidade excluída.']);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'erro' => $e->getMessage()], 500);
}

json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);
