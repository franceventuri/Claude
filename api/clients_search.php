<?php
require_once __DIR__ . '/_bootstrap.php';
requireLogin();
$pdo = db();

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT id, ragione_sociale, citta, piva, email FROM clients WHERE 1=1";
if ($q !== '') {
    $sql .= " AND (ragione_sociale LIKE :q OR piva LIKE :q OR citta LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
$sql .= " ORDER BY ragione_sociale LIMIT 30";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
jsonResponse($stmt->fetchAll());
