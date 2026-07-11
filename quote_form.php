<?php
$pageTitle = 'Nuovo preventivo';
$activeNav = 'quotes';
require_once __DIR__ . '/includes/layout_start.php';

$id = (int) ($_GET['id'] ?? 0);
$quote = null;
$items = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM quotes WHERE id = ?');
    $stmt->execute([$id]);
    $quote = $stmt->fetch();
    if (!$quote) { header('Location: quotes.php'); exit; }
    $itemsStmt = $pdo->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order, id');
    $itemsStmt->execute([$id]);
    $items = $itemsStmt->fetchAll();
    $pageTitle = 'Modifica preventivo ' . $quote['quote_number'];
}

$companies = $pdo->query('SELECT * FROM companies ORDER BY id')->fetchAll();
$paymentTemplates = $pdo->query('SELECT * FROM payment_terms_templates ORDER BY sort_order')->fetchAll();

$prefillClient = null;
if (!$id && !empty($_GET['client_id'])) {
    $cs = $pdo->prepare('SELECT id, ragione_sociale FROM clients WHERE id = ?');
    $cs->execute([(int) $_GET['client_id']]);
    $prefillClient = $cs->fetch();
}

$initial = [
    'id' => $quote['id'] ?? null,
    'company_id' => $quote['company_id'] ?? ($companies[0]['id'] ?? null),
    'client_id' => $prefillClient['id'] ?? ($quote['client_id'] ?? null),
    'client_label' => $prefillClient['ragione_sociale'] ?? null,
    'title' => $quote['title'] ?? '',
    'event_location' => $quote['event_location'] ?? '',
    'event_start' => $quote['event_start'] ?? '',
    'event_end' => $quote['event_end'] ?? '',
    'issue_date' => $quote['issue_date'] ?? date('Y-m-d'),
    'validity_days' => $quote['validity_days'] ?? 30,
    'discount_type' => $quote['discount_type'] ?? 'percent',
    'discount_value' => $quote['discount_value'] ?? 0,
    'vat_rate' => $quote['vat_rate'] ?? 22,
    'vat_label' => $quote['vat_label'] ?? 'IVA 22%',
    'payment_terms' => $quote['payment_terms'] ?? ($paymentTemplates[0]['body'] ?? ''),
    'notes' => $quote['notes'] ?? "Validità dell'offerta: come indicato sopra dalla data di emissione.\nSalvo diversa indicazione, il presente preventivo non include eventuali oneri di sicurezza specifici, tasse di iscrizione all'ente fiera, energia elettrica consumata e quanto non espressamente elencato.",
    'internal_notes' => $quote['internal_notes'] ?? '',
    'items' => $items ?: [
        ['section' => 'Struttura e allestimento', 'description' => '', 'unit' => 'pz', 'quantity' => 1, 'unit_price' => 0, 'unit_cost' => 0, 'discount_percent' => 0, 'optional' => 0, 'article_id' => null],
    ],
];
if (!$id && isset($initial['client_id']) && $initial['client_id'] === null) {
    unset($initial['client_label']);
}
?>
<div class="card-header">
  <h2><?= $id ? 'Modifica preventivo ' . h($quote['quote_number']) : 'Nuovo preventivo' ?></h2>
  <a href="<?= $id ? 'quote_view.php?id=' . $id : 'quotes.php' ?>" class="btn btn-ghost btn-sm">← Annulla</a>
</div>

<div id="qe-alert"></div>

<div id="quote-editor" class="card"></div>

<script>
window.QE_INITIAL = <?= json_encode($initial, JSON_UNESCAPED_UNICODE) ?>;
window.QE_COMPANIES = <?= json_encode($companies, JSON_UNESCAPED_UNICODE) ?>;
window.QE_PAYMENT_TEMPLATES = <?= json_encode($paymentTemplates, JSON_UNESCAPED_UNICODE) ?>;
window.QE_CSRF = <?= json_encode(csrfToken()) ?>;
window.QE_VIEW_URL = 'quote_view.php';
</script>
<script src="assets/js/quote_editor.js"></script>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
