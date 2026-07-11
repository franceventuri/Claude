<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/helpers.php';
$pdo = db();

$publicMode = false;
$id = (int) ($_GET['id'] ?? 0);
$token = trim($_GET['token'] ?? '');

if ($token !== '') {
    $publicMode = true;
    $stmt = $pdo->prepare('SELECT * FROM quotes WHERE share_token = ?');
    $stmt->execute([$token]);
} elseif ($id) {
    require_once __DIR__ . '/../auth.php';
    requireLogin();
    $stmt = $pdo->prepare('SELECT * FROM quotes WHERE id = ?');
    $stmt->execute([$id]);
} else {
    http_response_code(404);
    exit('Preventivo non trovato.');
}

$quote = $stmt->fetch();
if (!$quote) {
    http_response_code(404);
    exit('Preventivo non trovato.');
}

$companyStmt = $pdo->prepare('SELECT * FROM companies WHERE id = ?');
$companyStmt->execute([$quote['company_id']]);
$company = $companyStmt->fetch();

$clientStmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
$clientStmt->execute([$quote['client_id']]);
$client = $clientStmt->fetch();

$itemsStmt = $pdo->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order, id');
$itemsStmt->execute([$quote['id']]);
$items = $itemsStmt->fetchAll();

$expiryTs = strtotime('+' . (int) $quote['validity_days'] . ' days', strtotime($quote['issue_date']));
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($quote['quote_number']) ?> · <?= h($quote['title']) ?></title>
<link rel="stylesheet" href="../assets/css/print.css">
</head>
<body>

<?php if (!$publicMode): ?>
<div class="print-toolbar">
  <button onclick="window.print()">🖨️ Stampa / Salva PDF</button>
  <a href="../quote_view.php?id=<?= (int) $quote['id'] ?>">← Torna al preventivo</a>
</div>
<?php endif; ?>

