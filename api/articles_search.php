<?php
require_once __DIR__ . '/_bootstrap.php';
requireLogin();
$pdo = db();

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT a.id, a.code, a.name, a.unit, a.sell_price, a.cost_price, c.name AS category_name
        FROM articles a LEFT JOIN categories c ON c.id = a.category_id
        WHERE a.active = 1";
if ($q !== '') {
    $sql .= " AND (a.name LIKE :q OR a.code LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
$sql .= " ORDER BY c.sort_order, a.name LIMIT 40";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
jsonResponse($stmt->fetchAll());
