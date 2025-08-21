<?php
// As variáveis de sessão já são iniciadas no index.php
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'god']);
?>
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <div>
            <h2 class="text-2xl font-bold text-[#4A90E2]">Calendário de Eventos</h2>
            <p class="text-gray-600">Visualize os eventos da empresa, feriados e datas importantes.</p>
        </div>
        <?php if ($is_admin): ?>
            <button id="btn-toggle-evento-form" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium flex items-center transition-transform transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i> Adicionar Evento
            </button>
        <?php endif; ?>
    </div>

    <!-- Formulário para Adicionar/Editar Evento (Oculto por padrão) -->
    <div id="form-add-evento" class="hidden bg-gray-50 p-6 rounded-lg border border-gray-200 mb-6">
        <h3 id="form-evento-title" class="text-lg font-semibold text-[#4A90E2] mb-4">Novo Evento</h3>
        <form id="form-evento" class="space-y-4">
            <input type="hidden" name="evento_id" id="evento_id">
            <div>
                <label for="evento_titulo" class="block text-sm font-medium text-gray-700">Título do Evento</label>
                <input type="text" id="evento_titulo" name="titulo" required class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="evento_descricao" class="block text-sm font-medium text-gray-700">Descrição (Opcional)</label>
                <textarea id="evento_descricao" name="descricao" rows="3" class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="evento_data_inicio" class="block text-sm font-medium text-gray-700">Data de Início</label>
                    <input type="datetime-local" id="evento_data_inicio" name="data_inicio" required class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="evento_data_fim" class="block text-sm font-medium text-gray-700">Data de Fim (Opcional)</label>
                    <input type="datetime-local" id="evento_data_fim" name="data_fim" class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div>
                <label for="evento_cor" class="block text-sm font-medium text-gray-700">Cor do Evento</label>
                <input type="color" id="evento_cor" name="cor" value="#3788d8" class="mt-1 w-full h-10 p-1 border border-gray-300 rounded-md cursor-pointer">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="btn-cancelar-evento" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Salvar Evento</button>
            </div>
        </form>
    </div>

    <!-- Container do Calendário -->
    <div id="calendar" class="mt-6"></div>
</div>

<!-- Modal para visualizar detalhes do evento -->
<div id="event-details-modal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg transform transition-all scale-95 opacity-0">
        <div class="flex justify-between items-center border-b pb-3 mb-4">
            <h3 id="modal-event-title" class="text-xl font-semibold text-[#4A90E2]">Detalhes do Evento</h3>
            <button id="close-event-modal" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        <div class="space-y-4">
            <div id="modal-event-description-container">
                <p class="text-sm font-medium text-gray-500">Descrição</p>
                <p id="modal-event-description" class="text-gray-800 mt-1"></p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Início</p>
                <p id="modal-event-start" class="text-gray-800 mt-1"></p>
            </div>
            <div id="modal-event-end-container">
                <p class="text-sm font-medium text-gray-500">Fim</p>
                <p id="modal-event-end" class="text-gray-800 mt-1"></p>
            </div>
        </div>
        <div class="flex justify-end mt-6 pt-4 border-t">
            <?php if ($is_admin): ?>
            <button id="btn-delete-evento" class="px-4 py-2 bg-red-600 text-white rounded-md mr-2 hover:bg-red-700">Excluir</button>
            <button id="btn-edit-evento" class="px-4 py-2 bg-blue-600 text-white rounded-md mr-2 hover:bg-blue-700">Editar</button>
            <?php endif; ?>
            <button id="btn-close-modal-details" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Fechar</button>
        </div>
    </div>
</div>
