<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/helpers.php';
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$article = [
    'category_id' => '', 'code' => '', 'name' => '', 'description' => '', 'unit' => 'pz',
    'sell_price' => '', 'cost_price' => '', 'supplier' => '', 'notes' => '', 'active' => 1,
];
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { header('Location: articles.php'); exit; }
    $article = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $article['category_id'] = (int) ($_POST['category_id'] ?? 0) ?: null;
    $article['code'] = trim($_POST['code'] ?? '');
    $article['name'] = trim($_POST['name'] ?? '');
    $article['description'] = trim($_POST['description'] ?? '');
    $article['unit'] = trim($_POST['unit'] ?? 'pz') ?: 'pz';
    $article['sell_price'] = (float) str_replace(',', '.', $_POST['sell_price'] ?? 0);
    $article['cost_price'] = (float) str_replace(',', '.', $_POST['cost_price'] ?? 0);
    $article['supplier'] = trim($_POST['supplier'] ?? '');
    $article['notes'] = trim($_POST['notes'] ?? '');
    $article['active'] = !empty($_POST['active']) ? 1 : 0;

    if ($article['name'] === '') {
        $errors[] = 'Il nome dell\'articolo è obbligatorio.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE articles SET category_id=?, code=?, name=?, description=?, unit=?, sell_price=?, cost_price=?, supplier=?, notes=?, active=?, updated_at=datetime(\'now\') WHERE id=?');
            $stmt->execute([$article['category_id'], $article['code'], $article['name'], $article['description'], $article['unit'], $article['sell_price'], $article['cost_price'], $article['supplier'], $article['notes'], $article['active'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO articles (category_id, code, name, description, unit, sell_price, cost_price, supplier, notes, active) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$article['category_id'], $article['code'], $article['name'], $article['description'], $article['unit'], $article['sell_price'], $article['cost_price'], $article['supplier'], $article['notes'], $article['active']]);
            $id = (int) $pdo->lastInsertId();
        }
        header('Location: articles.php?saved=1');
        exit;
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();

$pageTitle = $id ? 'Modifica articolo' : 'Nuovo articolo';
$activeNav = 'articles';
require_once __DIR__ . '/includes/layout_start.php';
?>
<div class="card-header">
  <h2><?= $id ? 'Modifica articolo' : 'Nuovo articolo' ?></h2>
  <a href="articles.php" class="btn btn-ghost btn-sm">← Torna alla libreria</a>
</div>

<?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>

<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
  <div class="grid grid-2">
    <div class="field">
      <label>Categoria</label>
      <select name="category_id">
        <option value="">— Nessuna —</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)$article['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Codice articolo</label>
      <input type="text" name="code" value="<?= h($article['code']) ?>">
    </div>
  </div>

  <div class="field">
    <label>Nome articolo *</label>
    <input type="text" name="name" value="<?= h($article['name']) ?>" required>
  </div>
  <div class="field">
    <label>Descrizione (compare nel preventivo se necessario dettagliare)</label>
    <textarea name="description"><?= h($article['description']) ?></textarea>
  </div>

  <div class="grid grid-3">
    <div class="field">
      <label>Unità di misura</label>
      <select name="unit">
        <?php foreach (['pz','mq','ml','cad','gg','h','forfait','mese'] as $u): ?>
          <option value="<?= $u ?>" <?= $article['unit'] === $u ? 'selected' : '' ?>><?= $u ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Prezzo di vendita (€)</label>
      <input type="text" inputmode="decimal" name="sell_price" value="<?= h($article['sell_price']) ?>">
    </div>
    <div class="field">
      <label>Prezzo di costo (€) <span class="small muted">— uso interno, non stampato</span></label>
      <input type="text" inputmode="decimal" name="cost_price" value="<?= h($article['cost_price']) ?>">
    </div>
  </div>

  <div class="field">
    <label>Fornitore</label>
    <input type="text" name="supplier" value="<?= h($article['supplier']) ?>">
  </div>
  <div class="field">
    <label>Note interne</label>
    <textarea name="notes"><?= h($article['notes']) ?></textarea>
  </div>
  <div class="field">
    <label><input type="checkbox" name="active" value="1" <?= $article['active'] ? 'checked' : '' ?> style="width:auto;display:inline-block;margin-right:6px"> Articolo attivo (selezionabile nei nuovi preventivi)</label>
  </div>

  <button type="submit" class="btn btn-primary">Salva articolo</button>
</form>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
