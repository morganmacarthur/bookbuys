<?php
$slug = isset($_GET['slug']) ? strtolower(trim($_GET['slug'])) : '';
$slug = preg_replace('/[^a-z0-9-]/', '', $slug);

if ($slug === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing slug']);
    exit;
}

$path = __DIR__ . '/../data/authors/' . $slug . '.json';
if (!file_exists($path)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Author not found']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=21600');
readfile($path);
