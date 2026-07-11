<?php
$pageTitle = 'Preventivo';
$activeNav = 'quotes';
require_once __DIR__ . '/includes/layout_start.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT q.*, cl.ragione_sociale, cl.referente, cl.email AS client_email, co.short_name AS company_name
    FROM quotes q JOIN clients cl ON cl.id = q.client_id JOIN companies co ON co.id = q.company_id WHERE q.id = ?');
$stmt->execute([$id]);
$quote = $stmt->fetch();
if (!$quote) { header('Location: quotes.php'); exit; }

$itemsStmt = $pdo->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order, id');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$shareUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
    . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/print/quote_print.php?token=' . $quote['share_token'];
$printUrl = 'print/quote_print.php?id=' . $id;
?>
<div class="card-header">
  <div>
    <h2><?= h($quote['quote_number']) ?> <span class="badge <?= statusClass($quote['status']) ?>"><?= h(statusLabel($quote['status'])) ?></span></h2>
    <p class="muted mt-0"><?= h($quote['title']) ?> — <?= h($quote['company_name']) ?> · <?= h($quote['ragione_sociale']) ?></p>
  </div>
  <div class="flex gap-8">
    <a href="<?= h($printUrl) ?>" target="_blank" class="btn btn-outline btn-sm">🖨️ Stampa / PDF</a>
    <a href="quote_form.php?id=<?= $id ?>" class="btn btn-outline btn-sm">Modifica</a>
    <a href="quotes.php" class="btn btn-ghost btn-sm">← Archivio</a>
  </div>
</div>

<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Preventivo salvato correttamente.</div><?php endif; ?>

