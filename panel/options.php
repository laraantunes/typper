<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../about.php';
if (empty($_SESSION['typper_logged_in'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opções do Sistema - Typper</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="icon.svg" type="image/svg+xml">
</head>
<body>
    <div class="container">
        <?php include 'layout_header.php'; ?>

        <main>
            <div id="alert-container"></div>

            <div style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem;">
                
                <!-- Sobre o Typper -->
                <div class="glass-panel" style="padding: 2rem; text-align: center;">
                    <img src="icon.svg" alt="Typper Logo" style="width: 80px; height: 80px; margin-bottom: 1rem;">
                    <h3 style="margin-top: 0; margin-bottom: 0.5rem; color: var(--color-cyan);">Typper</h3>
                    <p style="color: var(--color-text-muted); margin-bottom: 1.5rem;">Um CMS minimalista baseado em Markdown.</p>
                    <p style="color: var(--color-text-muted); margin-bottom: 0.5rem;"><?=$version?></p>
                    <p style="color: var(--color-text-muted); margin-bottom: 1.5rem;">
                        <a href="https://github.com/laraantunes/typper" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            github.com/laraantunes/typper
                        </a>
                    </p>
                    <a href="https://laralabs.dev" target="_blank" class="btn btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <img src="laralabs-icon.png" style="border:none;width:16px;height:16px"/>
                        Laralabs
                    </a>
                </div>

                <!-- Atualização de Sistema -->
                <div class="glass-panel" style="padding: 2rem;">
                    <h3 style="margin-top: 0; margin-bottom: 1rem; color: var(--color-cyan);">Atualização de Sistema</h3>
                    <p style="color: var(--color-text-muted); margin-bottom: 1.5rem; line-height: 1.6;">
                        Mantenha seu Typper CMS sempre na última versão. O processo de atualização fará o download da última release oficial via GitHub e substituirá os arquivos do núcleo, sem apagar seus conteúdos, configurações ou temas (desde que não modifique os arquivos *core*).
                    </p>
                    
                    <button id="btn-update" class="btn btn-primary" style="width: 100%; display: flex; justify-content: center; gap: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-icon">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span class="btn-text">Verificar e Atualizar Agora</span>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        const btnUpdate = document.getElementById('btn-update');
        const alertContainer = document.getElementById('alert-container');

        btnUpdate.addEventListener('click', async () => {
            if (!confirm('Deseja iniciar a verificação e atualização do sistema?')) {
                return;
            }

            // UI Feedback
            const originalHtml = btnUpdate.innerHTML;
            btnUpdate.disabled = true;
            btnUpdate.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="4.93" x2="19.07" y2="7.76"></line>
                </svg>
                <span class="btn-text">Atualizando... Não feche a página!</span>
            `;

            alertContainer.innerHTML = '';

            try {
                const response = await fetch('update_action.php', { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-error">${data.message || 'Erro desconhecido'}</div>`;
                }
            } catch (err) {
                alertContainer.innerHTML = `<div class="alert alert-error">Falha ao se comunicar com o servidor.</div>`;
            } finally {
                btnUpdate.disabled = false;
                btnUpdate.innerHTML = originalHtml;
            }
        });
    </script>
    <style>
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</body>
</html>
