<?php
require_once __DIR__ . '/_bootstrap.php';
requireLogin();
requireCsrf();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Metodo non consentito', 405);
}

$data = jsonInput();

$companyId = (int) ($data['company_id'] ?? 0);
$clientId = (int) ($data['client_id'] ?? 0);
$title = trim($data['title'] ?? '');
$items = is_array($data['items'] ?? null) ? $data['items'] : [];

if (!$companyId || !$clientId || $title === '') {
    jsonError('Azienda, cliente e oggetto del preventivo sono obbligatori.');
}
if (count($items) === 0) {
    jsonError('Aggiungi almeno una voce al preventivo.');
}

$companyStmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$companyStmt->execute([$companyId]);
$company = $companyStmt->fetch();
if (!$company) {
    jsonError('Azienda non valida.');
}

$clientStmt = $pdo->prepare('SELECT id FROM clients WHERE id = ?');
$clientStmt->execute([$clientId]);
if (!$clientStmt->fetch()) {
    jsonError('Cliente non valido.');
}

$discountType = ($data['discount_type'] ?? 'percent') === 'amount' ? 'amount' : 'percent';
$discountValue = (float) ($data['discount_value'] ?? 0);
$vatRate = (float) ($data['vat_rate'] ?? 22);
$vatLabel = trim($data['vat_label'] ?? ('IVA ' . $vatRate . '%'));
$issueDate = $data['issue_date'] ?? todayIso();
$validityDays = max(1, (int) ($data['validity_days'] ?? 30));

$cleanItems = [];
foreach ($items as $idx => $it) {
    $desc = trim($it['description'] ?? '');
    if ($desc === '') {
        continue;
    }
    $cleanItems[] = [
        'article_id' => !empty($it['article_id']) ? (int) $it['article_id'] : null,
        'section' => trim($it['section'] ?? ''),
        'sort_order' => (int) ($it['sort_order'] ?? $idx),
        'description' => $desc,
        'unit' => trim($it['unit'] ?? 'pz') ?: 'pz',
        'quantity' => (float) ($it['quantity'] ?? 1),
        'unit_price' => (float) ($it['unit_price'] ?? 0),
        'unit_cost' => (float) ($it['unit_cost'] ?? 0),
        'discount_percent' => (float) ($it['discount_percent'] ?? 0),
        'optional' => !empty($it['optional']) ? 1 : 0,
    ];
}
if (count($cleanItems) === 0) {
    jsonError('Aggiungi almeno una voce valida (con descrizione) al preventivo.');
}

$totals = calcQuoteTotals($cleanItems, $discountType, $discountValue, $vatRate);

$id = !empty($data['id']) ? (int) $data['id'] : null;

$pdo->beginTransaction();
try {
    if ($id) {
        $existing = $pdo->prepare('SELECT * FROM quotes WHERE id = ?');
        $existing->execute([$id]);
        $existingQuote = $existing->fetch();
        if (!$existingQuote) {
            throw new RuntimeException('Preventivo non trovato.');
        }
        $quoteNumber = $existingQuote['quote_number'];
        $shareToken = $existingQuote['share_token'] ?: generateShareToken();
        $status = $existingQuote['status'];

        $stmt = $pdo->prepare('UPDATE quotes SET company_id=?, client_id=?, title=?, event_location=?, event_start=?, event_end=?,
            issue_date=?, validity_days=?, discount_type=?, discount_value=?, vat_rate=?, vat_label=?,
            subtotal=?, discount_amount=?, taxable=?, vat_amount=?, total=?, total_cost=?, margin=?, margin_pct=?,
            payment_terms=?, notes=?, internal_notes=?, share_token=?, updated_at=datetime(\'now\') WHERE id=?');
        $stmt->execute([
            $companyId, $clientId, $title, $data['event_location'] ?? '', $data['event_start'] ?? null, $data['event_end'] ?? null,
            $issueDate, $validityDays, $discountType, $discountValue, $vatRate, $vatLabel,
            $totals['subtotal'], $totals['discount_amount'], $totals['taxable'], $totals['vat_amount'], $totals['total'], $totals['total_cost'], $totals['margin'], $totals['margin_pct'],
            $data['payment_terms'] ?? '', $data['notes'] ?? '', $data['internal_notes'] ?? '', $shareToken, $id,
        ]);

        $pdo->prepare('DELETE FROM quote_items WHERE quote_id = ?')->execute([$id]);
    } else {
        $quoteNumber = nextQuoteNumber($pdo, $companyId, $company['quote_prefix']);
        $shareToken = generateShareToken();
        $status = 'bozza';

        $stmt = $pdo->prepare('INSERT INTO quotes (quote_number, company_id, client_id, title, event_location, event_start, event_end,
            issue_date, validity_days, status, discount_type, discount_value, vat_rate, vat_label,
            subtotal, discount_amount, taxable, vat_amount, total, total_cost, margin, margin_pct,
            payment_terms, notes, internal_notes, share_token)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $quoteNumber, $companyId, $clientId, $title, $data['event_location'] ?? '', $data['event_start'] ?? null, $data['event_end'] ?? null,
            $issueDate, $validityDays, $status, $discountType, $discountValue, $vatRate, $vatLabel,
            $totals['subtotal'], $totals['discount_amount'], $totals['taxable'], $totals['vat_amount'], $totals['total'], $totals['total_cost'], $totals['margin'], $totals['margin_pct'],
            $data['payment_terms'] ?? '', $data['notes'] ?? '', $data['internal_notes'] ?? '', $shareToken,
        ]);
        $id = (int) $pdo->lastInsertId();
    }

    $itemStmt = $pdo->prepare('INSERT INTO quote_items (quote_id, article_id, section, sort_order, description, unit, quantity, unit_price, unit_cost, discount_percent, optional)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($cleanItems as $it) {
        $itemStmt->execute([
            $id, $it['article_id'], $it['section'], $it['sort_order'], $it['description'], $it['unit'],
            $it['quantity'], $it['unit_price'], $it['unit_cost'], $it['discount_percent'], $it['optional'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError('Errore nel salvataggio: ' . $e->getMessage(), 500);
}

jsonResponse(['id' => $id, 'quote_number' => $quoteNumber]);
