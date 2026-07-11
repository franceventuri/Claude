<?php
/**
 * Richiede $pageTitle (string) e opzionale $activeNav (string) prima dell'include.
 */
require_once __DIR__ . '/../auth.php';
requireLogin();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/helpers.php';
$pdo = db();
$activeNav = $activeNav ?? '';
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? APP_NAME) ?> · <?= h(APP_NAME) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <header class="app-header">
    <div class="app-header-brand">
      <a href="index.php" class="brand-link">Preventivi<span>Henoto · Emvisia</span></a>
    </div>
    <nav class="app-nav">
      <a href="index.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <a href="quotes.php" class="<?= $activeNav === 'quotes' ? 'active' : '' ?>">Preventivi</a>
      <a href="clients.php" class="<?= $activeNav === 'clients' ? 'active' : '' ?>">Clienti</a>
      <a href="articles.php" class="<?= $activeNav === 'articles' ? 'active' : '' ?>">Libreria articoli</a>
      <a href="settings.php" class="<?= $activeNav === 'settings' ? 'active' : '' ?>">Impostazioni</a>
    </nav>
    <div class="app-header-actions">
      <a href="quote_form.php" class="btn btn-primary btn-sm">+ Nuovo preventivo</a>
      <a href="logout.php" class="btn btn-ghost btn-sm">Esci</a>
    </div>
  </header>
  <main class="app-main">
