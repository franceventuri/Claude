<?php
$pageTitle = 'Impostazioni';
$activeNav = 'settings';
require_once __DIR__ . '/includes/layout_start.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_company') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('UPDATE companies SET legal_name=?, short_name=?, address=?, zip=?, city=?, province=?, country=?,
            piva=?, cf=?, rea=?, pec=?, email=?, phone=?, website=?, iban=?, bank_name=?,
            quote_prefix=?, next_quote_seq=?, default_vat_rate=?, default_validity_days=?, footer_notes=? WHERE id=?');
        $stmt->execute([
            trim($_POST['legal_name']), trim($_POST['short_name']), trim($_POST['address']), trim($_POST['zip']), trim($_POST['city']), trim($_POST['province']), trim($_POST['country']),
            trim($_POST['piva']), trim($_POST['cf']), trim($_POST['rea']), trim($_POST['pec']), trim($_POST['email']), trim($_POST['phone']), trim($_POST['website']),
            trim($_POST['iban']), trim($_POST['bank_name']),
            trim($_POST['quote_prefix']), (int) $_POST['next_quote_seq'], (float) str_replace(',', '.', $_POST['default_vat_rate']), (int) $_POST['default_validity_days'], trim($_POST['footer_notes']), $id,
        ]);
        $msg = 'Dati azienda aggiornati.';
    }

    if ($action === 'save_payment_term') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($name !== '' && $body !== '') {
            if ($id) {
                $pdo->prepare('UPDATE payment_terms_templates SET name=?, body=? WHERE id=?')->execute([$name, $body, $id]);
            } else {
                $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order),0) FROM payment_terms_templates')->fetchColumn();
                $pdo->prepare('INSERT INTO payment_terms_templates (name, body, sort_order) VALUES (?,?,?)')->execute([$name, $body, $maxOrder + 1]);
            }
        }
        $msg = 'Template termini di pagamento salvato.';
    }

    if ($action === 'delete_payment_term') {
        $pdo->prepare('DELETE FROM payment_terms_templates WHERE id=?')->execute([(int) $_POST['id']]);
        $msg = 'Template eliminato.';
    }
}

$companies = $pdo->query('SELECT * FROM companies ORDER BY id')->fetchAll();
$paymentTerms = $pdo->query('SELECT * FROM payment_terms_templates ORDER BY sort_order')->fetchAll();
?>
<div class="card-header"><h2>Impostazioni</h2></div>
<?php if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>

