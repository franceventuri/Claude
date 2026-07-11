<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$client = [
    'ragione_sociale' => '', 'referente' => '', 'indirizzo' => '', 'cap' => '', 'citta' => '', 'provincia' => '',
    'nazione' => 'Italia', 'piva' => '', 'cf' => '', 'telefono' => '', 'email' => '', 'pec' => '', 'sdi_code' => '', 'note' => '',
];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: clients.php');
        exit;
    }
    $client = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    foreach (array_keys($client) as $field) {
        $client[$field] = trim($_POST[$field] ?? '');
    }
    if ($client['ragione_sociale'] === '') {
        $errors[] = 'La ragione sociale è obbligatoria.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE clients SET ragione_sociale=?, referente=?, indirizzo=?, cap=?, citta=?, provincia=?, nazione=?, piva=?, cf=?, telefono=?, email=?, pec=?, sdi_code=?, note=?, updated_at=datetime(\'now\') WHERE id=?');
            $stmt->execute([
                $client['ragione_sociale'], $client['referente'], $client['indirizzo'], $client['cap'], $client['citta'], $client['provincia'],
                $client['nazione'], $client['piva'], $client['cf'], $client['telefono'], $client['email'], $client['pec'], $client['sdi_code'], $client['note'], $id,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO clients (ragione_sociale, referente, indirizzo, cap, citta, provincia, nazione, piva, cf, telefono, email, pec, sdi_code, note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $client['ragione_sociale'], $client['referente'], $client['indirizzo'], $client['cap'], $client['citta'], $client['provincia'],
                $client['nazione'], $client['piva'], $client['cf'], $client['telefono'], $client['email'], $client['pec'], $client['sdi_code'], $client['note'],
            ]);
            $id = (int) $pdo->lastInsertId();
        }
        header('Location: client_view.php?id=' . $id . '&saved=1');
        exit;
    }
}

$pageTitle = $id ? 'Modifica cliente' : 'Nuovo cliente';
$activeNav = 'clients';
require_once __DIR__ . '/includes/layout_start.php';
?>
<div class="card-header">
  <h2><?= $id ? 'Modifica cliente' : 'Nuovo cliente' ?></h2>
  <a href="clients.php" class="btn btn-ghost btn-sm">← Torna all'archivio</a>
</div>

<?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>

<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
  <div class="grid grid-2">
    <div class="field">
      <label>Ragione sociale *</label>
      <input type="text" name="ragione_sociale" value="<?= h($client['ragione_sociale']) ?>" required>
    </div>
    <div class="field">
      <label>Referente</label>
      <input type="text" name="referente" value="<?= h($client['referente']) ?>">
    </div>
  </div>

  <div class="field">
    <label>Indirizzo</label>
    <input type="text" name="indirizzo" value="<?= h($client['indirizzo']) ?>">
  </div>
  <div class="grid grid-3">
    <div class="field"><label>CAP</label><input type="text" name="cap" value="<?= h($client['cap']) ?>"></div>
    <div class="field"><label>Città</label><input type="text" name="citta" value="<?= h($client['citta']) ?>"></div>
    <div class="field"><label>Provincia</label><input type="text" name="provincia" maxlength="2" value="<?= h($client['provincia']) ?>"></div>
  </div>
  <div class="field"><label>Nazione</label><input type="text" name="nazione" value="<?= h($client['nazione']) ?>"></div>

  <div class="divider"></div>

  <div class="grid grid-2">
    <div class="field"><label>Partita IVA</label><input type="text" name="piva" value="<?= h($client['piva']) ?>"></div>
    <div class="field"><label>Codice fiscale</label><input type="text" name="cf" value="<?= h($client['cf']) ?>"></div>
  </div>
  <div class="grid grid-3">
    <div class="field"><label>Telefono</label><input type="text" name="telefono" value="<?= h($client['telefono']) ?>"></div>
    <div class="field"><label>Email</label><input type="email" name="email" value="<?= h($client['email']) ?>"></div>
    <div class="field"><label>PEC</label><input type="email" name="pec" value="<?= h($client['pec']) ?>"></div>
  </div>
  <div class="field"><label>Codice destinatario SDI</label><input type="text" name="sdi_code" value="<?= h($client['sdi_code']) ?>"></div>

  <div class="field"><label>Note</label><textarea name="note"><?= h($client['note']) ?></textarea></div>

  <button type="submit" class="btn btn-primary">Salva cliente</button>
</form>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
