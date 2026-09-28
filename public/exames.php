<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
exigir_login();
$tituloPagina = 'Meus Exames';
$layout = 'dashboard';
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
  <h1>Meus Exames</h1>
  <p class="subtitle">CRUD completo — criar, listar, editar e excluir exames.</p>
</div>

<section class="card crud-section">
  <h2 id="form-exame-titulo">Novo exame</h2>
  <form id="form-exame" class="crud-form">
    <input type="hidden" name="id" id="exame-id">
    <div class="form-row">
      <div class="form-group">
        <label for="exame-nome">Nome do exame</label>
        <input type="text" id="exame-nome" name="nome" required placeholder="Ex: Hemograma Completo">
      </div>
      <div class="form-group">
        <label for="exame-data">Data</label>
        <input type="date" id="exame-data" name="data_exame" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="exame-tipo">Tipo</label>
        <input type="text" id="exame-tipo" name="tipo" required placeholder="Laboratorial, Imagem...">
      </div>
      <div class="form-group">
        <label for="exame-status">Status</label>
        <select id="exame-status" name="status">
          <option value="pendente">Pendente</option>
          <option value="disponivel">Disponível</option>
          <option value="cancelado">Cancelado</option>
        </select>
      </div>
    </div>
    <div id="crud-feedback-exames" class="feedback" role="alert"></div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <button type="button" class="btn btn-ghost" id="exame-cancelar" hidden>Cancelar edição</button>
    </div>
  </form>
</section>

<section class="card mt">
  <h2>Lista de exames</h2>
  <div class="table-wrap">
    <table class="data-table" id="tabela-exames">
      <thead>
        <tr>
          <th>Exame</th><th>Data</th><th>Tipo</th><th>Status</th><th>Ações</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    MedConectaCrud.initExames();
  });
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
