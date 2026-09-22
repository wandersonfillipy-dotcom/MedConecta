<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$usuario = usuario_logado();

if (!$usuario) {
    json_response(['ok' => false, 'erro' => 'Não autenticado'], 401);
}

if (($usuario['perfil'] ?? '') !== 'paciente') {
    json_response(['ok' => false, 'erro' => 'Acesso negado'], 403);
}

$pacienteId = (int) $usuario['id'];

/*
 * GET: lista os documentos do paciente logado.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = db()->prepare(
            'SELECT id, tipo, titulo, data_documento,
                    especialidade, profissional, medicamentos,
                    arquivo_path, created_at
             FROM documentos
             WHERE paciente_id = :paciente_id
             ORDER BY created_at DESC, id DESC'
        );

        $stmt->execute(['paciente_id' => $pacienteId]);

        json_response([
            'ok' => true,
            'documentos' => $stmt->fetchAll()
        ]);
    } catch (Throwable $erro) {
        json_response([
            'ok' => false,
            'erro' => 'Erro ao listar documentos.'
        ], 500);
    }
}

/*
 * Somente POST pode cadastrar ou excluir documentos.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'ok' => false,
        'erro' => 'Método não permitido'
    ], 405);
}

if (!validar_csrf($_POST['csrf'] ?? null)) {
    json_response([
        'ok' => false,
        'erro' => 'Token inválido'
    ], 403);
}

/*
 * POST com acao=excluir: exclui um documento do paciente logado.
 */
if (($_POST['acao'] ?? '') === 'excluir') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        json_response([
            'ok' => false,
            'erro' => 'ID inválido'
        ], 422);
    }

    try {
        $stmt = db()->prepare(
            'SELECT arquivo_path
             FROM documentos
             WHERE id = :id AND paciente_id = :paciente_id'
        );

        $stmt->execute([
            'id' => $id,
            'paciente_id' => $pacienteId
        ]);

        $documento = $stmt->fetch();

        if (!$documento) {
            json_response([
                'ok' => false,
                'erro' => 'Documento não encontrado'
            ], 404);
        }

        $stmt = db()->prepare(
            'DELETE FROM documentos
             WHERE id = :id AND paciente_id = :paciente_id'
        );

        $stmt->execute([
            'id' => $id,
            'paciente_id' => $pacienteId
        ]);

        if (!empty($documento['arquivo_path'])) {
            $prefixoEsperado = 'storage/documentos/' . $pacienteId . '/';
            $caminhoRelativo = (string) $documento['arquivo_path'];

            if (
                str_starts_with($caminhoRelativo, $prefixoEsperado)
                && basename($caminhoRelativo) === substr(
                    $caminhoRelativo,
                    strlen($prefixoEsperado)
                )
            ) {
                $caminhoCompleto = dirname(__DIR__, 2)
                    . '/'
                    . $caminhoRelativo;

                if (is_file($caminhoCompleto)) {
                    unlink($caminhoCompleto);
                }
            }
        }

        registrar_log(
            'documento',
            "Documento excluído: $id",
            $pacienteId
        );

        json_response([
            'ok' => true,
            'mensagem' => 'Documento excluído.'
        ]);
    } catch (Throwable $erro) {
        json_response([
            'ok' => false,
            'erro' => 'Erro ao excluir documento.'
        ], 500);
    }
}

/*
 * POST sem acao=excluir: cadastra um novo documento.
 */
$tipo = trim((string) ($_POST['tipo'] ?? ''));
$titulo = trim((string) ($_POST['titulo'] ?? ''));
$dataDocumento = trim((string) ($_POST['data_documento'] ?? ''));
$especialidade = trim((string) ($_POST['especialidade'] ?? ''));
$profissional = trim((string) ($_POST['profissional'] ?? ''));
$medicamentos = trim((string) ($_POST['medicamentos'] ?? ''));

