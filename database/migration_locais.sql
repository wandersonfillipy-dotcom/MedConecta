-- Locais de atendimento para RF06/RF07.
-- Execute depois de database/schema.sql.

CREATE TABLE IF NOT EXISTS locais_atendimento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo ENUM('Clinica', 'Hospital', 'Laboratorio') NOT NULL,
    endereco VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    UNIQUE KEY uq_local_nome_endereco (nome, endereco)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS local_plano_saude (
    local_id INT UNSIGNED NOT NULL,
    plano_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (local_id, plano_id),
    CONSTRAINT fk_local_plano_local
        FOREIGN KEY (local_id)
        REFERENCES locais_atendimento(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_local_plano_convenio
        FOREIGN KEY (plano_id)
        REFERENCES convenios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dados de demonstração sem alterar os convênios existentes.
INSERT IGNORE INTO convenios (nome, descricao) VALUES
    ('Bradesco Saúde', 'Convênio Bradesco Saúde'),
    ('SulAmérica', 'Convênio SulAmérica');

INSERT IGNORE INTO locais_atendimento
    (nome, tipo, endereco, latitude, longitude)
VALUES
    ('Clínica Vida Saúde', 'Clinica', 'Rua das Flores, 123', -15.793889, -47.882778),
    ('Hospital Central', 'Hospital', 'Avenida Principal, 500', -15.800000, -47.890000);

INSERT IGNORE INTO local_plano_saude (local_id, plano_id)
SELECT l.id, c.id
FROM locais_atendimento AS l
JOIN convenios AS c
WHERE
    (l.nome = 'Clínica Vida Saúde'
     AND l.endereco = 'Rua das Flores, 123'
     AND c.nome IN ('Unimed', 'Bradesco Saúde'))
    OR
    (l.nome = 'Hospital Central'
     AND l.endereco = 'Avenida Principal, 500'
     AND c.nome IN ('Bradesco Saúde', 'SulAmérica'));