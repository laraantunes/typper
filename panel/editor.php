<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../about.php';

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

$full_slug = $category ? $category . '/' . $slug : $slug;
$images = [];
if ($full_slug) {
    $files_dir = __DIR__ . '/../files/' . $full_slug;
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
    <link rel="stylesheet" href="style.css?v=<?= $version ?>">
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

    <style>
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--color-bg-light);
            border: 1px solid var(--color-glass-border);
            padding: 2rem;
            border-radius: var(--radius-md);
            width: 100%;
            max-width: 640px;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
        }
        #embed-code {
            font-family: Consolas, Monaco, 'Courier New', monospace;
            font-size: 0.85rem;
            resize: vertical;
            min-height: 180px;
        }
        .embed-hint {
            color: var(--color-text-muted);
            font-size: 0.85rem;
            margin: 0 0 1rem;
        }
        .embed-error {
            display: none;
            color: #ff6b81;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        /* Botão "Incorporar Conteúdo" na barra do editor */
        .toastui-editor-toolbar-icons.typper-embed-btn {
            background-image: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #b3b3cc;
        }
        .toastui-editor-toolbar-icons.typper-embed-btn:hover { color: #ffffff; }

        /* Pré-visualização do bloco $$embed dentro do editor */
        .typper-embed-preview {
            border: 1px dashed rgba(0, 242, 254, 0.4);
            border-radius: 6px;
            padding: 0.5rem;
            margin: 0.5rem 0;
            background: rgba(255, 255, 255, 0.03);
        }
        .typper-embed-preview-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: #00f2fe;
            margin-bottom: 0.4rem;
        }
        .typper-embed-preview iframe {
            display: block;
            width: 100%;
            min-height: 80px;
            border: 0;
            background: #ffffff;
            border-radius: 4px;
        }
    </style>
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
                        <div class="media-card" id="media-<?= md5($img) ?>" style="position: relative; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--color-glass-border); background: #000;">
                            <img src="<?= htmlspecialchars($base_url) ?>/files/<?= htmlspecialchars($full_slug) ?>/<?= htmlspecialchars($img) ?>" style="width: 100%; height: 150px; object-fit: contain; display: block;" alt="<?= htmlspecialchars($img) ?>">
                            <button type="button" class="btn btn-primary btn-icon" onclick="insertImageToEditor('<?= htmlspecialchars($base_url) ?>/files/<?= htmlspecialchars($full_slug) ?>/<?= htmlspecialchars($img) ?>', '<?= htmlspecialchars($img) ?>')" style="position: absolute; top: 0.5rem; left: 0.5rem; padding: 0.4rem; background: rgba(138, 43, 226, 0.9); border: none;" title="Inserir no Editor">
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

    <!-- Modal: Incorporar Conteúdo -->
    <div class="modal" id="modal-embed" role="dialog" aria-modal="true" aria-labelledby="modal-embed-title">
        <div class="modal-content">
            <h3 style="margin-top: 0; color: var(--color-cyan);" id="modal-embed-title">Incorporar Conteúdo</h3>
            <p class="embed-hint">Cole o código de incorporação fornecido pelo serviço (iframe, script ou HTML). Também é possível colar um link do YouTube ou de qualquer página para exibi-la em um iframe.</p>
            <div class="form-group">
                <label class="form-label" for="embed-code">Código HTML</label>
                <textarea class="form-control" id="embed-code" rows="8" spellcheck="false" placeholder="&lt;iframe src=&quot;https://...&quot;&gt;&lt;/iframe&gt;"></textarea>
                <div class="embed-error" id="embed-error">Cole o código ou o link a ser incorporado.</div>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" id="btn-embed-cancel">Cancelar</button>
                <button type="button" class="btn btn-primary" style="flex: 1;" id="btn-embed-insert">Incorporar</button>
            </div>
        </div>
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
                    youku: createVideoNode('https://player.youku.com/embed/'),
                    bilibili: createVideoNode('https://player.bilibili.com/player.html?aid='),
                    qq: createVideoNode('https://v.qq.com/txp/iframe/player.html?vid='),
                    mp4: createVideoNode('', true)
                }
            };
        }

        // Bloco "$$embed ... $$": HTML incorporado. No editor vira um marcador
        // que é trocado por um iframe isolado (sandbox) em hydrateEmbedPreviews().
        function embedPlugin() {
            // Base64 (UTF-8) para o código passar intacto pelo sanitizador do editor
            const toBase64 = (s) => btoa(unescape(encodeURIComponent(s)));

            return {
                toHTMLRenderers: {
                    embed(node) {
                        return [
                            { type: 'openTag', tagName: 'div', classNames: ['typper-embed-preview'], attributes: { 'data-embed': toBase64(node.literal || '') } },
                            { type: 'html', content: '<div class="typper-embed-preview-label">Conteúdo incorporado</div>' },
                            { type: 'closeTag', tagName: 'div' }
                        ];
                    }
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
            plugins: [[codeSyntaxHighlight, { highlighter: Prism }], colorSyntax, videoPlugin, embedPlugin],
            hooks: {
                addImageBlobHook: async (blob, callback) => {
                    let slug = document.getElementById('slug').value.trim() || 'temp';
                    const cat = document.getElementById('category').value;
                    if (cat) slug = cat + '/' + slug;
                    
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

        // ===== Incorporar Conteúdo =====
        const embedModal = document.getElementById('modal-embed');
        const embedCode = document.getElementById('embed-code');
        const embedError = document.getElementById('embed-error');

        (function addEmbedToolbarButton() {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'toastui-editor-toolbar-icons typper-embed-btn';
            btn.setAttribute('aria-label', 'Incorporar Conteúdo');
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.48-.968-.925a2.501 2.501 0 1 0-3.214 3.214c.446.166.855.497.925.968a.979.979 0 0 1-.276.837l-1.61 1.61a2.404 2.404 0 0 1-1.705.707 2.402 2.402 0 0 1-1.704-.706l-1.568-1.568a1.026 1.026 0 0 0-.877-.29c-.493.074-.84.504-1.02.968a2.5 2.5 0 1 1-3.237-3.237c.464-.18.894-.527.967-1.02a1.026 1.026 0 0 0-.289-.877l-1.568-1.568A2.402 2.402 0 0 1 1.998 12c0-.617.236-1.234.706-1.704L4.23 8.77c.24-.24.581-.353.917-.303.515.077.877.528 1.073 1.01a2.5 2.5 0 1 0 3.259-3.259c-.482-.196-.933-.558-1.01-1.073-.05-.336.062-.676.303-.917l1.525-1.525A2.402 2.402 0 0 1 12 1.998c.617 0 1.234.236 1.704.706l1.568 1.568c.23.23.556.338.877.29.493-.074.84-.504 1.02-.968a2.5 2.5 0 1 1 3.237 3.237c-.464.18-.894.527-.967 1.02Z"/></svg>';
            btn.addEventListener('click', openEmbedModal);

            editor.insertToolbarItem({ groupIndex: 3, itemIndex: 3 }, {
                name: 'embed',
                tooltip: 'Incorporar Conteúdo',
                el: btn
            });
        })();

        function openEmbedModal() {
            embedCode.value = '';
            embedError.style.display = 'none';
            embedModal.classList.add('active');
            setTimeout(() => embedCode.focus(), 0);
        }

        function closeEmbedModal() {
            embedModal.classList.remove('active');
            editor.focus();
        }

        // Link solto vira iframe (YouTube vira o player)
        function normalizeEmbedInput(raw) {
            const value = raw.trim();
            if (!/^https?:\/\/\S+$/i.test(value)) return value;

            let src = value;
            try {
                const url = new URL(value);
                const host = url.hostname.replace(/^www\.|^m\./, '');
                let videoId = null;
                if (host === 'youtu.be') {
                    videoId = url.pathname.slice(1);
                } else if (host === 'youtube.com') {
                    if (url.pathname === '/watch') videoId = url.searchParams.get('v');
                    else if (/^\/(shorts|embed|live)\//.test(url.pathname)) videoId = url.pathname.split('/')[2];
                }
                if (videoId) {
                    src = 'https://www.youtube.com/embed/' + encodeURIComponent(videoId);
                    return `<iframe width="640" height="360" src="${src}" title="YouTube" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="max-width: 100%;"></iframe>`;
                }
            } catch (e) { /* mantém o link como está */ }

            const safeSrc = src.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
            return `<iframe src="${safeSrc}" width="100%" height="450" frameborder="0" loading="lazy" style="max-width: 100%;"></iframe>`;
        }

        // Última linha (1-based) do bloco que contém a linha informada:
        // blocos de código (```/~~~), blocos $$...$$ ou parágrafo/lista/tabela
        function endOfMarkdownBlock(lines, line) {
            let open = null;
            for (let i = 0; i < lines.length; i++) {
                const t = lines[i].trim();
                if (!open) {
                    if (/^\$\$\S/.test(t)) open = { close: (s) => s === '$$', start: i };
                    else if (/^(```|~~~)/.test(t)) { const f = t.slice(0, 3); open = { close: (s) => s.startsWith(f), start: i }; }
                    if (open && i > line - 1) break; // bloco começa depois do cursor
                } else if (open.close(t)) {
                    if (line - 1 >= open.start && line - 1 <= i) return i + 1;
                    open = null;
                }
            }
            if (open && line - 1 >= open.start) return lines.length; // bloco sem fechamento

            while (line < lines.length && lines[line - 1].trim() !== '' && lines[line].trim() !== '') line++;
            return line;
        }

        // Insere o bloco "$$embed" no fim do parágrafo onde está o cursor
        function insertEmbedBlock(html) {
            // Uma linha "$$" sozinha encerraria o bloco antes da hora
            const body = html.replace(/^\s*\$\$\s*$/gm, '');
            const block = '$$embed\n' + body + '\n$$';

            const wasWysiwyg = editor.isWysiwygMode();
            let line = Infinity; // sem posição conhecida: vai para o fim do texto
            try {
                if (wasWysiwyg) {
                    const [from, to] = editor.getSelection();
                    line = editor.convertPosToMatchEditorMode(from, to, 'markdown')[1][0];
                } else {
                    line = editor.getSelection()[1][0];
                }
            } catch (e) { /* usa o fim do texto */ }
            if (wasWysiwyg) editor.changeMode('markdown', true);

            const lines = editor.getMarkdown().split('\n');
            line = Math.min(Math.max(line, 1), lines.length);
            line = endOfMarkdownBlock(lines, line);
            const lineText = lines[line - 1];
            const endPos = [line, lineText.length + 1];

            editor.setSelection(endPos, endPos);
            editor.insertText((lineText.trim() ? '\n\n' : '\n') + block + '\n');

            if (wasWysiwyg) editor.changeMode('wysiwyg', true);
        }

        document.getElementById('btn-embed-insert').addEventListener('click', () => {
            const html = normalizeEmbedInput(embedCode.value);
            if (!html) {
                embedError.style.display = 'block';
                embedCode.focus();
                return;
            }
            embedModal.classList.remove('active');
            insertEmbedBlock(html);
            editor.focus();
        });

        document.getElementById('btn-embed-cancel').addEventListener('click', closeEmbedModal);
        embedModal.addEventListener('mousedown', (e) => { if (e.target === embedModal) closeEmbedModal(); });
        embedCode.addEventListener('input', () => { embedError.style.display = 'none'; });
        embedCode.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                document.getElementById('btn-embed-insert').click();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && embedModal.classList.contains('active')) closeEmbedModal();
        });

        // Troca o marcador do bloco por um iframe isolado com o HTML incorporado.
        // Sem allow-same-origin: o código colado não acessa a sessão do painel.
        const embedFrames = new Map();
        const fromBase64 = (s) => {
            try { return decodeURIComponent(escape(atob(s || ''))); } catch (e) { return ''; }
        };

        function hydrateEmbedPreviews() {
            document.querySelectorAll('#editor .typper-embed-preview[data-embed]:not([data-hydrated])').forEach((el) => {
                el.setAttribute('data-hydrated', '1');
                const id = 'e' + Math.random().toString(36).slice(2);
                const iframe = document.createElement('iframe');
                iframe.setAttribute('sandbox', 'allow-scripts allow-popups allow-popups-to-escape-sandbox allow-presentation');
                iframe.setAttribute('loading', 'lazy');
                iframe.srcdoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><base target="_blank">'
                    + '<style>html,body{margin:0;padding:0;}body{padding:4px;font-family:sans-serif;}iframe,img,video{max-width:100%;}</style></head><body>'
                    + fromBase64(el.getAttribute('data-embed'))
                    + '<script>(function(){var s=function(){parent.postMessage({typperEmbed:"' + id + '",height:document.documentElement.scrollHeight},"*");};'
                    + 'window.addEventListener("load",s);if(window.ResizeObserver){new ResizeObserver(s).observe(document.body);}setTimeout(s,1500);s();})();<\/script>'
                    + '</body></html>';
                embedFrames.set(id, iframe);
                el.appendChild(iframe);
            });

            // Limpa iframes que saíram do editor
            embedFrames.forEach((iframe, id) => { if (!iframe.isConnected) embedFrames.delete(id); });
        }

        window.addEventListener('message', (e) => {
            const data = e.data;
            if (!data || typeof data.typperEmbed !== 'string') return;
            const iframe = embedFrames.get(data.typperEmbed);
            if (!iframe || e.source !== iframe.contentWindow) return;
            const h = Math.min(Math.max(parseInt(data.height, 10) || 0, 80), 2000);
            iframe.style.height = h + 'px';
        });

        new MutationObserver(hydrateEmbedPreviews).observe(document.getElementById('editor'), { childList: true, subtree: true });
        hydrateEmbedPreviews();

        // Insert Image logic
        function insertImageToEditor(url, altText) {
            if (editor) {
                editor.exec('addImage', { imageUrl: url, altText: altText });
            }
        }

        // Image delete logic
        async function deleteImage(imageName, cardId) {
            if (!confirm('Tem certeza que deseja excluir esta imagem? Se ela estiver sendo usada no conteúdo, ficará quebrada.')) return;
            
            let slug = document.getElementById('slug').value.trim();
            if (!slug) return;
            
            const cat = document.getElementById('category').value;
            if (cat) slug = cat + '/' + slug;
            
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
