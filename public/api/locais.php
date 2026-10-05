<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "
        SELECT
            l.id,
            l.nome,
            l.tipo,
            l.endereco,
            l.latitude,
            l.longitude,
            GROUP_CONCAT(
                DISTINCT c.nome
                ORDER BY c.nome
                SEPARATOR ', '
            ) AS convenios_aceites
        FROM locais_atendimento AS l
        LEFT JOIN local_plano_saude AS lc
            ON lc.local_id = l.id
        LEFT JOIN convenios AS c
            ON c.id = lc.plano_id
        GROUP BY
            l.id,
            l.nome,
            l.tipo,
            l.endereco,
            l.latitude,
            l.longitude
        ORDER BY l.nome
    ";

    $locais = db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locais as &$local) {
        if (empty($local['convenios_aceites'])) {
            $local['convenios_aceites'] = 'Particular / A confirmar';
        }
    }

    unset($local);

    echo json_encode(
        $locais,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (Throwable $erro) {
    error_log(
        'Falha ao consultar locais: ' . $erro->getMessage()
    );

    http_response_code(500);

    echo json_encode(
        ['erro' => 'Não foi possível carregar os locais.'],
        JSON_UNESCAPED_UNICODE
    );
}