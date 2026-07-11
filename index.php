<?php
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/includes/layout_start.php';

$statusCounts = [];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM quotes GROUP BY status') as $row) {
    $statusCounts[$row['status']] = (int) $row['c'];
}
$totalQuotes = array_sum($statusCounts);

$year = date('Y');
$acceptedValueYear = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM quotes WHERE status='accettato' AND strftime('%Y', issue_date) = '$year'")->fetchColumn();
$openValue = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM quotes WHERE status IN ('bozza','inviato')")->fetchColumn();

$byCompany = $pdo->query("SELECT co.short_name, COUNT(*) n, COALESCE(SUM(q.total),0) tot
    FROM quotes q JOIN companies co ON co.id = q.company_id GROUP BY co.id")->fetchAll();

$expiringSoon = $pdo->query("SELECT q.*, cl.ragione_sociale, co.short_name AS company_name FROM quotes q
    JOIN clients cl ON cl.id = q.client_id JOIN companies co ON co.id = q.company_id
    WHERE q.status IN ('bozza','inviato')
    AND date(q.issue_date, '+' || q.validity_days || ' days') BETWEEN date('now') AND date('now', '+7 days')
    ORDER BY date(q.issue_date, '+' || q.validity_days || ' days')")->fetchAll();

$recent = $pdo->query("SELECT q.*, cl.ragione_sociale, co.short_name AS company_name FROM quotes q
    JOIN clients cl ON cl.id = q.client_id JOIN companies co ON co.id = q.company_id
    ORDER BY q.created_at DESC LIMIT 8")->fetchAll();
?>

<div class="grid grid-4">
  <div class="kpi"><div class="kpi-label">Preventivi totali</div><div class="kpi-value"><?= $totalQuotes ?></div></div>
  <div class="kpi"><div class="kpi-label">In corso (bozza/inviati)</div><div class="kpi-value"><?= money($openValue) ?></div></div>
  <div class="kpi"><div class="kpi-label">Accettati <?= $year ?></div><div class="kpi-value"><?= money($acceptedValueYear) ?></div></div>
  <div class="kpi"><div class="kpi-label">In scadenza (7gg)</div><div class="kpi-value"><?= count($expiringSoon) ?></div></div>
</div>

<div class="grid grid-2" style="margin-top:20px">
  <div class="card">
    <h3 class="mt-0">Preventivi per stato</h3>
    <?php foreach (['bozza','inviato','accettato','rifiutato','scaduto'] as $s): ?>
      <div class="flex space-between" style="padding:6px 0;border-bottom:1px solid var(--line)">
        <span class="badge <?= statusClass($s) ?>"><?= statusLabel($s) ?></span>
        <strong><?= $statusCounts[$s] ?? 0 ?></strong>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h3 class="mt-0">Per azienda</h3>
    <?php foreach ($byCompany as $c): ?>
      <div class="flex space-between" style="padding:6px 0;border-bottom:1px solid var(--line)">
        <span><?= h($c['short_name']) ?> <span class="muted small">(<?= (int)$c['n'] ?> preventivi)</span></span>
        <strong><?= money((float)$c['tot']) ?></strong>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($expiringSoon): ?>
<div class="card">
  <h3 class="mt-0">⏰ In scadenza nei prossimi 7 giorni</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Numero</th><th>Cliente</th><th>Oggetto</th><th>Scadenza</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($expiringSoon as $q):
      $expiry = date('d/m/Y', strtotime('+' . (int)$q['validity_days'] . ' days', strtotime($q['issue_date'])));
    ?>
      <tr class="row-link" onclick="window.location='quote_view.php?id=<?= (int)$q['id'] ?>'">
        <td><?= h($q['quote_number']) ?></td><td><?= h($q['ragione_sociale']) ?></td><td><?= h($q['title']) ?></td>
        <td><?= $expiry ?></td><td class="text-right"><?= money((float)$q['total']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <h3 class="mt-0">Ultimi preventivi</h3>
  <?php if (!$recent): ?>
    <div class="empty-state">Nessun preventivo ancora creato. <a href="quote_form.php">Crea il primo</a>.</div>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Numero</th><th>Azienda</th><th>Cliente</th><th>Oggetto</th><th>Stato</th><th class="text-right">Totale</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $q): ?>
      <tr class="row-link" onclick="window.location='quote_view.php?id=<?= (int)$q['id'] ?>'">
        <td><?= h($q['quote_number']) ?></td><td><?= h($q['company_name']) ?></td><td><?= h($q['ragione_sociale']) ?></td><td><?= h($q['title']) ?></td>
        <td><span class="badge <?= statusClass($q['status']) ?>"><?= statusLabel($q['status']) ?></span></td>
        <td class="text-right"><?= money((float)$q['total']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
