<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$usuario = usuario_logado();
if (!$usuario) {
    json_response(['ok' => false, 'erro' => 'Não autenticado'], 401);
}

/* ══════════════════════════════════════
   GET — listar documentos
══════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = db()->prepare(
            'SELECT id, tipo, titulo, especialidade, profissional,
                    medicamentos, arquivo_path, created_at
             FROM documentos
             WHERE paciente_id = :id
             ORDER BY created_at DESC'
        );
        $stmt->execute(['id' => $usuario['id']]);
        json_response(['ok' => true, 'documentos' => $stmt->fetchAll()]);
    } catch (Throwable) {
        json_response(['ok' => false, 'erro' => 'Erro ao listar documentos'], 500);
    }
}

/* ══════════════════════════════════════
   POST — salvar ou excluir
══════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Valida CSRF
    if (!validar_csrf($_POST['csrf'] ?? null)) {
        json_response(['ok' => false, 'erro' => 'Token inválido'], 403);
    }

    /* ── EXCLUIR ── */
    if (($_POST['acao'] ?? '') === 'excluir') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            json_response(['ok' => false, 'erro' => 'ID inválido'], 422);
        }
        try {
            $s = db()->prepare(
                'SELECT arquivo_path FROM documentos
                 WHERE id = :id AND paciente_id = :pid'
            );
            $s->execute(['id' => $id, 'pid' => $usuario['id']]);
            $doc = $s->fetch();

            // Apaga o arquivo físico se existir
            if ($doc && $doc['arquivo_path']) {
                $full = dirname(__DIR__, 2) . '/' . $doc['arquivo_path'];
                if (file_exists($full)) {
                    unlink($full);
                }
            }

            $del = db()->prepare(
                'DELETE FROM documentos WHERE id = :id AND paciente_id = :pid'
            );
            $del->execute(['id' => $id, 'pid' => $usuario['id']]);

            registrar_log('documento', "Documento excluído: $id", (int) $usuario['id']);
            json_response(['ok' => true, 'mensagem' => 'Documento excluído.']);

        } catch (Throwable) {
            json_response(['ok' => false, 'erro' => 'Erro ao excluir.'], 500);
        }
    }

    /* ── SALVAR NOVO ── */
    $tipo          = trim($_POST['tipo']          ?? '');
    $titulo        = trim($_POST['titulo']        ?? '');
    $especialidade = trim($_POST['especialidade'] ?? '');
    $profissional  = trim($_POST['profissional']  ?? '');
    $medicamentos  = trim($_POST['medicamentos']  ?? '');

    if ($tipo === '' || $titulo === '') {
        json_response(['ok' => false, 'erro' => 'Tipo e título são obrigatórios.'], 422);
    }

    // Upload do arquivo
    $arquivoPath = null;
    if (!empty($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['arquivo'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if ($file['size'] > $maxSize) {
            json_response(['ok' => false, 'erro' => 'Arquivo muito grande. Máximo 5MB.'], 422);
        }

        $mime       = mime_content_type($file['tmp_name']);
        $permitidos = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mime, $permitidos, true)) {
            json_response(['ok' => false, 'erro' => 'Formato não permitido. Use PDF ou imagem.'], 422);
        }

        $ext  = $mime === 'application/pdf'
              ? 'pdf'
              : strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $pasta = dirname(__DIR__, 2) . '/storage/documentos/' . $usuario['id'];
        if (!is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }

        $nomeArquivo = uniqid('doc_', true) . '.' . $ext;
        $destino     = $pasta . '/' . $nomeArquivo;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            json_response(['ok' => false, 'erro' => 'Erro ao salvar arquivo no servidor.'], 500);
        }

        $arquivoPath = 'storage/documentos/' . $usuario['id'] . '/' . $nomeArquivo;
    }

    // Salva no banco
    try {
        $stmt = db()->prepare(
            'INSERT INTO documentos
                (paciente_id, tipo, titulo, especialidade, profissional, medicamentos, arquivo_path)
             VALUES
                (:pid, :tipo, :titulo, :esp, :prof, :med, :path)'
        );
        $stmt->execute([
            'pid'   => $usuario['id'],
            'tipo'  => $tipo,
            'titulo'=> $titulo,
            'esp'   => $especialidade ?: null,
            'prof'  => $profissional  ?: null,
            'med'   => $medicamentos  ?: null,
            'path'  => $arquivoPath,
        ]);

        registrar_log('documento', "Documento adicionado: $titulo", (int) $usuario['id']);
        json_response(['ok' => true, 'mensagem' => 'Documento salvo com sucesso!']);

    } catch (Throwable) {
        json_response(['ok' => false, 'erro' => 'Erro ao salvar no banco de dados.'], 500);
    }
}

json_response(['ok' => false, 'erro' => 'Método não permitido'], 405);