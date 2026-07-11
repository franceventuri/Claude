<?php
// Endpoint pubblico: nessun login richiesto, protetto dal token univoco del preventivo.
require_once __DIR__ . '/_bootstrap.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Metodo non consentito', 405);
}

$data = jsonInput();
$token = trim($data['token'] ?? '');
$action = $data['action'] ?? '';
$name = trim($data['name'] ?? '');

if ($token === '' || !in_array($action, ['accept', 'reject'], true)) {
    jsonError('Richiesta non valida.');
}

$stmt = $pdo->prepare('SELECT id, status FROM quotes WHERE share_token = ?');
$stmt->execute([$token]);
$quote = $stmt->fetch();
if (!$quote) {
    jsonError('Preventivo non trovato.', 404);
}
if (in_array($quote['status'], ['accettato', 'rifiutato'], true)) {
    jsonError('Questo preventivo ha gia\' ricevuto una risposta.', 409);
}

$newStatus = $action === 'accept' ? 'accettato' : 'rifiutato';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$upd = $pdo->prepare('UPDATE quotes SET status = ?, accepted_at = datetime(\'now\'), accepted_by = ?, accepted_ip = ?, updated_at = datetime(\'now\') WHERE id = ?');
$upd->execute([$newStatus, $name !== '' ? $name : null, $ip, $quote['id']]);

jsonResponse(['ok' => true, 'status' => $newStatus]);
