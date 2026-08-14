<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="header glass-panel">
    <div class="brand">
        <img src="icon.svg" width="24" height="24" alt="Typper Logo">
        Typper
    </div>
    
    <nav class="header-nav">
        <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="categories.php" class="<?= $current_page === 'categories.php' ? 'active' : '' ?>">Categorias</a>
        <a href="site.php" class="<?= $current_page === 'site.php' ? 'active' : '' ?>">Site</a>
    </nav>

    <div class="header-actions">
        <?php if ($current_page === 'index.php'): ?>
            <a href="editor.php" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"></path>
                </svg>
                Novo Conteúdo
            </a>
        <?php elseif ($current_page === 'categories.php'): ?>
            <button onclick="document.getElementById('modal-categoria').classList.add('active')" class="btn btn-primary">Nova Categoria</button>
        <?php elseif ($current_page === 'editor.php'): ?>
            <?php if (!empty($_GET['slug'])): ?>
                <a href="<?= $base_url ?>/<?= htmlspecialchars($_GET['slug']) ?>" target="_blank" class="btn btn-secondary" title="Abrir em nova guia">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px;">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    Visualizar
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
            <button type="button" class="btn btn-primary" id="btn-save">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Salvar
            </button>
        <?php elseif ($current_page === 'site.php'): ?>
            <button type="button" class="btn btn-primary" id="btn-save-site">Salvar</button>
        <?php endif; ?>

        <?php if ($current_page !== 'editor.php'): ?>
            <a href="logout.php" class="btn btn-secondary" style="margin-left: 10px;">Sair</a>
        <?php endif; ?>
    </div>
</header>
