<?php
$pageTitle = 'Scheda cliente';
$activeNav = 'clients';
require_once __DIR__ . '/includes/layout_start.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
$stmt->execute([$id]);
$client = $stmt->fetch();
if (!$client) {
    header('Location: clients.php');
    exit;
}

$qStmt = $pdo->prepare("SELECT q.*, co.short_name AS company_name FROM quotes q
    JOIN companies co ON co.id = q.company_id WHERE q.client_id = ? ORDER BY q.issue_date DESC, q.id DESC");
$qStmt->execute([$id]);
$quotes = $qStmt->fetchAll();
?>
<div class="card-header">
  <h2><?= h($client['ragione_sociale']) ?></h2>
  <div class="flex gap-8">
    <a href="quote_form.php?client_id=<?= (int)$id ?>" class="btn btn-primary btn-sm">+ Nuovo preventivo</a>
    <a href="client_form.php?id=<?= (int)$id ?>" class="btn btn-outline btn-sm">Modifica</a>
    <a href="clients.php" class="btn btn-ghost btn-sm">← Archivio</a>
  </div>
</div>

<?php if (!empty($_GET['saved'])): ?><div class="alert alert-success">Cliente salvato correttamente.</div><?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <h3 class="mt-0">Anagrafica</h3>
    <p><?= h($client['indirizzo']) ?><br>
    <?= h($client['cap']) ?> <?= h($client['citta']) ?> (<?= h($client['provincia']) ?>) — <?= h($client['nazione']) ?></p>
    <div class="divider"></div>
    <p><strong>P.IVA:</strong> <?= h($client['piva']) ?: '—' ?> &nbsp; <strong>CF:</strong> <?= h($client['cf']) ?: '—' ?></p>
    <p><strong>SDI:</strong> <?= h($client['sdi_code']) ?: '—' ?></p>
    <?php if ($client['referente']): ?><p><strong>Referente:</strong> <?= h($client['referente']) ?></p><?php endif; ?>
    <p><strong>Tel:</strong> <?= h($client['telefono']) ?: '—' ?> &nbsp; <strong>Email:</strong> <?= h($client['email']) ?: '—' ?></p>
    <?php if ($client['pec']): ?><p><strong>PEC:</strong> <?= h($client['pec']) ?></p><?php endif; ?>
    <?php if ($client['note']): ?><div class="divider"></div><p class="muted"><?= nl2br(h($client['note'])) ?></p><?php endif; ?>
  </div>

  <div class="card">
    <h3 class="mt-0">Riepilogo</h3>
    <?php
      $total = array_sum(array_column($quotes, 'total'));
      $accepted = array_sum(array_map(fn($q) => $q['status'] === 'accettato' ? $q['total'] : 0, $quotes));
    ?>
    <p><strong><?= count($quotes) ?></strong> preventivi totali</p>
    <p><strong><?= money($accepted) ?></strong> valore preventivi accettati</p>
    <p><strong><?= money($total) ?></strong> valore complessivo preventivato</p>
  </div>
</div>

<div class="card">
  <h3 class="mt-0">Storico preventivi</h3>
  <?php if (!$quotes): ?>
    <div class="empty-state">Nessun preventivo per questo cliente.</div>
  <?php else: ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Numero</th><th>Azienda</th><th>Oggetto</th><th>Data</th><th>Stato</th><th class="text-right">Totale</th></tr></thead>
    <tbody>
    <?php foreach ($quotes as $q): ?>
      <tr class="row-link" onclick="window.location='quote_view.php?id=<?= (int)$q['id'] ?>'">
        <td><?= h($q['quote_number']) ?></td>
        <td><?= h($q['company_name']) ?></td>
        <td><?= h($q['title']) ?></td>
        <td><?= dateIt($q['issue_date']) ?></td>
        <td><span class="badge <?= statusClass($q['status']) ?>"><?= h(statusLabel($q['status'])) ?></span></td>
        <td class="text-right"><?= money((float)$q['total']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
