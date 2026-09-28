-- Execute após cadastrar um paciente (substitua :paciente_id pelo ID real)
-- Exemplo: paciente_id = 1

USE medconecta;

INSERT INTO documentos (paciente_id, tipo, titulo, especialidade, profissional, medicamentos, arquivo_path) VALUES
(1, 'receita', 'Receita — Losartana 50mg', 'Cardiologia', 'Dr. Carlos Mendes', 'Losartana', '/docs/receita-losartana.pdf'),
(1, 'atestado', 'Atestado médico — 3 dias', 'Clínica Geral', 'Dra. Ana Silva', NULL, '/docs/atestado.pdf'),
(1, 'exame', 'Hemograma completo', 'Laboratório', 'Lab. Parceiro', NULL, '/docs/hemograma.pdf');

INSERT INTO consultas (paciente_id, medico, especialidade, local, data_hora, status) VALUES
(1, 'Dr. Carlos Mendes', 'Cardiologia', 'MedConecta — Telemedicina (online)', DATE_ADD(NOW(), INTERVAL 7 DAY), 'agendada');
