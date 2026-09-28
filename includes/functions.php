<?php

declare(strict_types=1);

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    return $config;
}

function base_path(string $path = ''): string
{
    $root = dirname(__DIR__) . '/public';
    return $path === '' ? $root : $root . '/' . ltrim($path, '/');
}

function url(string $path = ''): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (str_ends_with($base, '/api')) {
        $base = dirname($base);
    }
    $base = $base === '/' || $base === '\\' ? '' : $base;
    return $base . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function only_digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function validar_cpf(string $cpf): bool
{
    $cpf = only_digits($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) {
            $sum += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digit = ((10 * $sum) % 11) % 10;
        if ((int) $cpf[$t] !== $digit) {
            return false;
        }
    }
    return true;
}

function formatar_cpf(string $cpf): string
{
    $cpf = only_digits($cpf);
    if (strlen($cpf) !== 11) {
        return $cpf;
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' .
        substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function formatar_telefone(string $tel): string
{
    $tel = only_digits($tel);
    if (strlen($tel) === 11) {
        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
    }
    if (strlen($tel) === 10) {
        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
    }
    return $tel;
}

function csrf_token(): string
{
    $key = app_config()['csrf_key'];
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$key];
}

function validar_csrf(?string $token): bool
{
    $key = app_config()['csrf_key'];
    return is_string($token)
        && !empty($_SESSION[$key])
        && hash_equals($_SESSION[$key], $token);
}

function registrar_log(string $tipo, string $mensagem, ?int $pacienteId = null): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO logs (tipo, mensagem, paciente_id, ip) VALUES (:tipo, :msg, :pid, :ip)'
        );
        $stmt->execute([
            'tipo' => $tipo,
            'msg' => $mensagem,
            'pid' => $pacienteId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable) {
        // Log silencioso em ambiente local sem banco
    }
}

function paginas_site(): array
{
    return [
        ['titulo' => 'Início', 'url' => 'index.php', 'palavras' => 'medconecta saúde prontuário acessibilidade'],
        ['titulo' => 'Sobre', 'url' => 'sobre.php', 'palavras' => 'sobre missão valores senac inclusão lgpd'],
        ['titulo' => 'Cadastro', 'url' => 'cadastro.php', 'palavras' => 'cadastro paciente cpf lgpd registro'],
        ['titulo' => 'Login', 'url' => 'login.php', 'palavras' => 'login entrar autenticação senha'],
        ['titulo' => 'Dashboard', 'url' => 'dashboard.php', 'palavras' => 'dashboard receitas atestados documentos consultas'],
        ['titulo' => 'Agendamento', 'url' => 'agendamento.php', 'palavras' => 'agendamento consulta exame médico horário'],
        ['titulo' => 'Contato', 'url' => 'contato.php', 'palavras' => 'contato suporte sac whatsapp'],
        ['titulo' => 'Definition of Done', 'url' => 'dod.php', 'palavras' => 'dod qualidade checklist pronto'],
        ['titulo' => 'Sprints', 'url' => 'sprints.php', 'palavras' => 'sprint backlog histórico velocity'],
        ['titulo' => 'Arquitetura', 'url' => 'arquitetura.php', 'palavras' => 'arquitetura php mysql javascript api'],
        ['titulo' => 'Instalação', 'url' => 'instalar.php', 'palavras' => 'instalar xampp mysql php ambiente'],
    ];
}
