<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!validar_csrf($input['csrf'] ?? null)) {
    json_response(['ok' => false, 'erro' => 'Token inválido'], 403);
}

$nome = trim((string) ($input['nome'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$assunto = trim((string) ($input['assunto'] ?? ''));
$mensagem = trim((string) ($input['mensagem'] ?? ''));

$erros = [];
if (strlen($nome) < 2) {
    $erros[] = 'Informe seu nome.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'E-mail inválido.';
}
if (strlen($assunto) < 3) {
    $erros[] = 'Informe o assunto.';
}
if (strlen($mensagem) < 10) {
    $erros[] = 'A mensagem deve ter pelo menos 10 caracteres.';
}

if ($erros) {
    json_response(['ok' => false, 'erros' => $erros], 422);
}

try {
    $stmt = db()->prepare(
        'INSERT INTO contatos (nome, email, assunto, mensagem) VALUES (:n, :e, :a, :m)'
    );
    $stmt->execute(['n' => $nome, 'e' => $email, 'a' => $assunto, 'm' => $mensagem]);
    registrar_log('contato', "Mensagem de $email: $assunto");
    json_response(['ok' => true, 'mensagem' => 'Mensagem enviada! Retornaremos em breve.']);
} catch (Throwable) {
    json_response(['ok' => false, 'erro' => 'Não foi possível enviar. Verifique o banco de dados.'], 500);
}
