<?php
require_once __DIR__ . '/session.php';
if (empty($_SESSION['typper_logged_in'])) {
    header("Location: login.php");
    exit;
}

$slug = $_GET['slug'] ?? '';
$title = '';
$type = 'post';
$published = true;
$markdown = '';
$category = ''; // Novo
$tags = '';

// Extrair categoria do slug, se houver
if ($slug && strpos($slug, '/') !== false) {
    $parts = explode('/', $slug, 2);
    $category = $parts[0];
    $slug = $parts[1];
}

// Carregar categorias
use Symfony\Component\Yaml\Yaml;
$categories_file = __DIR__ . '/../config/categories.yml';
$categories = file_exists($categories_file) ? Yaml::parseFile($categories_file) : [];

if ($slug) {
    $file_path = __DIR__ . '/../contents/' . ($category ? $category . '/' : '') . $slug . '.md';
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $parts = explode('===', $content, 2);
        
        if (count($parts) > 1) {
            $meta = trim($parts[0]);
            $markdown = ltrim($parts[1]);
            
            if (preg_match('/title:\s*(.*)/i', $meta, $matches)) {
                $title = trim($matches[1]);
            }
            if (preg_match('/type:\s*(.*)/i', $meta, $matches)) {
                $type = trim($matches[1]);
            }
            if (preg_match('/tags:\s*(?:\[)?([^\]\n]+)(?:\])?/i', $meta, $matches)) {
                $tags = trim($matches[1]);
            }
            if (preg_match('/published:\s*(.*)/i', $meta, $matches)) {
                $pub_val = strtolower(trim($matches[1]));
                $published = ($pub_val === 'true' || $pub_val === '1');
            }
        } else {
            $markdown = trim($content);
        }
    }
}

$images = [];
if ($slug) {
    $files_dir = __DIR__ . '/../files/' . $slug;
    if (is_dir($files_dir)) {
        $files = scandir($files_dir);
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..') {
                $images[] = $f;
            }
        }
    }
}

