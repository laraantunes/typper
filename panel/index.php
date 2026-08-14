<?php

require_once __DIR__ . '/session.php';
if (empty($_SESSION['typper_logged_in'])) {
    header("Location: login.php");
    exit;
}

$contents_dir = __DIR__ . '/../contents';
if (!is_dir($contents_dir)) {
    @mkdir($contents_dir, 0755, true);
}

// Lógica de exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_slug = $_POST['slug'] ?? '';
    // Proteção básica
    $delete_slug = preg_replace('/[^a-z0-9\-\/]/', '', strtolower(trim($delete_slug)));
    if ($delete_slug) {
        $file_path = $contents_dir . '/' . $delete_slug . '.md';
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
}

// Carregar categorias
use Symfony\Component\Yaml\Yaml;
require_once __DIR__ . '/../vendor/autoload.php'; // Ensure autoload is included for Yaml
$categories_file = __DIR__ . '/../config/categories.yml';
$categories = file_exists($categories_file) ? Yaml::parseFile($categories_file) : [];

$search_query = mb_strtolower(trim($_GET['q'] ?? ''));
$filter_category = trim($_GET['category'] ?? '');
$filter_tag = trim($_GET['tag'] ?? '');
$filter_published = $_GET['published'] ?? '';

// Ler os arquivos markdown recursivamente
$all_files = [];
$all_tags = [];
$files = [];
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($contents_dir, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($dir as $fileinfo) {
    if ($fileinfo->isFile() && $fileinfo->getExtension() === 'md') {
        $filename = $fileinfo->getFilename();
        $slug = $fileinfo->getBasename('.md');
        
        // Determinar a categoria baseada na pasta
        $relativePath = str_replace('\\', '/', str_replace($contents_dir, '', $fileinfo->getPath()));
        $category = trim($relativePath, '/');
        
        // O slug real que o editor espera (ex: categoria/meu-post)
        $fullSlug = $category ? $category . '/' . $slug : $slug;

        // Ler metadados rudimentar para listar
        $content = file_get_contents($fileinfo->getPathname());
        $parts = explode('===', $content, 2);
        
        $title = $slug;
        $type = 'post';
        $published = true;
        
        $tags = [];
        $markdown = '';
        if (count($parts) > 1) {
            $meta = trim($parts[0]);
            $markdown = strtolower(trim($parts[1]));
            if (preg_match('/title:\s*(.*)/i', $meta, $matches)) {
                $title = trim($matches[1]);
            }
            if (preg_match('/type:\s*(.*)/i', $meta, $matches)) {
                $type = trim($matches[1]);
            }
            if (preg_match('/tags:\s*(?:\[)?([^\]\n]+)(?:\])?/i', $meta, $matches)) {
                $tags_str = trim($matches[1]);
                $tags = array_filter(array_map('trim', explode(',', $tags_str)));
                foreach ($tags as $t) {
                    $all_tags[$t] = true;
                }
            }
            if (preg_match('/published:\s*(.*)/i', $meta, $matches)) {
                $pub_val = strtolower(trim($matches[1]));
                $published = ($pub_val === 'true' || $pub_val === '1');
            }
        } else {
            $markdown = strtolower(trim($content));
        }
        
        $all_files[] = [
            'slug' => $fullSlug,
            'category' => $category,
            'filename' => $filename,
            'title' => $title,
            'type' => $type,
            'published' => $published,
            'modified' => $fileinfo->getMTime(),
            'tags' => $tags,
            'markdown' => $markdown
        ];
    }
}

ksort($all_tags);
$all_tags_list = array_keys($all_tags);

// Filtrar os arquivos
$files = [];
foreach ($all_files as $file) {
    if ($filter_category === '--root--') {
        if ($file['category'] !== '') continue;
    } elseif ($filter_category && $file['category'] !== $filter_category) {
        continue;
    }
    if ($filter_tag && !in_array($filter_tag, $file['tags'])) {
        continue;
    }
    if ($filter_published !== '') {
        $is_pub = $filter_published === '1';
        if ($file['published'] !== $is_pub) {
            continue;
        }
    }
    if ($search_query) {
        $search_title = mb_strtolower($file['title']);
        if (strpos($search_title, $search_query) === false && strpos($file['markdown'], $search_query) === false) {
            continue;
        }
    }
    $files[] = $file;
}

// Ordenar por data de modificação
usort($files, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Typper</title>
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
            <form method="GET" class="glass-panel" style="margin-bottom: 2rem; padding: 1.5rem; display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                    <label class="form-label">Buscar (Título ou Conteúdo)</label>
                    <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Digite sua busca...">
                </div>
                <div class="form-group" style="width: 200px; margin-bottom: 0;">
                    <label class="form-label">Categoria</label>
                    <select name="category" class="form-control">
                        <option value="">Todas</option>
                        <option value="--root--" <?= $filter_category === '--root--' ? 'selected' : '' ?>>Raiz (Sem pasta)</option>
                        <?php foreach ($categories as $catSlug => $catData): ?>
                            <option value="<?= htmlspecialchars($catSlug) ?>" <?= $filter_category === $catSlug ? 'selected' : '' ?>>
                                <?= htmlspecialchars($catData['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="width: 200px; margin-bottom: 0;">
                    <label class="form-label">Tag</label>
                    <select name="tag" class="form-control">
                        <option value="">Todas</option>
                        <?php foreach ($all_tags_list as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $filter_tag === $t ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="width: 150px; margin-bottom: 0;">
                    <label class="form-label">Publicado</label>
                    <select name="published" class="form-control">
                        <option value="">Todos</option>
                        <option value="1" <?= $filter_published === '1' ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= $filter_published === '0' ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <?php if ($search_query || $filter_category || $filter_tag || $filter_published !== ''): ?>
                        <a href="index.php" class="btn btn-secondary">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (empty($files)): ?>
                <div class="glass-panel empty-state">
                    <h3>Nenhum conteúdo encontrado</h3>
                    <p style="color: var(--color-text-muted); margin-bottom: 2rem;">Não há resultados para os filtros atuais ou não há arquivos.</p>
                    <a href="editor.php" class="btn btn-primary">Criar Conteúdo</a>
                </div>
            <?php else: ?>
                <div class="content-list">
                    <?php foreach ($files as $file): ?>
                        <div class="content-card glass-panel" onclick="window.location.href='editor.php?slug=<?= urlencode($file['slug']) ?>'" style="cursor: pointer;">
                            <h3 class="content-title"><?= htmlspecialchars($file['title']) ?></h3>
                            <div class="content-meta">
                                <?php if (!empty($file['category'])): ?>
                                    <span style="background: var(--color-purple);">/<?= htmlspecialchars($file['category']) ?></span>
                                <?php endif; ?>
                                <span>/<?= htmlspecialchars(basename($file['slug'])) ?></span>
                                <span><?= htmlspecialchars(ucfirst($file['type'])) ?></span>
                                <span><?= date('d/m/Y', $file['modified']) ?></span>
                                <?php if (!$file['published']): ?>
                                    <span style="background: rgba(255, 71, 87, 0.15); color: #ff4757; border: 1px solid rgba(255, 71, 87, 0.3);">Rascunho</span>
                                <?php endif; ?>
                                <?php if (!empty($file['tags'])): ?>
                                    <span style="opacity: 0.7; margin-left: auto;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 2px;">
                                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                                        </svg>
                                        <?= htmlspecialchars(implode(', ', $file['tags'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="content-actions">
                                <span class="btn btn-secondary btn-icon" title="Editar">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </span>
                                <form method="POST" style="display: inline;" onclick="event.stopPropagation();" onsubmit="return confirm('Tem certeza que deseja excluir este conteúdo?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="slug" value="<?= htmlspecialchars($file['slug']) ?>">
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
</body>
</html>
