<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

/*
 * A API só aceita requisições POST.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(
        [
            'ok' => false,
            'erro' => 'Método não permitido.',
        ],
        405
    );
}

/*
 * Verifica se existe usuário autenticado.
 */
$usuario = usuario_logado();

if (!$usuario) {
    json_response(
        [
            'ok' => false,
            'erro' => 'Usuário não autenticado.',
        ],
        401
    );
}

/*
 * Somente administradores podem alterar perfis.
 */
if (($usuario['perfil'] ?? '') !== 'administrador') {
    json_response(
        [
            'ok' => false,
            'erro' => 'Você não possui permissão.',
        ],
        403
    );
}

/*
 * Recebe os dados enviados pelo formulário.
 */
$usuarioId = filter_input(
    INPUT_POST,
    'usuario_id',
    FILTER_VALIDATE_INT
);

$perfil = trim(
    (string) ($_POST['perfil'] ?? '')
);

$csrf = $_POST['csrf'] ?? null;

/*
 * Valida o token de segurança.
 */
if (!validar_csrf($csrf)) {
    json_response(
        [
            'ok' => false,
            'erro' => 'Token de segurança inválido.',
        ],
        403
    );
}

/*
 * Lista dos perfis aceitos pelo sistema.
 */
$perfisPermitidos = [
    'paciente',
    'profissional',
    'cuidador',
    'administrador',
];

if (!$usuarioId) {
    json_response(
        [
            'ok' => false,
            'erro' => 'Usuário inválido.',
        ],
        422
    );
}

if (!in_array($perfil, $perfisPermitidos, true)) {
    json_response(
        [
            'ok' => false,
            'erro' => 'Perfil inválido.',
        ],
        422
    );
}

/*
 * Impede o administrador de remover o próprio
 * perfil enquanto estiver usando a conta.
 */
if (
    $usuarioId === (int) $usuario['id']
    && $perfil !== 'administrador'
) {
    json_response(
        [
            'ok' => false,
            'erro' => 'Você não pode remover seu próprio perfil de administrador.',
        ],
        422
    );
}

try {
    $pdo = db();

    $stmt = $pdo->prepare(
        'UPDATE pacientes
         SET perfil = :perfil
         WHERE id = :id'
    );

    $stmt->execute([
        'perfil' => $perfil,
        'id' => $usuarioId,
    ]);

    if ($stmt->rowCount() === 0) {
        json_response(
            [
                'ok' => false,
                'erro' => 'Usuário não encontrado ou perfil não alterado.',
            ],
            404
        );
    }

    registrar_log(
        'alteracao_perfil',
        'Perfil do usuário '
            . $usuarioId
            . ' alterado para '
            . $perfil,
        (int) $usuario['id']
    );

    json_response([
        'ok' => true,
        'mensagem' => 'Perfil atualizado com sucesso.',
    ]);
} catch (Throwable $e) {
    json_response(
        [
            'ok' => false,
            'erro' => 'Não foi possível atualizar o perfil.',
        ],
        500
    );
}