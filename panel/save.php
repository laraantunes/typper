<?php
require_once __DIR__ . '/session.php';
if (empty($_SESSION['typper_logged_in'])) {
    die(json_encode(['success' => false, 'error' => 'Não autorizado']));
}

header('Content-Type: application/json');

$original_slug = $_POST['original_slug'] ?? '';
$original_category = $_POST['original_category'] ?? '';
$category = preg_replace('/[^a-z0-9\-]/', '', $_POST['category'] ?? '');
$slug = $_POST['slug'] ?? '';
$title = $_POST['title'] ?? '';
$type = $_POST['type'] ?? 'post';
$tags = $_POST['tags'] ?? '';
$published = $_POST['published'] ?? 'true';
$markdown = $_POST['markdown'] ?? '';

if (empty($slug) || empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Título e Slug são obrigatórios']);
    exit;
}

// Previne traversal attack
$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
if ($original_slug) {
    $original_slug = preg_replace('/[^a-z0-9\-]/', '', $original_slug);
}

$contents_dir = __DIR__ . '/../contents';
$files_dir = __DIR__ . '/../files';

$new_dir = $contents_dir . ($category ? '/' . $category : '');
if (!is_dir($new_dir)) {
    @mkdir($new_dir, 0755, true);
}

$new_file_path = $new_dir . '/' . $slug . '.md';
$old_file_path = '';
if ($original_slug) {
    $old_dir = $contents_dir . ($original_category ? '/' . $original_category : '');
    $old_file_path = $old_dir . '/' . $original_slug . '.md';
}

// Construir o conteúdo do arquivo com os metadados (YAML style)
$content = "title: {$title}\n";
$content .= "type: {$type}\n";
if (trim($tags) !== '') {
    $tagsArray = array_filter(array_map('trim', explode(',', $tags)));
    if (!empty($tagsArray)) {
        $content .= "tags: [" . implode(', ', $tagsArray) . "]\n";
    }
}
$content .= "published: {$published}\n";
// Manter outros metadados existentes, se houver
if ($original_slug && file_exists($old_file_path)) {
    $old_content = file_get_contents($old_file_path);
    $parts = explode('===', $old_content, 2);
    if (count($parts) > 1) {
        $meta_lines = explode("\n", trim($parts[0]));
        foreach ($meta_lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            // Ignorar as chaves que estamos atualizando agora
            if (!preg_match('/^(title|type|tags|published):/i', $line)) {
                $content .= $line . "\n";
            }
        }
    }
}
$content .= "===\n";
$content .= ltrim($markdown);

// Lógica de renomear se o slug ou categoria mudou
$isNewPath = ($original_slug !== $slug || $original_category !== $category);

if ($original_slug && $isNewPath) {
    if (file_exists($old_file_path)) {
        unlink($old_file_path); // Remove o antigo, será salvo com novo nome abaixo
    }
    
    // Opcional: Para uploads o Typper salvava as imagens por slug. 
    // Com categorias, podemos manter na raiz de files/slug ou files/categoria-slug. 
    // Para simplificar e manter a retrocompatibilidade, mantemos em files/slug.
    // Se quiser alterar o caminho dos arquivos, basta alterar aqui.
    if ($original_slug !== $slug) {
        $old_files_path = $files_dir . '/' . $original_slug;
        $new_files_path = $files_dir . '/' . $slug;
        if (is_dir($old_files_path)) {
            @rename($old_files_path, $new_files_path);
            // Atualizar caminhos de imagens no markdown
            $content = str_replace("files/{$original_slug}/", "files/{$slug}/", $content);
        }
    }
}

// Salvar o novo arquivo
if (file_put_contents($new_file_path, $content) === false) {
    echo json_encode(['success' => false, 'error' => 'Falha ao salvar o arquivo no servidor.']);
    exit;
}

// Limpar cache do Typper via CLI (opcional/preventivo)
$typper_path = __DIR__ . '/../typper.php';
if (file_exists($typper_path)) {
    // Clear all cache just to be safe
    @exec("php " . escapeshellarg($typper_path) . " clear");
}

echo json_encode(['success' => true]);
