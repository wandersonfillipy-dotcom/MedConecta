<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
exigir_login();
$tituloPagina = 'Agendamentos';
$layout = 'dashboard';

$especialidadesOpts = [];
try {
    $especialidadesOpts = db()->query('SELECT nome FROM especialidades WHERE ativo = 1 ORDER BY nome')->fetchAll();
} catch (Throwable) {
    $especialidadesOpts = [];
}

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <h1>Agendamentos</h1>
  <p class="subtitle">CRUD de consultas — agendar, editar, cancelar e excluir.</p>
</div>

<section class="card crud-section">
  <h2 id="form-consulta-titulo">Nova consulta</h2>
  <form id="form-consulta" class="crud-form">
    <input type="hidden" name="id" id="consulta-id">
    <div class="form-row">
      <div class="form-group">
        <label for="consulta-medico">Médico</label>
        <input type="text" id="consulta-medico" name="medico" required placeholder="Dr(a). Nome">
      </div>
      <div class="form-group">
        <label for="consulta-especialidade">Especialidade</label>
        <input type="text" id="consulta-especialidade" name="especialidade" required list="lista-esp">
        <datalist id="lista-esp">
          <?php foreach ($especialidadesOpts as $e): ?>
            <option value="<?= e($e['nome']) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="consulta-local">Local</label>
        <input type="text" id="consulta-local" name="local" value="MedConecta — Telemedicina">
      </div>
      <div class="form-group">
        <label for="consulta-data">Data e hora</label>
        <input type="datetime-local" id="consulta-data" name="data_hora" required>
      </div>
    </div>
    <div class="form-group">
      <label for="consulta-status">Status</label>
      <select id="consulta-status" name="status">
        <option value="agendada">Agendada</option>
        <option value="pendente">Pendente</option>
        <option value="realizada">Realizada</option>
        <option value="cancelada">Cancelada</option>
      </select>
    </div>
    <div id="crud-feedback-consultas" class="feedback" role="alert"></div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <button type="button" class="btn btn-ghost" id="consulta-cancelar" hidden>Cancelar edição</button>
    </div>
  </form>
</section>

<section class="card mt">
  <h2>Consultas marcadas</h2>
  <div class="table-wrap">
    <table class="data-table" id="tabela-consultas">
      <thead>
        <tr>
          <th>Médico</th><th>Especialidade</th><th>Data/Hora</th><th>Local</th><th>Status</th><th>Ações</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</section>

<script>document.addEventListener('DOMContentLoaded', () => MedConectaCrud.initConsultas());</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
