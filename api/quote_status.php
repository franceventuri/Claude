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
$status = $data['status'] ?? '';
$allowed = ['bozza', 'inviato', 'accettato', 'rifiutato', 'scaduto'];
if (!$id || !in_array($status, $allowed, true)) {
    jsonError('Dati non validi.');
}

$stmt = $pdo->prepare('UPDATE quotes SET status = ?, updated_at = datetime(\'now\') WHERE id = ?');
$stmt->execute([$status, $id]);

jsonResponse(['ok' => true]);
