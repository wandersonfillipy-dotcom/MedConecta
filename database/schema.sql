-- MedConecta - Schema do banco de dados
-- Importar pelo phpMyAdmin

CREATE DATABASE IF NOT EXISTS medconecta
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE medconecta;

-- Usuários/pacientes

CREATE TABLE IF NOT EXISTS pacientes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(150) NOT NULL,
  cpf             CHAR(11) NOT NULL UNIQUE,
  data_nascimento DATE NOT NULL,
  email           VARCHAR(180) NOT NULL UNIQUE,
  telefone        VARCHAR(15) NOT NULL,
  senha_hash      VARCHAR(255) NOT NULL,
  lgpd_aceite     TINYINT(1) NOT NULL DEFAULT 0,

  perfil ENUM(
    'paciente',
    'profissional',
    'cuidador',
    'administrador'
  ) NOT NULL DEFAULT 'paciente',

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Consultas agendadas

CREATE TABLE IF NOT EXISTS consultas (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id      INT UNSIGNED NOT NULL,
  medico           VARCHAR(120) NOT NULL,
  especialidade    VARCHAR(100) NOT NULL,
  local            VARCHAR(150) NOT NULL
                   DEFAULT 'MedConecta - Telemedicina',
  data_hora        DATETIME NOT NULL,

  status ENUM(
    'agendada',
    'realizada',
    'cancelada'
  ) NOT NULL DEFAULT 'agendada',

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_consultas_paciente
    FOREIGN KEY (paciente_id)
    REFERENCES pacientes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Documentos médicos

CREATE TABLE IF NOT EXISTS documentos (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id    INT UNSIGNED NOT NULL,
  tipo           VARCHAR(60) NOT NULL,
  titulo         VARCHAR(200) NOT NULL,
  especialidade  VARCHAR(100) NULL,
  profissional   VARCHAR(150) NULL,
  medicamentos   TEXT NULL,
  arquivo_path   VARCHAR(255) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_documentos_paciente
    FOREIGN KEY (paciente_id)
    REFERENCES pacientes(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mensagens de contato

CREATE TABLE IF NOT EXISTS contatos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(150) NOT NULL,
  email       VARCHAR(180) NOT NULL,
  assunto     VARCHAR(200) NOT NULL,
  mensagem    TEXT NOT NULL,
  lido        TINYINT(1) NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Logs de auditoria

CREATE TABLE IF NOT EXISTS logs (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo         VARCHAR(60) NOT NULL,
  mensagem     TEXT NOT NULL,
  paciente_id  INT UNSIGNED NULL,
  ip           VARCHAR(45) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_logs_paciente
    FOREIGN KEY (paciente_id)
    REFERENCES pacientes(id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- Convênios

CREATE TABLE IF NOT EXISTS convenios (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(150) NOT NULL UNIQUE,
  descricao   VARCHAR(255) NULL,
  ativo       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Convênios iniciais para demonstração

INSERT IGNORE INTO convenios (nome, descricao) VALUES
  ('Particular', 'Atendimento particular'),
  ('Unimed', 'Convênio Unimed'),
  ('Amil', 'Convênio Amil');