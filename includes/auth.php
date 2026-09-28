<?php

declare(strict_types=1);

function usuario_logado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function exigir_login(): void
{
    if (!usuario_logado()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

function exigir_perfil(array $perfisPermitidos): void
{
    exigir_login();

    $usuario = usuario_logado();
    $perfil = $usuario['perfil'] ?? '';

    if (!in_array($perfil, $perfisPermitidos, true)) {
        http_response_code(403);

        echo '<h1>Acesso negado</h1>';
        echo '<p>Seu perfil não possui permissão para acessar esta página.</p>';
        echo '<a href="' . e(url('dashboard.php')) . '">Voltar ao painel</a>';

        exit;
    }
}

function usuario_tem_perfil(string $perfil): bool
{
    $usuario = usuario_logado();

    if (!$usuario) {
        return false;
    }

    return ($usuario['perfil'] ?? '') === $perfil;
}

function fazer_login(array $paciente): void
{
    session_regenerate_id(true);

    $_SESSION['usuario'] = [
        'id' => (int) $paciente['id'],
        'nome' => $paciente['nome'],
        'email' => $paciente['email'],
        'cpf' => $paciente['cpf'],
        'perfil' => $paciente['perfil'] ?? 'paciente',
    ];
}

function fazer_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
