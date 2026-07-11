<?php
$pageTitle = 'Clienti';
$activeNav = 'clients';
require_once __DIR__ . '/includes/layout_start.php';

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT c.*,
    (SELECT COUNT(*) FROM quotes qq WHERE qq.client_id = c.id) AS quote_count,
    (SELECT COALESCE(SUM(total),0) FROM quotes qq WHERE qq.client_id = c.id AND qq.status='accettato') AS accepted_value
    FROM clients c WHERE 1=1";
if ($q !== '') {
    $sql .= " AND (ragione_sociale LIKE :q OR citta LIKE :q OR piva LIKE :q OR email LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
$sql .= " ORDER BY ragione_sociale";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>
<div class="card-header">
  <h2>Archivio clienti</h2>
  <a href="client_form.php" class="btn btn-primary btn-sm">+ Nuovo cliente</a>
</div>

<div class="filter-bar">
  <form method="get" class="grow flex gap-8">
    <input type="search" name="q" placeholder="Cerca per ragione sociale, città, P.IVA, email…" value="<?= h($q) ?>" class="grow">
    <button class="btn btn-outline btn-sm" type="submit">Cerca</button>
    <?php if ($q): ?><a href="clients.php" class="btn btn-ghost btn-sm">Azzera</a><?php endif; ?>
  </form>
</div>

<div class="card">
<?php if (!$clients): ?>
  <div class="empty-state">Nessun cliente trovato. <a href="client_form.php">Aggiungi il primo cliente</a>.</div>
<?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>Ragione sociale</th><th>Città</th><th>P.IVA</th><th>Contatto</th>
      <th class="text-center">Preventivi</th><th class="text-right">Valore accettato</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($clients as $c): ?>
      <tr class="row-link" onclick="window.location='client_view.php?id=<?= (int)$c['id'] ?>'">
        <td><strong><?= h($c['ragione_sociale']) ?></strong><?php if ($c['referente']): ?><br><span class="muted small"><?= h($c['referente']) ?></span><?php endif; ?></td>
        <td><?= h($c['citta']) ?></td>
        <td><?= h($c['piva']) ?></td>
        <td><?= h($c['email'] ?: $c['telefono']) ?></td>
        <td class="text-center"><?= (int) $c['quote_count'] ?></td>
        <td class="text-right"><?= money((float) $c['accepted_value']) ?></td>
        <td class="text-right"><a href="client_form.php?id=<?= (int)$c['id'] ?>" class="btn btn-ghost btn-sm" onclick="event.stopPropagation()">Modifica</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
