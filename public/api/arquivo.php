<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    exit('Método não permitido.');
}

$usuario = usuario_logado();

if (!$usuario) {
    http_response_code(401);
    exit('Faça login para acessar o arquivo.');
}

if (($usuario['perfil'] ?? '') !== 'paciente') {
    http_response_code(403);
    exit('Acesso negado.');
}

$pacienteId = (int) $usuario['id'];
$documentoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($documentoId === false || $documentoId === null || $documentoId <= 0) {
    http_response_code(400);
    exit('Documento inválido.');
}

try {
    $stmt = db()->prepare(
        'SELECT arquivo_path
         FROM documentos
         WHERE id = :id AND paciente_id = :paciente_id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $documentoId,
        'paciente_id' => $pacienteId
    ]);

    $documento = $stmt->fetch();
} catch (Throwable $erro) {
    http_response_code(500);
    exit('Erro ao consultar o documento.');
}

if (!$documento || empty($documento['arquivo_path'])) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$caminhoBanco = str_replace('\\', '/', (string) $documento['arquivo_path']);
$prefixo = 'storage/documentos/' . $pacienteId . '/';

if (
    !str_starts_with($caminhoBanco, $prefixo)
    || !preg_match(
        '/\A[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*\.(pdf|jpg|jpeg|png)\z/i',
        substr($caminhoBanco, strlen($prefixo))
    )
) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$pastaPaciente = realpath(
    dirname(__DIR__, 2) . '/storage/documentos/' . $pacienteId
);

$caminhoArquivo = realpath(
    dirname(__DIR__, 2) . '/' . $caminhoBanco
);

if (
    $pastaPaciente === false
    || $caminhoArquivo === false
    || dirname($caminhoArquivo) !== $pastaPaciente
    || !is_file($caminhoArquivo)
    || !is_readable($caminhoArquivo)
) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));

$tipos = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png'
];

if (!isset($tipos[$extensao])) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

header('Content-Type: ' . $tipos[$extensao]);
header('Content-Disposition: inline; filename="documento.' . $extensao . '"');
header('Content-Length: ' . filesize($caminhoArquivo));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($caminhoArquivo);
exit;