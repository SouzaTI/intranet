    <div class="row">
            <?php
            require_once 'conexao.php'; // Ensure conexao.php is included

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
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><i class="fas fa-briefcase mr-2"></i><?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted"><i class="fas fa-building mr-2"></i><?php echo htmlspecialchars($vaga['setor_nome']); ?></h6>
                                <p class="card-text"><strong>Descrição:</strong><br><?php echo nl2br(htmlspecialchars($vaga['descricao'])); ?></p>
                                <p class="card-text"><strong>Requisitos:</strong><br><?php echo nl2br(htmlspecialchars($vaga['requisitos'])); ?></p>
                            </div>
                            <div class="card-footer text-muted d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-calendar-alt mr-2"></i>Publicado em: <?php echo $data_publicacao_formatada; ?></span>
                                <!-- Add a button to view more details if needed -->
                                <!-- <a href="#" class="btn btn-sm btn-outline-primary">Ver Detalhes</a> -->
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
    
    