if (!in_array($tipo, ['Exame', 'Receita', 'Laudo'], true)) {
    json_response([
        'ok' => false,
        'erro' => 'Selecione Exame, Receita ou Laudo.'
    ], 422);
}

if ($titulo === '' || mb_strlen($titulo) > 200) {
    json_response([
        'ok' => false,
        'erro' => 'Informe um título de até 200 caracteres.'
    ], 422);
}

$data = DateTimeImmutable::createFromFormat('!Y-m-d', $dataDocumento);

if (!$data || $data->format('Y-m-d') !== $dataDocumento) {
    json_response([
        'ok' => false,
        'erro' => 'Informe uma data válida.'
    ], 422);
}

/*
 * Verifica se um arquivo foi realmente enviado.
 */
$arquivo = $_FILES['arquivo'] ?? null;

if (
    !is_array($arquivo)
    || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    || empty($arquivo['tmp_name'])
    || !is_uploaded_file($arquivo['tmp_name'])
) {
    json_response([
        'ok' => false,
        'erro' => 'Selecione um arquivo para enviar.'
    ], 422);
}

/*
 * Limite do arquivo: 5 MB.
 */
$tamanhoMaximo = 5 * 1024 * 1024;

if (
    !isset($arquivo['size'])
    || $arquivo['size'] <= 0
    || $arquivo['size'] > $tamanhoMaximo
) {
    json_response([
        'ok' => false,
        'erro' => 'O arquivo deve ter até 5 MB.'
    ], 422);
}

/*
 * Verifica o conteúdo do arquivo, não apenas seu nome.
 */
$detector = new finfo(FILEINFO_MIME_TYPE);
$mime = $detector->file($arquivo['tmp_name']);

$formatosPermitidos = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png'
];

if (!isset($formatosPermitidos[$mime])) {
    json_response([
        'ok' => false,
        'erro' => 'Formato não permitido. Use PDF, JPG ou PNG.'
    ], 422);
}

/*
 * Cria uma pasta separada para cada paciente.
 */
$pasta = dirname(__DIR__, 2)
    . '/storage/documentos/'
    . $pacienteId;

if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
    json_response([
        'ok' => false,
        'erro' => 'Não foi possível criar a pasta do documento.'
    ], 500);
}

$nomeArquivo = bin2hex(random_bytes(16))
    . '.'
    . $formatosPermitidos[$mime];

$caminhoCompleto = $pasta . '/' . $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
    json_response([
        'ok' => false,
        'erro' => 'Não foi possível salvar o arquivo.'
    ], 500);
}

$caminhoBanco = 'storage/documentos/'
    . $pacienteId
    . '/'
    . $nomeArquivo;

/*
 * Grava os dados do documento no MySQL.
 */
try {
    $stmt = db()->prepare(
        'INSERT INTO documentos
            (paciente_id, tipo, titulo, data_documento,
             especialidade, profissional, medicamentos, arquivo_path)
         VALUES
            (:paciente_id, :tipo, :titulo, :data_documento,
             :especialidade, :profissional, :medicamentos, :arquivo_path)'
    );

    $stmt->execute([
        'paciente_id' => $pacienteId,
        'tipo' => $tipo,
        'titulo' => $titulo,
        'data_documento' => $dataDocumento,
        'especialidade' => $especialidade !== '' ? $especialidade : null,
        'profissional' => $profissional !== '' ? $profissional : null,
        'medicamentos' => $medicamentos !== '' ? $medicamentos : null,
        'arquivo_path' => $caminhoBanco
    ]);

    registrar_log(
        'documento',
        "Documento adicionado: $titulo",
        $pacienteId
    );

    json_response([
        'ok' => true,
        'mensagem' => 'Documento salvo com sucesso!'
    ]);
} catch (Throwable $erro) {
    // Se o banco falhar, remove o arquivo recém-enviado.
    if (is_file($caminhoCompleto)) {
        unlink($caminhoCompleto);
    }

    json_response([
        'ok' => false,
        'erro' => 'Erro ao salvar o documento no banco.'
    ], 500);
}