// URL base dinâmica
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_url = dirname($script_path);
if ($base_url === '\\' || $base_url === '/') {
    $base_url = '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conteúdo - Typper</title>
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
    
    <!-- TUI Editor CSS -->
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/theme/toastui-editor-dark.min.css">
    
    <!-- Code Syntax Highlight CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" />
    <link rel="stylesheet" href="https://uicdn.toast.com/editor-plugin-code-syntax-highlight/latest/toastui-editor-plugin-code-syntax-highlight.min.css">
    
    <!-- Color Syntax CSS -->
    <link rel="stylesheet" href="https://uicdn.toast.com/tui-color-picker/latest/tui-color-picker.min.css">
    <link rel="stylesheet" href="https://uicdn.toast.com/editor-plugin-color-syntax/latest/toastui-editor-plugin-color-syntax.min.css">
</head>
<body>
    <div class="loader-overlay" id="loader">
        <div class="spinner"></div>
    </div>

    <div class="container" style="max-width: 1400px;">
        <?php include 'layout_header.php'; ?>

        <main>
            <div id="alert-container"></div>
            
            <form id="editor-form" class="glass-panel" style="padding: 2rem; backdrop-filter: none; -webkit-backdrop-filter: none; background: rgba(36, 36, 62, 0.5);">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="max-width: 300px;">
                        <label class="form-label" for="category">Categoria</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">-- Raiz (Sem pasta) --</option>
                            <?php foreach ($categories as $catSlug => $catData): ?>
                                <option value="<?= htmlspecialchars($catSlug) ?>" <?= $category === $catSlug ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($catData['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slug do Arquivo (URL)</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($slug) ?>" required placeholder="ex: meu-primeiro-post">
                            <button type="button" class="btn btn-secondary btn-icon" id="btn-refresh-slug" title="Atualizar Slug" style="flex-shrink: 0;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="max-width: 300px;">
                        <label class="form-label">Tipo de Conteúdo</label>
                        <select class="form-control" id="type" name="type">
                            <option value="post" <?= $type === 'post' ? 'selected' : '' ?>>Post</option>
                            <option value="page" <?= $type === 'page' ? 'selected' : '' ?>>Page</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tags (separadas por vírgula)</label>
                        <input type="text" class="form-control" id="tags" name="tags" value="<?= htmlspecialchars($tags) ?>" placeholder="ex: tutoriais, php, web">
                    </div>
                    <div class="form-group" style="display: flex; flex-direction: column; justify-content: flex-end; padding-bottom: 0.5rem; max-width: 150px;">
                        <label class="form-label" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <span style="margin-bottom: 0;">Publicado</span>
                            <label class="custom-switch">
                                <input type="checkbox" id="published" name="published" <?= $published ? 'checked' : '' ?>>
                                <span class="custom-slider"></span>
                            </label>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Conteúdo</label>
                    <div id="editor"></div>
                </div>
            </form>
            
            <!-- Mídia do Conteúdo -->
            <div class="editor-panel" id="media-panel" style="margin-top: 2rem; padding: 2rem; background: rgba(36, 36, 62, 0.5); border: 1px solid var(--color-glass-border); border-radius: var(--radius-md);">
                <h3 style="margin-bottom: 1.5rem; color: var(--color-text-main);">Mídia do Conteúdo</h3>
                <div id="media-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                    <p id="no-media-msg" style="color: var(--color-text-muted); grid-column: 1 / -1; <?= empty($images) ? '' : 'display: none;' ?>">Nenhuma imagem enviada ainda.</p>
                    
                    <?php foreach ($images as $img): ?>
                        <div class="media-card" id="media-<?= htmlspecialchars(md5($img)) ?>" style="position: relative; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--color-glass-border); background: #000;">
                            <img src="<?= htmlspecialchars($base_url) ?>/files/<?= htmlspecialchars($slug) ?>/<?= htmlspecialchars($img) ?>" style="width: 100%; height: 150px; object-fit: contain; display: block;" alt="<?= htmlspecialchars($img) ?>">
                            <button type="button" class="btn btn-primary btn-icon" onclick="insertImageToEditor('<?= htmlspecialchars($base_url) ?>/files/<?= htmlspecialchars($slug) ?>/<?= htmlspecialchars($img) ?>', '<?= htmlspecialchars($img) ?>')" style="position: absolute; top: 0.5rem; left: 0.5rem; padding: 0.4rem; background: rgba(138, 43, 226, 0.9); border: none;" title="Inserir no Editor">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="12" y1="18" x2="12" y2="12"></line>
                                    <line x1="9" y1="15" x2="15" y2="15"></line>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-danger btn-icon" onclick="deleteImage('<?= htmlspecialchars($img) ?>', 'media-<?= htmlspecialchars(md5($img)) ?>')" style="position: absolute; top: 0.5rem; right: 0.5rem; padding: 0.4rem;" title="Excluir Imagem">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                            <div style="padding: 0.5rem; font-size: 0.75rem; background: rgba(0,0,0,0.7); position: absolute; bottom: 0; left: 0; right: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white;">
                                <?= htmlspecialchars($img) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- TUI Editor JS -->
    <script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
    
    <!-- Code Syntax Highlight Plugin JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-css.min.js"></script>
    <script src="https://uicdn.toast.com/editor-plugin-code-syntax-highlight/latest/toastui-editor-plugin-code-syntax-highlight-all.min.js"></script>
    
    <!-- Color Syntax Plugin JS -->
    <script src="https://uicdn.toast.com/tui-color-picker/latest/tui-color-picker.min.js"></script>
    <script src="https://uicdn.toast.com/editor-plugin-color-syntax/latest/toastui-editor-plugin-color-syntax.min.js"></script>
    
    <!-- Video Plugin JS (Custom v3 Implementation) -->
    <script>
        function videoPlugin() {
            const createVideoNode = (url, isVideoTag = false) => {
                return function(node) {
                    const source = node.literal.trim();
                    const content = isVideoTag 
                        ? `<video width="640" controls src="${source}" style="max-width: 100%;"></video>`
                        : `<iframe width="640" height="450" src="${url}${source}" frameborder="0" allowfullscreen style="max-width: 100%;"></iframe>`;
                    
                    return [
                        { type: 'openTag', tagName: 'div', style: 'text-align: center; margin: 1rem 0;' },
                        { type: 'html', content: content },
                        { type: 'closeTag', tagName: 'div' }
                    ];
                }
            };

            return {
                toHTMLRenderers: {
                    youtube: createVideoNode('https://www.youtube.com/embed/'),
                    vimeo: createVideoNode('https://player.vimeo.com/video/'),
                    youku: createVideoNode('http://player.youku.com/embed/'),
                    bilibili: createVideoNode('http://player.bilibili.com/player.html?aid='),
                    qq: createVideoNode('https://v.qq.com/txp/iframe/player.html?vid='),
                    mp4: createVideoNode('', true)
                }
            };
        }
    </script>
    
    <!-- Idioma pt-BR para o TUI Editor -->
    <script src="https://uicdn.toast.com/editor/latest/i18n/pt-br.min.js"></script>

    <script>
        const Editor = toastui.Editor;
        const { codeSyntaxHighlight, colorSyntax } = Editor.plugin;
        
        // Inicializa o conteúdo markdown via PHP de forma segura
        const initialMarkdown = <?= json_encode($markdown) ?>;

        const editor = new Editor({
            el: document.querySelector('#editor'),
            height: '600px',
            initialEditType: 'wysiwyg',
            previewStyle: 'vertical',
            initialValue: initialMarkdown,
            theme: 'dark',
            language: 'pt-BR',
            plugins: [[codeSyntaxHighlight, { highlighter: Prism }], colorSyntax, videoPlugin],
            hooks: {
                addImageBlobHook: async (blob, callback) => {
                    const slug = document.getElementById('slug').value.trim() || 'temp';
                    const formData = new FormData();
                    formData.append('image', blob);
                    formData.append('slug', slug);

                    try {
                        const response = await fetch('upload.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        
                        if (data.success) {
                            // Add to grid
                            const grid = document.getElementById('media-grid');
                            const noMediaMsg = document.getElementById('no-media-msg');
                            if (noMediaMsg) noMediaMsg.style.display = 'none';
                            
                            const filename = data.url.split('/').pop();
                            const cardId = 'media-new-' + Date.now();
                            
                            const cardHTML = `
                                <div class="media-card" id="${cardId}" style="position: relative; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--color-glass-border); background: #000;">
                                    <img src="${data.url}" style="width: 100%; height: 150px; object-fit: contain; display: block;" alt="${filename}">
                                    <button type="button" class="btn btn-primary btn-icon" onclick="insertImageToEditor('${data.url}', '${filename}')" style="position: absolute; top: 0.5rem; left: 0.5rem; padding: 0.4rem; background: rgba(138, 43, 226, 0.9); border: none;" title="Inserir no Editor">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="12" y1="18" x2="12" y2="12"></line>
                                            <line x1="9" y1="15" x2="15" y2="15"></line>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-icon" onclick="deleteImage('${filename}', '${cardId}')" style="position: absolute; top: 0.5rem; right: 0.5rem; padding: 0.4rem;" title="Excluir Imagem">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                    <div style="padding: 0.5rem; font-size: 0.75rem; background: rgba(0,0,0,0.7); position: absolute; bottom: 0; left: 0; right: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white;">
                                        ${filename}
                                    </div>
                                </div>
                            `;
                            grid.insertAdjacentHTML('beforeend', cardHTML);
                            
                            // Retorna a URL da imagem salva
                            callback(data.url, data.alt || 'image');
                        } else {
                            alert('Erro ao fazer upload da imagem: ' + (data.error || 'Desconhecido'));
                        }
                    } catch (error) {
                        console.error('Erro:', error);
                        alert('Falha na comunicação de upload.');
                    }
                }
            }
        });

        // Insert Image logic
        function insertImageToEditor(url, altText) {
            if (editor) {
                editor.exec('addImage', { imageUrl: url, altText: altText });
            }
        }

        // Image delete logic
        async function deleteImage(imageName, cardId) {
            if (!confirm('Tem certeza que deseja excluir esta imagem? Se ela estiver sendo usada no conteúdo, ficará quebrada.')) return;
            
            const slug = document.getElementById('slug').value.trim();
            if (!slug) return;
            
            try {
                const formData = new URLSearchParams();
                formData.append('slug', slug);
                formData.append('image', imageName);
                
                const response = await fetch('delete_image.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                const data = await response.json();
                
                if (data.success) {
                    const card = document.getElementById(cardId);
                    if (card) card.remove();
                    
                    const grid = document.getElementById('media-grid');
                    if (grid.querySelectorAll('.media-card').length === 0) {
                        document.getElementById('no-media-msg').style.display = 'block';
                    }
                } else {
                    alert('Erro: ' + (data.error || 'Desconhecido'));
                }
            } catch (e) {
                alert('Erro ao excluir imagem.');
            }
        }

        // Slug autogenerate
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        const originalSlug = "<?= addslashes($slug) ?>";
        const originalCategory = "<?= addslashes($category) ?>";

        if (!originalSlug) {
            titleInput.addEventListener('input', function() {
                slugInput.value = this.value.toLowerCase()
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                    .replace(/[^a-z0-9]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            });
        }

        document.getElementById('btn-refresh-slug').addEventListener('click', function() {
            const title = titleInput.value;
            if (title) {
                slugInput.value = title.toLowerCase()
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                    .replace(/[^a-z0-9]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });

        // Save logic
        document.getElementById('btn-save').addEventListener('click', async function() {
            const btn = this;
            const loader = document.getElementById('loader');
            const alertContainer = document.getElementById('alert-container');
            
            const title = document.getElementById('title').value.trim();
            const slug = document.getElementById('slug').value.trim();
            const category = document.getElementById('category').value;
            const type = document.getElementById('type').value;
            const tags = document.getElementById('tags').value.trim();
            const published = document.getElementById('published').checked;
            const markdown = editor.getMarkdown();

            if (!title || !slug) {
                alertContainer.innerHTML = '<div class="alert alert-error">Por favor, preencha o Título e o Slug.</div>';
                return;
            }

            btn.disabled = true;
            loader.classList.add('active');
            alertContainer.innerHTML = '';

            try {
                const formData = new URLSearchParams();
                formData.append('original_slug', originalSlug);
                formData.append('original_category', originalCategory);
                formData.append('category', category);
                formData.append('slug', slug);
                formData.append('title', title);
                formData.append('type', type);
                formData.append('tags', tags);
                formData.append('published', published ? 'true' : 'false');
                formData.append('markdown', markdown);

                const response = await fetch('save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alertContainer.innerHTML = '<div class="alert alert-success">Conteúdo salvo com sucesso!</div>';
                    // Atualiza a URL sem recarregar caso seja novo
                    if (!originalSlug && history.pushState) {
                        const fullSlug = category ? category + '/' + slug : slug;
                        const newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?slug=' + encodeURIComponent(fullSlug);
                        window.history.pushState({path:newurl}, '', newurl);
                    }
                } else {
                    alertContainer.innerHTML = '<div class="alert alert-error">Erro: ' + (data.error || 'Desconhecido') + '</div>';
                }
            } catch (error) {
                alertContainer.innerHTML = '<div class="alert alert-error">Erro na requisição de salvamento.</div>';
                console.error(error);
            } finally {
                btn.disabled = false;
                loader.classList.remove('active');
                window.scrollTo(0, 0);
            }
        });

        // Ctrl+S / Cmd+S Shortcut
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                document.getElementById('btn-save').click();
            }
        });
    </script>
</body>
</html>
