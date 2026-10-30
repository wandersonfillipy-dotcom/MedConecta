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
        // Ajustado para cruzar as tabelas e trazer o nome do médico e da especialidade
        $stmt = db()->prepare(
            'SELECT c.*, u.nome as medico, e.nome as especialidade_nome 
             FROM consultas c 
             INNER JOIN pacientes u ON c.profissional_id = u.id 
             LEFT JOIN especialidades e ON c.especialidade_id = e.id 
             WHERE c.paciente_id = :id 
             ORDER BY c.data_hora ASC'
        );
        $stmt->execute(['id' => $usuario['id']]);
        json_response(['ok' => true, 'consultas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) {
        json_response(['ok' => false, 'erro' => 'Erro ao listar consultas: ' . $e->getMessage()], 500);
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

    // Recebe os IDs (se o frontend JS continuar enviando 'medico', ele pega o ID contido nele)
    $profissional_id = (int) ($input['profissional_id'] ?? $input['medico'] ?? 0);
    $especialidade_id = (int) ($input['especialidade_id'] ?? 0);
    $local = trim((string) ($input['local'] ?? 'MedConecta — Telemedicina'));
    $dataHoraCrua = (string) ($input['data_hora'] ?? '');

    if ($profissional_id === 0 || $dataHoraCrua === '') {
        json_response(['ok' => false, 'erro' => 'Preencha médico, especialidade e data/hora.'], 422);
    }

    // Converte a data do HTML datetime-local para o formato do MySQL (YYYY-MM-DD HH:MM:SS)
    $dataHora = date('Y-m-d H:i:s', strtotime($dataHoraCrua));

    try {
        // ATENÇÃO: A sua tabela `consultas` precisa ter as colunas `profissional_id` e `especialidade_id`
        $stmt = db()->prepare(
            'INSERT INTO consultas (paciente_id, profissional_id, especialidade_id, local, data_hora, status)
             VALUES (:pid, :pro_id, :esp_id, :l, :dh, "agendada")'
        );
        $stmt->execute([
            'pid'    => $usuario['id'],
            'pro_id' => $profissional_id,
            'esp_id' => $especialidade_id,
            'l'      => $local,
            'dh'     => $dataHora,
        ]);
        
        json_response([
            'ok' => true,
            'mensagem' => 'Consulta agendada com sucesso!',
            'confirmacao' => [
                'data_hora' => $dataHora,
            ],
        ]);
    } catch (Throwable $e) {
        // Exibe o erro real do banco de dados na mensagem vermelha para descobrirmos o que falta
        json_response(['ok' => false, 'erro' => 'Erro do BD: ' . $e->getMessage()], 500);
    }
}

json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);