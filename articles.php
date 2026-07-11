<?php
$pageTitle = 'Libreria articoli';
$activeNav = 'articles';
require_once __DIR__ . '/includes/layout_start.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    requireCsrf();
    $name = trim($_POST['category_name'] ?? '');
    if ($name !== '') {
        $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM categories')->fetchColumn();
        $pdo->prepare('INSERT OR IGNORE INTO categories (name, sort_order) VALUES (?,?)')->execute([$name, $maxOrder + 1]);
    }
    header('Location: articles.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_active') {
    requireCsrf();
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare('UPDATE articles SET active = 1 - active WHERE id = ?')->execute([$id]);
    header('Location: articles.php' . (!empty($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
    exit;
}

$q = trim($_GET['q'] ?? '');
$catFilter = (int) ($_GET['cat'] ?? 0);

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();

$sql = "SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id = a.category_id WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (a.name LIKE :q OR a.code LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($catFilter) {
    $sql .= " AND a.category_id = :cat";
    $params[':cat'] = $catFilter;
}
$sql .= " ORDER BY c.sort_order, a.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

$grouped = [];
foreach ($articles as $a) {
    $grouped[$a['category_name'] ?? 'Senza categoria'][] = $a;
}
?>
<div class="card-header">
  <h2>Libreria articoli</h2>
  <a href="article_form.php" class="btn btn-primary btn-sm">+ Nuovo articolo</a>
</div>

<div class="filter-bar">
  <form method="get" class="flex gap-8 grow">
    <input type="search" name="q" placeholder="Cerca articolo o codice…" value="<?= h($q) ?>" class="grow">
    <select name="cat">
      <option value="0">Tutte le categorie</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $catFilter === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline btn-sm" type="submit">Filtra</button>
    <?php if ($q || $catFilter): ?><a href="articles.php" class="btn btn-ghost btn-sm">Azzera</a><?php endif; ?>
  </form>

  <form method="post" class="flex gap-8">
    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
    <input type="hidden" name="action" value="add_category">
    <input type="text" name="category_name" placeholder="Nuova categoria…">
    <button type="submit" class="btn btn-outline btn-sm">+ Categoria</button>
  </form>
</div>

<?php if (!$grouped): ?>
  <div class="card"><div class="empty-state">Nessun articolo trovato.</div></div>
<?php endif; ?>

<?php foreach ($grouped as $catName => $items): ?>
  <div class="card">
    <h3 class="mt-0"><?= h($catName) ?> <span class="muted small">(<?= count($items) ?>)</span></h3>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Codice</th><th>Articolo</th><th>Unità</th><th class="text-right">Prezzo vendita</th><th class="text-right">Costo</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $a): ?>
        <tr style="<?= $a['active'] ? '' : 'opacity:.45' ?>">
          <td class="muted small"><?= h($a['code']) ?></td>
          <td><?= h($a['name']) ?></td>
          <td><?= h($a['unit']) ?></td>
          <td class="text-right"><?= money((float)$a['sell_price']) ?></td>
          <td class="text-right muted"><?= money((float)$a['cost_price']) ?></td>
          <td class="text-right">
            <a href="article_form.php?id=<?= (int)$a['id'] ?>" class="btn btn-ghost btn-sm">Modifica</a>
            <form method="post" style="display:inline" onsubmit="return confirm('<?= $a['active'] ? 'Disattivare' : 'Riattivare' ?> questo articolo?')">
              <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm"><?= $a['active'] ? 'Disattiva' : 'Riattiva' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
