<?php
require_once __DIR__ . '/session.php';

header('Content-Type: application/json');

if (empty($_SESSION['typper_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Ensure the class can be autoloaded
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $result = \Typper\Updater::update();
    echo json_encode($result);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
