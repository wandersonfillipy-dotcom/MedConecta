<?php
header('Content-Type: application/json');
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

try {
    $pdo = db();
    $query = "
        SELECT 
            l.id, l.nome, l.tipo, l.endereco, l.latitude, l.longitude,
            GROUP_CONCAT(c.nome SEPARATOR ', ') as convenios_aceites
        FROM locais_atendimento l
        LEFT JOIN local_plano_saude lc ON l.id = lc.local_id
        LEFT JOIN convenios c ON lc.plano_id = c.id
        GROUP BY l.id
    ";
    
    $stmt = $pdo->query($query);
    $locais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locais as &$local) {
        if (empty($local['convenios_aceites'])) {
            $local['convenios_aceites'] = 'Particular / A confirmar';
        }
    }

    echo json_encode($locais);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao carregar locais: ' . $e->getMessage()]);
}