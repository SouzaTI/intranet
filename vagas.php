    <?php
require_once 'conexao.php'; // Ensure conexao.php is included

// Fetch sectors for the modal
$setores_result = $conn->query("SELECT id, nome FROM setores ORDER BY nome");
$setores = [];
while ($setor = $setores_result->fetch_assoc()) {
    $setores[] = $setor;
}
// We need a new connection for the vacancies query, because the previous one was closed.
require_once 'conexao.php';
?>
<script>
    const phpSetores = <?php echo json_encode($setores); ?>;
</script>

    <div class="row">
            <?php

            $vagas = [];
            try {
                $stmt = $conn->prepare("SELECT v.id, v.titulo, s.nome AS setor_nome, v.descricao, v.requisitos, v.data_publicacao FROM vagas v JOIN setores s ON v.setor = s.id ORDER BY v.data_publicacao DESC");
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $vagas[] = $row;
                }
            } catch (Exception $e) {
                error_log("Erro ao carregar vagas: " . $e->getMessage());
                echo '<div class="col-12"><div class="alert alert-danger">Erro ao carregar vagas. Tente novamente mais tarde.</div></div>';
            } finally {
                if (isset($stmt)) {
                    $stmt->close();
                }
                if (isset($conn)) {
                    $conn->close();
                }
            }

            if (count($vagas) > 0) {
                foreach ($vagas as $vaga) {
                    // Format date
                    $data_publicacao_formatada = date('d/m/Y', strtotime($vaga['data_publicacao']));
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card vaga-card h-100 shadow-lg rounded-lg border-left-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0 font-weight-bold"><i class="fa-solid fa-bullhorn mr-2"></i><?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-3 text-muted font-italic"><i class="fas fa-building mr-2"></i><?php echo htmlspecialchars($vaga['setor_nome']); ?></h6>
                                <div class="mb-3">
                                    <h6 class="font-weight-bold"><i class="fas fa-info-circle mr-2"></i>Descrição</h6>
                                    <div class="vaga-descricao"><?php echo $vaga['descricao']; ?></div>
                                </div>
                                <div class="mb-3">
                                    <h6 class="font-weight-bold"><i class="fas fa-tasks mr-2"></i>Requisitos</h6>
                                    <div class="vaga-requisitos"><?php echo $vaga['requisitos']; ?></div>
                                </div>
                            </div>
                            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                                <span class="text-muted"><i class="fas fa-calendar-alt mr-2"></i><?php echo $data_publicacao_formatada; ?></span>
                                <div class="card-actions">
                                    <button class="btn btn-sm btn-outline-primary edit-vaga-btn" data-id="<?php echo $vaga['id']; ?>" title="Editar Vaga"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger delete-vaga-btn" data-id="<?php echo $vaga['id']; ?>" title="Excluir Vaga"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info">Nenhuma vaga disponível no momento.</div></div>';
            }
            ?>
        </div>

<!-- Modal de Edição de Vaga -->
<div class="modal fade" id="editVagaModal" tabindex="-1" role="dialog" aria-labelledby="editVagaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVagaModalLabel">Editar Vaga</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editVagaForm">
                    <input type="hidden" id="editVagaId" name="vaga_id">
                    <div class="form-group">
                        <label for="editVagaTitulo">Título</label>
                        <input type="text" class="form-control" id="editVagaTitulo" name="titulo" required>
                    </div>
                    <div class="form-group">
                        <label for="editVagaSetor">Setor</label>
                        <select class="form-control" id="editVagaSetor" name="setor" required>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editVagaDescricao">Descrição</label>
                        <textarea class="form-control tinymce-editor" id="editVagaDescricao" name="descricao" rows="5"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editVagaRequisitos">Requisitos</label>
                        <textarea class="form-control tinymce-editor" id="editVagaRequisitos" name="requisitos" rows="5"></textarea>
                    </div>
                    <div id="editVagaStatus"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="editVagaForm">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>