<?php foreach ($companies as $c): ?>
<div class="card">
  <h3 class="mt-0">Dati fiscali — <?= h($c['short_name']) ?></h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
    <input type="hidden" name="action" value="save_company">
    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <div class="grid grid-2">
      <div class="field"><label>Ragione sociale</label><input type="text" name="legal_name" value="<?= h($c['legal_name']) ?>"></div>
      <div class="field"><label>Nome breve (visualizzato in app)</label><input type="text" name="short_name" value="<?= h($c['short_name']) ?>"></div>
    </div>
    <div class="field"><label>Indirizzo</label><input type="text" name="address" value="<?= h($c['address']) ?>"></div>
    <div class="grid grid-3">
      <div class="field"><label>CAP</label><input type="text" name="zip" value="<?= h($c['zip']) ?>"></div>
      <div class="field"><label>Città</label><input type="text" name="city" value="<?= h($c['city']) ?>"></div>
      <div class="field"><label>Provincia</label><input type="text" name="province" maxlength="2" value="<?= h($c['province']) ?>"></div>
    </div>
    <div class="field"><label>Nazione</label><input type="text" name="country" value="<?= h($c['country']) ?>"></div>
    <div class="divider"></div>
    <div class="grid grid-3">
      <div class="field"><label>Partita IVA</label><input type="text" name="piva" value="<?= h($c['piva']) ?>"></div>
      <div class="field"><label>Codice fiscale</label><input type="text" name="cf" value="<?= h($c['cf']) ?>"></div>
      <div class="field"><label>REA</label><input type="text" name="rea" value="<?= h($c['rea']) ?>"></div>
    </div>
    <div class="grid grid-3">
      <div class="field"><label>PEC</label><input type="text" name="pec" value="<?= h($c['pec']) ?>"></div>
      <div class="field"><label>Email</label><input type="text" name="email" value="<?= h($c['email']) ?>"></div>
      <div class="field"><label>Telefono</label><input type="text" name="phone" value="<?= h($c['phone']) ?>"></div>
    </div>
    <div class="grid grid-2">
      <div class="field"><label>Sito web</label><input type="text" name="website" value="<?= h($c['website']) ?>"></div>
      <div class="field"><label>IBAN</label><input type="text" name="iban" value="<?= h($c['iban']) ?>"></div>
    </div>
    <div class="field"><label>Banca</label><input type="text" name="bank_name" value="<?= h($c['bank_name']) ?>"></div>
    <div class="divider"></div>
    <div class="grid grid-4">
      <div class="field"><label>Prefisso numerazione</label><input type="text" name="quote_prefix" value="<?= h($c['quote_prefix']) ?>"></div>
      <div class="field"><label>Prossimo numero (anno <?= date('Y') ?>)</label><input type="number" name="next_quote_seq" value="<?= (int) $c['next_quote_seq'] ?>"></div>
      <div class="field"><label>IVA predefinita %</label><input type="text" name="default_vat_rate" value="<?= h($c['default_vat_rate']) ?>"></div>
      <div class="field"><label>Validità offerta (gg)</label><input type="number" name="default_validity_days" value="<?= (int) $c['default_validity_days'] ?>"></div>
    </div>
    <div class="field"><label>Note a piè di pagina (stampate su ogni preventivo)</label><textarea name="footer_notes"><?= h($c['footer_notes']) ?></textarea></div>
    <p class="small muted">Logo: <code><?= h($c['logo_path']) ?></code> — per sostituirlo, carica il file via FTP/File Manager di cPanel nello stesso percorso (stesso nome file).</p>
    <button type="submit" class="btn btn-primary btn-sm">Salva dati <?= h($c['short_name']) ?></button>
  </form>
</div>
<?php endforeach; ?>

<div class="card">
  <h3 class="mt-0">Template termini di pagamento</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Nome</th><th>Testo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($paymentTerms as $t): ?>
      <tr>
        <td style="width:160px"><strong><?= h($t['name']) ?></strong></td>
        <td class="small"><?= h($t['body']) ?></td>
        <td class="text-right" style="white-space:nowrap">
          <button type="button" class="btn btn-ghost btn-sm btn-edit-term" data-id="<?= (int)$t['id'] ?>" data-name="<?= h($t['name']) ?>" data-body="<?= h($t['body']) ?>">Modifica</button>
          <form method="post" style="display:inline" onsubmit="return confirm('Eliminare questo template?')">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="action" value="delete_payment_term">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm">Elimina</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <div class="divider"></div>
  <form method="post" id="term-form">
    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
    <input type="hidden" name="action" value="save_payment_term">
    <input type="hidden" name="id" id="term-id" value="">
    <div class="grid grid-2">
      <div class="field"><label>Nome template</label><input type="text" name="name" id="term-name" placeholder="Es. 30/40/30"></div>
      <div class="field"><label>Testo</label><input type="text" name="body" id="term-body" placeholder="Es. 30% alla conferma, ..."></div>
    </div>
    <button type="submit" class="btn btn-outline btn-sm" id="term-submit">+ Aggiungi template</button>
  </form>
</div>

<div class="card">
  <h3 class="mt-0">Accesso</h3>
  <p class="small muted">La password di accesso condivisa si trova nel file <code>config.php</code> caricato sul server (costante <code>APP_PASSWORD_HASH</code>).
  Per cambiarla, genera un nuovo hash da riga di comando: <code>php -r "echo password_hash('nuova_password', PASSWORD_DEFAULT);"</code> e incollalo in quel file.</p>
</div>

<script>
document.querySelectorAll('.btn-edit-term').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('term-id').value = btn.dataset.id;
    document.getElementById('term-name').value = btn.dataset.name;
    document.getElementById('term-body').value = btn.dataset.body;
    document.getElementById('term-submit').textContent = 'Aggiorna template';
    document.getElementById('term-form').scrollIntoView({ behavior: 'smooth' });
  });
});
</script>

<?php require_once __DIR__ . '/includes/layout_end.php'; ?>
