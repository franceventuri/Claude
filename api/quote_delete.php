<?php
require_once __DIR__ . '/_bootstrap.php';
requireLogin();
requireCsrf();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Metodo non consentito', 405);
}

$data = jsonInput();
$id = (int) ($data['id'] ?? 0);
if (!$id) {
    jsonError('Preventivo non valido.');
}

$pdo->prepare('DELETE FROM quotes WHERE id = ?')->execute([$id]);
jsonResponse(['ok' => true]);
