-- Execute depois de database/migration_locais.sql.
-- Terceiro local de demonstração, sem convênio vinculado, para validar RF06 e RF07.
-- Nome e endereço são fictícios; substitua por dados reais antes de uso fora da apresentação.
INSERT IGNORE INTO locais_atendimento
    (nome, tipo, endereco, latitude, longitude)
VALUES
    ('Laboratório Demonstrativo', 'Laboratorio',
     'Endereço fictício para demonstração, Brasília - DF', -15.81000000, -47.90000000);
