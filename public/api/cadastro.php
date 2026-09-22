<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!validar_csrf($input['csrf'] ?? null)) {
    json_response(['ok' => false, 'erro' => 'Token de segurança inválido. Recarregue a página.'], 403);
}

$nome = trim((string) ($input['nome'] ?? ''));
$cpf = only_digits((string) ($input['cpf'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$telefone = only_digits((string) ($input['telefone'] ?? ''));
$dataNasc = (string) ($input['data_nascimento'] ?? '');
$senha = (string) ($input['senha'] ?? '');
$lgpd = !empty($input['lgpd']);

$erros = [];
if (strlen($nome) < 3) {
    $erros[] = 'Informe o nome completo.';
}
if (!validar_cpf($cpf)) {
    $erros[] = 'CPF inválido.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'E-mail inválido.';
}
if (strlen($telefone) < 10) {
    $erros[] = 'Telefone inválido.';
}
$d = DateTime::createFromFormat('Y-m-d', $dataNasc);
if (!$d || $d->format('Y-m-d') !== $dataNasc) {
    $erros[] = 'Data de nascimento inválida.';
}
if (strlen($senha) < 8) {
    $erros[] = 'A senha deve ter no mínimo 8 caracteres.';
}
if (!$lgpd) {
    $erros[] = 'É obrigatório aceitar os termos da LGPD.';
}

if ($erros) {
    json_response(['ok' => false, 'erros' => $erros], 422);
}

try {
    $pdo = db();
    $check = $pdo->prepare('SELECT id FROM pacientes WHERE cpf = :cpf OR email = :email LIMIT 1');
    $check->execute(['cpf' => $cpf, 'email' => $email]);
    if ($check->fetch()) {
        json_response(['ok' => false, 'erro' => 'CPF ou e-mail já cadastrado.'], 409);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO pacientes (nome, cpf, data_nascimento, email, telefone, senha_hash, lgpd_aceite)
         VALUES (:nome, :cpf, :dn, :email, :tel, :senha, 1)'
    );
    $stmt->execute([
        'nome' => $nome,
        'cpf' => $cpf,
        'dn' => $dataNasc,
        'email' => $email,
        'tel' => $telefone,
        'senha' => password_hash($senha, PASSWORD_BCRYPT),
    ]);

    $id = (int) $pdo->lastInsertId();
    registrar_log('cadastro', "Paciente cadastrado: $email", $id);

    json_response([
        'ok' => true,
        'mensagem' => 'Cadastro realizado com sucesso! Você já pode fazer login.',
        'redirect' => url('login.php'),
    ]);
} catch (Throwable $e) {
    registrar_log('erro', 'Falha no cadastro: ' . $e->getMessage());
    json_response(['ok' => false, 'erro' => 'Erro ao salvar cadastro. Verifique se o MySQL está configurado.'], 500);
}