<div class="grid grid-3">
  <div class="card" style="grid-column: span 2">
    <h3 class="mt-0">Riepilogo</h3>
    <div class="grid grid-2">
      <p><strong>Cliente:</strong> <?= h($quote['ragione_sociale']) ?><?php if ($quote['referente']): ?><br><span class="muted"><?= h($quote['referente']) ?></span><?php endif; ?></p>
      <p><strong>Luogo evento:</strong> <?= h($quote['event_location']) ?: '—' ?></p>
      <p><strong>Data emissione:</strong> <?= dateIt($quote['issue_date']) ?></p>
      <p><strong>Validità:</strong> <?= (int)$quote['validity_days'] ?> giorni <?= isQuoteExpired($quote) ? '<span class="badge badge-orange">Scaduto</span>' : '' ?></p>
      <p><strong>Date evento:</strong> <?= dateIt($quote['event_start']) ?: '—' ?> <?= $quote['event_end'] ? ' → ' . dateIt($quote['event_end']) : '' ?></p>
      <p><strong>IVA:</strong> <?= h($quote['vat_label']) ?></p>
    </div>

    <div class="divider"></div>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Sezione / Voce</th><th>UM</th><th class="text-right">Qtà</th><th class="text-right">Prezzo</th><th class="text-right">Sconto</th><th class="text-right">Totale</th></tr></thead>
      <tbody>
      <?php $lastSection = null; foreach ($items as $it):
        if ($it['section'] !== $lastSection) { $lastSection = $it['section']; ?>
          <tr><td colspan="6"><strong><?= h($lastSection ?: 'Voci') ?></strong></td></tr>
        <?php }
        $lineTotal = $it['quantity'] * $it['unit_price'] * (1 - $it['discount_percent'] / 100);
      ?>
        <tr style="<?= $it['optional'] ? 'opacity:.6;font-style:italic' : '' ?>">
          <td>&nbsp;&nbsp;<?= h($it['description']) ?><?= $it['optional'] ? ' <span class="small">(opzionale)</span>' : '' ?></td>
          <td><?= h($it['unit']) ?></td>
          <td class="text-right"><?= num($it['quantity']) ?></td>
          <td class="text-right"><?= money((float)$it['unit_price']) ?></td>
          <td class="text-right"><?= $it['discount_percent'] ? num($it['discount_percent']) . '%' : '—' ?></td>
          <td class="text-right"><?= money($lineTotal) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="qe-totals" style="margin-top:16px">
      <table>
        <tr><td>Subtotale</td><td class="text-right"><?= money((float)$quote['subtotal']) ?></td></tr>
        <tr><td>Sconto</td><td class="text-right">− <?= money((float)$quote['discount_amount']) ?></td></tr>
        <tr><td>Imponibile</td><td class="text-right"><?= money((float)$quote['taxable']) ?></td></tr>
        <tr><td><?= h($quote['vat_label']) ?></td><td class="text-right"><?= money((float)$quote['vat_amount']) ?></td></tr>
        <tr class="total-row"><td>Totale</td><td class="text-right"><?= money((float)$quote['total']) ?></td></tr>
      </table>
    </div>

    <div class="qe-margin-box" style="margin-top:16px">
      📊 <strong>Analisi interna (non visibile al cliente):</strong> costo stimato <?= money((float)$quote['total_cost']) ?> ·
      margine <?= money((float)$quote['margin']) ?> (<?= num($quote['margin_pct'], 1) ?>%)
    </div>

    <?php if ($quote['payment_terms']): ?>
      <div class="divider"></div>
      <h3>Termini di pagamento</h3>
      <p style="white-space:pre-line"><?= h($quote['payment_terms']) ?></p>
    <?php endif; ?>
    <?php if ($quote['notes']): ?>
      <h3>Note</h3>
      <p style="white-space:pre-line" class="muted"><?= h($quote['notes']) ?></p>
    <?php endif; ?>
    <?php if ($quote['internal_notes']): ?>
      <div class="alert alert-info"><strong>Note interne:</strong> <?= nl2br(h($quote['internal_notes'])) ?></div>
    <?php endif; ?>
  </div>

  <div>
    <div class="card">
      <h3 class="mt-0">Stato preventivo</h3>
      <form id="status-form" class="field">
        <select id="status-select">
          <?php foreach (['bozza','inviato','accettato','rifiutato','scaduto'] as $s): ?>
            <option value="<?= $s ?>" <?= $quote['status'] === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <button class="btn btn-outline btn-block btn-sm" id="btn-update-status">Aggiorna stato</button>
      <?php if ($quote['accepted_at']): ?>
        <p class="small muted" style="margin-top:10px">
          <?= $quote['status'] === 'accettato' ? 'Accettato' : 'Risposto' ?> online il <?= date('d/m/Y H:i', strtotime($quote['accepted_at'])) ?>
          <?php if ($quote['accepted_by']): ?> da <strong><?= h($quote['accepted_by']) ?></strong><?php endif; ?>
        </p>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3 class="mt-0">Link condivisibile</h3>
      <p class="small muted">Invialo al cliente: potrà visualizzare il preventivo e accettarlo/rifiutarlo online, senza bisogno di credenziali.</p>
      <div class="copy-link">
        <input type="text" readonly value="<?= h($shareUrl) ?>" id="share-url">
        <button class="btn btn-outline btn-sm" id="btn-copy-link" type="button">Copia</button>
      </div>
      <a href="mailto:<?= h($quote['client_email']) ?>?subject=<?= rawurlencode('Preventivo ' . $quote['quote_number'] . ' - ' . $quote['title']) ?>&body=<?= rawurlencode("Gentile cliente,\n\nin allegato/link trova il nostro preventivo n. " . $quote['quote_number'] . ".\n\n" . $shareUrl . "\n\nRestiamo a disposizione.") ?>" class="btn btn-outline btn-block btn-sm" style="margin-top:10px">✉️ Invia via email</a>
    </div>

    <div class="card">
      <h3 class="mt-0">Azioni</h3>
      <button class="btn btn-outline btn-block btn-sm" id="btn-duplicate">📄 Duplica come nuovo preventivo</button>
      <button class="btn btn-danger btn-block btn-sm" id="btn-delete" style="margin-top:8px">🗑️ Elimina preventivo</button>
    </div>
  </div>
</div>

<script>
const CSRF = <?= json_encode(csrfToken()) ?>;
const QUOTE_ID = <?= (int) $id ?>;

document.getElementById('btn-update-status').addEventListener('click', async () => {
  const status = document.getElementById('status-select').value;
  const res = await fetch('api/quote_status.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ id: QUOTE_ID, status }),
  });
  if (res.ok) location.reload(); else alert('Errore nell\'aggiornamento dello stato.');
});

document.getElementById('btn-copy-link').addEventListener('click', () => {
  const el = document.getElementById('share-url');
  el.select(); el.setSelectionRange(0, 99999);
  navigator.clipboard?.writeText(el.value);
});

document.getElementById('btn-duplicate').addEventListener('click', async () => {
  if (!confirm('Creare una copia di questo preventivo con un nuovo numero?')) return;
  const res = await fetch('api/quote_duplicate.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ id: QUOTE_ID }),
  });
  const out = await res.json();
  if (res.ok) window.location.href = 'quote_form.php?id=' + out.id;
  else alert(out.error || 'Errore nella duplicazione.');
});

document.getElementById('btn-delete').addEventListener('click', async () => {
  if (!confirm('Eliminare definitivamente questo preventivo? L\'operazione non è reversibile.')) return;
  const res = await fetch('api/quote_delete.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ id: QUOTE_ID }),
  });
  if (res.ok) window.location.href = 'quotes.php';
  else alert('Errore nell\'eliminazione.');
});
</script>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
