<?php
require_once __DIR__ . '/_bootstrap.php';
requireLogin();
requireCsrf();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Metodo non consentito', 405);
}

$data = jsonInput();
$sourceId = (int) ($data['id'] ?? 0);
if (!$sourceId) {
    jsonError('Preventivo non valido.');
}

$src = $pdo->prepare('SELECT * FROM quotes WHERE id = ?');
$src->execute([$sourceId]);
$q = $src->fetch();
if (!$q) {
    jsonError('Preventivo non trovato.', 404);
}

$companyStmt = $pdo->prepare('SELECT quote_prefix FROM companies WHERE id = ?');
$companyStmt->execute([$q['company_id']]);
$prefix = $companyStmt->fetchColumn();

$pdo->beginTransaction();
try {
    $newNumber = nextQuoteNumber($pdo, (int) $q['company_id'], $prefix);
    $shareToken = generateShareToken();

    $stmt = $pdo->prepare('INSERT INTO quotes (quote_number, company_id, client_id, title, event_location, event_start, event_end,
        issue_date, validity_days, status, discount_type, discount_value, vat_rate, vat_label,
        subtotal, discount_amount, taxable, vat_amount, total, total_cost, margin, margin_pct,
        payment_terms, notes, internal_notes, share_token, revision_of)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $newNumber, $q['company_id'], $q['client_id'], $q['title'] . ' (copia)', $q['event_location'], $q['event_start'], $q['event_end'],
        todayIso(), $q['validity_days'], 'bozza', $q['discount_type'], $q['discount_value'], $q['vat_rate'], $q['vat_label'],
        $q['subtotal'], $q['discount_amount'], $q['taxable'], $q['vat_amount'], $q['total'], $q['total_cost'], $q['margin'], $q['margin_pct'],
        $q['payment_terms'], $q['notes'], $q['internal_notes'], $shareToken, $sourceId,
    ]);
    $newId = (int) $pdo->lastInsertId();

    $items = $pdo->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order');
    $items->execute([$sourceId]);
    $insItem = $pdo->prepare('INSERT INTO quote_items (quote_id, article_id, section, sort_order, description, unit, quantity, unit_price, unit_cost, discount_percent, optional)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($items->fetchAll() as $it) {
        $insItem->execute([
            $newId, $it['article_id'], $it['section'], $it['sort_order'], $it['description'], $it['unit'],
            $it['quantity'], $it['unit_price'], $it['unit_cost'], $it['discount_percent'], $it['optional'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError('Errore nella duplicazione: ' . $e->getMessage(), 500);
}

jsonResponse(['id' => $newId, 'quote_number' => $newNumber]);
