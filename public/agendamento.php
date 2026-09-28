<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
exigir_login();
$tituloPagina = 'Agendamento';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="section">
  <div class="container container-narrow">
    <h1>Agendar consulta</h1>
    <p class="subtitle">Interface com botões grandes e confirmação visual clara — pensada para acessibilidade.</p>

    <form id="form-agendamento" class="form-card form-large" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <div class="form-group">
        <label for="medico">Nome do médico</label>
        <select id="medico" name="medico" required class="input-large" aria-required="true">
          <option value="">Selecione...</option>
          <option value="Dra. Ana Silva">Dra. Ana Silva — Clínica Geral</option>
          <option value="Dr. Carlos Mendes">Dr. Carlos Mendes — Cardiologia</option>
          <option value="Dra. Marina Costa">Dra. Marina Costa — Pediatria</option>
          <option value="Dr. Paulo Ribeiro">Dr. Paulo Ribeiro — Neurologia</option>
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
          <option value="Clínica SENAC — Sala 204">Clínica SENAC — Sala 204</option>
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

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
