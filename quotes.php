<?php
$pageTitle = 'Preventivi';
$activeNav = 'quotes';
require_once __DIR__ . '/includes/layout_start.php';

$q = trim($_GET['q'] ?? '');
$companyFilter = (int) ($_GET['company'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');

$sql = "SELECT q.*, cl.ragione_sociale, co.short_name AS company_name FROM quotes q
    JOIN clients cl ON cl.id = q.client_id
    JOIN companies co ON co.id = q.company_id WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (q.quote_number LIKE :q OR q.title LIKE :q OR cl.ragione_sociale LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($companyFilter) {
    $sql .= " AND q.company_id = :company";
    $params[':company'] = $companyFilter;
}
if ($statusFilter) {
    $sql .= " AND q.status = :status";
    $params[':status'] = $statusFilter;
}
$sql .= " ORDER BY q.issue_date DESC, q.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quotes = $stmt->fetchAll();

$companies = $pdo->query('SELECT * FROM companies ORDER BY id')->fetchAll();
?>
<div class="card-header">
  <h2>Archivio preventivi</h2>
  <a href="quote_form.php" class="btn btn-primary btn-sm">+ Nuovo preventivo</a>
</div>

<div class="filter-bar">
  <form method="get" class="flex gap-8 grow" style="flex-wrap:wrap">
    <input type="search" name="q" placeholder="Cerca numero, oggetto, cliente…" value="<?= h($q) ?>" class="grow">
    <select name="company">
      <option value="0">Tutte le aziende</option>
      <?php foreach ($companies as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $companyFilter === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['short_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status">
      <option value="">Tutti gli stati</option>
      <?php foreach (['bozza','inviato','accettato','rifiutato','scaduto'] as $s): ?>
        <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= statusLabel($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm" type="submit">Filtra</button>
    <?php if ($q || $companyFilter || $statusFilter): ?><a href="quotes.php" class="btn btn-ghost btn-sm">Azzera</a><?php endif; ?>
  </form>
</div>

<div class="card">
<?php if (!$quotes): ?>
  <div class="empty-state">Nessun preventivo trovato. <a href="quote_form.php">Crea il primo preventivo</a>.</div>
<?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>Numero</th><th>Azienda</th><th>Cliente</th><th>Oggetto</th><th>Data</th><th>Scadenza</th><th>Stato</th><th class="text-right">Totale</th>
    </tr></thead>
    <tbody>
    <?php foreach ($quotes as $qt):
      $expired = isQuoteExpired($qt);
      $expiryTs = strtotime('+' . (int)$qt['validity_days'] . ' days', strtotime($qt['issue_date']));
    ?>
      <tr class="row-link" onclick="window.location='quote_view.php?id=<?= (int)$qt['id'] ?>'">
        <td><strong><?= h($qt['quote_number']) ?></strong></td>
        <td><?= h($qt['company_name']) ?></td>
        <td><?= h($qt['ragione_sociale']) ?></td>
        <td><?= h($qt['title']) ?></td>
        <td><?= dateIt($qt['issue_date']) ?></td>
        <td><?= date('d/m/Y', $expiryTs) ?></td>
        <td>
          <span class="badge <?= statusClass($qt['status']) ?>"><?= h(statusLabel($qt['status'])) ?></span>
          <?php if ($expired): ?><span class="badge badge-orange">Scaduto</span><?php endif; ?>
        </td>
        <td class="text-right"><?= money((float)$qt['total']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
