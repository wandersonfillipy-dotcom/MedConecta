<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$usuario = usuario_logado();

if (!$usuario) {
    json_response([
        'ok' => false,
        'erro' => 'Faça login para acessar o chat.'
    ], 401);
}

$usuarioId = (int) $usuario['id'];
$perfil = (string) ($usuario['perfil'] ?? '');
$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

if (!in_array($perfil, ['paciente', 'profissional'], true)) {
    json_response([
        'ok' => false,
        'erro' => 'Seu perfil não tem acesso ao chat.'
    ], 403);
}

/*
|--------------------------------------------------------------------------
| Configuração dos anexos
|--------------------------------------------------------------------------
*/

const CHAT_TAMANHO_MAXIMO = 10 * 1024 * 1024;

function diretorioAnexosChat(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
        . 'storage' . DIRECTORY_SEPARATOR
        . 'chat_anexos';
}

function tiposPermitidosAnexoChat(): array
{
    return [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];
}

/**
 * Retorna uma conversa somente se o usuário autenticado
 * for o paciente ou o profissional responsável.
 */
function obterConversaAutorizada(
    PDO $pdo,
    int $conversaId,
    int $usuarioId,
    string $perfil
): ?array {
    $campo = $perfil === 'paciente'
        ? 'c.paciente_id'
        : 'c.profissional_id';

    $sql = "SELECT
                c.id,
                c.consulta_id,
                c.paciente_id,
                c.profissional_id,
                c.status,
                c.inicio_em,
                c.fim_em,
                q.medico,
                q.especialidade,
                p.nome AS paciente_nome
            FROM conversas c
            INNER JOIN consultas q
                ON q.id = c.consulta_id
            INNER JOIN pacientes p
                ON p.id = c.paciente_id
            WHERE c.id = :conversa_id
                AND {$campo} = :usuario_id
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'conversa_id' => $conversaId,
        'usuario_id' => $usuarioId
    ]);

    $conversa = $stmt->fetch();

    return $conversa ?: null;
}

/*
|--------------------------------------------------------------------------
| GET: baixar anexo com autorização
|--------------------------------------------------------------------------
*/

