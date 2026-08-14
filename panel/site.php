<?php
require_once __DIR__ . '/session.php';
if (empty($_SESSION['typper_logged_in'])) {
    header("Location: login.php");
    exit;
}

use Symfony\Component\Yaml\Yaml;

$site_file = __DIR__ . '/../config/site.yml';
$siteData = [];

if (file_exists($site_file)) {
    $siteData = Yaml::parseFile($site_file) ?: [];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteData['siteTitle'] = $_POST['siteTitle'] ?? '';
    $siteData['author'] = $_POST['author'] ?? '';
    $siteData['theme'] = $_POST['theme'] ?? 'default';
    
    // Podem ser adicionados mais campos livremente aqui
    $siteData['description'] = $_POST['description'] ?? '';
    
    // Analytics
    $siteData['ga_code'] = $_POST['ga_code'] ?? '';
    
    // SEO
    $siteData['seo_append_title'] = isset($_POST['seo_append_title']);
    $siteData['seo_title_separator'] = $_POST['seo_title_separator'] ?? '|';
    $siteData['seo_auto_description'] = isset($_POST['seo_auto_description']);
    $siteData['seo_max_description'] = (int)($_POST['seo_max_description'] ?? 30);
    $siteData['seo_auto_keywords'] = isset($_POST['seo_auto_keywords']);
    $siteData['seo_max_keywords'] = (int)($_POST['seo_max_keywords'] ?? 20);
    $siteData['seo_auto_og'] = isset($_POST['seo_auto_og']);
    $siteData['seo_auto_twitter'] = isset($_POST['seo_auto_twitter']);

    try {
        $yaml = Yaml::dump($siteData, 4, 4);
        file_put_contents($site_file, $yaml);
        $success = 'Configurações salvas com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao salvar as configurações: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Site - Typper</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="icon.svg" type="image/svg+xml">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f0c29">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <?php include 'layout_header.php'; ?>

        <main>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="form-site" style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem;">
                
                <!-- Informações do Site -->
                <div class="glass-panel" style="padding: 2rem;">
                    <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--color-cyan);">Informações do Site</h3>
                    
                    <div class="form-group">
                        <label class="form-label" for="siteTitle">Título do Site</label>
                        <input type="text" class="form-control" id="siteTitle" name="siteTitle" value="<?= htmlspecialchars($siteData['siteTitle'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="author">Autor Padrão</label>
                        <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($siteData['author'] ?? '') ?>" required>
                    </div>
                    
                    <?php
                    $themes_dir = __DIR__ . '/../themes';
                    $themes = [];
                    if (is_dir($themes_dir)) {
                        $items = scandir($themes_dir);
                        foreach ($items as $item) {
                            if ($item !== '.' && $item !== '..' && is_dir($themes_dir . '/' . $item)) {
                                $themes[] = $item;
                            }
                        }
                    }
                    $current_theme = $siteData['theme'] ?? 'default';
                    ?>
                    <div class="form-group">
                        <label class="form-label" for="theme">Tema do Site</label>
                        <select class="form-control" id="theme" name="theme">
                            <?php foreach ($themes as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $current_theme === $t ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($t)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($siteData['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Analytics -->
                <div class="glass-panel" style="padding: 2rem;">
                    <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--color-cyan);">Analytics</h3>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="ga_code">Código Google Analytics (GA4)</label>
                        <input type="text" class="form-control" id="ga_code" name="ga_code" value="<?= htmlspecialchars($siteData['ga_code'] ?? '') ?>" placeholder="ex: G-XXXXXXXXXX">
                    </div>
                </div>

                <!-- SEO -->
                <div class="glass-panel" style="padding: 2rem;">
                    <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--color-cyan);">SEO (Otimização para Motores de Busca)</h3>
                    
                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                        <label class="form-label" style="margin-bottom: 0; cursor: pointer;">Anexar título do site à meta "title"</label>
                        <label class="custom-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="checkbox" name="seo_append_title" <?= !empty($siteData['seo_append_title']) ? 'checked' : '' ?>>
                            <span class="custom-slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="seo_title_separator">Separador para a meta "title"</label>
                        <input type="text" class="form-control" id="seo_title_separator" name="seo_title_separator" value="<?= htmlspecialchars($siteData['seo_title_separator'] ?? '|') ?>">
                    </div>
                    
                    <hr style="border: 0; border-top: 1px solid var(--color-glass-border); margin: 1.5rem 0;">
                    
                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                        <label class="form-label" style="margin-bottom: 0; cursor: pointer;">Preenchimento automático para a meta "description"</label>
                        <label class="custom-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="checkbox" name="seo_auto_description" <?= !empty($siteData['seo_auto_description']) ? 'checked' : '' ?>>
                            <span class="custom-slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="seo_max_description">Limite máximo de palavras para a meta "description"</label>
                        <input type="number" class="form-control" id="seo_max_description" name="seo_max_description" value="<?= htmlspecialchars($siteData['seo_max_description'] ?? '30') ?>">
                    </div>
                    
                    <hr style="border: 0; border-top: 1px solid var(--color-glass-border); margin: 1.5rem 0;">
                    
                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                        <label class="form-label" style="margin-bottom: 0; cursor: pointer;">Preenchimento automático para a meta "keywords"</label>
                        <label class="custom-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="checkbox" name="seo_auto_keywords" <?= !empty($siteData['seo_auto_keywords']) ? 'checked' : '' ?>>
                            <span class="custom-slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="seo_max_keywords">Limite máximo de palavras para a meta "keywords"</label>
                        <input type="number" class="form-control" id="seo_max_keywords" name="seo_max_keywords" value="<?= htmlspecialchars($siteData['seo_max_keywords'] ?? '20') ?>">
                    </div>
                    
                    <hr style="border: 0; border-top: 1px solid var(--color-glass-border); margin: 1.5rem 0;">
                    
                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                        <label class="form-label" style="margin-bottom: 0; cursor: pointer;">Preenchimento automático para as tags Open Graph (Facebook)</label>
                        <label class="custom-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="checkbox" name="seo_auto_og" <?= !empty($siteData['seo_auto_og']) ? 'checked' : '' ?>>
                            <span class="custom-slider"></span>
                        </label>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0;">
                        <label class="form-label" style="margin-bottom: 0; cursor: pointer;">Preenchimento automático para as tags do Twitter</label>
                        <label class="custom-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="checkbox" name="seo_auto_twitter" <?= !empty($siteData['seo_auto_twitter']) ? 'checked' : '' ?>>
                            <span class="custom-slider"></span>
                        </label>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        document.getElementById('btn-save-site').addEventListener('click', function() {
            document.getElementById('form-site').submit();
        });
    </script>
</body>
</html>
