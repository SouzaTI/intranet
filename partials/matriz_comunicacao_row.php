<?php
/**
 * Renderiza uma única linha (<tr>) para a tabela da Matriz de Comunicação.
 * Espera que as variáveis $funcionario e $is_admin_tab estejam definidas.
 */
?>
<tr class="hover:bg-gray-50" data-id="<?= $funcionario['id'] ?>">
    <td class="py-3 px-4 text-sm text-gray-700" data-column="nome">
        <div class="cell-content-wrapper">
            <span class="cell-content"><?= htmlspecialchars($funcionario['nome']) ?></span>
            <?php if ($is_admin_tab): ?><i class="fa-solid fa-pen-to-square edit-trigger"></i><?php endif; ?>
        </div>
    </td>
    <td class="py-3 px-4 text-sm text-gray-700" data-column="setor">
        <div class="cell-content-wrapper">
            <span class="cell-content"><?= htmlspecialchars($funcionario['setor']) ?></span>
            <?php if ($is_admin_tab): ?><i class="fa-solid fa-pen-to-square edit-trigger"></i><?php endif; ?>
        </div>
    </td>
    <td class="py-3 px-4 text-sm text-gray-700" data-column="email">
        <div class="cell-content-wrapper">
            <span class="cell-content"><?= htmlspecialchars($funcionario['email']) ?></span>
            <?php if ($is_admin_tab): ?><i class="fa-solid fa-pen-to-square edit-trigger"></i><?php endif; ?>
        </div>
    </td>
    <td class="py-3 px-4 text-sm text-gray-700" data-column="ramal">
        <div class="cell-content-wrapper">
            <span class="cell-content"><?= htmlspecialchars($funcionario['ramal']) ?></span>
            <?php if ($is_admin_tab): ?><i class="fa-solid fa-pen-to-square edit-trigger"></i><?php endif; ?>
        </div>
    </td>
</tr>