if ($metodo === 'GET' && isset($_GET['baixar_anexo'])) {
    try {
        $pdo = db();

        $mensagemId = filter_var(
            $_GET['baixar_anexo'],
            FILTER_VALIDATE_INT
        );

        if (!$mensagemId || $mensagemId <= 0) {
            json_response([
                'ok' => false,
                'erro' => 'Anexo inválido.'
            ], 422);
        }

        $stmt = $pdo->prepare(
            'SELECT
                id,
                conversa_id,
                anexo_nome,
                anexo_arquivo,
                anexo_mime,
                anexo_tamanho
             FROM mensagens_chat
             WHERE id = :mensagem_id
               AND anexo_arquivo IS NOT NULL
             LIMIT 1'
        );

        $stmt->execute([
            'mensagem_id' => $mensagemId
        ]);

        $anexo = $stmt->fetch();

        if (!$anexo) {
            json_response([
                'ok' => false,
                'erro' => 'Anexo não encontrado.'
            ], 404);
        }

        $conversa = obterConversaAutorizada(
            $pdo,
            (int) $anexo['conversa_id'],
            $usuarioId,
            $perfil
        );

        if (!$conversa) {
            json_response([
                'ok' => false,
                'erro' => 'Anexo não encontrado ou acesso negado.'
            ], 404);
        }

        /*
         * O nome armazenado no servidor é gerado aleatoriamente.
         * Não aceitamos caminhos enviados pelo usuário.
         */
        $nomeInterno = (string) $anexo['anexo_arquivo'];

        if (
            $nomeInterno === ''
            || basename($nomeInterno) !== $nomeInterno
            || !preg_match(
                '/\A[a-f0-9]{32}\.(pdf|jpg|jpeg|png|webp)\z/i',
                $nomeInterno
            )
        ) {
            json_response([
                'ok' => false,
                'erro' => 'Arquivo indisponível.'
            ], 404);
        }

        $caminho = diretorioAnexosChat()
            . DIRECTORY_SEPARATOR
            . $nomeInterno;

        if (!is_file($caminho) || !is_readable($caminho)) {
            json_response([
                'ok' => false,
                'erro' => 'Arquivo não encontrado no armazenamento.'
            ], 404);
        }

        $nomeOriginal = (string) $anexo['anexo_nome'];

        // Evita caracteres de controle e cabeçalhos HTTP inválidos.
        $nomeOriginal = str_replace(
            ["\r", "\n", "\0"],
            '',
            $nomeOriginal
        );

        if ($nomeOriginal === '') {
            $nomeOriginal = 'anexo';
        }

        $tiposPermitidos = tiposPermitidosAnexoChat();
        $mime = (string) $anexo['anexo_mime'];

        if (!isset($tiposPermitidos[$mime])) {
            json_response([
                'ok' => false,
                'erro' => 'Tipo de arquivo não permitido.'
            ], 404);
        }

        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="anexo"; filename*=UTF-8\'\'' . rawurlencode($nomeOriginal));
        header('Content-Length: ' . (string) filesize($caminho));
        header('Cache-Control: private, no-store');

        readfile($caminho);
        exit;
    } catch (Throwable $erro) {
        error_log('MedConecta chat download: ' . $erro->getMessage());

        json_response([
            'ok' => false,
            'erro' => 'Não foi possível baixar o anexo.'
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| GET: listar conversas ou consultar o histórico de mensagens
|--------------------------------------------------------------------------
*/

if ($metodo === 'GET') {
    try {
        $pdo = db();

        if (isset($_GET['conversa_id'])) {
            $conversaId = filter_input(
                INPUT_GET,
                'conversa_id',
                FILTER_VALIDATE_INT
            );

            if (!$conversaId || $conversaId <= 0) {
                json_response([
                    'ok' => false,
                    'erro' => 'Conversa inválida.'
                ], 422);
            }

            $conversa = obterConversaAutorizada(
                $pdo,
                $conversaId,
                $usuarioId,
                $perfil
            );

            if (!$conversa) {
                json_response([
                    'ok' => false,
                    'erro' => 'Conversa não encontrada ou acesso negado.'
                ], 404);
            }

            $stmt = $pdo->prepare(
                'SELECT
                    m.id,
                    m.remetente_id,
                    m.mensagem,
                    m.enviada_em,
                    m.anexo_nome,
                    m.anexo_mime,
                    m.anexo_tamanho,
                    p.nome AS remetente_nome
                 FROM mensagens_chat m
                 INNER JOIN pacientes p
                    ON p.id = m.remetente_id
                 WHERE m.conversa_id = :conversa_id
                 ORDER BY m.id ASC'
            );

            $stmt->execute([
                'conversa_id' => $conversaId
            ]);

            json_response([
                'ok' => true,
                'conversa' => $conversa,
                'mensagens' => $stmt->fetchAll()
            ]);
        }

        if ($perfil === 'paciente') {
            $stmt = $pdo->prepare(
                'SELECT
                    c.id AS conversa_id,
                    c.status,
                    c.inicio_em,
                    c.fim_em,
                    q.id AS consulta_id,
                    q.medico,
                    q.especialidade,
                    q.data_hora
                 FROM conversas c
                 INNER JOIN consultas q
                    ON q.id = c.consulta_id
                 WHERE c.paciente_id = :usuario_id
                 ORDER BY c.inicio_em DESC'
            );
        } else {
            $stmt = $pdo->prepare(
                'SELECT
                    c.id AS conversa_id,
                    c.status,
                    c.inicio_em,
                    c.fim_em,
                    q.id AS consulta_id,
                    q.medico,
                    q.especialidade,
                    q.data_hora,
                    p.nome AS paciente_nome
                 FROM conversas c
                 INNER JOIN consultas q
                    ON q.id = c.consulta_id
                 INNER JOIN pacientes p
                    ON p.id = c.paciente_id
                 WHERE c.profissional_id = :usuario_id
                 ORDER BY c.inicio_em DESC'
            );
        }

        $stmt->execute([
            'usuario_id' => $usuarioId
        ]);

        json_response([
            'ok' => true,
            'conversas' => $stmt->fetchAll()
        ]);
    } catch (Throwable $erro) {
        error_log('MedConecta chat GET: ' . $erro->getMessage());

        json_response([
            'ok' => false,
            'erro' => 'Não foi possível consultar o chat.'
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| Somente GET e POST são aceitos
|--------------------------------------------------------------------------
*/

if ($metodo !== 'POST') {
    json_response([
        'ok' => false,
        'erro' => 'Método não permitido.'
    ], 405);
}

/*
 * Para requisições JSON, os dados vêm do corpo da requisição.
 * Para multipart/form-data, usado no envio de arquivos,
 * os dados são recebidos por $_POST e $_FILES.
 */
$entrada = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($entrada)) {
    $entrada = $_POST;
}

if (!validar_csrf($entrada['csrf'] ?? null)) {
    json_response([
        'ok' => false,
        'erro' => 'Token inválido.'
    ], 403);
}

$acao = (string) ($entrada['acao'] ?? '');

try {
    $pdo = db();

    /*
    |--------------------------------------------------------------------------
    | POST: iniciar ou recuperar a conversa de uma consulta
    |--------------------------------------------------------------------------
    */

    if ($acao === 'iniciar') {
        $consultaId = filter_var(
            $entrada['consulta_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$consultaId || $consultaId <= 0) {
            json_response([
                'ok' => false,
                'erro' => 'Informe uma consulta válida.'
            ], 422);
        }

        $stmt = $pdo->prepare(
            'SELECT
                id,
                paciente_id,
                profissional_id,
                status,
                medico
             FROM consultas
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $consultaId
        ]);

        $consulta = $stmt->fetch();

        if (!$consulta || $consulta['profissional_id'] === null) {
            json_response([
                'ok' => false,
                'erro' => 'Consulta sem profissional responsável vinculado.'
            ], 422);
        }

        $participa = $perfil === 'paciente'
            ? (int) $consulta['paciente_id'] === $usuarioId
            : (int) $consulta['profissional_id'] === $usuarioId;

        if (!$participa) {
            json_response([
                'ok' => false,
                'erro' => 'Você não participa desta consulta.'
            ], 403);
        }

        if ($consulta['status'] === 'cancelada') {
            json_response([
                'ok' => false,
                'erro' => 'Não é possível iniciar chat para consulta cancelada.'
            ], 422);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO conversas (
                consulta_id,
                paciente_id,
                profissional_id,
                status
            ) VALUES (
                :consulta_id,
                :paciente_id,
                :profissional_id,
                'ativa'
            )
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id)"
        );

        $stmt->execute([
            'consulta_id' => $consultaId,
            'paciente_id' => $consulta['paciente_id'],
            'profissional_id' => $consulta['profissional_id']
        ]);

        $conversaId = (int) $pdo->lastInsertId();

        if ($conversaId <= 0) {
            $stmt = $pdo->prepare(
                'SELECT id
                 FROM conversas
                 WHERE consulta_id = :consulta_id
                 LIMIT 1'
            );

            $stmt->execute([
                'consulta_id' => $consultaId
            ]);

            $conversaId = (int) $stmt->fetchColumn();
        }

        json_response([
            'ok' => true,
            'conversa_id' => $conversaId,
            'mensagem' => 'Conversa disponível.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST: enviar mensagem de texto
    |--------------------------------------------------------------------------
    */

    if ($acao === 'enviar') {
        $conversaId = filter_var(
            $entrada['conversa_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $mensagem = trim(
            (string) ($entrada['mensagem'] ?? '')
        );

        if (!$conversaId || $conversaId <= 0) {
            json_response([
                'ok' => false,
                'erro' => 'Conversa inválida.'
            ], 422);
        }

        if (
            $mensagem === ''
            || mb_strlen($mensagem, 'UTF-8') > 2000
        ) {
            json_response([
                'ok' => false,
                'erro' => 'A mensagem deve ter entre 1 e 2.000 caracteres.'
            ], 422);
        }

        $conversa = obterConversaAutorizada(
            $pdo,
            $conversaId,
            $usuarioId,
            $perfil
        );

        if (!$conversa) {
            json_response([
                'ok' => false,
                'erro' => 'Conversa não encontrada ou acesso negado.'
            ], 404);
        }

        if ($conversa['status'] !== 'ativa') {
            json_response([
                'ok' => false,
                'erro' => 'Esta conversa está encerrada.'
            ], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO mensagens_chat (
                conversa_id,
                remetente_id,
                mensagem
            ) VALUES (
                :conversa_id,
                :remetente_id,
                :mensagem
            )'
        );

        $stmt->execute([
            'conversa_id' => $conversaId,
            'remetente_id' => $usuarioId,
            'mensagem' => $mensagem
        ]);

        json_response([
            'ok' => true,
            'mensagem' => 'Mensagem enviada.',
            'mensagem_id' => (int) $pdo->lastInsertId()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST: enviar anexo
    |--------------------------------------------------------------------------
    */

    if ($acao === 'enviar_anexo') {
        $conversaId = filter_var(
            $entrada['conversa_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$conversaId || $conversaId <= 0) {
            json_response([
                'ok' => false,
                'erro' => 'Conversa inválida.'
            ], 422);
        }

        $conversa = obterConversaAutorizada(
            $pdo,
            $conversaId,
            $usuarioId,
            $perfil
        );

        if (!$conversa) {
            json_response([
                'ok' => false,
                'erro' => 'Conversa não encontrada ou acesso negado.'
            ], 404);
        }

        if ($conversa['status'] !== 'ativa') {
            json_response([
                'ok' => false,
                'erro' => 'Esta conversa está encerrada.'
            ], 422);
        }

        if (
            !isset($_FILES['anexo'])
            || !is_array($_FILES['anexo'])
        ) {
            json_response([
                'ok' => false,
                'erro' => 'Selecione um arquivo para anexar.'
            ], 422);
        }

        $arquivo = $_FILES['anexo'];
        $erroUpload = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($erroUpload !== UPLOAD_ERR_OK) {
            $mensagemErro = match ($erroUpload) {
                UPLOAD_ERR_INI_SIZE,
                UPLOAD_ERR_FORM_SIZE =>
                    'O arquivo excede o limite permitido pelo servidor.',
                UPLOAD_ERR_PARTIAL =>
                    'O arquivo foi enviado parcialmente. Tente novamente.',
                UPLOAD_ERR_NO_FILE =>
                    'Selecione um arquivo para anexar.',
                default =>
                    'Não foi possível receber o arquivo. Tente novamente.'
            };

            json_response([
                'ok' => false,
                'erro' => $mensagemErro
            ], 422);
        }

        $caminhoTemporario = (string) ($arquivo['tmp_name'] ?? '');
        $tamanho = (int) ($arquivo['size'] ?? 0);

        if (
            $caminhoTemporario === ''
            || !is_uploaded_file($caminhoTemporario)
            || $tamanho <= 0
        ) {
            json_response([
                'ok' => false,
                'erro' => 'Arquivo inválido ou vazio.'
            ], 422);
        }

        if ($tamanho > CHAT_TAMANHO_MAXIMO) {
            json_response([
                'ok' => false,
                'erro' => 'O tamanho máximo do anexo é 10 MB.'
            ], 422);
        }

        $nomeOriginal = basename(
            str_replace('\\', '/', (string) ($arquivo['name'] ?? 'anexo'))
        );

        $nomeOriginal = preg_replace(
            '/[\x00-\x1F\x7F]/u',
            '',
            $nomeOriginal
        ) ?? 'anexo';

        $nomeOriginal = trim($nomeOriginal);

        if ($nomeOriginal === '') {
            $nomeOriginal = 'anexo';
        }

        if (function_exists('mb_substr')) {
            $nomeOriginal = mb_substr($nomeOriginal, 0, 255, 'UTF-8');
        } else {
            $nomeOriginal = substr($nomeOriginal, 0, 255);
        }

        $extensao = strtolower(
            pathinfo($nomeOriginal, PATHINFO_EXTENSION)
        );

        $tiposPermitidos = tiposPermitidosAnexoChat();

        if (!class_exists('finfo')) {
            json_response([
                'ok' => false,
                'erro' => 'A validação de arquivos não está disponível no servidor.'
            ], 500);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($caminhoTemporario);

        if (
            !is_string($mimeReal)
            || !isset($tiposPermitidos[$mimeReal])
            || !in_array(
                $extensao,
                $tiposPermitidos[$mimeReal],
                true
            )
        ) {
            json_response([
                'ok' => false,
                'erro' => 'Formato não permitido. Envie PDF, JPG, PNG ou WEBP válido.'
            ], 422);
        }

        $legenda = trim(
            (string) ($entrada['mensagem'] ?? '')
        );

        if (mb_strlen($legenda, 'UTF-8') > 2000) {
            json_response([
                'ok' => false,
                'erro' => 'A mensagem junto ao anexo deve ter no máximo 2.000 caracteres.'
            ], 422);
        }

        // Mantém a coluna mensagem preenchida mesmo em anexos sem legenda.
        $textoMensagem = $legenda !== ''
            ? $legenda
            : 'Anexo: ' . $nomeOriginal;

        $diretorio = diretorioAnexosChat();

        if (
            !is_dir($diretorio)
            && !mkdir($diretorio, 0700, true)
            && !is_dir($diretorio)
        ) {
            json_response([
                'ok' => false,
                'erro' => 'Não foi possível preparar o armazenamento de anexos.'
            ], 500);
        }

        $nomeInterno = bin2hex(random_bytes(16))
            . '.'
            . $extensao;

        $destino = $diretorio
            . DIRECTORY_SEPARATOR
            . $nomeInterno;

        if (!move_uploaded_file($caminhoTemporario, $destino)) {
            json_response([
                'ok' => false,
                'erro' => 'Não foi possível salvar o anexo.'
            ], 500);
        }

        @chmod($destino, 0600);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO mensagens_chat (
                    conversa_id,
                    remetente_id,
                    mensagem,
                    anexo_nome,
                    anexo_arquivo,
                    anexo_mime,
                    anexo_tamanho
                ) VALUES (
                    :conversa_id,
                    :remetente_id,
                    :mensagem,
                    :anexo_nome,
                    :anexo_arquivo,
                    :anexo_mime,
                    :anexo_tamanho
                )'
            );

            $stmt->execute([
                'conversa_id' => $conversaId,
                'remetente_id' => $usuarioId,
                'mensagem' => $textoMensagem,
                'anexo_nome' => $nomeOriginal,
                'anexo_arquivo' => $nomeInterno,
                'anexo_mime' => $mimeReal,
                'anexo_tamanho' => $tamanho
            ]);
        } catch (Throwable $erroBanco) {
            if (is_file($destino)) {
                @unlink($destino);
            }

            throw $erroBanco;
        }

        json_response([
            'ok' => true,
            'mensagem' => 'Anexo enviado.',
            'mensagem_id' => (int) $pdo->lastInsertId()
        ]);
    }

    json_response([
        'ok' => false,
        'erro' => 'Ação não reconhecida.'
    ], 422);
} catch (Throwable $erro) {
    error_log('MedConecta chat POST: ' . $erro->getMessage());

    json_response([
        'ok' => false,
        'erro' => 'Não foi possível processar a solicitação do chat.'
    ], 500);
}
