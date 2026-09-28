<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!validar_csrf($input['csrf'] ?? null)) {
    json_response(['ok' => false, 'erro' => 'Token de segurança inválido.'], 403);
}

$email = strtolower(trim((string) ($input['email'] ?? '')));
$senha = (string) ($input['senha'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    json_response(['ok' => false, 'erro' => 'E-mail ou senha inválidos.'], 422);
}

try {
    $stmt = db()->prepare('SELECT * FROM pacientes WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $paciente = $stmt->fetch();

    if (!$paciente || !password_verify($senha, $paciente['senha_hash'])) {
        registrar_log('login_falha', "Tentativa inválida: $email");
        json_response(['ok' => false, 'erro' => 'Credenciais incorretas.'], 401);
    }

    fazer_login($paciente);
    registrar_log('login', 'Login realizado', (int) $paciente['id']);

   $perfil = $paciente['perfil'] ?? 'paciente';

$paginasPorPerfil = [
    'paciente' => 'dashboard.php',
    'profissional' => 'profissional.php',
    'cuidador' => 'cuidador.php',
    'administrador' => 'admin.php',
];

$paginaDestino = $paginasPorPerfil[$perfil] ?? 'dashboard.php';

json_response([
    'ok' => true,
    'mensagem' => 'Login realizado com sucesso!',
    'redirect' => url($paginaDestino),
    'usuario' => usuario_logado(),
]);
} catch (Throwable) {
    json_response(['ok' => false, 'erro' => 'Erro de conexão com o banco de dados.'], 500);
}
