<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
exigir_login();

// 1. Busca os médicos e suas especialidades diretamente do banco de dados
$sql = "SELECT p.id as profissional_id, p.nome as medico, e.nome as especialidade, e.id as especialidade_id
        FROM pacientes p 
        INNER JOIN profissional_especialidades pe ON p.id = pe.profissional_id 
        INNER JOIN especialidades e ON pe.especialidade_id = e.id 
        WHERE p.perfil = 'profissional'";
$stmt = db()->query($sql);
$profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tituloPagina = 'Agendamento';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container container-narrow">
    <h1>Agendar consulta</h1>

    <form id="form-agendamento" class="form-card form-large" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      
      <!-- Campos ocultos para enviar os IDs corretos para o banco -->
      <input type="hidden" id="profissional_id" name="profissional_id" value="">
      <input type="hidden" id="especialidade_id" name="especialidade_id" value="">

      <div class="form-group">
        <label for="medico">Nome do médico</label>
        <select id="medico" name="medico" required class="input-large" aria-required="true">
  <option value="">Selecione...</option>
  <?php foreach ($profissionais as $prof): ?>
    <option 
        value="<?= htmlspecialchars((string)$prof['profissional_id']) ?>" 
        data-esp-id="<?= htmlspecialchars((string)$prof['especialidade_id']) ?>" 
        data-esp-nome="<?= htmlspecialchars($prof['especialidade']) ?>"
    >
      <?= htmlspecialchars($prof['medico'] . ' — ' . $prof['especialidade']) ?>
    </option>
  <?php endforeach; ?>
</select>
      </div>

      <div class="form-group">
        <label for="especialidade">Especialidade</label>
        <input type="text" id="especialidade" name="especialidade" required class="input-large" readonly aria-readonly="true">
      </div>

      <div class="form-group">
        <label for="local">Local</label>
        <select id="local" name="local" class="input-large">
          <option value="MedConecta — Telemedicina (online)">Telemedicina (online)</option>
          <option value="Clínica SENAC">Clínica SENAC</option>
          <option value="Laboratório Parceiro — Unidade Centro">Laboratório — Unidade Centro</option>
        </select>
      </div>

      <div class="form-group">
        <label for="data_hora">Data e horário</label>
        <input type="datetime-local" id="data_hora" name="data_hora" required class="input-large" aria-required="true">
      </div>

      <div id="form-feedback" class="feedback" role="alert" aria-live="polite"></div>
      <button type="submit" class="btn btn-primary btn-xl btn-block">Confirmar agendamento</button>
    </form>

    <div id="confirmacao-agendamento" class="confirmacao-card hidden" role="status" aria-live="polite">
      <h2>✓ Consulta confirmada!</h2>
      <p id="confirmacao-texto"></p>
    </div>

    <section class="card mt-lg" aria-labelledby="minhas-consultas">
      <h2 id="minhas-consultas">Minhas consultas</h2>
      <div id="agenda-lista"></div>
    </section>
  </div>
</section>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const selectMedico = document.getElementById('medico');
    const inputEspecialidade = document.getElementById('especialidade');
    const inputProfissionalId = document.getElementById('profissional_id');
    const inputEspecialidadeId = document.getElementById('especialidade_id');

    selectMedico.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value !== "") {
            // Preenche os campos com os dados do atributo data-* da opção selecionada
            inputEspecialidade.value = selectedOption.getAttribute('data-esp-nome') || '';
            inputProfissionalId.value = selectedOption.value;
            inputEspecialidadeId.value = selectedOption.getAttribute('data-esp-id') || '';
        } else {
            // Limpa os campos se voltar para "Selecione..."
            inputEspecialidade.value = '';
            inputProfissionalId.value = '';
            inputEspecialidadeId.value = '';
        }
    });
});
</script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>