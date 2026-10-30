
-- RF08: estrutura inicial do chat entre paciente e profissional.

ALTER TABLE consultas
    ADD COLUMN profissional_id INT UNSIGNED NULL AFTER paciente_id,
    ADD INDEX idx_consultas_profissional_id (profissional_id),
    ADD CONSTRAINT fk_consultas_profissional
        FOREIGN KEY (profissional_id)
        REFERENCES pacientes(id)
        ON DELETE SET NULL;

CREATE TABLE conversas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consulta_id INT UNSIGNED NOT NULL,
    paciente_id INT UNSIGNED NOT NULL,
    profissional_id INT UNSIGNED NOT NULL,
    inicio_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fim_em DATETIME NULL,
    status ENUM('ativa', 'encerrada') NOT NULL DEFAULT 'ativa',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_conversas_consulta (consulta_id),
    INDEX idx_conversas_paciente (paciente_id),
    INDEX idx_conversas_profissional (profissional_id),

    CONSTRAINT fk_conversas_consulta
        FOREIGN KEY (consulta_id)
        REFERENCES consultas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_conversas_paciente
        FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_conversas_profissional
        FOREIGN KEY (profissional_id)
        REFERENCES pacientes(id)
        ON DELETE CASCADE
);

CREATE TABLE mensagens_chat (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversa_id INT UNSIGNED NOT NULL,
    remetente_id INT UNSIGNED NOT NULL,
    mensagem TEXT NOT NULL,
    enviada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_mensagens_conversa_data (conversa_id, enviada_em),

    CONSTRAINT fk_mensagens_conversa
        FOREIGN KEY (conversa_id)
        REFERENCES conversas(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_mensagens_remetente
        FOREIGN KEY (remetente_id)
        REFERENCES pacientes(id)
        ON DELETE CASCADE
);
