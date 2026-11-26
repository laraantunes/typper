<?php
$current_page = basename($_SERVER['PHP_SELF']);
if (!isset($base_url)) {
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = dirname($script_path);
    if ($base_url === '\\' || $base_url === '/') {
        $base_url = '';
    }
}
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
        <a href="options.php" class="<?= $current_page === 'options.php' ? 'active' : '' ?>">Opções</a>
    </nav>

    <div class="header-actions">
        <?php if ($current_page === 'index.php'): ?>
            <a href="<?= $base_url ?>/" target="_blank" class="btn btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px;">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                Visualizar Site
            </a>
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

<!-- Styled confirmation, used instead of the browser's confirm().
     typperConfirm(message, onYes, { title, confirmLabel }); forms can use data-confirm="…" -->
<div class="confirm-modal" id="typper-confirm" role="alertdialog" aria-modal="true" aria-labelledby="typper-confirm-title">
    <div class="confirm-modal-content glass-panel">
        <h3 id="typper-confirm-title">Confirmar</h3>
        <p id="typper-confirm-message"></p>
        <div class="confirm-modal-actions">
            <button type="button" class="btn btn-secondary" id="typper-confirm-cancel">Cancelar</button>
            <button type="button" class="btn btn-danger" id="typper-confirm-ok">Confirmar</button>
        </div>
    </div>
</div>
<script>
    (function () {
        const modal = document.getElementById('typper-confirm');
        let pending = null;

        function close(confirmed) {
            modal.classList.remove('active');
            const callbacks = pending;
            pending = null;
            if (!callbacks) return;
            if (confirmed) callbacks.onYes();
            else if (callbacks.onNo) callbacks.onNo();
        }

        window.typperConfirm = function (message, onYes, options = {}) {
            document.getElementById('typper-confirm-title').textContent = options.title || 'Confirmar';
            document.getElementById('typper-confirm-message').textContent = message;
            const ok = document.getElementById('typper-confirm-ok');
            ok.textContent = options.confirmLabel || 'Confirmar';
            ok.className = 'btn ' + (options.danger === false ? 'btn-primary' : 'btn-danger');
            modal.classList.toggle('is-neutral', options.danger === false);
            pending = { onYes, onNo: options.onNo };
            modal.classList.add('active');
            document.getElementById('typper-confirm-ok').focus();
        };

        document.getElementById('typper-confirm-ok').addEventListener('click', () => close(true));
        document.getElementById('typper-confirm-cancel').addEventListener('click', () => close(false));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(false); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                e.stopImmediatePropagation();
                close(false);
            }
        }, true);

        // Promise version: if (!(await typperConfirmAsync('…'))) return;
        window.typperConfirmAsync = (message, options = {}) => new Promise(resolve =>
            typperConfirm(message, () => resolve(true), { ...options, onNo: () => resolve(false) }));

        // <form data-confirm="…" data-confirm-title="…" data-confirm-label="…">
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!form.dataset || !form.dataset.confirm) return;
            e.preventDefault();
            typperConfirm(form.dataset.confirm, () => form.submit(), {
                title: form.dataset.confirmTitle,
                confirmLabel: form.dataset.confirmLabel,
            });
        });
    })();
</script>
