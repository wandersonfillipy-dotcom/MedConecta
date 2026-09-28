-- Execute no phpMyAdmin se o banco já existia antes da v2
USE medconecta;

CREATE TABLE IF NOT EXISTS especialidades (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao VARCHAR(500) NOT NULL,
  icone VARCHAR(40) NOT NULL DEFAULT 'stethoscope',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_especialidades_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exames (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT UNSIGNED NOT NULL,
  nome VARCHAR(200) NOT NULL,
  data_exame DATE NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  status ENUM('pendente','disponivel','cancelado') NOT NULL DEFAULT 'pendente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_exames_paciente FOREIGN KEY (paciente_id)
    REFERENCES pacientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO especialidades (nome, descricao, icone) VALUES
('Clínico Geral', 'Atendimento integral e preventivo para todas as idades.', 'clinico'),
('Pediatria', 'Cuidado especializado para bebês, crianças e adolescentes.', 'pediatria'),
('Cardiologia', 'Saúde do coração e sistema circulatório.', 'cardio'),
('Ginecologia', 'Saúde da mulher em todas as fases da vida.', 'gineco'),
('Psicologia', 'Apoio emocional e saúde mental.', 'psico'),
('Ortopedia', 'Ossos, articulações e recuperação musculoesquelética.', 'ortopedia');
