<?php
require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (password_verify($password, APP_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $redirect = $_POST['redirect'] ?? 'index.php';
        if (!str_starts_with($redirect, '/') && !str_starts_with($redirect, 'http')) {
            header('Location: ' . $redirect);
        } else {
            header('Location: index.php');
        }
        exit;
    }
    $error = 'Password non corretta.';
}

$redirect = $_GET['redirect'] ?? 'index.php';
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Accesso · <?= htmlspecialchars(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="login-logos">
      <img src="assets/img/henoto-logo.svg" alt="Henoto">
      <img src="assets/img/emvisia-logo.svg" alt="Emvisia">
    </div>
    <h1>Preventivi</h1>
    <p class="muted">Allestimenti fieristici, retail, strutture ed eventi</p>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autofocus required>
      <button type="submit" class="btn btn-primary btn-block">Entra</button>
    </form>
  </div>
</body>
</html>
