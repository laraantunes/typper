<?php
require_once __DIR__ . '/session.php';
if (empty($_SESSION['typper_logged_in'])) {
    die(json_encode(['success' => false, 'error' => 'Não autorizado']));
}

header('Content-Type: application/json');

$slug = $_POST['slug'] ?? 'temp';
$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
if (empty($slug)) {
    $slug = 'temp';
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Nenhuma imagem recebida ou erro no upload.']);
    exit;
}

$file = $_FILES['image'];
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!$extension) {
    // Detect from mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $mime_map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg'
    ];
    $extension = $mime_map[$mime] ?? 'jpg';
}

$extension = strtolower($extension);
$allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
if (!in_array($extension, $allowed_exts)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido.']);
    exit;
}

// Criar pasta do slug se não existir
$files_dir = __DIR__ . '/../files';
$slug_dir = $files_dir . '/' . $slug;

if (!is_dir($files_dir)) {
    @mkdir($files_dir, 0755, true);
}
if (!is_dir($slug_dir)) {
    @mkdir($slug_dir, 0755, true);
}

// Gerar nome único
$filename = uniqid('img_') . '.' . $extension;
$destination = $slug_dir . '/' . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    // URL base dinâmica
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = dirname($script_path);
    if ($base_url === '\\' || $base_url === '/') {
        $base_url = '';
    }
    
    $url = $base_url . '/files/' . $slug . '/' . $filename;
    echo json_encode(['success' => true, 'url' => $url, 'alt' => $file['name']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao mover arquivo.']);
}
