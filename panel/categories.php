<?php
require_once __DIR__ . '/session.php';
if (empty($_SESSION['typper_logged_in'])) {
    header("Location: login.php");
    exit;
}

use Symfony\Component\Yaml\Yaml;

$categories_file = __DIR__ . '/../config/categories.yml';
$categories = [];
if (file_exists($categories_file)) {
    $categories = Yaml::parseFile($categories_file) ?: [];
}

$error = '';
$success = '';

// Handle add, edit, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug)));

    if ($action === 'delete') {
        if (isset($categories[$slug])) {
            unset($categories[$slug]);
            $yaml = Yaml::dump($categories, 4, 4);
            file_put_contents($categories_file, $yaml);
            $success = "Categoria removida com sucesso!";
        }
    } elseif ($action === 'save') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $old_slug = $_POST['old_slug'] ?? '';

        if (empty($slug) || empty($title)) {
            $error = "O Slug e o Título são obrigatórios.";
        } else {
            // Remove old key if slug changed
            if ($old_slug && $old_slug !== $slug && isset($categories[$old_slug])) {
                unset($categories[$old_slug]);
            }

            $categories[$slug] = [
                'title' => $title
            ];
            if (!empty($description)) {
                $categories[$slug]['description'] = $description;
            }

            $yaml = Yaml::dump($categories, 4, 4);
            if (file_put_contents($categories_file, $yaml) !== false) {
                // Ensure contents folder exists for category
                $contents_dir = __DIR__ . '/../contents/' . $slug;
                if (!is_dir($contents_dir)) {
                    @mkdir($contents_dir, 0755, true);
                }
                $success = "Categoria salva com sucesso!";
            } else {
                $error = "Erro ao salvar o arquivo de categorias.";
            }
        }
    }
}

// Reload after modifications
if (file_exists($categories_file)) {
    $categories = Yaml::parseFile($categories_file) ?: [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Typper</title>
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
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--color-bg-light);
            border: 1px solid var(--color-glass-border);
            padding: 2rem;
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 500px;
        }
    </style>
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

            <?php if (empty($categories)): ?>
                <div class="glass-panel empty-state">
                    <h3>Nenhuma categoria encontrada</h3>
                    <p style="color: var(--color-text-muted);">Crie categorias para organizar seus conteúdos em pastas.</p>
                </div>
            <?php else: ?>
                <div class="content-list">
                    <?php foreach ($categories as $slug => $cat): ?>
                        <div class="content-card glass-panel" style="display: block; cursor: pointer;" onclick="editCategory('<?= htmlspecialchars($slug) ?>', '<?= htmlspecialchars($cat['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES) ?>')">
                            <h3 class="content-title"><?= htmlspecialchars($cat['title']) ?></h3>
                            <div class="content-meta" style="margin-bottom: 1rem;">
                                <span style="background: var(--color-purple);">/<?= htmlspecialchars($slug) ?></span>
                            </div>
                            <?php if (!empty($cat['description'])): ?>
                                <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">
                                    <?= htmlspecialchars($cat['description']) ?>
                                </p>
                            <?php else: ?>
                                <p style="margin-bottom: 1.5rem;"></p>
                            <?php endif; ?>
                            
                            <div class="content-actions" style="margin-top: auto;">
                                <button type="button" class="btn btn-secondary btn-icon" title="Editar" onclick="event.stopPropagation(); editCategory('<?= htmlspecialchars($slug) ?>', '<?= htmlspecialchars($cat['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES) ?>')">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                <form method="POST" style="display: inline;" onclick="event.stopPropagation();" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? Os arquivos contidos nela NÃO serão apagados, mas ficarão sem categoria registrada no sistema.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($slug) ?>">
                                    <button type="submit" class="btn btn-danger btn-icon" title="Excluir">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Categoria -->
    <div class="modal" id="modal-categoria">
        <div class="modal-content">
            <h3 style="margin-top: 0; color: var(--color-cyan);" id="modal-title">Nova Categoria</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="old_slug" id="cat-old-slug" value="">
                
                <div class="form-group">
                    <label class="form-label" for="cat-title">Título</label>
                    <input type="text" class="form-control" id="cat-title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="cat-slug">Slug da Pasta</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" class="form-control" id="cat-slug" name="slug" required placeholder="ex: tutoriais">
                        <button type="button" class="btn btn-secondary btn-icon" onclick="refreshCatSlug()" title="Atualizar Slug" style="flex-shrink: 0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="cat-desc">Descrição (Opcional)</label>
                    <textarea class="form-control" id="cat-desc" name="description" rows="2"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="document.getElementById('modal-categoria').classList.remove('active')">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Slug autogenerate for Categories
        const catTitleInput = document.getElementById('cat-title');
        const catSlugInput = document.getElementById('cat-slug');
        catTitleInput.addEventListener('input', function() {
            if (!document.getElementById('cat-old-slug').value) {
                catSlugInput.value = this.value.toLowerCase()
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                    .replace(/[^a-z0-9]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });

        function refreshCatSlug() {
            const title = document.getElementById('cat-title').value;
            if (title) {
                document.getElementById('cat-slug').value = title.toLowerCase()
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                    .replace(/[^a-z0-9]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        }

        function editCategory(slug, title, desc) {
            document.getElementById('modal-title').innerText = 'Editar Categoria';
            document.getElementById('cat-old-slug').value = slug;
            document.getElementById('cat-slug').value = slug;
            document.getElementById('cat-title').value = title;
            document.getElementById('cat-desc').value = desc;
            document.getElementById('modal-categoria').classList.add('active');
        }
    </script>
</body>
</html>
