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

$uid = usuario_logado()['id'] ?? null;
fazer_logout();
if ($uid) {
    registrar_log('logout', 'Logout realizado', (int) $uid);
}

json_response(['ok' => true, 'redirect' => url('index.php')]);