<?php if ($publicMode): ?>
  <?php if ($quote['status'] === 'accettato'): ?>
    <div class="accept-bar accepted"><div class="msg">✅ Preventivo accettato<?= $quote['accepted_by'] ? ' da ' . h($quote['accepted_by']) : '' ?> il <?= date('d/m/Y', strtotime($quote['accepted_at'])) ?>.</div></div>
  <?php elseif ($quote['status'] === 'rifiutato'): ?>
    <div class="accept-bar rejected"><div class="msg">❌ Preventivo rifiutato il <?= date('d/m/Y', strtotime($quote['accepted_at'])) ?>.</div></div>
  <?php else: ?>
    <div class="accept-bar" id="accept-bar">
      <div class="msg">Questo è un preventivo inviato da <?= h($company['short_name']) ?>. Puoi accettarlo o rifiutarlo direttamente qui.</div>
      <div class="flex gap-8">
        <button class="btn-reject" id="btn-reject">Rifiuta</button>
        <button class="btn-accept" id="btn-accept">✓ Accetto il preventivo</button>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<div class="sheet">
  <div class="doc-header">
    <img class="logo" src="../<?= h($company['logo_path']) ?>" alt="<?= h($company['short_name']) ?>">
    <div class="doc-meta">
      <h1>PREVENTIVO</h1>
      <div class="num">N. <?= h($quote['quote_number']) ?> del <?= dateIt($quote['issue_date']) ?></div>
      <div class="num">Valido fino al <?= date('d/m/Y', $expiryTs) ?></div>
    </div>
  </div>

  <div class="doc-parties">
    <div class="box">
      <h3>Da</h3>
      <p><strong><?= h($company['legal_name']) ?></strong><br>
      <?= h($company['address']) ?><br>
      <?= h($company['zip']) ?> <?= h($company['city']) ?> (<?= h($company['province']) ?>)<br>
      P.IVA <?= h($company['piva']) ?><?= $company['rea'] ? ' · REA ' . h($company['rea']) : '' ?><br>
      <?= h($company['email']) ?> <?= $company['phone'] ? '· ' . h($company['phone']) : '' ?></p>
    </div>
    <div class="box">
      <h3>Spett.le</h3>
      <p><strong><?= h($client['ragione_sociale']) ?></strong><?php if ($client['referente']): ?><br>c.a. <?= h($client['referente']) ?><?php endif; ?><br>
      <?= h($client['indirizzo']) ?><br>
      <?= h($client['cap']) ?> <?= h($client['citta']) ?> <?= $client['provincia'] ? '(' . h($client['provincia']) . ')' : '' ?><br>
      <?php if ($client['piva']): ?>P.IVA <?= h($client['piva']) ?><br><?php endif; ?>
      <?= h($client['email']) ?></p>
    </div>
  </div>

  <div class="event-box">
    <div><span>Oggetto</span><?= h($quote['title']) ?></div>
    <?php if ($quote['event_location']): ?><div><span>Luogo</span><?= h($quote['event_location']) ?></div><?php endif; ?>
    <?php if ($quote['event_start']): ?><div><span>Date evento</span><?= dateIt($quote['event_start']) ?><?= $quote['event_end'] ? ' → ' . dateIt($quote['event_end']) : '' ?></div><?php endif; ?>
  </div>

  <table class="items">
    <thead><tr><th style="width:44%">Descrizione</th><th>UM</th><th class="num">Qtà</th><th class="num">Prezzo</th><th class="num">Sconto</th><th class="num">Totale</th></tr></thead>
    <tbody>
    <?php $lastSection = null; foreach ($items as $it):
      if ($it['section'] !== $lastSection) {
        $lastSection = $it['section'];
        if ($lastSection !== '') { ?>
          <tr class="section-row"><td colspan="6"><?= h($lastSection) ?></td></tr>
        <?php }
      }
      $lineTotal = $it['quantity'] * $it['unit_price'] * (1 - $it['discount_percent'] / 100);
    ?>
      <tr class="<?= $it['optional'] ? 'optional-row' : '' ?>">
        <td class="desc"><?= h($it['description']) ?></td>
        <td><?= h($it['unit']) ?></td>
        <td class="num"><?= num($it['quantity']) ?></td>
        <td class="num"><?= money((float) $it['unit_price']) ?></td>
        <td class="num"><?= $it['discount_percent'] ? num($it['discount_percent']) . '%' : '—' ?></td>
        <td class="num"><?= money($lineTotal) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <table>
      <tr><td class="label">Subtotale</td><td class="val"><?= money((float) $quote['subtotal']) ?></td></tr>
      <tr><td class="label">Sconto</td><td class="val">− <?= money((float) $quote['discount_amount']) ?></td></tr>
      <tr><td class="label">Imponibile</td><td class="val"><?= money((float) $quote['taxable']) ?></td></tr>
      <tr><td class="label"><?= h($quote['vat_label']) ?></td><td class="val"><?= money((float) $quote['vat_amount']) ?></td></tr>
      <tr class="grand"><td class="label">Totale</td><td class="val"><?= money((float) $quote['total']) ?></td></tr>
    </table>
  </div>

  <?php if ($quote['payment_terms']): ?>
    <div class="section-block"><h3>Termini di pagamento</h3><p><?= h($quote['payment_terms']) ?></p></div>
  <?php endif; ?>
  <?php if ($quote['notes']): ?>
    <div class="section-block"><h3>Note e condizioni</h3><p><?= h($quote['notes']) ?></p></div>
  <?php endif; ?>

  <div class="signature-area">
    <div class="box">Per <?= h($company['legal_name']) ?></div>
    <div class="box">Per accettazione — <?= h($client['ragione_sociale']) ?></div>
  </div>

  <div class="doc-footer">
    <?= h($company['legal_name']) ?> — <?= h($company['address']) ?>, <?= h($company['zip']) ?> <?= h($company['city']) ?> (<?= h($company['province']) ?>) —
    P.IVA <?= h($company['piva']) ?> <?= $company['pec'] ? '· PEC ' . h($company['pec']) : '' ?>
    <?php if ($company['footer_notes']): ?><br><?= h($company['footer_notes']) ?><?php endif; ?>
  </div>
</div>

<?php if ($publicMode && !in_array($quote['status'], ['accettato', 'rifiutato'], true)): ?>
<script>
async function respond(action) {
  const name = prompt('Conferma il tuo nome e cognome per registrare la risposta:');
  if (name === null) return;
  const res = await fetch('../api/quote_accept.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ token: '<?= h($quote['share_token']) ?>', action, name }),
  });
  const out = await res.json();
  if (res.ok) location.reload(); else alert(out.error || 'Errore, riprova.');
}
document.getElementById('btn-accept').addEventListener('click', () => respond('accept'));
document.getElementById('btn-reject').addEventListener('click', () => respond('reject'));
</script>
<?php endif; ?>

</body>
</html>
