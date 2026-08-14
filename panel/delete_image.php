<?php
require_once __DIR__ . '/session.php';
if (empty($_SESSION['typper_logged_in'])) {
    die(json_encode(['success' => false, 'error' => 'Não autorizado']));
}

header('Content-Type: application/json');

$slug = $_POST['slug'] ?? '';
$image = $_POST['image'] ?? '';

// Sanitize inputs
$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
$image = basename($image); // Prevent directory traversal

if (empty($slug) || empty($image)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

$file_path = __DIR__ . '/../files/' . $slug . '/' . $image;

if (file_exists($file_path)) {
    if (unlink($file_path)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao excluir arquivo.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Arquivo não encontrado.']);
}
